<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Event;

final class VoteCastEvent extends DomainEvent
{
    public function __construct(
        public readonly int    $voteId,
        public readonly int    $userId,
        public readonly int    $contestantId,
        public readonly int    $editionId,
        public readonly int    $voteCount,
        public readonly bool   $isPaid,
        public readonly string $ipAddress,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return 'pulse_engine.vote_cast';
    }
}
