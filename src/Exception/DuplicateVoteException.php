<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Exception;

final class DuplicateVoteException extends PulseEngineException
{
    public function __construct(int $userId, int $contestantId)
    {
        parent::__construct(
            "User #{$userId} has already cast a free vote for contestant #{$contestantId}.",
            409,
        );
    }
}
