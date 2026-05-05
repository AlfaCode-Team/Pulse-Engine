<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Unit\Config;

use AlfaCode\PulseEngine\Config\VotingConfig;
use PHPUnit\Framework\TestCase;

final class VotingConfigTest extends TestCase
{
    private function validRaw(): array
    {
        return [
            'security' => ['max_per_minute' => 5, 'window_seconds' => 60, 'secret_key' => 'a-long-secret-key-32-chars-here!!'],
            'pricing'  => ['tier1_max' => 20, 'tier1_kobo' => 1000, 'tier2_max' => 100, 'tier2_kobo' => 800, 'tier3_kobo' => 500],
            'features' => ['free_vote' => true],
        ];
    }

    public function test_constructs_with_valid_config(): void
    {
        $config = new VotingConfig($this->validRaw());

        $this->assertSame(5, $config->rateLimitPerMinute);
        $this->assertSame(60, $config->rateLimitWindowSec);
        $this->assertSame(20, $config->priceTierOneMax);
        $this->assertSame(1000, $config->priceTierOneKobo);
        $this->assertTrue($config->freeVoteEnabled);
    }

    public function test_throws_when_secret_key_is_empty(): void
    {
        $raw = $this->validRaw();
        $raw['security']['secret_key'] = '';

        $this->expectException(\InvalidArgumentException::class);
        new VotingConfig($raw);
    }

    public function test_applies_defaults_for_missing_optional_keys(): void
    {
        $config = new VotingConfig([
            'security' => ['secret_key' => 'valid-key-32-chars-at-minimum!!'],
        ]);

        $this->assertSame(5, $config->rateLimitPerMinute);   // default
        $this->assertSame(1000, $config->priceTierOneKobo);  // default
        $this->assertTrue($config->freeVoteEnabled);          // default
    }
}
