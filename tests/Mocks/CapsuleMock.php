<?php

namespace Ascio\Tests\Mocks;

/**
 * Mock for WHMCS Database Capsule (Illuminate\Database\Capsule\Manager)
 *
 * Provides mock database operations for unit testing
 */
class CapsuleMock
{
    /** @var array Mock data storage keyed by table name */
    private static array $tables = [];

    /** @var array Last query executed */
    private static array $lastQuery = [];

    /** @var string|null Current table for chained queries */
    private static ?string $currentTable = null;

    /** @var array Where conditions for current query */
    private static array $whereConditions = [];

    /** @var array Order by clauses for current query */
    private static array $orderByClauses = [];

    /** @var int|null Limit for current query */
    private static ?int $queryLimit = null;

    /** @var int Auto-increment ID counter */
    private static int $autoIncrement = 1;

    /**
     * Reset all mock state
     */
    public static function reset(): void
    {
        self::$tables = [];
        self::$lastQuery = [];
        self::$currentTable = null;
        self::$whereConditions = [];
        self::$orderByClauses = [];
        self::$queryLimit = null;
        self::$autoIncrement = 1;
    }

    /**
     * Set mock data for a table
     */
    public static function setTableData(string $table, array $data): void
    {
        self::$tables[$table] = $data;
    }

    /**
     * Get last query info
     */
    public static function getLastQuery(): array
    {
        return self::$lastQuery;
    }

    /**
     * Execute raw select query OR set columns for query builder
     *
     * @param string|array $queryOrColumns SQL query string or array of column names
     * @param array $bindings Query bindings (only for raw queries)
     * @return array|self Returns array for raw queries, self for column selection
     */
    public static function select($queryOrColumns, array $bindings = []): array|self
    {
        // If called with an array, it's column selection for query builder
        if (is_array($queryOrColumns)) {
            // Return self for chaining - columns are ignored in mock
            return new self();
        }

        // If currentTable is set, this is a query builder chain selecting columns
        // e.g., Capsule::table('foo')->select('domain')
        if (self::$currentTable !== null) {
            // Return self for chaining - columns are ignored in mock
            return new self();
        }

        // Otherwise it's a raw SQL query
        $query = $queryOrColumns;
        self::$lastQuery = ['type' => 'select', 'query' => $query, 'bindings' => $bindings];

        // Parse simple queries for mock data
        if (preg_match('/from\s+(\w+)/i', $query, $matches)) {
            $table = $matches[1];
            if (isset(self::$tables[$table])) {
                return array_map(function ($row) {
                    return (object) $row;
                }, self::$tables[$table]);
            }
        }

        // Default responses for common queries
        if (strpos($query, 'tblasciotlds') !== false) {
            return [(object) ['Threshold' => -30, 'Renew' => 1]];
        }

        if (strpos($query, 'tbladmins') !== false) {
            return [(object) ['username' => 'admin', 'notes' => 'apiuser']];
        }

        return [];
    }

    /**
     * Start a table query
     */
    public static function table(string $table): self
    {
        self::$currentTable = $table;
        self::$whereConditions = [];
        self::$orderByClauses = [];
        self::$queryLimit = null;
        return new self();
    }

    /**
     * Add where conditions
     */
    public function where($column, $operator = null, $value = null): self
    {
        if (is_array($column)) {
            self::$whereConditions = array_merge(self::$whereConditions, $column);
        } else {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }
            self::$whereConditions[$column] = ['operator' => $operator, 'value' => $value];
        }
        return $this;
    }

    /**
     * Add whereIn condition
     */
    public function whereIn(string $column, array $values): self
    {
        self::$whereConditions[$column] = ['operator' => 'in', 'value' => $values];
        return $this;
    }

    /**
     * Add whereRaw condition (just stores it, doesn't evaluate)
     */
    public function whereRaw(string $sql, array $bindings = []): self
    {
        // Store as a special raw condition - mock always matches
        self::$whereConditions['_raw'] = ['sql' => $sql, 'bindings' => $bindings];
        return $this;
    }

    /**
     * Left join (mock implementation - doesn't actually join)
     */
    public function leftJoin(string $table, $first, $operator = null, $second = null): self
    {
        // Mock just returns self, doesn't actually perform join
        return $this;
    }

    /**
     * Join (mock implementation - doesn't actually join)
     */
    public function join(string $table, $first, $operator = null, $second = null): self
    {
        return $this;
    }

    /**
     * Distinct query
     */
    public function distinct(): self
    {
        return $this;
    }

    /**
     * Offset for pagination
     */
    public function offset(int $offset): self
    {
        // Mock doesn't implement offset
        return $this;
    }

    /**
     * Add whereNotNull condition
     */
    public function whereNotNull(string $column): self
    {
        self::$whereConditions[$column] = ['operator' => 'notNull', 'value' => null];
        return $this;
    }

    /**
     * Get maximum value of a column
     */
    public function max(string $column): mixed
    {
        self::$lastQuery = [
            'type' => 'max',
            'table' => self::$currentTable,
            'column' => $column,
            'where' => self::$whereConditions
        ];

        $table = self::$currentTable;
        $maxValue = null;

        if (isset(self::$tables[$table])) {
            foreach (self::$tables[$table] as $row) {
                if (self::matchesConditions($row)) {
                    $value = $row[$column] ?? null;
                    if ($value !== null && ($maxValue === null || $value > $maxValue)) {
                        $maxValue = $value;
                    }
                }
            }
        }

        return $maxValue;
    }

    /**
     * Create a raw expression (mock returns the value as-is)
     */
    public static function raw(string $expression): RawExpressionMock
    {
        return new RawExpressionMock($expression);
    }

    /**
     * Count matching rows
     */
    public function count(): int
    {
        self::$lastQuery = [
            'type' => 'count',
            'table' => self::$currentTable,
            'where' => self::$whereConditions
        ];

        $table = self::$currentTable;
        $count = 0;

        if (isset(self::$tables[$table])) {
            foreach (self::$tables[$table] as $row) {
                if (self::matchesConditions($row)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Add order by clause
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        self::$orderByClauses[] = ['column' => $column, 'direction' => strtolower($direction)];
        return $this;
    }

    /**
     * Set limit for query
     */
    public function limit(int $limit): self
    {
        self::$queryLimit = $limit;
        return $this;
    }

    /**
     * Get single value
     */
    public function value(string $column): mixed
    {
        self::$lastQuery = [
            'type' => 'value',
            'table' => self::$currentTable,
            'column' => $column,
            'where' => self::$whereConditions
        ];

        $table = self::$currentTable;
        if (isset(self::$tables[$table])) {
            foreach (self::$tables[$table] as $row) {
                if (self::matchesConditions($row)) {
                    return $row[$column] ?? null;
                }
            }
        }

        return null;
    }

    /**
     * Get key-value pairs from matching rows
     */
    public function pluck(string $valueColumn, ?string $keyColumn = null): PluckCollectionMock
    {
        self::$lastQuery = [
            'type' => 'pluck',
            'table' => self::$currentTable,
            'where' => self::$whereConditions
        ];

        $result = [];
        $table = self::$currentTable;
        if (isset(self::$tables[$table])) {
            foreach (self::$tables[$table] as $row) {
                if (self::matchesConditions($row)) {
                    if ($keyColumn) {
                        $result[$row[$keyColumn] ?? ''] = $row[$valueColumn] ?? null;
                    } else {
                        $result[] = $row[$valueColumn] ?? null;
                    }
                }
            }
        }

        return new PluckCollectionMock($result);
    }

    /**
     * Get first matching row
     */
    public function first(): ?object
    {
        self::$lastQuery = [
            'type' => 'first',
            'table' => self::$currentTable,
            'where' => self::$whereConditions
        ];

        $table = self::$currentTable;

        if (isset(self::$tables[$table])) {
            foreach (self::$tables[$table] as $row) {
                if (self::matchesConditions($row)) {
                    return (object) $row;
                }
            }
        }

        return null;
    }

    /**
     * Get all matching rows
     */
    public function get(): CollectionMock
    {
        self::$lastQuery = [
            'type' => 'get',
            'table' => self::$currentTable,
            'where' => self::$whereConditions,
            'orderBy' => self::$orderByClauses,
            'limit' => self::$queryLimit
        ];

        $table = self::$currentTable;
        $results = [];

        if (isset(self::$tables[$table])) {
            foreach (self::$tables[$table] as $row) {
                if (self::matchesConditions($row)) {
                    $results[] = $row;
                }
            }
        }

        // Apply ordering
        if (!empty(self::$orderByClauses)) {
            usort($results, function ($a, $b) {
                foreach (self::$orderByClauses as $clause) {
                    $col = $clause['column'];
                    $dir = $clause['direction'];
                    $valA = $a[$col] ?? '';
                    $valB = $b[$col] ?? '';
                    $cmp = strcmp($valA, $valB);
                    if ($cmp !== 0) {
                        return $dir === 'desc' ? -$cmp : $cmp;
                    }
                }
                return 0;
            });
        }

        // Apply limit
        if (self::$queryLimit !== null && self::$queryLimit > 0) {
            $results = array_slice($results, 0, self::$queryLimit);
        }

        // Convert to objects
        $results = array_map(fn($row) => (object) $row, $results);

        return new CollectionMock($results);
    }

    /**
     * Insert a row
     */
    public function insert(array $data): bool
    {
        self::$lastQuery = [
            'type' => 'insert',
            'table' => self::$currentTable,
            'data' => $data
        ];

        $table = self::$currentTable;
        if (!isset(self::$tables[$table])) {
            self::$tables[$table] = [];
        }
        self::$tables[$table][] = $data;

        return true;
    }

    /**
     * Insert a row and get the auto-increment ID
     */
    public function insertGetId(array $data): int
    {
        $id = self::$autoIncrement++;
        $data['id'] = $id;

        self::$lastQuery = [
            'type' => 'insertGetId',
            'table' => self::$currentTable,
            'data' => $data
        ];

        $table = self::$currentTable;
        if (!isset(self::$tables[$table])) {
            self::$tables[$table] = [];
        }
        self::$tables[$table][] = $data;

        return $id;
    }

    /**
     * Update or insert a row
     */
    public function updateOrInsert(array $attributes, array $values = []): bool
    {
        self::$lastQuery = [
            'type' => 'updateOrInsert',
            'table' => self::$currentTable,
            'attributes' => $attributes,
            'values' => $values
        ];

        $table = self::$currentTable;

        // Generic updateOrInsert for other tables
        if (!isset(self::$tables[$table])) {
            self::$tables[$table] = [];
        }

        // Look for existing row matching attributes
        foreach (self::$tables[$table] as $key => $row) {
            $match = true;
            foreach ($attributes as $col => $val) {
                if (($row[$col] ?? null) != $val) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                // Update existing row
                self::$tables[$table][$key] = array_merge($row, $attributes, $values);
                return true;
            }
        }

        // Insert new row
        self::$tables[$table][] = array_merge($attributes, $values);
        return true;
    }

    /**
     * Update rows
     */
    public function update(array $data): int
    {
        self::$lastQuery = [
            'type' => 'update',
            'table' => self::$currentTable,
            'data' => $data,
            'where' => self::$whereConditions
        ];

        $table = self::$currentTable;
        $count = 0;

        if (isset(self::$tables[$table])) {
            foreach (self::$tables[$table] as $key => $row) {
                if (self::matchesConditions($row)) {
                    self::$tables[$table][$key] = array_merge($row, $data);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Delete rows
     */
    public function delete(): int
    {
        self::$lastQuery = [
            'type' => 'delete',
            'table' => self::$currentTable,
            'where' => self::$whereConditions
        ];

        $table = self::$currentTable;
        $count = 0;

        if (isset(self::$tables[$table])) {
            foreach (self::$tables[$table] as $key => $row) {
                if (self::matchesConditions($row)) {
                    unset(self::$tables[$table][$key]);
                    $count++;
                }
            }
            self::$tables[$table] = array_values(self::$tables[$table]);
        }

        return $count;
    }

    /**
     * Get schema builder (mock)
     */
    public static function schema(): SchemaMock
    {
        return new SchemaMock();
    }

    /**
     * Execute raw statement
     */
    public static function statement(string $query, array $bindings = []): bool
    {
        self::$lastQuery = ['type' => 'statement', 'query' => $query, 'bindings' => $bindings];
        return true;
    }

    /**
     * Check if a row matches where conditions
     */
    private static function matchesConditions(array $row): bool
    {
        foreach (self::$whereConditions as $column => $condition) {
            if (is_array($condition)) {
                // Skip raw conditions (they store sql/bindings, not operator/value)
                if (isset($condition['sql']) || $column === '_raw') {
                    continue;
                }

                $operator = $condition['operator'] ?? '=';
                $value = $condition['value'] ?? null;

                $rowValue = $row[$column] ?? null;

                switch ($operator) {
                    case '=':
                        if ($rowValue != $value) return false;
                        break;
                    case '!=':
                    case '<>':
                        if ($rowValue == $value) return false;
                        break;
                    case 'in':
                        if (!in_array($rowValue, $value)) return false;
                        break;
                    case '>':
                        if ($rowValue <= $value) return false;
                        break;
                    case '<':
                        if ($rowValue >= $value) return false;
                        break;
                    case 'notNull':
                        if ($rowValue === null) return false;
                        break;
                }
            } else {
                // Simple equality check
                if (($row[$column] ?? null) != $condition) return false;
            }
        }

        return true;
    }
}

/**
 * Mock Schema Builder
 */
class SchemaMock
{
    private static array $tables = ['tblasciotlds', 'tblasciojobs', 'tblasciohandles', 'tblascio_domain_history'];

    public function hasTable(string $table): bool
    {
        return in_array($table, self::$tables);
    }

    public function hasColumn(string $table, string $column): bool
    {
        return true;
    }

    /**
     * Get column listing for a table
     */
    public function getColumnListing(string $table): array
    {
        return self::$tableColumns[$table] ?? ['Tld', 'Threshold', 'Renew', 'LocalPresenceRequired', 'LocalPresenceOffered', 'AuthCodeRequired', 'Country', 'LastUpdated'];
    }

    /** @var array Mock column definitions per table */
    private static array $tableColumns = [];

    public function create(string $table, callable $callback): void
    {
        self::$tables[] = $table;
    }

    public function table(string $table, callable $callback): void
    {
        // Mock table modification
    }

    public function dropIfExists(string $table): void
    {
        self::$tables = array_filter(self::$tables, fn($t) => $t !== $table);
    }

    public static function addTable(string $table): void
    {
        if (!in_array($table, self::$tables)) {
            self::$tables[] = $table;
        }
    }

    public static function reset(): void
    {
        self::$tables = ['tblasciotlds', 'tblasciojobs', 'tblasciohandles', 'tblascio_domain_history'];
        self::$tableColumns = [];
    }

    /**
     * Set column listing for a table (for testing)
     */
    public static function setTableColumns(string $table, array $columns): void
    {
        self::$tableColumns[$table] = $columns;
    }

    /**
     * Remove a table from the schema (for testing)
     */
    public static function removeTable(string $table): void
    {
        self::$tables = array_filter(self::$tables, fn($t) => $t !== $table);
    }
}

/**
 * Mock collection for pluck results (needs toArray method)
 */
class PluckCollectionMock implements \ArrayAccess, \IteratorAggregate, \Countable
{
    private array $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    // ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // IteratorAggregate implementation
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}

/**
 * Mock raw SQL expression
 */
class RawExpressionMock
{
    private string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function __toString(): string
    {
        return $this->expression;
    }

    public function getValue(): string
    {
        return $this->expression;
    }
}

/**
 * Mock Collection class for query results
 */
class CollectionMock implements \ArrayAccess, \IteratorAggregate, \Countable
{
    private array $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function first(): ?object
    {
        return $this->items[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    public function filter(?callable $callback = null): self
    {
        if ($callback === null) {
            return new self(array_filter($this->items));
        }
        return new self(array_filter($this->items, $callback));
    }

    // ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // IteratorAggregate implementation
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
