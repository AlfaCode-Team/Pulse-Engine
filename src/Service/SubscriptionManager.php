<?php
namespace AlfacodeTeam\PulseEngine\Service;

class SubscriptionManager {
    public function checkLimit(array $userSubs, int $editionId): int {
        return $userSubs[$editionId]["daily_limit"] ?? 0;
    }
}