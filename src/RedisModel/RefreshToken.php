<?php
declare(strict_types=1);

namespace Hyperf\Support\RedisModel;

use Hyperf\Extra\Hash\HashInterface;
use Hyperf\Extra\Common\RedisModel;
use Psr\Container\ContainerInterface;
use RuntimeException;

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
     */
    public function verify(string $jti, string $ack): bool
    {
        if (!$this->redis->exists($this->key . $jti)) {
            return false;
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
     */
    public function clear(string $jti, string $ack): int
    {
        if (!$this->redis->exists($this->key . $jti)) {
            return 1;
        }
        if (!$this->hash->check($ack, $this->redis->get($this->key . $jti))) {
            throw new RuntimeException('Token confirmation codes are inconsistent.');
        }
        return $this->redis->del([$this->key . $jti]);
    }
}