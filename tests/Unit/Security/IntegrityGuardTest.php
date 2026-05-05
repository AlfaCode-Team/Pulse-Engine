<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Unit\Security;

use AlfaCode\PulseEngine\Config\VotingConfig;
use AlfaCode\PulseEngine\Security\IntegrityGuard;
use PHPUnit\Framework\TestCase;

final class IntegrityGuardTest extends TestCase
{
    private IntegrityGuard $guard;

    protected function setUp(): void
    {
        $config = new VotingConfig([
            'security' => ['max_per_minute' => 5, 'window_seconds' => 60, 'secret_key' => 'test-secret-32-chars-long-here!!'],
            'pricing'  => ['tier1_max' => 20, 'tier1_kobo' => 1000, 'tier2_max' => 100, 'tier2_kobo' => 800, 'tier3_kobo' => 500],
            'features' => ['free_vote' => true],
        ]);
        $this->guard = new IntegrityGuard($config);
    }

    public function test_sign_returns_64_char_hex_string(): void
    {
        $sig = $this->guard->sign(1, 100);
        $this->assertSame(64, strlen($sig));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $sig);
    }

    public function test_verify_returns_true_for_correct_signature(): void
    {
        $sig = $this->guard->sign(42, 500);
        $this->assertTrue($this->guard->verify(42, 500, $sig));
    }

    public function test_verify_returns_false_for_tampered_values(): void
    {
        $sig = $this->guard->sign(42, 500);
        $this->assertFalse($this->guard->verify(42, 501, $sig)); // votes tampered
        $this->assertFalse($this->guard->verify(43, 500, $sig)); // id tampered
    }

    public function test_generate_reference_returns_pe_prefixed_string(): void
    {
        $ref = $this->guard->generateReference(1, 1);
        $this->assertStringStartsWith('PE-', $ref);
    }

    public function test_generate_reference_is_unique_across_calls(): void
    {
        $refs = [];
        for ($i = 0; $i < 10; $i++) {
            $refs[] = $this->guard->generateReference(1, 1);
            usleep(1000);
        }
        $this->assertSame(count($refs), count(array_unique($refs)));
    }
}
