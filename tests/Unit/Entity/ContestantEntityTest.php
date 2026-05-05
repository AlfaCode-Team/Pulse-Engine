<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Unit\Entity;

use AlfaCode\PulseEngine\Enum\ContestantStatus;
use AlfaCode\PulseEngine\Tests\Stub\ContestantFactory;
use PHPUnit\Framework\TestCase;

final class ContestantEntityTest extends TestCase
{
    public function test_is_active_returns_true_for_active_status(): void
    {
        $c = ContestantFactory::active();
        $this->assertTrue($c->isActive());
    }

    public function test_is_active_returns_false_for_disqualified(): void
    {
        $c = ContestantFactory::disqualified();
        $this->assertFalse($c->isActive());
    }

    public function test_add_votes_increments_total(): void
    {
        $c = ContestantFactory::active(votes: 100);
        $c->addVotes(25);
        $this->assertSame(125, $c->getVotes());
    }

    public function test_add_votes_throws_on_zero_or_negative(): void
    {
        $c = ContestantFactory::active();
        $this->expectException(\InvalidArgumentException::class);
        $c->addVotes(0);
    }

    public function test_from_array_round_trips_through_to_array(): void
    {
        $original = ContestantFactory::active(id: 7, editionId: 3, votes: 55, name: 'Round Trip');
        $restored = \AlfaCode\PulseEngine\Entity\Contestant::fromArray($original->toArray());

        $this->assertSame($original->getId(), $restored->getId());
        $this->assertSame($original->getFullName(), $restored->getFullName());
        $this->assertSame($original->getVotes(), $restored->getVotes());
        $this->assertSame($original->getStatus(), $restored->getStatus());
    }
}
