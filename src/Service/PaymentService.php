<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Service;

use AlfaCode\PulseEngine\Contract\EventDispatcherInterface;
use AlfaCode\PulseEngine\Contract\PaymentProviderInterface;
use AlfaCode\PulseEngine\Contract\PaymentRepositoryInterface;
use AlfaCode\PulseEngine\DTO\PaymentInitPayload;
use AlfaCode\PulseEngine\DTO\PurchaseVotesCommand;
use AlfaCode\PulseEngine\DTO\VoteResult;
use AlfaCode\PulseEngine\Entity\PaymentRecord;
use AlfaCode\PulseEngine\Event\PaymentInitiatedEvent;
use AlfaCode\PulseEngine\Event\PaymentVerifiedEvent;
use AlfaCode\PulseEngine\Exception\PaymentFailedException;
use AlfaCode\PulseEngine\Security\IntegrityGuard;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Orchestrates the paid-vote purchase flow:
 *
 *  1. Calculate price via PricingEngine
 *  2. Generate a unique, signed payment reference
 *  3. Create a PENDING PaymentRecord
 *  4. Delegate to the payment provider to get a redirect URL
 *  5. Emit PaymentInitiatedEvent
 *
 * On callback / webhook, call verifyAndApply() which:
 *  1. Verifies the transaction with the provider
 *  2. Marks the PaymentRecord as COMPLETED
 *  3. Calls VoteService::applyPaidVotes() to credit the votes
 *  4. Emits PaymentVerifiedEvent
 */
final class PaymentService
{
    public function __construct(
        private readonly PaymentProviderInterface  $provider,
        private readonly PaymentRepositoryInterface $paymentRepo,
        private readonly PricingEngine             $pricing,
        private readonly IntegrityGuard            $guard,
        private readonly VoteService               $voteService,
        private readonly EventDispatcherInterface  $events,
        private readonly LoggerInterface           $logger = new NullLogger(),
    ) {}

    /**
     * Initialise a paid vote purchase. Returns the provider's payment redirect URL.
     */
    public function initiate(PurchaseVotesCommand $cmd, string $userEmail): string
    {
        $amountKobo = $this->pricing->calculate($cmd->voteCount);
        $reference  = $this->guard->generateReference($cmd->userId, $cmd->contestantId);

        $payment = PaymentRecord::pending(
            userId:       $cmd->userId,
            contestantId: $cmd->contestantId,
            editionId:    $cmd->editionId,
            voteCount:    $cmd->voteCount,
            amountKobo:   $amountKobo,
            reference:    $reference,
        );

        $paymentId = $this->paymentRepo->save($payment);
        $payment->setId($paymentId);

        $payload = new PaymentInitPayload(
            reference:   $reference,
            amountKobo:  $amountKobo,
            email:       $userEmail,
            callbackUrl: $cmd->callbackUrl,
            metadata: [
                'user_id'       => $cmd->userId,
                'contestant_id' => $cmd->contestantId,
                'edition_id'    => $cmd->editionId,
                'vote_count'    => $cmd->voteCount,
            ],
        );

        $redirectUrl = $this->provider->initialize($payload);

        $this->events->dispatch(new PaymentInitiatedEvent(
            paymentId:    $paymentId,
            userId:       $cmd->userId,
            contestantId: $cmd->contestantId,
            voteCount:    $cmd->voteCount,
            amountKobo:   $amountKobo,
            reference:    $reference,
        ));

        $this->logger->info('Payment initiated', [
            'reference' => $reference,
            'userId'    => $cmd->userId,
            'votes'     => $cmd->voteCount,
            'amount'    => $amountKobo,
        ]);

        return $redirectUrl;
    }

    /**
     * Verify a completed transaction and apply the purchased votes.
     *
     * @throws PaymentFailedException when the provider rejects the transaction
     */
    public function verifyAndApply(string $reference, string $ipAddress = ''): VoteResult
    {
        $payment = $this->paymentRepo->findByReference($reference);

        if ($payment === null) {
            throw new PaymentFailedException($reference, 'Payment record not found.');
        }

        $result = $this->provider->verify($reference);

        if (!$result->success) {
            $payment->fail($result->gatewayResponse);
            $this->paymentRepo->updateStatus($payment->getId() ?? 0, $payment->getStatus()->value);

            $this->logger->warning('Payment verification failed', [
                'reference' => $reference,
                'reason'    => $result->gatewayResponse,
            ]);

            throw new PaymentFailedException($reference, $result->gatewayResponse);
        }

        $payment->complete($result->gatewayResponse);
        $this->paymentRepo->updateStatus($payment->getId() ?? 0, $payment->getStatus()->value);

        $voteResult = $this->voteService->applyPaidVotes(
            userId:       $payment->getUserId(),
            contestantId: $payment->getContestantId(),
            editionId:    $payment->getEditionId(),
            voteCount:    $payment->getVoteCount(),
            ipAddress:    $ipAddress,
        );

        $this->events->dispatch(new PaymentVerifiedEvent(
            paymentId:    $payment->getId() ?? 0,
            userId:       $payment->getUserId(),
            contestantId: $payment->getContestantId(),
            voteCount:    $payment->getVoteCount(),
            reference:    $reference,
        ));

        $this->logger->info('Payment verified and votes applied', [
            'reference' => $reference,
            'voteId'    => $voteResult->voteId,
            'votes'     => $payment->getVoteCount(),
        ]);

        return $voteResult;
    }
}
