<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Config;

/**
 * Typed, immutable configuration value object.
 * Construct from the raw PHP config array.
 */
final class VotingConfig
{
    public readonly int    $rateLimitPerMinute;
    public readonly string $hmacSecret;
    public readonly int    $priceTierOneMax;     // max votes for tier-1 price
    public readonly int    $priceTierOneKobo;    // kobo per vote, tier 1
    public readonly int    $priceTierTwoMax;
    public readonly int    $priceTierTwoKobo;
    public readonly int    $priceTierThreeKobo;  // rate for volumes above tier 2
    public readonly bool   $freeVoteEnabled;
    public readonly int    $rateLimitWindowSec;

    public function __construct(array $raw)
    {
        $security = $raw['security'] ?? [];
        $pricing  = $raw['pricing']  ?? [];
        $features = $raw['features'] ?? [];

        $this->rateLimitPerMinute  = (int)($security['max_per_minute']   ?? 5);
        $this->rateLimitWindowSec  = (int)($security['window_seconds']   ?? 60);
        $this->hmacSecret          = (string)($security['secret_key']    ?? '');

        $this->priceTierOneMax    = (int)($pricing['tier1_max']    ?? 20);
        $this->priceTierOneKobo   = (int)($pricing['tier1_kobo']   ?? 1000);
        $this->priceTierTwoMax    = (int)($pricing['tier2_max']    ?? 100);
        $this->priceTierTwoKobo   = (int)($pricing['tier2_kobo']   ?? 800);
        $this->priceTierThreeKobo = (int)($pricing['tier3_kobo']   ?? 500);

        $this->freeVoteEnabled = (bool)($features['free_vote'] ?? true);

        if ($this->hmacSecret === '') {
            throw new \InvalidArgumentException(
                'VotingConfig: security.secret_key must not be empty.',
            );
        }
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }

        return new self(require $path);
    }
}
