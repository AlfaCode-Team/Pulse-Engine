<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Tests\Stub;

use AlfaCode\PulseEngine\Contract\DatabaseInterface;

/**
 * In-memory database stub for tests — no real DB required.
 */
final class InMemoryDatabase implements DatabaseInterface
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public array $tables = [];

    private int $lastInsertId = 0;
    private bool $inTx = false;

    public function execute(string $sql, array $params = []): int { return 0; }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        // Minimal: return null by default; override via $tables for specific queries.
        return null;
    }

    public function fetchAll(string $sql, array $params = []): array { return []; }

    public function insert(string $table, array $data): int
    {
        $this->lastInsertId++;
        $data['_id'] = $this->lastInsertId;
        $this->tables[$table][] = $data;
        return $this->lastInsertId;
    }

    public function update(string $table, array $data, array $where): int { return 1; }

    public function beginTransaction(): void { $this->inTx = true; }
    public function commit(): void          { $this->inTx = false; }
    public function rollback(): void        { $this->inTx = false; }
    public function inTransaction(): bool   { return $this->inTx; }
}
