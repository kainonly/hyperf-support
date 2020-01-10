<?php
declare(strict_types=1);

namespace Hyperf\Support\Traits;

use Hyperf\Utils\Str;
use Hyperf\Extra\Contract\TokenServiceInterface;
use Hyperf\Extra\Contract\UtilsServiceInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Support\Redis\RefreshToken;
use Psr\Container\ContainerInterface;

/**
 * Trait Auth
 * @package Hyperf\Support\Traits
 * @property RequestInterface $request
 * @property ResponseInterface $response
 * @property ContainerInterface $container
 * @property TokenServiceInterface $token
 * @property UtilsServiceInterface $utils
 * @property \Redis $redis
 */
trait Auth
{
    /**
     * Set RefreshToken Expires
     * @return int
     */
    protected function __refreshTokenExpires()
    {
        return 604800;
    }

    /**
     * Create Cookie Auth
     * @param string $scene
     * @param array $symbol
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    protected function __create(string $scene, array $symbol = [])
    {
        $jti = $this->utils->uuid()->toString();
        $ack = Str::random();
        $result = RefreshToken::create($this->container)->factory($jti, $ack, $this->__refreshTokenExpires());
        if (!$result) {
            return $this->response->json([
                'error' => 1,
                'msg' => 'refresh token set failed'
            ]);
        }
        $tokenString = (string)$this->token->create($scene, $jti, $ack, $symbol);
        if (!$tokenString) {
            return [
                'error' => 1,
                'msg' => 'create token failed'
            ];
        }
        $cookie = $this->utils->cookie($scene . '_token', $tokenString);
        return $this->response->withCookie($cookie)->json([
            'error' => 0,
            'msg' => 'ok'
        ]);
    }

    /**
     * Auth Verify
     * @param $scene
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    protected function __verify($scene)
    {
        try {
            $tokenString = $this->request->cookie($scene . '_token');
            if (empty($tokenString)) {
                return [
                    'error' => 1,
                    'msg' => 'refresh token not exists'
                ];
            }

            $result = $this->token->verify($scene, $tokenString);
            if ($result->expired) {
                /**
                 * @var $token \Lcobucci\JWT\Token
                 */
                $token = $result->token;
                $jti = $token->getClaim('jti');
                $ack = $token->getClaim('ack');
                $verify = RefreshToken::create($this->container)->verify($jti, $ack);
                if (!$verify) {
                    return [
                        'error' => 1,
                        'msg' => 'refresh token verification expired'
                    ];
                }
                $symbol = (array)$token->getClaim('symbol');
                $preTokenString = (string)$this->token->create(
                    $scene,
                    $jti,
                    $ack,
                    $symbol
                );
                if (!$preTokenString) {
                    return [
                        'error' => 1,
                        'msg' => 'create token failed'
                    ];
                }
                $cookie = $this->utils->cookie($scene . '_token', $preTokenString);
                return $this->response->withCookie($cookie)->json([
                    'error' => 0,
                    'msg' => 'ok'
                ]);
            }

            return [
                'error' => 0,
                'msg' => 'ok'
            ];
        } catch (\Exception $e) {
            return [
                'error' => 1,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * Destory Auth
     * @param string $scene
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function __destory(string $scene)
    {
        $tokenString = $this->request->cookie($scene . '_token');
        $token = $this->token->get($tokenString);
        RefreshToken::create($this->container)->clear(
            $token->getClaim('jti'),
            $token->getClaim('ack')
        );
        $cookie = $this->utils->cookie($scene . '_token', '');
        return $this->response->withCookie($cookie)->json([
            'error' => 0,
            'msg' => 'ok'
        ]);
    }
}