<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Stub;

use AlfaCode\PulseEngine\Entity\Contestant;
use AlfaCode\PulseEngine\Enum\ContestantStatus;

final class ContestantFactory
{
    public static function active(
        int    $id        = 1,
        int    $editionId = 10,
        int    $votes     = 0,
        string $name      = 'Test Contestant',
    ): Contestant {
        return Contestant::fromArray([
            'ID'        => $id,
            'FullName'  => $name,
            'Votes'     => $votes,
            'EditionID' => $editionId,
            'Status'    => ContestantStatus::Active->value,
            'CreatedAt' => '2026-01-01 00:00:00',
            'UpdatedAt' => '2026-01-01 00:00:00',
        ]);
    }

    public static function disqualified(int $id = 2, int $editionId = 10): Contestant
    {
        return Contestant::fromArray([
            'ID'        => $id,
            'FullName'  => 'Disqualified Contestant',
            'Votes'     => 0,
            'EditionID' => $editionId,
            'Status'    => ContestantStatus::Disqualified->value,
            'CreatedAt' => '2026-01-01 00:00:00',
            'UpdatedAt' => '2026-01-01 00:00:00',
        ]);
    }
}
