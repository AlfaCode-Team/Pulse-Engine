-- Migration: 002_create_vote_contestants
-- Description: Contestant registration table.
-- Depends on: 001_create_vote_editions

CREATE TABLE IF NOT EXISTS vote_contestants (
    ID          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    EditionID   INT UNSIGNED    NOT NULL,
    FullName    VARCHAR(255)    NOT NULL,
    StageName   VARCHAR(255)    NULL,
    PhotoURL    VARCHAR(500)    NULL,
    Votes       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    Status      ENUM('active','disqualified','withdrawn') NOT NULL DEFAULT 'active',
    CreatedAt   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (ID),
    INDEX idx_edition        (EditionID),
    INDEX idx_edition_status (EditionID, Status),
    INDEX idx_votes          (Votes DESC),

    CONSTRAINT fk_contestant_edition
        FOREIGN KEY (EditionID)
        REFERENCES vote_editions (ID)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
