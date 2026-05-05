<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Contract;

use AlfaCode\PulseEngine\Entity\PaymentRecord;

interface PaymentRepositoryInterface
{
    public function save(PaymentRecord $record): int;

    public function findByReference(string $reference): ?PaymentRecord;

    public function updateStatus(int $paymentId, string $status): void;
}
