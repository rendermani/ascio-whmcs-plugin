<?php

namespace Ascio\Core;

use Ascio\Core\Contracts\DatabaseInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Database adapter using WHMCS Capsule (Eloquent).
 * Wraps Capsule calls to implement DatabaseInterface for testability.
 */
class CapsuleDatabase implements DatabaseInterface
{
    /**
     * {@inheritdoc}
     */
    public function insert(string $table, array $data): ?int
    {
        return Capsule::table($table)->insertGetId($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $table, array $data, array $where): int
    {
        $query = Capsule::table($table);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        return $query->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function select(string $table, array $columns, array $where)
    {
        $query = Capsule::table($table)->select($columns);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function first(string $table, array $columns, array $where)
    {
        $query = Capsule::table($table)->select($columns);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        return $query->first();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $table, array $where): int
    {
        $query = Capsule::table($table);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        return $query->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function raw(string $sql, array $bindings = [])
    {
        return Capsule::select($sql, $bindings);
    }
}
