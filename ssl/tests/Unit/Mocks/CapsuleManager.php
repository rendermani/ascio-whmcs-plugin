<?php
/**
 * Mock Illuminate Database Capsule Manager
 *
 * Provides a mock implementation of the Eloquent Capsule Manager
 * for unit testing without a database connection.
 */

declare(strict_types=1);

namespace Illuminate\Database\Capsule;

class Manager
{
    private static array $mockData = [];
    private static array $insertedData = [];
    private static array $updatedData = [];

    /**
     * Get a query builder for a table.
     * Note: In PHP, method names are case-insensitive, so table() and Table() are the same.
     * The actual Illuminate Manager only has table() - WHMCS code uses both casings.
     */
    public static function table(string $table): MockQuery
    {
        return new MockQuery($table);
    }

    public static function setMockData(string $table, $data): void
    {
        self::$mockData[$table] = $data;
    }

    public static function getMockData(string $table)
    {
        return self::$mockData[$table] ?? null;
    }

    public static function getInsertedData(string $table): array
    {
        return self::$insertedData[$table] ?? [];
    }

    public static function recordInsert(string $table, array $data): void
    {
        self::$insertedData[$table][] = $data;
    }

    public static function recordUpdate(string $table, array $where, array $data): void
    {
        self::$updatedData[$table][] = ['where' => $where, 'data' => $data];
    }

    public static function reset(): void
    {
        self::$mockData = [];
        self::$insertedData = [];
        self::$updatedData = [];
    }
}

/**
 * Mock Query Builder
 */
class MockQuery
{
    private string $table;
    private array $wheres = [];
    private array $selects = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function select(...$columns): self
    {
        $this->selects = is_array($columns[0] ?? null) ? ($columns[0] ?? []) : $columns;
        return $this;
    }

    public function join(string $table, string $col1, string $op, string $col2): self
    {
        return $this;
    }

    public function where($column, $operator = null, $value = null): self
    {
        if (is_array($column)) {
            $this->wheres = array_merge($this->wheres, $column);
        } else {
            $this->wheres[$column] = $value ?? $operator;
        }
        return $this;
    }

    public function first(): ?object
    {
        $data = Manager::getMockData($this->table);
        if (is_array($data) && isset($data[0])) {
            return is_object($data[0]) ? $data[0] : (object) $data[0];
        }
        return is_object($data) ? $data : ($data ? (object) $data : null);
    }

    public function get(): array
    {
        $data = Manager::getMockData($this->table);
        if (!$data) {
            return [];
        }
        if (is_array($data) && isset($data[0])) {
            return $data;
        }
        return [$data];
    }

    public function value(string $column)
    {
        $result = $this->first();
        return $result ? ($result->$column ?? null) : null;
    }

    public function insert(array $data): bool
    {
        Manager::recordInsert($this->table, $data);
        return true;
    }

    public function update(array $data): int
    {
        Manager::recordUpdate($this->table, $this->wheres, $data);
        return 1;
    }

    public function delete(): int
    {
        return 1;
    }
}
