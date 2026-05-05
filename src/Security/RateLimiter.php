<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Security;

use AlfaCode\PulseEngine\Config\VotingConfig;
use AlfaCode\PulseEngine\Contract\CacheInterface;
use AlfaCode\PulseEngine\Contract\RateLimiterInterface;
use AlfaCode\PulseEngine\Exception\RateLimitExceededException;

/**
 * Cache-backed sliding-window rate limiter.
 *
 * Each IP gets a counter key in the cache. The TTL is the window length.
 * On every call to check(), if the count meets or exceeds the limit we throw.
 * On record() we increment (and create if absent).
 */
final class RateLimiter implements RateLimiterInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly VotingConfig   $config,
    ) {}

    public function check(string $ip): void
    {
        $count = (int)$this->cache->get($this->key($ip), 0);

        if ($count >= $this->config->rateLimitPerMinute) {
            throw new RateLimitExceededException($ip, $this->config->rateLimitPerMinute);
        }
    }

    public function record(string $ip): void
    {
        $key   = $this->key($ip);
        $count = (int)$this->cache->get($key, 0);
        $this->cache->set($key, $count + 1, $this->config->rateLimitWindowSec);
    }

    private function key(string $ip): string
    {
        return 'pulse_rate:' . hash('xxh3', $ip);
    }
}
