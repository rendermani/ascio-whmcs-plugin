<?php
/**
 * Mock Query Builder utilities for tests
 *
 * Provides an easy-to-use interface for setting up and inspecting
 * mock database operations in unit tests.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Unit;

use Illuminate\Database\Capsule\Manager;

class MockQueryBuilder
{
    /**
     * Reset all mock data
     */
    public static function reset(): void
    {
        Manager::reset();
    }

    /**
     * Set mock data for a table
     */
    public static function setMockData(string $table, $data): void
    {
        Manager::setMockData($table, $data);
    }

    /**
     * Get inserted data for a table
     */
    public static function getInsertedData(string $table): array
    {
        return Manager::getInsertedData($table);
    }
}
