<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Stub;

use AlfaCode\PulseEngine\Contract\EventDispatcherInterface;
use AlfaCode\PulseEngine\Event\DomainEvent;

final class SpyEventDispatcher implements EventDispatcherInterface
{
    /** @var DomainEvent[] */
    public array $dispatched = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatched[] = $event;
    }

    public function hasDispatched(string $eventClass): bool
    {
        foreach ($this->dispatched as $e) {
            if ($e instanceof $eventClass) {
                return true;
            }
        }
        return false;
    }

    public function last(): ?DomainEvent
    {
        return empty($this->dispatched) ? null : end($this->dispatched);
    }
}
