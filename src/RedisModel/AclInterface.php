<?php
declare (strict_types=1);

namespace Hyperf\Support\RedisModel;

interface AclInterface
{
    /**
     * clear acl redis
     */
    public function clear(): void;

    /**
     * get acl data
     * @param string $key acl key
     * @param int $policy 0 => read 1 => read and write
     * @return array
     */
    public function get(string $key, int $policy): array;
}