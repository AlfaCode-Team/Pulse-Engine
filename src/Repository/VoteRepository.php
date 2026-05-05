<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Repository;

use AlfaCode\PulseEngine\Contract\DatabaseInterface;
use AlfaCode\PulseEngine\Contract\VoteRepositoryInterface;
use AlfaCode\PulseEngine\Entity\VoteRecord;
use AlfaCode\PulseEngine\Enum\VoteStatus;

final class VoteRepository implements VoteRepositoryInterface
{
    public function __construct(
        private readonly DatabaseInterface $db,
    ) {}

    public function save(VoteRecord $record): int
    {
        return $this->db->insert('vote_voting', $record->toArray());
    }

    public function findById(int $voteId): ?VoteRecord
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM vote_voting WHERE VoteID = ? LIMIT 1',
            [$voteId],
        );

        return $row !== null ? VoteRecord::fromArray($row) : null;
    }

    public function hasFreeVote(int $userId, int $contestantId, int $editionId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT 1 FROM vote_voting
              WHERE UserID = ? AND ContestantID = ? AND EditionID = ? AND IsPaid = 0
              LIMIT 1',
            [$userId, $contestantId, $editionId],
        );

        return $row !== null;
    }

    public function countByIpSince(string $ip, int $windowSeconds): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS cnt FROM vote_voting
              WHERE IPAddress = ?
                AND CreatedAt > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$ip, $windowSeconds],
        );

        return (int)($row['cnt'] ?? 0);
    }

    /** @return VoteRecord[] */
    public function findByUser(int $userId, int $editionId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM vote_voting
              WHERE UserID = ? AND EditionID = ?
              ORDER BY CreatedAt DESC',
            [$userId, $editionId],
        );

        return array_map(static fn(array $r) => VoteRecord::fromArray($r), $rows);
    }
}
