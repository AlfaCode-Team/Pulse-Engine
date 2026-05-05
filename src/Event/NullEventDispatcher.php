<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Event;

use AlfaCode\PulseEngine\Contract\EventDispatcherInterface;

/**
 * Default no-op dispatcher. Replace with your real PSR-14 adapter in production.
 */
final class NullEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(DomainEvent $event): void
    {
        // no-op — events are silently dropped until a real dispatcher is injected
    }
}
