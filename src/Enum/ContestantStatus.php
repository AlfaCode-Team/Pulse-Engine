<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Enum;

enum ContestantStatus: string
{
    case Active      = 'active';
    case Disqualified = 'disqualified';
    case Withdrawn   = 'withdrawn';
}
