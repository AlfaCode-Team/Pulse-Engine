<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Service;

use AlfaCode\PulseEngine\Contract\ContestantRepositoryInterface;
use AlfaCode\PulseEngine\Contract\DatabaseInterface;
use AlfaCode\PulseEngine\Contract\EventDispatcherInterface;
use AlfaCode\PulseEngine\Contract\RateLimiterInterface;
use AlfaCode\PulseEngine\Contract\VoteRepositoryInterface;
use AlfaCode\PulseEngine\DTO\CastVoteCommand;
use AlfaCode\PulseEngine\DTO\VoteResult;
use AlfaCode\PulseEngine\Entity\VoteRecord;
use AlfaCode\PulseEngine\Event\VoteCastEvent;
use AlfaCode\PulseEngine\Exception\ContestantNotFoundException;
use AlfaCode\PulseEngine\Exception\DuplicateVoteException;
use AlfaCode\PulseEngine\Exception\InactiveContestantException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Core domain service for casting free votes.
 *
 * Responsibilities
 * ────────────────
 * 1. Rate-limit check (delegates to RateLimiterInterface)
 * 2. Validate contestant (active + exists)
 * 3. Idempotency check (one free vote per user per contestant per edition)
 * 4. Persist vote and increment contestant tally atomically
 * 5. Emit VoteCastEvent
 * 6. Record the rate-limit attempt
 *
 * Paid votes are handled by PaymentService, which calls back into
 * VoteService::applyPaidVotes() after payment verification.
 */
final class VoteService
{
    public function __construct(
        private readonly DatabaseInterface              $db,
        private readonly ContestantRepositoryInterface  $contestantRepo,
        private readonly VoteRepositoryInterface        $voteRepo,
        private readonly RateLimiterInterface           $rateLimiter,
        private readonly EventDispatcherInterface       $events,
        private readonly LoggerInterface                $logger = new NullLogger(),
    ) {}

    /**
     * Cast a single free vote.
     *
     * @throws \AlfaCode\PulseEngine\Exception\RateLimitExceededException
     * @throws ContestantNotFoundException
     * @throws InactiveContestantException
     * @throws DuplicateVoteException
     */
    public function castFreeVote(CastVoteCommand $cmd): VoteResult
    {
        // 1. Rate limit
        $this->rateLimiter->check($cmd->ipAddress);

        // 2. Load and validate contestant
        $contestant = $this->contestantRepo->findById($cmd->contestantId);

        if ($contestant === null) {
            throw new ContestantNotFoundException($cmd->contestantId);
        }

        if (!$contestant->isActive()) {
            throw new InactiveContestantException($cmd->contestantId);
        }

        // 3. Idempotency — one free vote per user+contestant+edition
        if ($this->voteRepo->hasFreeVote($cmd->userId, $cmd->contestantId, $cmd->editionId)) {
            throw new DuplicateVoteException($cmd->userId, $cmd->contestantId);
        }

        // 4. Persist atomically
        $record = VoteRecord::createFree(
            userId:       $cmd->userId,
            contestantId: $cmd->contestantId,
            editionId:    $cmd->editionId,
            ipAddress:    $cmd->ipAddress,
            userAgent:    $cmd->userAgent,
        );

        $this->db->beginTransaction();

        try {
            $voteId = $this->voteRepo->save($record);
            $this->contestantRepo->incrementVotes($cmd->contestantId, 1);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->logger->error('VoteService::castFreeVote failed', [
                'userId'       => $cmd->userId,
                'contestantId' => $cmd->contestantId,
                'error'        => $e->getMessage(),
            ]);

            throw $e;
        }

        // 5. Record rate-limit attempt (after commit — no harm if this fails)
        $this->rateLimiter->record($cmd->ipAddress);

        // 6. Emit event
        $this->events->dispatch(new VoteCastEvent(
            voteId:       $voteId,
            userId:       $cmd->userId,
            contestantId: $cmd->contestantId,
            editionId:    $cmd->editionId,
            voteCount:    1,
            isPaid:       false,
            ipAddress:    $cmd->ipAddress,
        ));

        $refreshed = $this->contestantRepo->findById($cmd->contestantId);

        $this->logger->info('Free vote cast', [
            'voteId'       => $voteId,
            'userId'       => $cmd->userId,
            'contestantId' => $cmd->contestantId,
        ]);

        return new VoteResult(
            voteId:       $voteId,
            contestantId: $cmd->contestantId,
            newVoteTotal: $refreshed?->getVotes() ?? 0,
            isPaid:       false,
            createdAt:    date('Y-m-d H:i:s'),
        );
    }

    /**
     * Apply a bundle of paid votes after successful payment verification.
     * Called internally by PaymentService.
     */
    public function applyPaidVotes(
        int    $userId,
        int    $contestantId,
        int    $editionId,
        int    $voteCount,
        string $ipAddress,
        string $userAgent = '',
    ): VoteResult {
        $contestant = $this->contestantRepo->findById($contestantId);

        if ($contestant === null) {
            throw new ContestantNotFoundException($contestantId);
        }

        if (!$contestant->isActive()) {
            throw new InactiveContestantException($contestantId);
        }

        $record = VoteRecord::createPaid(
            userId:       $userId,
            contestantId: $contestantId,
            editionId:    $editionId,
            voteCount:    $voteCount,
            ipAddress:    $ipAddress,
            userAgent:    $userAgent,
        );

        $this->db->beginTransaction();

        try {
            $voteId = $this->voteRepo->save($record);
            $this->contestantRepo->incrementVotes($contestantId, $voteCount);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            $this->logger->error('VoteService::applyPaidVotes failed', [
                'userId'       => $userId,
                'contestantId' => $contestantId,
                'voteCount'    => $voteCount,
                'error'        => $e->getMessage(),
            ]);

            throw $e;
        }

        $this->events->dispatch(new VoteCastEvent(
            voteId:       $voteId,
            userId:       $userId,
            contestantId: $contestantId,
            editionId:    $editionId,
            voteCount:    $voteCount,
            isPaid:       true,
            ipAddress:    $ipAddress,
        ));

        $refreshed = $this->contestantRepo->findById($contestantId);

        return new VoteResult(
            voteId:       $voteId,
            contestantId: $contestantId,
            newVoteTotal: $refreshed?->getVotes() ?? 0,
            isPaid:       true,
            createdAt:    date('Y-m-d H:i:s'),
        );
    }
}
