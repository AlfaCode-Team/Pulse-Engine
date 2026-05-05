-- Migration: 005_create_vote_audit_log
-- Description: Immutable audit trail for security-sensitive operations.

CREATE TABLE IF NOT EXISTS vote_audit_log (
    LogID       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    Event       VARCHAR(100)    NOT NULL,    -- e.g. 'vote_cast', 'payment_failed', 'rate_limit_hit'
    UserID      INT UNSIGNED    NULL,
    IPAddress   VARCHAR(45)     NOT NULL,
    Payload     JSON            NULL,        -- event-specific data
    CreatedAt   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (LogID),
    INDEX idx_event      (Event),
    INDEX idx_user       (UserID),
    INDEX idx_ip         (IPAddress),
    INDEX idx_created_at (CreatedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
