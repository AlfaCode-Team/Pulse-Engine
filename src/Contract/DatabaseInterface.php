<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Contract;

/**
 * Minimal database abstraction — implement this for any driver (PDO, mysqli, etc.).
 */
interface DatabaseInterface
{
    /** Execute a write query and return the affected row count. */
    public function execute(string $sql, array $params = []): int;

    /** Return a single row as an associative array, or null if not found. */
    public function fetchOne(string $sql, array $params = []): ?array;

    /** Return all rows as an array of associative arrays. */
    public function fetchAll(string $sql, array $params = []): array;

    /** Insert a row into $table and return the last-insert ID. */
    public function insert(string $table, array $data): int;

    /** Update rows in $table matching $where conditions. Returns affected rows. */
    public function update(string $table, array $data, array $where): int;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;

    /** Returns true when a transaction is currently open. */
    public function inTransaction(): bool;
}
