<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Service;

use AlfaCode\PulseEngine\Config\VotingConfig;

/**
 * Tiered pricing engine.
 *
 * Tier 1  : up to $config->priceTierOneMax votes  → $config->priceTierOneKobo  per vote
 * Tier 2  : up to $config->priceTierTwoMax votes  → $config->priceTierTwoKobo  per vote
 * Tier 3+ : any volume above tier 2               → $config->priceTierThreeKobo per vote
 *
 * All amounts are in the smallest currency unit (kobo, cents, etc.).
 */
final class PricingEngine
{
    public function __construct(
        private readonly VotingConfig $config,
    ) {}

    /**
     * Calculate total cost in kobo for $count votes.
     */
    public function calculate(int $count): int
    {
        if ($count < 1) {
            throw new \InvalidArgumentException("Vote count must be >= 1, got {$count}.");
        }

        if ($count <= $this->config->priceTierOneMax) {
            return $count * $this->config->priceTierOneKobo;
        }

        if ($count <= $this->config->priceTierTwoMax) {
            return $count * $this->config->priceTierTwoKobo;
        }

        return $count * $this->config->priceTierThreeKobo;
    }

    /**
     * Returns a human-readable price breakdown.
     */
    public function describe(int $count): string
    {
        $kobo   = $this->calculate($count);
        $naira  = number_format($kobo / 100, 2);

        return "{$count} votes = ₦{$naira}";
    }
}
