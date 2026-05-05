<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\DTO;

/**
 * Immutable command object for casting a free (non-paid) vote.
 */
final readonly class CastVoteCommand
{
    public function __construct(
        public int    $userId,
        public int    $contestantId,
        public int    $editionId,
        public string $ipAddress,
        public string $userAgent = '',
    ) {}
}
