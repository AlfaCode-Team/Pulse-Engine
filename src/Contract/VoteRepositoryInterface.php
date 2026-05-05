<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Contract;

use AlfaCode\PulseEngine\Entity\VoteRecord;

interface VoteRepositoryInterface
{
    public function save(VoteRecord $record): int;

    public function findById(int $voteId): ?VoteRecord;

    /**
     * Check whether a user already cast a free vote for a contestant in an edition.
     */
    public function hasFreeVote(int $userId, int $contestantId, int $editionId): bool;

    /**
     * Count votes cast from a given IP within the last $windowSeconds seconds.
     */
    public function countByIpSince(string $ip, int $windowSeconds): int;

    /** @return VoteRecord[] */
    public function findByUser(int $userId, int $editionId): array;
}
