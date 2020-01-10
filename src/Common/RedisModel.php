<?php
declare(strict_types=1);

namespace Hyperf\Support\Common;

use Psr\Container\ContainerInterface;

/**
 * Class RedisModel
 * @package Hyperf\Support\Common
 */
abstract class RedisModel
{
    /**
     * Model key
     * @var string $key
     */
    protected $key;
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * Redis Manager
     * @var  \Redis $redis
     */
    protected $redis;

    /**
     * Create RedisModel
     * @param \Redis $redis
     * @return static
     */
    public static function create(ContainerInterface $container)
    {
        return make(static::class, [
            $container
        ]);
    }

    /**
     * RedisModel constructor.
     * @param \Redis $redis
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->redis = $container->get(\Redis::class);
    }
}
