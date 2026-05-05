<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Enum;

enum VoteStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Refunded = 'refunded';
}
