<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Factory;

use AlfaCode\PulseEngine\Config\VotingConfig;
use AlfaCode\PulseEngine\Contract\CacheInterface;
use AlfaCode\PulseEngine\Contract\DatabaseInterface;
use AlfaCode\PulseEngine\Contract\EventDispatcherInterface;
use AlfaCode\PulseEngine\Contract\PaymentProviderInterface;
use AlfaCode\PulseEngine\Event\NullEventDispatcher;
use AlfaCode\PulseEngine\Repository\ContestantRepository;
use AlfaCode\PulseEngine\Repository\PaymentRepository;
use AlfaCode\PulseEngine\Repository\VoteRepository;
use AlfaCode\PulseEngine\Security\IntegrityGuard;
use AlfaCode\PulseEngine\Security\RateLimiter;
use AlfaCode\PulseEngine\Service\PaymentService;
use AlfaCode\PulseEngine\Service\PricingEngine;
use AlfaCode\PulseEngine\Service\SubscriptionService;
use AlfaCode\PulseEngine\Service\VoteService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Convenience factory that wires the entire object graph.
 *
 * Use this when you don't have a DI container (e.g. legacy apps).
 * In Laravel / Symfony / etc., register the services individually.
 */
final class VoteServiceFactory
{
    public static function createVoteService(
        DatabaseInterface       $db,
        CacheInterface          $cache,
        VotingConfig            $config,
        EventDispatcherInterface $events  = new NullEventDispatcher(),
        LoggerInterface          $logger  = new NullLogger(),
    ): VoteService {
        return new VoteService(
            db:             $db,
            contestantRepo: new ContestantRepository($db),
            voteRepo:       new VoteRepository($db),
            rateLimiter:    new RateLimiter($cache, $config),
            events:         $events,
            logger:         $logger,
        );
    }

    public static function createPaymentService(
        DatabaseInterface       $db,
        CacheInterface          $cache,
        VotingConfig            $config,
        PaymentProviderInterface $provider,
        EventDispatcherInterface $events  = new NullEventDispatcher(),
        LoggerInterface          $logger  = new NullLogger(),
    ): PaymentService {
        $voteService = self::createVoteService($db, $cache, $config, $events, $logger);

        return new PaymentService(
            provider:    $provider,
            paymentRepo: new PaymentRepository($db),
            pricing:     new PricingEngine($config),
            guard:       new IntegrityGuard($config),
            voteService: $voteService,
            events:      $events,
            logger:      $logger,
        );
    }
}
