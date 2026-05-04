<?php
namespace AlfacodeTeam\PulseEngine\Contract;

interface PaymentProviderInterface {
    public function initialize(array $payload): string;
    public function verify(string $transactionReference): bool;
}