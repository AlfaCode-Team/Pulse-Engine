<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Contract;

use AlfaCode\PulseEngine\Event\DomainEvent;

interface EventDispatcherInterface
{
    public function dispatch(DomainEvent $event): void;
}
