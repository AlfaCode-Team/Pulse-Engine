<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Event;

final class PaymentVerifiedEvent extends DomainEvent
{
    public function __construct(
        public readonly int    $paymentId,
        public readonly int    $userId,
        public readonly int    $contestantId,
        public readonly int    $voteCount,
        public readonly string $reference,
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return 'pulse_engine.payment_verified';
    }
}
