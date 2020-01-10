<?php
declare(strict_types=1);

namespace Hyperf\Support\Redis;

use Hyperf\Extra\Contract\HashServiceInterface;
use Hyperf\Support\Common\RedisModel;
use Psr\Container\ContainerInterface;

class RefreshToken extends RedisModel
{
    protected $key = 'refresh-token:';
    /**
     * @var HashServiceInterface
     */
    private $hash;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->hash = $container->get(HashServiceInterface::class);
    }

    /**
     * Factory Refresh Token
     * @param string $jti Token ID
     * @param string $ack Ack Code
     * @param int $expires Expires
     * @return bool
     */
    public function factory(string $jti, string $ack, int $expires)
    {
        return $this->redis->setex(
            $this->key . $jti,
            $expires,
            $this->hash->make($ack)
        );
    }

    /**
     * Verify Refresh Token
     * @param string $jti Token ID
     * @param string $ack Ack Code
     * @return bool
     */
    public function verify(string $jti, string $ack)
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
    public function clear(string $jti, string $ack)
    {
        if (!$this->redis->exists($this->key . $jti)) {
            return true;
        }

        if (!$this->hash->check($ack, $this->redis->get($this->key . $jti))) {
            return false;
        }

        return $this->redis->del([$this->key . $jti]);
    }
}