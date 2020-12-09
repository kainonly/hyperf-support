<?php
declare(strict_types=1);

namespace Hyperf\Support\RedisModel;

use Hyperf\Extra\Common\RedisModel;
use RuntimeException;

class Sms extends RedisModel
{
    protected string $key = 'sms:';

    /**
     * Factory Verify Code For Phone
     * @param string $phone PhoneNumber
     * @param string $code Code
     * @param int $timeout Timeout
     * @return bool
     */
    public function factory(string $phone, string $code, int $timeout = 120): bool
    {
        $data = json_encode([
            'code' => $code,
            'publish_time' => time(),
            'timeout' => $timeout
        ]);
        return $this->redis->setex($this->getKey($phone), $timeout, $data);
    }

    /**
     * Verify Code
     * @param string $phone PhoneNumber
     * @param string $code Code
     * @param boolean $once Only Once
     * @return bool
     */
    public function check(string $phone, string $code, bool $once = false): bool
    {
        if (!$this->redis->exists($this->getKey($phone))) {
            return false;
        }
        $raw = $this->redis->get($this->getKey($phone));
        $data = json_decode($raw, true);
        $result = ($code === $data['code']);
        if ($once && $result) {
            $this->redis->del([$this->getKey($phone)]);
        }
        return $result;
    }

    /**
     * Get Time Information
     * @param string $phone PhoneNumber
     * @return array
     */
    public function time(string $phone): array
    {
        if (!$this->redis->exists($this->getKey($phone))) {
            throw new RuntimeException("The [{$this->getKey($phone)}] cache not exists.");
        }
        $raw = $this->redis->get($this->getKey($phone));
        $data = json_decode($raw, true);
        return [
            'publish_time' => $data['publish_time'],
            'timeout' => $data['timeout']
        ];
    }
}