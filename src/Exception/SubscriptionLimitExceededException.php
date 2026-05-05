<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Exception;

final class SubscriptionLimitExceededException extends PulseEngineException
{
    public function __construct(int $userId, int $limit)
    {
        parent::__construct(
            "User #{$userId} has reached the daily vote limit of {$limit}.",
            429,
        );
    }
}
