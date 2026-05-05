<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Unit\Service;

use AlfaCode\PulseEngine\Config\VotingConfig;
use AlfaCode\PulseEngine\DTO\CastVoteCommand;
use AlfaCode\PulseEngine\Event\VoteCastEvent;
use AlfaCode\PulseEngine\Exception\ContestantNotFoundException;
use AlfaCode\PulseEngine\Exception\DuplicateVoteException;
use AlfaCode\PulseEngine\Exception\InactiveContestantException;
use AlfaCode\PulseEngine\Exception\RateLimitExceededException;
use AlfaCode\PulseEngine\Security\RateLimiter;
use AlfaCode\PulseEngine\Service\VoteService;
use AlfaCode\PulseEngine\Tests\Stub\ContestantFactory;
use AlfaCode\PulseEngine\Tests\Stub\InMemoryCache;
use AlfaCode\PulseEngine\Tests\Stub\InMemoryContestantRepository;
use AlfaCode\PulseEngine\Tests\Stub\InMemoryDatabase;
use AlfaCode\PulseEngine\Tests\Stub\InMemoryVoteRepository;
use AlfaCode\PulseEngine\Tests\Stub\SpyEventDispatcher;
use PHPUnit\Framework\TestCase;

final class VoteServiceTest extends TestCase
{
    private VotingConfig $config;
    private InMemoryCache $cache;
    private InMemoryDatabase $db;
    private InMemoryContestantRepository $contestantRepo;
    private InMemoryVoteRepository $voteRepo;
    private SpyEventDispatcher $events;
    private RateLimiter $rateLimiter;
    private VoteService $service;

    protected function setUp(): void
    {
        $this->config = new VotingConfig([
            'security' => [
                'max_per_minute' => 3,
                'window_seconds' => 60,
                'secret_key'     => 'test-secret-key-32-chars-minimum!!',
            ],
            'pricing' => [
                'tier1_max'  => 20, 'tier1_kobo' => 1000,
                'tier2_max'  => 100, 'tier2_kobo' => 800,
                'tier3_kobo' => 500,
            ],
            'features' => ['free_vote' => true],
        ]);

        $this->cache          = new InMemoryCache();
        $this->db             = new InMemoryDatabase();
        $this->contestantRepo = new InMemoryContestantRepository();
        $this->voteRepo       = new InMemoryVoteRepository();
        $this->events         = new SpyEventDispatcher();
        $this->rateLimiter    = new RateLimiter($this->cache, $this->config);

        $this->service = new VoteService(
            db:             $this->db,
            contestantRepo: $this->contestantRepo,
            voteRepo:       $this->voteRepo,
            rateLimiter:    $this->rateLimiter,
            events:         $this->events,
        );
    }

    // ---------------------------------------------------------------
    // Happy path
    // ---------------------------------------------------------------

    public function test_cast_free_vote_returns_vote_result(): void
    {
        $this->contestantRepo->seed(ContestantFactory::active(id: 1, editionId: 10));

        $cmd    = new CastVoteCommand(userId: 42, contestantId: 1, editionId: 10, ipAddress: '1.2.3.4');
        $result = $this->service->castFreeVote($cmd);

        $this->assertSame(1, $result->voteId);
        $this->assertSame(1, $result->contestantId);
        $this->assertFalse($result->isPaid);
    }

    public function test_cast_free_vote_increments_contestant_tally(): void
    {
        $this->contestantRepo->seed(ContestantFactory::active(id: 1, votes: 50));

        $cmd = new CastVoteCommand(userId: 1, contestantId: 1, editionId: 10, ipAddress: '1.1.1.1');
        $result = $this->service->castFreeVote($cmd);

        $this->assertSame(51, $result->newVoteTotal);
    }

    public function test_cast_free_vote_dispatches_vote_cast_event(): void
    {
        $this->contestantRepo->seed(ContestantFactory::active(id: 1));

        $cmd = new CastVoteCommand(userId: 7, contestantId: 1, editionId: 10, ipAddress: '9.9.9.9');
        $this->service->castFreeVote($cmd);

        $this->assertTrue($this->events->hasDispatched(VoteCastEvent::class));

        /** @var VoteCastEvent $event */
        $event = $this->events->last();
        $this->assertSame(7, $event->userId);
        $this->assertSame(1, $event->voteCount);
        $this->assertFalse($event->isPaid);
    }

    // ---------------------------------------------------------------
    // Contestant validation
    // ---------------------------------------------------------------

    public function test_throws_contestant_not_found_when_missing(): void
    {
        $this->expectException(ContestantNotFoundException::class);

        $cmd = new CastVoteCommand(userId: 1, contestantId: 999, editionId: 10, ipAddress: '1.1.1.1');
        $this->service->castFreeVote($cmd);
    }

    public function test_throws_inactive_contestant_exception(): void
    {
        $this->contestantRepo->seed(ContestantFactory::disqualified(id: 5));

        $this->expectException(InactiveContestantException::class);

        $cmd = new CastVoteCommand(userId: 1, contestantId: 5, editionId: 10, ipAddress: '1.1.1.1');
        $this->service->castFreeVote($cmd);
    }

    // ---------------------------------------------------------------
    // Duplicate vote protection
    // ---------------------------------------------------------------

    public function test_throws_duplicate_vote_exception_on_second_free_vote(): void
    {
        $this->contestantRepo->seed(ContestantFactory::active(id: 1));

        $cmd = new CastVoteCommand(userId: 99, contestantId: 1, editionId: 10, ipAddress: '5.5.5.5');
        $this->service->castFreeVote($cmd);

        $this->expectException(DuplicateVoteException::class);
        $this->service->castFreeVote($cmd);
    }

    public function test_different_users_can_vote_for_same_contestant(): void
    {
        $this->contestantRepo->seed(ContestantFactory::active(id: 1));

        $cmd1 = new CastVoteCommand(userId: 1, contestantId: 1, editionId: 10, ipAddress: '1.0.0.1');
        $cmd2 = new CastVoteCommand(userId: 2, contestantId: 1, editionId: 10, ipAddress: '1.0.0.2');

        $r1 = $this->service->castFreeVote($cmd1);
        $r2 = $this->service->castFreeVote($cmd2);

        $this->assertSame(1, $r1->voteId);
        $this->assertSame(2, $r2->voteId);
    }

    // ---------------------------------------------------------------
    // Rate limiting
    // ---------------------------------------------------------------

    public function test_throws_rate_limit_after_max_votes_per_minute(): void
    {
        // Seed 3 different contestants so duplicate check doesn't trigger first
        for ($i = 1; $i <= 4; $i++) {
            $this->contestantRepo->seed(ContestantFactory::active(id: $i, editionId: 10));
        }

        $ip = '8.8.8.8';

        // Cast 3 votes (the limit)
        for ($i = 1; $i <= 3; $i++) {
            $cmd = new CastVoteCommand(userId: $i, contestantId: $i, editionId: 10, ipAddress: $ip);
            $this->service->castFreeVote($cmd);
        }

        // 4th attempt must be blocked
        $this->expectException(RateLimitExceededException::class);
        $cmd = new CastVoteCommand(userId: 4, contestantId: 4, editionId: 10, ipAddress: $ip);
        $this->service->castFreeVote($cmd);
    }

    // ---------------------------------------------------------------
    // Paid votes
    // ---------------------------------------------------------------

    public function test_apply_paid_votes_credits_correct_count(): void
    {
        $this->contestantRepo->seed(ContestantFactory::active(id: 1, votes: 100));

        $result = $this->service->applyPaidVotes(
            userId:       10,
            contestantId: 1,
            editionId:    10,
            voteCount:    50,
            ipAddress:    '1.2.3.4',
        );

        $this->assertTrue($result->isPaid);
        $this->assertSame(150, $result->newVoteTotal);
    }

    public function test_apply_paid_votes_dispatches_vote_cast_event_with_paid_flag(): void
    {
        $this->contestantRepo->seed(ContestantFactory::active(id: 1));

        $this->service->applyPaidVotes(
            userId: 1, contestantId: 1, editionId: 10,
            voteCount: 25, ipAddress: '1.1.1.1',
        );

        /** @var VoteCastEvent $event */
        $event = $this->events->last();
        $this->assertInstanceOf(VoteCastEvent::class, $event);
        $this->assertTrue($event->isPaid);
        $this->assertSame(25, $event->voteCount);
    }
}
