<?php
namespace AlfacodeTeam\PulseEngine\Service;

class PricingEngine {
    public function calculate(int $count): int {
        if ($count <= 20) return $count * 1000;
        if ($count <= 100) return $count * 800;
        return $count * 500;
    }
}