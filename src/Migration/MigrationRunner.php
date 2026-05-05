<?php

declare(strict_types=1);

namespace AlfaCode\PulseEngine\Migration;

use AlfaCode\PulseEngine\Contract\DatabaseInterface;

/**
 * Simple, dependency-free SQL migration runner.
 *
 * Usage:
 *   $runner = new MigrationRunner($db, __DIR__ . '/../Migration');
 *   $runner->run();
 *
 * Migrations are executed in filename order (001_, 002_, …).
 * A `schema_migrations` table tracks which files have already been applied.
 */
final class MigrationRunner
{
    private const TRACKING_TABLE = 'schema_migrations';

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly string $migrationsDir,
    ) {}

    /**
     * Run all pending migrations in order.
     *
     * @return string[] List of migration filenames that were applied.
     */
    public function run(): array
    {
        $this->ensureTrackingTable();
        $applied = $this->appliedMigrations();
        $files   = $this->pendingFiles($applied);
        $ran     = [];

        foreach ($files as $file) {
            $sql = file_get_contents($this->migrationsDir . DIRECTORY_SEPARATOR . $file);
            if ($sql === false) {
                throw new \RuntimeException("Cannot read migration file: {$file}");
            }

            // Execute each statement individually (split on semicolon + newline)
            foreach ($this->splitStatements($sql) as $statement) {
                $statement = trim($statement);
                if ($statement === '') {
                    continue;
                }
                $this->db->execute($statement);
            }

            $this->db->insert(self::TRACKING_TABLE, [
                'filename'   => $file,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);

            $ran[] = $file;
            echo "[Pulse-Engine Migrations] Applied: {$file}" . PHP_EOL;
        }

        if (empty($ran)) {
            echo '[Pulse-Engine Migrations] Nothing to migrate.' . PHP_EOL;
        }

        return $ran;
    }

    /**
     * Roll back the last N applied migrations (reverse order).
     * NOTE: This calls the corresponding *_rollback.sql file if it exists.
     */
    public function rollback(int $steps = 1): array
    {
        $applied  = array_reverse($this->appliedMigrations());
        $rolledBack = [];

        foreach (array_slice($applied, 0, $steps) as $file) {
            $rollbackFile = str_replace('.sql', '_rollback.sql', $file);
            $path = $this->migrationsDir . DIRECTORY_SEPARATOR . $rollbackFile;

            if (!is_file($path)) {
                throw new \RuntimeException(
                    "Rollback file not found for migration '{$file}': expected '{$rollbackFile}'",
                );
            }

            $sql = (string) file_get_contents($path);
            foreach ($this->splitStatements($sql) as $statement) {
                $statement = trim($statement);
                if ($statement !== '') {
                    $this->db->execute($statement);
                }
            }

            $this->db->execute(
                'DELETE FROM ' . self::TRACKING_TABLE . ' WHERE filename = ?',
                [$file],
            );

            $rolledBack[] = $file;
            echo "[Pulse-Engine Migrations] Rolled back: {$file}" . PHP_EOL;
        }

        return $rolledBack;
    }

    /** @return string[] Filenames already in schema_migrations */
    private function appliedMigrations(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT filename FROM ' . self::TRACKING_TABLE . ' ORDER BY applied_at ASC',
        );

        return array_column($rows, 'filename');
    }

    /** @return string[] Pending .sql filenames sorted numerically */
    private function pendingFiles(array $applied): array
    {
        $allFiles = glob($this->migrationsDir . DIRECTORY_SEPARATOR . '[0-9]*.sql') ?: [];
        $allFiles = array_map('basename', $allFiles);
        sort($allFiles);

        // Exclude rollback files and already-applied migrations
        return array_values(array_filter(
            $allFiles,
            static fn(string $f) => !str_contains($f, '_rollback') && !in_array($f, $applied, true),
        ));
    }

    private function ensureTrackingTable(): void
    {
        $this->db->execute('
            CREATE TABLE IF NOT EXISTS ' . self::TRACKING_TABLE . ' (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                filename   VARCHAR(255) NOT NULL,
                applied_at DATETIME     NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_filename (filename)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    /** Split a SQL file into individual statements, handling comments. */
    private function splitStatements(string $sql): array
    {
        // Strip single-line comments (-- …)
        $sql = (string) preg_replace('/--[^\n]*\n/', "\n", $sql);
        // Strip block comments (/* … */)
        $sql = (string) preg_replace('/\/\*.*?\*\//s', '', $sql);

        return explode(';', $sql);
    }
}
