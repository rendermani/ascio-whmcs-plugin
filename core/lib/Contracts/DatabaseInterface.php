<?php

namespace Ascio\Core\Contracts;

/**
 * Interface for database operations.
 * Enables mocking of database calls in unit tests.
 */
interface DatabaseInterface
{
    /**
     * Insert a record into a table.
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int|null Inserted ID or null
     */
    public function insert(string $table, array $data): ?int;

    /**
     * Update records in a table.
     *
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where Where conditions
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $where): int;

    /**
     * Select records from a table.
     *
     * @param string $table Table name
     * @param array $columns Columns to select
     * @param array $where Where conditions
     * @return array|object|null Query result
     */
    public function select(string $table, array $columns, array $where);

    /**
     * Select first matching record.
     *
     * @param string $table Table name
     * @param array $columns Columns to select
     * @param array $where Where conditions
     * @return object|null First result or null
     */
    public function first(string $table, array $columns, array $where);

    /**
     * Delete records from a table.
     *
     * @param string $table Table name
     * @param array $where Where conditions
     * @return int Number of deleted rows
     */
    public function delete(string $table, array $where): int;

    /**
     * Execute raw SQL.
     *
     * @param string $sql SQL statement
     * @param array $bindings Parameter bindings
     * @return mixed Query result
     */
    public function raw(string $sql, array $bindings = []);
}
