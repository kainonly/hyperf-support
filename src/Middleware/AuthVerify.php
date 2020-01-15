<?php
declare(strict_types=1);

namespace Hyperf\Support\Middleware;

use Exception;
use Hyperf\Extra\Contract\TokenInterface;
use Hyperf\Extra\Contract\UtilsInterface;
use Hyperf\HttpServer\Exception\Http\InvalidResponseException;
use Hyperf\Support\Redis\RefreshToken;
use Hyperf\Utils\Context;
use Lcobucci\JWT\Token;
use Psr\Container\ContainerInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
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
    private HttpResponse $response;
    private TokenInterface $token;
    private $utils;

    /**
     * AuthVerify constructor.
     * @param ContainerInterface $container
     * @param HttpResponse $response
     */
    public function __construct(ContainerInterface $container, HttpResponse $response)
    {
        $this->container = $container;
        $this->response = $response;
        $this->token = $container->get(TokenInterface::class);
        $this->utils = $container->get(UtilsInterface::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $cookies = $request->getCookieParams();
            if (empty($cookies[$this->scene . '_token'])) {
                return $this->response->json([
                    'error' => 1,
                    'msg' => 'please first authorize user login'
                ]);
            }
            $tokenString = $cookies[$this->scene . '_token'];
            $result = $this->token->verify($this->scene, $tokenString);
            if ($result->expired) {
                $response = Context::get(ResponseInterface::class);
                /**
                 * @var $token Token
                 */
                $token = $result->token;
                $jti = $token->getClaim('jti');
                $ack = $token->getClaim('ack');
                $verify = RefreshToken::create($this->container)->verify($jti, $ack);
                if (!$verify) {
                    return $this->response->json([
                        'error' => 1,
                        'msg' => 'refresh token verification expired'
                    ]);
                }
                $symbol = (array)$token->getClaim('symbol');
                $preTokenString = (string)$this->token->create(
                    $this->scene,
                    $jti,
                    $ack,
                    $symbol
                );
                if (!$preTokenString) {
                    return $this->response->json([
                        'error' => 1,
                        'msg' => 'create token failed'
                    ]);
                }
                $cookie = $this->utils->cookie($this->scene . '_token', $preTokenString);
                $response = $response->withCookie($cookie);
                Context::set(ResponseInterface::class, $response);
            }
            return $handler->handle($request);
        } catch (InvalidResponseException $exception) {
            return $this->response->json([
                'error' => 1,
                'msg' => $exception->getMessage()
            ]);
        }
    }
}