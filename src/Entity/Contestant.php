<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Entity;

use AlfaCode\PulseEngine\Enum\ContestantStatus;

/**
 * Rich domain entity — not a plain data bag.
 */
final class Contestant
{
    public function __construct(
        private int               $id,
        private string            $fullName,
        private int               $votes,
        private int               $editionId,
        private ContestantStatus  $status,
        private string            $createdAt,
        private string            $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:        (int)   ($data['ID']        ?? 0),
            fullName:  (string)($data['FullName']   ?? ''),
            votes:     (int)   ($data['Votes']      ?? 0),
            editionId: (int)   ($data['EditionID']  ?? 0),
            status:    ContestantStatus::from((string)($data['Status'] ?? 'active')),
            createdAt: (string)($data['CreatedAt']  ?? ''),
            updatedAt: (string)($data['UpdatedAt']  ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'ID'        => $this->id,
            'FullName'  => $this->fullName,
            'Votes'     => $this->votes,
            'EditionID' => $this->editionId,
            'Status'    => $this->status->value,
            'CreatedAt' => $this->createdAt,
            'UpdatedAt' => $this->updatedAt,
        ];
    }

    public function getId(): int              { return $this->id; }
    public function getFullName(): string     { return $this->fullName; }
    public function getVotes(): int           { return $this->votes; }
    public function getEditionId(): int       { return $this->editionId; }
    public function getStatus(): ContestantStatus { return $this->status; }

    public function isActive(): bool
    {
        return $this->status === ContestantStatus::Active;
    }

    public function addVotes(int $amount): void
    {
        if ($amount < 1) {
            throw new \InvalidArgumentException("Vote amount must be at least 1, got {$amount}.");
        }
        $this->votes += $amount;
        $this->updatedAt = date('Y-m-d H:i:s');
    }
}
