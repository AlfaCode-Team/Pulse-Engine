<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Security;

use AlfaCode\PulseEngine\Config\VotingConfig;

/**
 * Generates and verifies HMAC-SHA256 signatures for vote payload integrity.
 * Use the generated signature to protect webhook callbacks and IPC messages.
 */
final class IntegrityGuard
{
    public function __construct(
        private readonly VotingConfig $config,
    ) {}

    /**
     * Generate a deterministic signature for a contestant's current vote count.
     */
    public function sign(int $contestantId, int $votes): string
    {
        return hash_hmac('sha256', "{$contestantId}|{$votes}", $this->config->hmacSecret);
    }

    /**
     * Verify a signature against expected values. Uses constant-time comparison.
     */
    public function verify(int $contestantId, int $votes, string $signature): bool
    {
        return hash_equals($this->sign($contestantId, $votes), $signature);
    }

    /**
     * Generate a unique payment reference string.
     */
    public function generateReference(int $userId, int $contestantId): string
    {
        $raw = "{$userId}:{$contestantId}:" . microtime(true) . ':' . random_int(100000, 999999);

        return 'PE-' . strtoupper(substr(hash_hmac('sha256', $raw, $this->config->hmacSecret), 0, 16));
    }
}
