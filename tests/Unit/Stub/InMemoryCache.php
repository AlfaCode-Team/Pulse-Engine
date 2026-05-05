<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Stub;

use AlfaCode\PulseEngine\Contract\CacheInterface;

final class InMemoryCache implements CacheInterface
{
    private array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->store[$key] = $value;
        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->store[$key]);
    }
}
