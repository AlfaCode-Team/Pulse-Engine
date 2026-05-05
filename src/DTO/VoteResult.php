<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\DTO;

/**
 * Returned from VoteService after a successful vote operation.
 */
final readonly class VoteResult
{
    public function __construct(
        public int    $voteId,
        public int    $contestantId,
        public int    $newVoteTotal,
        public bool   $isPaid,
        public string $createdAt,
    ) {}
}
