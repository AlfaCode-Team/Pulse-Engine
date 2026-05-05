<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\DTO;

/**
 * Immutable command object for initiating a paid vote purchase.
 */
final readonly class PurchaseVotesCommand
{
    public function __construct(
        public int    $userId,
        public int    $contestantId,
        public int    $editionId,
        public int    $voteCount,
        public string $ipAddress,
        public string $callbackUrl,
        public string $userAgent = '',
    ) {}
}
