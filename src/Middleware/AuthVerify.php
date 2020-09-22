<?php
declare(strict_types=1);

namespace Hyperf\Support\Middleware;

use Hyperf\HttpServer\Response;
use Lcobucci\JWT\Token;
use Hyperf\Utils\Context;
use Hyperf\Extra\Token\TokenInterface;
use Hyperf\Extra\Utils\UtilsInterface;
use Hyperf\Support\RedisModel\RefreshToken;
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
    private TokenInterface $token;
    private UtilsInterface $utils;
    private RefreshToken $refreshToken;

    /**
     * AuthVerify constructor.
     * @param TokenInterface $token
     * @param UtilsInterface $utils
     * @param RefreshToken $refreshToken
     */
    public function __construct(TokenInterface $token, UtilsInterface $utils, RefreshToken $refreshToken)
    {
        $this->token = $token;
        $this->utils = $utils;
        $this->refreshToken = $refreshToken;
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
            return (new Response())->json([
                'error' => 1,
                'msg' => 'please first authorize user login'
            ]);
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
            $verify = $this->refreshToken->verify($jti, $ack);
            if (!$verify) {
                return (new Response())->json([
                    'error' => 1,
                    'msg' => 'refresh token verification expired'
                ]);
            }
            $preTokenString = (string)$this->token->create(
                $this->scene,
                $jti,
                $ack,
                $symbol
            );
            if (!$preTokenString) {
                return (new Response())->json([
                    'error' => 1,
                    'msg' => 'create token failed'
                ]);
            }
            $cookie = $this->utils->cookie($this->scene . '_token', $preTokenString);
            $response = $response->withCookie($cookie);
        }
        Context::set('auth', $symbol);
        Context::set(ResponseInterface::class, $response);
        return $handler->handle($request);
    }
}