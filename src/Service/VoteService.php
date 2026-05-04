<?php
namespace AlfacodeTeam\PulseEngine\Service;

use AlfacodeTeam\PulseEngine\Repository\ContestantRepository;
use AlfacodeTeam\PulseEngine\Security\Guard;
use AlfacodeTeam\PulseEngine\Contract\DatabaseInterface;

class VoteService {
    private $db;
    private $repo;
    private $guard;

    public function __construct(DatabaseInterface $db, ContestantRepository $repo, Guard $guard) {
        $this->db = $db;
        $this->repo = $repo;
        $this->guard = $guard;
    }

    public function castStandardVote(int $userId, int $contestantId, string $ip): bool {
        $this->guard->checkRateLimit($ip);
        
        $contestant = $this->repo->findById($contestantId);
        if (!$contestant || !$contestant->isActive()) {
            throw new \Exception("Invalid or inactive contestant.");
        }

        $this->db->startTransaction();
        try {
            $this->db->insert("vote_voting", [
                "UserID" => $userId,
                "ContestantID" => $contestantId,
                "EditionID" => $contestant->editionId,
                "CreatedAt" => date("Y-m-d H:i:s")
            ]);
            $this->repo->incrementVotes($contestantId);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}