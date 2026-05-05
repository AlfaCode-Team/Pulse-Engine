<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Stub;

use AlfaCode\PulseEngine\Contract\VoteRepositoryInterface;
use AlfaCode\PulseEngine\Entity\VoteRecord;

final class InMemoryVoteRepository implements VoteRepositoryInterface
{
    /** @var array<int, VoteRecord> */
    private array $store = [];
    private int $nextId = 1;

    public function save(VoteRecord $record): int
    {
        $id = $this->nextId++;
        $record->setId($id);
        $this->store[$id] = $record;
        return $id;
    }

    public function findById(int $voteId): ?VoteRecord
    {
        return $this->store[$voteId] ?? null;
    }

    public function hasFreeVote(int $userId, int $contestantId, int $editionId): bool
    {
        foreach ($this->store as $record) {
            if (
                $record->getUserId()       === $userId       &&
                $record->getContestantId() === $contestantId &&
                $record->getEditionId()    === $editionId    &&
                !$record->isPaid()
            ) {
                return true;
            }
        }
        return false;
    }

    public function countByIpSince(string $ip, int $windowSeconds): int
    {
        return count(array_filter(
            $this->store,
            static fn(VoteRecord $r) => $r->getIpAddress() === $ip,
        ));
    }

    public function findByUser(int $userId, int $editionId): array
    {
        return array_values(array_filter(
            $this->store,
            static fn(VoteRecord $r) =>
                $r->getUserId() === $userId && $r->getEditionId() === $editionId,
        ));
    }
}
