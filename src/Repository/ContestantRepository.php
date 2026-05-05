<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Repository;

use AlfaCode\PulseEngine\Contract\ContestantRepositoryInterface;
use AlfaCode\PulseEngine\Contract\DatabaseInterface;
use AlfaCode\PulseEngine\Entity\Contestant;

final class ContestantRepository implements ContestantRepositoryInterface
{
    public function __construct(
        private readonly DatabaseInterface $db,
    ) {}

    public function findById(int $id): ?Contestant
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM vote_contestants WHERE ID = ? LIMIT 1',
            [$id],
        );

        return $row !== null ? Contestant::fromArray($row) : null;
    }

    /** @return Contestant[] */
    public function findByEdition(int $editionId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM vote_contestants WHERE EditionID = ? ORDER BY Votes DESC',
            [$editionId],
        );

        return array_map(static fn(array $r) => Contestant::fromArray($r), $rows);
    }

    public function incrementVotes(int $id, int $amount = 1): void
    {
        $this->db->execute(
            'UPDATE vote_contestants SET Votes = Votes + ?, UpdatedAt = NOW() WHERE ID = ?',
            [$amount, $id],
        );
    }

    public function save(Contestant $contestant): void
    {
        $data = $contestant->toArray();
        unset($data['ID']);

        if ($contestant->getId() > 0) {
            $this->db->update('vote_contestants', $data, ['ID' => $contestant->getId()]);
        } else {
            $this->db->insert('vote_contestants', $data);
        }
    }
}
