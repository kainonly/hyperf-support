<?php
declare(strict_types=1);

namespace Hyperf\Support\Traits;

use Exception;
use Hyperf\HttpServer\Exception\Http\InvalidResponseException;
use Hyperf\Utils\Str;
use Hyperf\Extra\Contract\TokenServiceInterface;
use Hyperf\Extra\Contract\UtilsServiceInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Support\Redis\RefreshToken;
use Lcobucci\JWT\Token;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

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
    protected function __refreshTokenExpires(): int
    {
        return 604800;
    }

    /**
     * Create Cookie Auth
     * @param string $scene
     * @param array $symbol
     * @return PsrResponseInterface
     * @throws Exception
     */
    protected function __create(string $scene, array $symbol = []): PsrResponseInterface
    {
        $jti = $this->utils->uuid()->toString();
        $ack = Str::random();
        $result = RefreshToken::create($this->container)->factory($jti, $ack, $this->__refreshTokenExpires());
        if (!$result) {
            throw new InvalidResponseException('refresh token set failed');
        }
        $tokenString = (string)$this->token->create($scene, $jti, $ack, $symbol);
        if (!$tokenString) {
            throw new InvalidResponseException('create token failed');
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
     * @return PsrResponseInterface
     */
    protected function __verify($scene): PsrResponseInterface
    {
        try {
            $tokenString = $this->request->cookie($scene . '_token');
            if (empty($tokenString)) {
                throw new InvalidResponseException('refresh token not exists');
            }

            $result = $this->token->verify($scene, $tokenString);
            if ($result->expired) {
                /**
                 * @var $token Token
                 */
                $token = $result->token;
                $jti = $token->getClaim('jti');
                $ack = $token->getClaim('ack');
                $verify = RefreshToken::create($this->container)->verify($jti, $ack);
                if (!$verify) {
                    throw new InvalidResponseException('refresh token verification expired');
                }
                $symbol = (array)$token->getClaim('symbol');
                $preTokenString = (string)$this->token->create(
                    $scene,
                    $jti,
                    $ack,
                    $symbol
                );
                if (!$preTokenString) {
                    throw new InvalidResponseException('create token failed');
                }
                $cookie = $this->utils->cookie($scene . '_token', $preTokenString);
                return $this->response->withCookie($cookie)->json([
                    'error' => 0,
                    'msg' => 'ok'
                ]);
            }

            return $this->response->json([
                'error' => 0,
                'msg' => 'ok'
            ]);
        } catch (InvalidResponseException $e) {
            return $this->response->json([
                'error' => 1,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * Destory Auth
     * @param string $scene
     * @return PsrResponseInterface
     */
    protected function __destory(string $scene): PsrResponseInterface
    {
        $tokenString = $this->request->cookie($scene . '_token');
        if (!empty($tokenString)) {
            $token = $this->token->get($tokenString);
            RefreshToken::create($this->container)->clear(
                $token->getClaim('jti'),
                $token->getClaim('ack')
            );
        }
        $cookie = $this->utils->cookie($scene . '_token', '');
        return $this->response->withCookie($cookie)->json([
            'error' => 0,
            'msg' => 'ok'
        ]);
    }
}