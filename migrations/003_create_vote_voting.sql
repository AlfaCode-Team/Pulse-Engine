-- Migration: 003_create_vote_voting
-- Description: Individual vote records (free and paid).
-- Depends on: 001_create_vote_editions, 002_create_vote_contestants

CREATE TABLE IF NOT EXISTS vote_voting (
    VoteID        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    UserID        INT UNSIGNED    NOT NULL,
    ContestantID  INT UNSIGNED    NOT NULL,
    EditionID     INT UNSIGNED    NOT NULL,
    VoteCount     SMALLINT        NOT NULL DEFAULT 1,
    IsPaid        TINYINT(1)      NOT NULL DEFAULT 0,
    Status        ENUM('pending','approved','rejected','refunded') NOT NULL DEFAULT 'approved',
    IPAddress     VARCHAR(45)     NOT NULL,           -- supports IPv6
    UserAgent     VARCHAR(500)    NOT NULL DEFAULT '',
    CreatedAt     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (VoteID),

    -- Enforce one free vote per user+contestant+edition
    UNIQUE INDEX uq_free_vote (UserID, ContestantID, EditionID, IsPaid),

    INDEX idx_user_edition    (UserID, EditionID),
    INDEX idx_contestant      (ContestantID),
    INDEX idx_ip_created      (IPAddress, CreatedAt),
    INDEX idx_created_at      (CreatedAt),

    CONSTRAINT fk_vote_contestant
        FOREIGN KEY (ContestantID)
        REFERENCES vote_contestants (ID)
        ON DELETE CASCADE,

    CONSTRAINT fk_vote_edition
        FOREIGN KEY (EditionID)
        REFERENCES vote_editions (ID)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE: The UNIQUE index on (UserID, ContestantID, EditionID, IsPaid) only
-- prevents duplicate FREE votes. Paid votes (IsPaid = 1) for the same
-- user+contestant are intentionally allowed (multiple purchases).
-- To allow that, remove IsPaid from the unique key and handle idempotency
-- at the application layer via payment reference uniqueness.
ALTER TABLE vote_voting
    DROP INDEX uq_free_vote,
    ADD UNIQUE INDEX uq_free_vote (UserID, ContestantID, EditionID)
        USING BTREE,
    ADD INDEX idx_paid (IsPaid);

-- Actually for paid votes we only block duplicates on the free path (IsPaid=0).
-- Re-apply correct partial unique constraint (MySQL workaround via filtered index not available,
-- so we enforce at service layer; index below is for query performance only):
ALTER TABLE vote_voting
    DROP INDEX uq_free_vote;

-- Covering index to speed up hasFreeVote() check
CREATE INDEX idx_free_vote_check
    ON vote_voting (UserID, ContestantID, EditionID, IsPaid);
