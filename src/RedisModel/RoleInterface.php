<?php
declare (strict_types=1);

namespace Hyperf\Support\RedisModel;

interface RoleInterface
{
    /**
     * clear role redis
     */
    public function clear(): void;

    /**
     * get role data
     * @param array $keys role key list
     * @param string $type resource or acl
     * @return array
     */
    public function get(array $keys, string $type): array;
}