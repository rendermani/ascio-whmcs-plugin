<?php

namespace Ascio\Core\Tests;

use Ascio\Core\Contracts\DatabaseInterface;

/**
 * Mock database implementation for unit testing.
 */
class MockDatabase implements DatabaseInterface
{
    /** @var array In-memory data store */
    protected array $tables = [];

    /** @var int Auto-increment counter */
    protected int $autoIncrement = 1;

    /**
     * {@inheritdoc}
     */
    public function insert(string $table, array $data): ?int
    {
        if (!isset($this->tables[$table])) {
            $this->tables[$table] = [];
        }

        $id = $this->autoIncrement++;
        $data['id'] = $id;
        $this->tables[$table][$id] = (object)$data;

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $table, array $data, array $where): int
    {
        $count = 0;

        if (!isset($this->tables[$table])) {
            return 0;
        }

        foreach ($this->tables[$table] as $id => $row) {
            if ($this->matchesWhere($row, $where)) {
                foreach ($data as $key => $value) {
                    $this->tables[$table][$id]->$key = $value;
                }
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function select(string $table, array $columns, array $where)
    {
        if (!isset($this->tables[$table])) {
            return [];
        }

        $results = [];
        foreach ($this->tables[$table] as $row) {
            if ($this->matchesWhere($row, $where)) {
                if ($columns === ['*']) {
                    $results[] = $row;
                } else {
                    $filtered = new \stdClass();
                    foreach ($columns as $col) {
                        $filtered->$col = $row->$col ?? null;
                    }
                    $results[] = $filtered;
                }
            }
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function first(string $table, array $columns, array $where)
    {
        $results = $this->select($table, $columns, $where);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $table, array $where): int
    {
        $count = 0;

        if (!isset($this->tables[$table])) {
            return 0;
        }

        foreach ($this->tables[$table] as $id => $row) {
            if ($this->matchesWhere($row, $where)) {
                unset($this->tables[$table][$id]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function raw(string $sql, array $bindings = [])
    {
        // Not implemented for mock
        return [];
    }

    /**
     * Check if row matches where conditions.
     *
     * @param object $row
     * @param array $where
     * @return bool
     */
    protected function matchesWhere(object $row, array $where): bool
    {
        foreach ($where as $key => $value) {
            if (!isset($row->$key) || $row->$key !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Seed table with data.
     *
     * @param string $table
     * @param array $rows
     * @return self
     */
    public function seed(string $table, array $rows): self
    {
        foreach ($rows as $row) {
            $this->insert($table, $row);
        }
        return $this;
    }

    /**
     * Get all data from a table.
     *
     * @param string $table
     * @return array
     */
    public function getTable(string $table): array
    {
        return $this->tables[$table] ?? [];
    }

    /**
     * Clear all data.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->tables = [];
        $this->autoIncrement = 1;
        return $this;
    }
}
