<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Event;

/**
 * Base class for all domain events emitted by Pulse-Engine.
 */
abstract class DomainEvent
{
    private string $occurredAt;

    public function __construct()
    {
        $this->occurredAt = date('Y-m-d H:i:s');
    }

    final public function getOccurredAt(): string
    {
        return $this->occurredAt;
    }

    abstract public function getName(): string;
}
