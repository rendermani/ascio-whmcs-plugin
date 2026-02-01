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

    /** @var array Session storage for mod_asciosession (account => sessionId) */
    private static array $sessions = [];

    /**
     * Reset all mock state
     */
    public static function reset(): void
    {
        self::$tables = [];
        self::$lastQuery = [];
        self::$currentTable = null;
        self::$whereConditions = [];
        self::$sessions = [];
    }

    /**
     * Store a session ID for an account (used by SessionCache::put via mysql_query)
     */
    public static function storeSession(string $account, string $sessionId): void
    {
        self::$sessions[$account] = $sessionId;
    }

    /**
     * Get a session ID for an account
     */
    public static function getSession(string $account): ?string
    {
        return self::$sessions[$account] ?? null;
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
     * Execute raw select query
     */
    public static function select(string $query, array $bindings = []): array
    {
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
        if (strpos($query, 'mod_asciosession') !== false) {
            // Extract account from query if possible
            if (preg_match("/account='([^']+)'/", $query, $matches)) {
                $account = $matches[1];
                $sessionId = self::$sessions[$account] ?? null;
                if ($sessionId) {
                    return [(object) ['sessionId' => $sessionId]];
                }
            }
            // Return empty/null session to trigger login
            return [];
        }

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

        // Handle session table specially
        if ($table === 'mod_asciosession') {
            foreach (self::$whereConditions as $column => $condition) {
                if ($column === 'account') {
                    $account = is_array($condition) ? $condition['value'] : $condition;
                    $sessionId = self::$sessions[$account] ?? null;
                    if ($sessionId) {
                        return (object) ['sessionId' => $sessionId, 'account' => $account];
                    }
                }
            }
            return null;
        }

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
    public function get(): array
    {
        self::$lastQuery = [
            'type' => 'get',
            'table' => self::$currentTable,
            'where' => self::$whereConditions
        ];

        $table = self::$currentTable;
        if (isset(self::$tables[$table])) {
            $results = [];
            foreach (self::$tables[$table] as $row) {
                if (self::matchesConditions($row)) {
                    $results[] = (object) $row;
                }
            }
            return $results;
        }

        return [];
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

        // Handle session table specially
        if ($table === 'mod_asciosession' && isset($attributes['account'])) {
            $account = $attributes['account'];
            $sessionId = $values['sessionId'] ?? $attributes['sessionId'] ?? null;
            if ($sessionId) {
                self::$sessions[$account] = $sessionId;
            }
            return true;
        }

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
                $operator = $condition['operator'];
                $value = $condition['value'];

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
    private static array $tables = ['tblasciotlds', 'tblasciojobs', 'tblasciohandles', 'mod_asciosession'];

    public function hasTable(string $table): bool
    {
        return in_array($table, self::$tables);
    }

    public function hasColumn(string $table, string $column): bool
    {
        return true;
    }

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
        self::$tables = ['tblasciotlds', 'tblasciojobs', 'tblasciohandles', 'mod_asciosession'];
    }
}
