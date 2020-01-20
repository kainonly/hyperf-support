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
    public function factory($phone, $code, $timeout = 120): bool
    {
        $data = json_encode([
            'code' => $code,
            'publish_time' => time(),
            'timeout' => $timeout
        ], JSON_THROW_ON_ERROR, 512);
        return $this->redis->setex($this->key . $phone, $timeout, $data);
    }

    /**
     * Verify Code
     * @param string $phone PhoneNumber
     * @param string $code Code
     * @param boolean $once Only Once
     * @return bool
     */
    public function check($phone, $code, $once = false): bool
    {
        if (!$this->redis->exists($this->key . $phone)) {
            return false;
        }
        $data = json_decode(
            $this->redis->get($this->key . $phone),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $result = ($code === $data['code']);
        if ($once && $result) {
            $this->redis->del([
                $this->key . $phone
            ]);
        }
        return $result;
    }

    /**
     * Get Time Information
     * @param string $phone PhoneNumber
     * @return array
     */
    public function time($phone): array
    {
        if (!$this->redis->exists($this->key . $phone)) {
            throw new RuntimeException("The [$this->key . $phone] cache not exists.");
        }
        $data = json_decode(
            $this->redis->get($this->key . $phone),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        return [
            'publish_time' => $data['publish_time'],
            'timeout' => $data['timeout']
        ];
    }
}