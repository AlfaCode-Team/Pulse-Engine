<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Repository;

use AlfaCode\PulseEngine\Contract\DatabaseInterface;
use AlfaCode\PulseEngine\Contract\PaymentRepositoryInterface;
use AlfaCode\PulseEngine\Entity\PaymentRecord;
use AlfaCode\PulseEngine\Enum\PaymentStatus;

final class PaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private readonly DatabaseInterface $db,
    ) {}

    public function save(PaymentRecord $record): int
    {
        $id = $this->db->insert('vote_payments', $record->toArray());
        $record->setId($id);

        return $id;
    }

    public function findByReference(string $reference): ?PaymentRecord
    {
        $row = $this->db->fetchOne(
            'SELECT * FROM vote_payments WHERE Reference = ? LIMIT 1',
            [$reference],
        );

        if ($row === null) {
            return null;
        }

        return new PaymentRecord(
            id:              (int)$row['ID'],
            userId:          (int)$row['UserID'],
            contestantId:    (int)$row['ContestantID'],
            editionId:       (int)$row['EditionID'],
            voteCount:       (int)$row['VoteCount'],
            amountKobo:      (int)$row['AmountKobo'],
            reference:       (string)$row['Reference'],
            status:          PaymentStatus::from((string)$row['Status']),
            gatewayResponse: (string)$row['GatewayResponse'],
            createdAt:       (string)$row['CreatedAt'],
            updatedAt:       (string)$row['UpdatedAt'],
        );
    }

    public function updateStatus(int $paymentId, string $status): void
    {
        $this->db->update(
            'vote_payments',
            ['Status' => $status, 'UpdatedAt' => date('Y-m-d H:i:s')],
            ['ID'     => $paymentId],
        );
    }
}
