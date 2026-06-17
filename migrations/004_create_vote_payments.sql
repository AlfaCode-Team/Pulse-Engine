-- Migration: 004_create_vote_payments
-- Description: Payment transaction records for paid vote purchases.
-- Depends on: 001_create_vote_editions, 002_create_vote_contestants

CREATE TABLE IF NOT EXISTS vote_payments (
    ID              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    UserID          INT UNSIGNED    NOT NULL,
    ContestantID    INT UNSIGNED    NOT NULL,
    EditionID       INT UNSIGNED    NOT NULL,
    VoteCount       INT             NOT NULL,
    AmountKobo      INT UNSIGNED    NOT NULL,           -- amount in kobo (smallest currency unit)
    Reference       VARCHAR(64)     NOT NULL,           -- unique payment reference (PE-XXXX)
    Status          ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
    GatewayResponse TEXT            NULL,               -- raw response from payment gateway
    CreatedAt       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (ID),
    UNIQUE INDEX uq_reference    (Reference),
    INDEX idx_user               (UserID),
    INDEX idx_contestant_edition (ContestantID, EditionID),
    INDEX idx_status             (Status),
    INDEX idx_created_at         (CreatedAt),

    CONSTRAINT fk_payment_contestant
        FOREIGN KEY (ContestantID)
        REFERENCES vote_contestants (ID)
        ON DELETE CASCADE,

    CONSTRAINT fk_payment_edition
        FOREIGN KEY (EditionID)
        REFERENCES vote_editions (ID)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
