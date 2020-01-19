<?php
declare(strict_types=1);

namespace Hyperf\Support\Redis;

use Exception;
use Hyperf\Extra\Contract\HashInterface;
use Hyperf\Support\Common\RedisModel;
use Psr\Container\ContainerInterface;

class RefreshToken extends RedisModel
{
    protected string $key = 'refresh-token:';
    private HashInterface $hash;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->hash = $container->get(HashInterface::class);
    }

    /**
     * Factory Refresh Token
     * @param string $jti Token ID
     * @param string $ack Ack Code
     * @param int $expires Expires
     * @return bool
     */
    public function factory(string $jti, string $ack, int $expires): bool
    {
        return $this->redis->setex(
            $this->key . $jti,
            $expires,
            $this->hash->create($ack)
        );
    }

    /**
     * Verify Refresh Token
     * @param string $jti Token ID
     * @param string $ack Ack Code
     * @return bool
     * @throws Exception
     */
    public function verify(string $jti, string $ack): bool
    {
        if (!$this->redis->exists($this->key . $jti)) {
            throw new Exception("The [$this->key . $jti] cache not exists.");
        }
        return $this->hash->check(
            $ack,
            $this->redis->get($this->key . $jti)
        );
    }

    /**
     * Delete Refresh Token
     * @param string $jti Token ID
     * @param string $ack Ack Code
     * @return int
     * @throws Exception
     */
    public function clear(string $jti, string $ack): int
    {
        if (!$this->redis->exists($this->key . $jti)) {
            throw new Exception("The [$this->key . $jti] cache not exists.");
        }
        if (!$this->hash->check($ack, $this->redis->get($this->key . $jti))) {
            throw new Exception("Token confirmation codes are inconsistent.");
        }
        return $this->redis->del([$this->key . $jti]);
    }
}