<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Exception;

final class RateLimitExceededException extends PulseEngineException
{
    public function __construct(string $ip, int $limit)
    {
        parent::__construct(
            "Rate limit of {$limit} votes/minute exceeded for IP {$ip}.",
            429,
        );
    }
}
