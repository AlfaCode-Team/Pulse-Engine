<?php
namespace AlfacodeTeam\PulseEngine\Entity;

class VoteRecord {
    public $voteId;
    public $userId;
    public $contestantId;
    public $votedAt;

    public function __construct(array $data) {
        $this->voteId = $data["VoteID"] ?? null;
        $this->userId = $data["UserID"] ?? null;
        $this->contestantId = $data["ContestantID"] ?? null;
        $this->votedAt = $data["CreatedAt"] ?? date("Y-m-d H:i:s");
    }
}