<?php
namespace AlfacodeTeam\PulseEngine\Repository;

use AlfacodeTeam\PulseEngine\Contract\DatabaseInterface;
use AlfacodeTeam\PulseEngine\Entity\Contestant;

class ContestantRepository {
    private $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function findById(int $id): ?Contestant {
        $data = $this->db->getOne("SELECT * FROM vote_contestants WHERE ID = ?", [$id]);
        return $data ? new Contestant($data) : null;
    }

    public function incrementVotes(int $id, int $amount = 1): void {
        $this->db->query("UPDATE vote_contestants SET Votes = Votes + ? WHERE ID = ?", [$amount, $id]);
    }
}