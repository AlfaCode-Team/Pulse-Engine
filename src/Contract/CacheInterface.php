<?php
namespace AlfacodeTeam\PulseEngine\Contract;

interface CacheInterface {
    public function get(string $key);
    public function set(string $key, $value, int $ttl = 3600);
    public function delete(string $key);
}