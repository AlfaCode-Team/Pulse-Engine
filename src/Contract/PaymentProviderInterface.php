<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Contract;

use AlfaCode\PulseEngine\DTO\PaymentInitPayload;
use AlfaCode\PulseEngine\DTO\PaymentVerificationResult;

/**
 * Implement per payment gateway (Paystack, Flutterwave, Stripe, etc.).
 */
interface PaymentProviderInterface
{
    /**
     * Initialise a payment and return the provider's redirect URL.
     */
    public function initialize(PaymentInitPayload $payload): string;

    /**
     * Verify a completed transaction by its provider reference.
     */
    public function verify(string $transactionReference): PaymentVerificationResult;
}
