<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\DTO;

/**
 * Data transferred to a payment provider to initialise a transaction.
 */
final readonly class PaymentInitPayload
{
    public function __construct(
        public string $reference,
        public int    $amountKobo,   // amount in smallest currency unit (kobo, cents, etc.)
        public string $email,
        public string $callbackUrl,
        public array  $metadata = [],
    ) {}
}
