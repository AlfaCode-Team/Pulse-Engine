-- Migration: 006_create_vote_user_subscriptions
-- Description: Per-user, per-edition subscription tiers and daily vote limits.
-- Depends on: 001_create_vote_editions

CREATE TABLE IF NOT EXISTS vote_user_subscriptions (
    ID          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    UserID      INT UNSIGNED    NOT NULL,
    EditionID   INT UNSIGNED    NOT NULL,
    PlanName    VARCHAR(100)    NOT NULL DEFAULT 'basic',   -- e.g. 'basic', 'premium', 'vip'
    DailyLimit  SMALLINT        NOT NULL DEFAULT 1,         -- free votes allowed per day
    ValidFrom   DATETIME        NOT NULL,
    ValidTo     DATETIME        NOT NULL,
    CreatedAt   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (ID),
    UNIQUE INDEX uq_user_edition (UserID, EditionID),
    INDEX idx_user               (UserID),
    INDEX idx_edition            (EditionID),
    INDEX idx_valid_to           (ValidTo),

    CONSTRAINT fk_sub_edition
        FOREIGN KEY (EditionID)
        REFERENCES vote_editions (ID)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
