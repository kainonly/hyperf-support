<?php
declare(strict_types=1);

namespace Hyperf\Support\Func;

use Redis;
use Exception;
use Hyperf\Utils\Str;
use Lcobucci\JWT\Token;
use Hyperf\Support\RedisModel\RefreshToken;
use Psr\Container\ContainerInterface;
use Hyperf\Extra\Token\TokenInterface;
use Hyperf\Extra\Utils\UtilsInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Exception\Http\InvalidResponseException;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use stdClass;

/**
 * Trait Auth
 * @package Hyperf\Support\Traits
 * @property RequestInterface $request
 * @property ResponseInterface $response
 * @property ContainerInterface $container
 * @property TokenInterface $token
 * @property UtilsInterface $utils
 * @property Redis $redis
 */
trait Auth
{
    /**
     * Set RefreshToken Expires
     * @return int
     */
    protected function refreshTokenExpires(): int
    {
        return 604800;
    }

    /**
     * Create Cookie Auth
     * @param string $scene
     * @param stdClass|null $symbol
     * @return PsrResponseInterface
     * @throws Exception
     */
    protected function create(string $scene, ?stdClass $symbol): PsrResponseInterface
    {
        $jti = uuid()->toString();
        $ack = Str::random();
        $result = RefreshToken::create($this->container)->factory($jti, $ack, $this->refreshTokenExpires());
        if (!$result) {
            return $this->response->json([
                'error' => 1,
                'msg' => 'refresh token set failed'
            ]);
        }
        $tokenString = (string)$this->token->create($scene, $jti, $ack, $symbol);
        if (!$tokenString) {
            return $this->response->json([
                'error' => 1,
                'msg' => 'create token failed'
            ]);
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
     * @throws Exception
     */
    protected function authVerify($scene): PsrResponseInterface
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
                $symbol = $token->getClaim('symbol');
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
        } catch (InvalidResponseException $exception) {
            return $this->response->json([
                'error' => 1,
                'msg' => $exception->getMessage()
            ]);
        }
    }

    /**
     * Destory Auth
     * @param string $scene
     * @return PsrResponseInterface
     * @throws Exception
     */
    protected function destory(string $scene): PsrResponseInterface
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