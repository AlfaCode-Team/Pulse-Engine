<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Contract;

use AlfaCode\PulseEngine\Entity\Contestant;

interface ContestantRepositoryInterface
{
    public function findById(int $id): ?Contestant;

    /** @return Contestant[] */
    public function findByEdition(int $editionId): array;

    public function incrementVotes(int $id, int $amount = 1): void;

    public function save(Contestant $contestant): void;
}
