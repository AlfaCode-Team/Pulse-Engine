<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Entity;

use AlfaCode\PulseEngine\Enum\VoteStatus;

final class VoteRecord
{
    public function __construct(
        private int|null  $voteId,
        private int       $userId,
        private int       $contestantId,
        private int       $editionId,
        private int       $voteCount,     // 1 for free votes; N for paid bundles
        private bool      $isPaid,
        private VoteStatus $status,
        private string    $ipAddress,
        private string    $userAgent,
        private string    $createdAt,
    ) {}

    public static function createFree(
        int    $userId,
        int    $contestantId,
        int    $editionId,
        string $ipAddress,
        string $userAgent = '',
    ): self {
        return new self(
            voteId:       null,
            userId:       $userId,
            contestantId: $contestantId,
            editionId:    $editionId,
            voteCount:    1,
            isPaid:       false,
            status:       VoteStatus::Approved,
            ipAddress:    $ipAddress,
            userAgent:    $userAgent,
            createdAt:    date('Y-m-d H:i:s'),
        );
    }

    public static function createPaid(
        int    $userId,
        int    $contestantId,
        int    $editionId,
        int    $voteCount,
        string $ipAddress,
        string $userAgent = '',
    ): self {
        return new self(
            voteId:       null,
            userId:       $userId,
            contestantId: $contestantId,
            editionId:    $editionId,
            voteCount:    $voteCount,
            isPaid:       true,
            status:       VoteStatus::Pending,
            ipAddress:    $ipAddress,
            userAgent:    $userAgent,
            createdAt:    date('Y-m-d H:i:s'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            voteId:       isset($data['VoteID']) ? (int)$data['VoteID'] : null,
            userId:       (int)($data['UserID']       ?? 0),
            contestantId: (int)($data['ContestantID'] ?? 0),
            editionId:    (int)($data['EditionID']    ?? 0),
            voteCount:    (int)($data['VoteCount']    ?? 1),
            isPaid:       (bool)($data['IsPaid']      ?? false),
            status:       VoteStatus::from((string)($data['Status'] ?? 'approved')),
            ipAddress:    (string)($data['IPAddress'] ?? ''),
            userAgent:    (string)($data['UserAgent'] ?? ''),
            createdAt:    (string)($data['CreatedAt'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'UserID'       => $this->userId,
            'ContestantID' => $this->contestantId,
            'EditionID'    => $this->editionId,
            'VoteCount'    => $this->voteCount,
            'IsPaid'       => (int)$this->isPaid,
            'Status'       => $this->status->value,
            'IPAddress'    => $this->ipAddress,
            'UserAgent'    => $this->userAgent,
            'CreatedAt'    => $this->createdAt,
        ];
    }

    public function setId(int $id): void    { $this->voteId = $id; }
    public function getId(): ?int           { return $this->voteId; }
    public function getUserId(): int        { return $this->userId; }
    public function getContestantId(): int  { return $this->contestantId; }
    public function getEditionId(): int     { return $this->editionId; }
    public function getVoteCount(): int     { return $this->voteCount; }
    public function isPaid(): bool          { return $this->isPaid; }
    public function getStatus(): VoteStatus { return $this->status; }
    public function getIpAddress(): string  { return $this->ipAddress; }

    public function approve(): void
    {
        $this->status = VoteStatus::Approved;
    }
}
