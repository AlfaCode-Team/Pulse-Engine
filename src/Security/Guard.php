<?php
namespace AlfacodeTeam\PulseEngine\Security;

use AlfacodeTeam\PulseEngine\Contract\DatabaseInterface;

class Guard {
    private $db;
    private $config;

    public function __construct(DatabaseInterface $db, array $config) {
        $this->db = $db;
        $this->config = $config;
    }

    public function checkRateLimit(string $ip): void {
        $sql = "SELECT COUNT(*) as total FROM vote_logs WHERE ip_address = ? AND created_at > NOW() - INTERVAL 1 MINUTE";
        $res = $this->db->getOne($sql, [$ip]);
        
        if ($res && $res["total"] >= $this->config["max_per_minute"]) {
            throw new \Exception("Rate limit exceeded. Please wait.");
        }
    }

    public function logAttempt(int $userId, int $contestantId, string $ip, string $status): void {
        $this->db->insert("vote_logs", [
            "user_id" => $userId,
            "contestant_id" => $contestantId,
            "ip_address" => $ip,
            "status" => $status,
            "created_at" => date("Y-m-d H:i:s")
        ]);
    }
}