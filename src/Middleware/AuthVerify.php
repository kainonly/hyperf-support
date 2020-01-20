<?php
declare(strict_types=1);

namespace Hyperf\Support\Middleware;

use RuntimeException;
use Lcobucci\JWT\Token;
use Hyperf\Utils\Context;
use Hyperf\Extra\Token\TokenInterface;
use Hyperf\Extra\Utils\UtilsInterface;
use Hyperf\Support\RedisModel\RefreshToken;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class AuthVerify
 * @package Hyperf\Support\Middleware
 */
abstract class AuthVerify implements MiddlewareInterface
{
    protected string $scene = 'default';
    private ContainerInterface $container;
    private TokenInterface $token;
    private $utils;

    /**
     * AuthVerify constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->token = $container->get(TokenInterface::class);
        $this->utils = $container->get(UtilsInterface::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = $request->getCookieParams();
        if (empty($cookies[$this->scene . '_token'])) {
            throw new RuntimeException('please first authorize user login');
        }
        /**
         * @var $response ResponseInterface
         * @var $token Token
         */
        $response = Context::get(ResponseInterface::class);
        $tokenString = $cookies[$this->scene . '_token'];
        $result = $this->token->verify($this->scene, $tokenString);
        $token = $result->token;
        $symbol = $token->getClaim('symbol');
        if ($result->expired) {
            $jti = $token->getClaim('jti');
            $ack = $token->getClaim('ack');
            $verify = RefreshToken::create($this->container)->verify($jti, $ack);
            if (!$verify) {
                throw new RuntimeException('refresh token verification expired');
            }
            $preTokenString = (string)$this->token->create(
                $this->scene,
                $jti,
                $ack,
                $symbol
            );
            if (!$preTokenString) {
                throw new RuntimeException('create token failed');
            }
            $cookie = $this->utils->cookie($this->scene . '_token', $preTokenString);
            $response = $response->withCookie($cookie);
        }
        Context::set('auth', $symbol);
        Context::set(ResponseInterface::class, $response);
        return $handler->handle($request);
    }
}