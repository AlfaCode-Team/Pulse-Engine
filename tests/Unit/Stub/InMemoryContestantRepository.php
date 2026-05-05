<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Stub;

use AlfaCode\PulseEngine\Contract\ContestantRepositoryInterface;
use AlfaCode\PulseEngine\Entity\Contestant;
use AlfaCode\PulseEngine\Enum\ContestantStatus;

final class InMemoryContestantRepository implements ContestantRepositoryInterface
{
    /** @var array<int, Contestant> */
    private array $store = [];

    public function seed(Contestant $contestant): void
    {
        $this->store[$contestant->getId()] = $contestant;
    }

    public function findById(int $id): ?Contestant
    {
        return $this->store[$id] ?? null;
    }

    public function findByEdition(int $editionId): array
    {
        return array_values(array_filter(
            $this->store,
            static fn(Contestant $c) => $c->getEditionId() === $editionId,
        ));
    }

    public function incrementVotes(int $id, int $amount = 1): void
    {
        $this->store[$id]?->addVotes($amount);
    }

    public function save(Contestant $contestant): void
    {
        $this->store[$contestant->getId()] = $contestant;
    }
}
