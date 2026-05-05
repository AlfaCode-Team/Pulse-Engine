<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Exception;

final class InactiveContestantException extends PulseEngineException
{
    public function __construct(int $contestantId)
    {
        parent::__construct("Contestant #{$contestantId} is not accepting votes.", 422);
    }
}
