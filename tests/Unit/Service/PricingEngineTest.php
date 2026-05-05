<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Unit\Service;

use AlfaCode\PulseEngine\Config\VotingConfig;
use AlfaCode\PulseEngine\Service\PricingEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PricingEngineTest extends TestCase
{
    private PricingEngine $engine;

    protected function setUp(): void
    {
        $config = new VotingConfig([
            'security' => ['max_per_minute' => 5, 'window_seconds' => 60, 'secret_key' => 'test-secret-32-chars-long-here!!'],
            'pricing'  => [
                'tier1_max'  => 20,  'tier1_kobo' => 1000,
                'tier2_max'  => 100, 'tier2_kobo' => 800,
                'tier3_kobo' => 500,
            ],
            'features' => ['free_vote' => true],
        ]);
        $this->engine = new PricingEngine($config);
    }

    #[DataProvider('pricingProvider')]
    public function test_calculate_returns_correct_kobo(int $count, int $expectedKobo): void
    {
        $this->assertSame($expectedKobo, $this->engine->calculate($count));
    }

    public static function pricingProvider(): array
    {
        return [
            'tier 1 — 1 vote'    => [1,   1_000],
            'tier 1 — 20 votes'  => [20,  20_000],
            'tier 2 — 21 votes'  => [21,  16_800],
            'tier 2 — 100 votes' => [100, 80_000],
            'tier 3 — 101 votes' => [101, 50_500],
            'tier 3 — 200 votes' => [200, 100_000],
        ];
    }

    public function test_calculate_throws_on_zero_count(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->calculate(0);
    }

    public function test_describe_returns_readable_string(): void
    {
        $description = $this->engine->describe(10);
        $this->assertStringContainsString('10 votes', $description);
        $this->assertStringContainsString('₦', $description);
    }
}
