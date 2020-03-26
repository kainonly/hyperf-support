<?php
declare(strict_types=1);

namespace Hyperf\Support\Middleware;

use App\RedisModel\System\AclRedis;
use RuntimeException;
use App\RedisModel\System\RoleRedis;
use Hyperf\Utils\Context;
use Hyperf\Utils\Str;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class RbacVerify implements MiddlewareInterface
{
    protected string $prefix = '';
    protected array $ignore = [];
    private RoleRedis $roleRedis;
    private AclRedis $aclRedis;

    public function __construct(RoleRedis $roleRedis, AclRedis $aclRedis)
    {
        $this->roleRedis = $roleRedis;
        $this->aclRedis = $aclRedis;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = str_replace('/' . $this->prefix . '/', '', $request->getUri()->getPath());
        [$controller, $action] = explode('/', $path);
        if (!empty($this->ignore)) {
            foreach ($this->ignore as $value) {
                if (Str::is($value, $action)) {
                    return $handler->handle($request);
                }
            }
        }

        $roleKey = Context::get('auth')->role;
        $roleLists = $this->roleRedis->get($roleKey, 'acl');
        rsort($roleLists);
        $policy = null;
        foreach ($roleLists as $k => $value) {
            [$roleController, $roleAction] = explode(':', $value);
            if ($roleController === $controller) {
                $policy = $roleAction;
                break;
            }
        }

        if ($policy === null) {
            throw new RuntimeException('rbac invalid, policy is empty');
        }

        $aclLists = $this->aclRedis->get($controller, (int)$policy);

        if (empty($aclLists)) {
            throw new RuntimeException('rbac invalid, acl is empty');
        }

        if (!in_array($action, $aclLists, true)) {
            throw new RuntimeException('rbac invalid, access denied');
        }

        return $handler->handle($request);
    }

}