<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Service;

use AlfaCode\PulseEngine\Contract\VoteRepositoryInterface;
use AlfaCode\PulseEngine\Exception\SubscriptionLimitExceededException;

/**
 * Enforces per-user, per-edition daily free-vote limits.
 *
 * Subscription records are expected to be managed externally (your app).
 * Pass the user's subscription data array to checkLimit().
 */
final class SubscriptionService
{
    public function __construct(
        private readonly VoteRepositoryInterface $voteRepository,
    ) {}

    /**
     * Retrieve the user's daily limit for a given edition from their subscription data.
     * Returns 0 if no subscription entry exists.
     */
    public function getDailyLimit(array $userSubscriptions, int $editionId): int
    {
        return (int)($userSubscriptions[$editionId]['daily_limit'] ?? 0);
    }

    /**
     * Throw SubscriptionLimitExceededException if the user has no remaining free votes.
     *
     * @param array $userSubscriptions  Keyed by edition ID, each with a 'daily_limit' key.
     */
    public function assertWithinLimit(
        int   $userId,
        int   $editionId,
        array $userSubscriptions,
    ): void {
        $limit = $this->getDailyLimit($userSubscriptions, $editionId);

        if ($limit < 1) {
            // No subscription — free votes are not permitted for this edition.
            throw new SubscriptionLimitExceededException($userId, 0);
        }

        $todayCast = count($this->voteRepository->findByUser($userId, $editionId));

        if ($todayCast >= $limit) {
            throw new SubscriptionLimitExceededException($userId, $limit);
        }
    }
}
