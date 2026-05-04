<?php
namespace AlfacodeTeam\PulseEngine\Contract;

interface DatabaseInterface {
    public function query(string $sql, array $params = []);
    public function insert(string $table, array $data): int;
    public function update(string $table, array $data, array $where): bool;
    public function getOne(string $sql, array $params = []);
    public function startTransaction();
    public function commit();
    public function rollback();
}