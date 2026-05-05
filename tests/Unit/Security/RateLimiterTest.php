<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Unit\Security;

use AlfaCode\PulseEngine\Config\VotingConfig;
use AlfaCode\PulseEngine\Exception\RateLimitExceededException;
use AlfaCode\PulseEngine\Security\RateLimiter;
use AlfaCode\PulseEngine\Tests\Stub\InMemoryCache;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        $config = new VotingConfig([
            'security' => ['max_per_minute' => 3, 'window_seconds' => 60, 'secret_key' => 'test-secret-32-chars-long-here!!'],
            'pricing'  => ['tier1_max' => 20, 'tier1_kobo' => 1000, 'tier2_max' => 100, 'tier2_kobo' => 800, 'tier3_kobo' => 500],
            'features' => ['free_vote' => true],
        ]);
        $this->limiter = new RateLimiter(new InMemoryCache(), $config);
    }

    public function test_check_does_not_throw_below_limit(): void
    {
        $this->limiter->check('1.2.3.4');
        $this->assertTrue(true); // no exception
    }

    public function test_check_throws_after_limit_is_hit(): void
    {
        $ip = '9.9.9.9';

        // Record 3 attempts (at the limit)
        $this->limiter->record($ip);
        $this->limiter->record($ip);
        $this->limiter->record($ip);

        $this->expectException(RateLimitExceededException::class);
        $this->expectExceptionCode(429);
        $this->limiter->check($ip);
    }

    public function test_different_ips_are_tracked_independently(): void
    {
        $this->limiter->record('1.1.1.1');
        $this->limiter->record('1.1.1.1');
        $this->limiter->record('1.1.1.1');

        // Different IP should still be fine
        $this->limiter->check('2.2.2.2');
        $this->assertTrue(true);
    }
}
