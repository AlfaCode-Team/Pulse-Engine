-- Migration: 001_create_vote_editions
-- Description: Contest edition master table.
-- Run: Execute against your MySQL/MariaDB database.

CREATE TABLE IF NOT EXISTS vote_editions (
    ID          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    Title       VARCHAR(255)    NOT NULL,
    Description TEXT            NULL,
    StartDate   DATETIME        NOT NULL,
    EndDate     DATETIME        NOT NULL,
    Status      ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
    CreatedAt   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (ID),
    INDEX idx_status (Status),
    INDEX idx_dates  (StartDate, EndDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
