<?php

namespace Ascio\Tests\Mocks;

/**
 * Mock for WHMCS global functions
 *
 * Provides mock implementations of WHMCS functions for unit testing
 */
class WhmcsFunctionsMock
{
    /** @var array Stored log activity calls */
    private static array $activityLogs = [];

    /** @var array Stored module call logs */
    private static array $moduleLogs = [];

    /** @var array Mock responses for localAPI */
    private static array $localApiResponses = [];

    /** @var array Tracked localAPI calls for verification */
    private static array $localApiCalls = [];

    /** @var array Mock results for database queries */
    private static array $queryResults = [];

    /** @var int Current query result index */
    private static int $queryResultIndex = 0;

    /** @var string|null Last mysql error */
    private static ?string $lastMysqlError = null;

    /** @var int Last insert ID for mysql_insert_id */
    private static int $lastMysqlInsertId = 0;

    /** @var array Tracked insert_query calls */
    private static array $insertQueryCalls = [];

    /** @var int Auto-increment ID for insert_query */
    private static int $insertQueryLastId = 0;

    /** @var array Tracked update_query calls */
    private static array $updateQueryCalls = [];

    /** @var array Tracked select_query calls */
    private static array $selectQueryCalls = [];

    /** @var array Mock results for select_query (indexed by call order) */
    private static array $selectQueryResults = [];

    /** @var int Current select_query result index */
    private static int $selectQueryResultIndex = 0;

    /** @var array Mock results for get_query_val (table.field.where_key => value) */
    private static array $getQueryValResults = [];

    /**
     * Reset all mock state
     */
    public static function reset(): void
    {
        self::$activityLogs = [];
        self::$moduleLogs = [];
        self::$localApiResponses = [];
        self::$localApiCalls = [];
        self::$queryResults = [];
        self::$queryResultIndex = 0;
        self::$lastMysqlError = null;
        self::$lastMysqlInsertId = 0;
        self::$insertQueryCalls = [];
        self::$insertQueryLastId = 0;
        self::$updateQueryCalls = [];
        self::$selectQueryCalls = [];
        self::$selectQueryResults = [];
        self::$selectQueryResultIndex = 0;
        self::$getQueryValResults = [];
    }

    /**
     * Mock logActivity function
     */
    public static function logActivity(string $message): void
    {
        self::$activityLogs[] = $message;
    }

    /**
     * Get all logged activity messages
     */
    public static function getActivityLogs(): array
    {
        return self::$activityLogs;
    }

    /**
     * Mock logModuleCall function
     */
    public static function logModuleCall(
        string $module,
        string $action,
        mixed $requestData,
        mixed $responseData,
        mixed $processedData = null,
        array $replaceVars = []
    ): void {
        self::$moduleLogs[] = [
            'module' => $module,
            'action' => $action,
            'requestData' => $requestData,
            'responseData' => $responseData,
            'processedData' => $processedData,
            'replaceVars' => $replaceVars,
        ];
    }

    /**
     * Get all module call logs
     */
    public static function getModuleLogs(): array
    {
        return self::$moduleLogs;
    }

    /**
     * Set mock response for localAPI
     */
    public static function setLocalApiResponse(string $command, array $response): void
    {
        self::$localApiResponses[$command] = $response;
    }

    /**
     * Get all tracked localAPI calls
     */
    public static function getLocalApiCalls(): array
    {
        return self::$localApiCalls;
    }

    /**
     * Mock localAPI function
     */
    public static function localAPI(string $command, array $values = [], ?string $adminUser = ''): array
    {
        // Track all calls for verification
        self::$localApiCalls[] = [
            'command' => $command,
            'values' => $values,
            'adminUser' => $adminUser,
        ];

        if (isset(self::$localApiResponses[$command])) {
            return self::$localApiResponses[$command];
        }

        // Default mock responses
        return match ($command) {
            'logactivity' => ['result' => 'success'],
            'getclientsdomains' => [
                'result' => 'success',
                'domains' => [
                    'domain' => [
                        ['id' => 1, 'domain' => 'example.com', 'notes' => '']
                    ]
                ]
            ],
            'updateclientdomain' => ['result' => 'success'],
            'getemailtemplates' => [
                'result' => 'success',
                'emailtemplates' => [
                    'emailtemplate' => []
                ]
            ],
            'sendemail' => ['result' => 'success'],
            'GetTLDPricing' => [
                'result' => 'success',
                'pricing' => [
                    'com' => [],
                    'net' => [],
                    'org' => []
                ]
            ],
            default => ['result' => 'error', 'message' => 'Unknown command: ' . $command]
        };
    }

    /**
     * Mock get_query_val function
     */
    public static function get_query_val(string $table, string $field, array $where): mixed
    {
        // Default: return 'ascio' for registrar checks
        if ($table === 'tbldomains' && $field === 'registrar') {
            return 'ascio';
        }
        return null;
    }

    /**
     * Set mock results for mysql_query
     */
    public static function setQueryResults(array $results): void
    {
        self::$queryResults = $results;
        self::$queryResultIndex = 0;
    }

    /**
     * Mock mysql_query function
     */
    public static function mysql_query(string $query): mixed
    {
        // Return a mock resource identifier (just an array that mysql_fetch_assoc can use)
        if (!empty(self::$queryResults)) {
            return ['__mock_result' => true, 'index' => self::$queryResultIndex++];
        }
        return ['__mock_result' => true, 'index' => 0];
    }

    /**
     * Mock mysql_fetch_assoc function
     */
    public static function mysql_fetch_assoc(mixed $result): ?array
    {
        if (!is_array($result) || !isset($result['__mock_result'])) {
            return null;
        }

        $index = $result['index'] ?? 0;
        if (isset(self::$queryResults[$index])) {
            $row = self::$queryResults[$index];
            // Mark as consumed
            unset(self::$queryResults[$index]);
            return $row;
        }
        return null;
    }

    /**
     * Set mysql error message
     */
    public static function setMysqlError(?string $error): void
    {
        self::$lastMysqlError = $error;
    }

    /**
     * Mock mysql_error function
     */
    public static function mysql_error(): string
    {
        return self::$lastMysqlError ?? '';
    }

    /**
     * Set last insert ID for mysql_insert_id
     */
    public static function setMysqlInsertId(int $id): void
    {
        self::$lastMysqlInsertId = $id;
    }

    /**
     * Mock mysql_insert_id function
     */
    public static function mysql_insert_id(): int
    {
        return self::$lastMysqlInsertId;
    }

    // =========================================================================
    // insert_query mock
    // =========================================================================

    /**
     * Set the starting auto-increment ID for insert_query
     */
    public static function setInsertQueryLastId(int $id): void
    {
        self::$insertQueryLastId = $id;
    }

    /**
     * Get all tracked insert_query calls
     */
    public static function getInsertQueryCalls(): array
    {
        return self::$insertQueryCalls;
    }

    /**
     * Mock insert_query function - returns auto-increment ID
     */
    public static function insert_query(string $table, array $data): int
    {
        self::$insertQueryLastId++;
        self::$insertQueryCalls[] = [
            'table' => $table,
            'data' => $data,
            'inserted_id' => self::$insertQueryLastId,
        ];
        return self::$insertQueryLastId;
    }

    // =========================================================================
    // update_query mock
    // =========================================================================

    /**
     * Get all tracked update_query calls
     */
    public static function getUpdateQueryCalls(): array
    {
        return self::$updateQueryCalls;
    }

    /**
     * Mock update_query function
     */
    public static function update_query(string $table, array $data, array $where): bool
    {
        self::$updateQueryCalls[] = [
            'table' => $table,
            'data' => $data,
            'where' => $where,
        ];
        return true;
    }

    // =========================================================================
    // select_query mock
    // =========================================================================

    /**
     * Set mock results for select_query (each call consumes one result set)
     */
    public static function setSelectQueryResults(array $results): void
    {
        self::$selectQueryResults = $results;
        self::$selectQueryResultIndex = 0;
    }

    /**
     * Get all tracked select_query calls
     */
    public static function getSelectQueryCalls(): array
    {
        return self::$selectQueryCalls;
    }

    /**
     * Mock select_query function - returns a mock resource
     */
    public static function select_query(string $table, string $fields, array $where): array
    {
        $index = self::$selectQueryResultIndex++;
        self::$selectQueryCalls[] = [
            'table' => $table,
            'fields' => $fields,
            'where' => $where,
            'result_index' => $index,
        ];

        // Return a mock result resource that mysql_fetch_assoc can use
        return [
            '__mock_select_result' => true,
            'index' => $index,
            'rows' => self::$selectQueryResults[$index] ?? [],
            'current_row' => 0,
        ];
    }

    /**
     * Enhanced mysql_fetch_assoc that also handles select_query results
     */
    public static function mysql_fetch_assoc_enhanced(mixed $result): ?array
    {
        if (!is_array($result)) {
            return null;
        }

        // Handle select_query mock results
        if (isset($result['__mock_select_result'])) {
            $rows = $result['rows'] ?? [];
            $currentRow = $result['current_row'] ?? 0;

            if (isset($rows[$currentRow])) {
                // Note: We can't modify the original array, so this is a simplification
                // In real usage, each call should advance the pointer
                return $rows[$currentRow];
            }
            return null;
        }

        // Fall back to original mysql_query mock handling
        if (isset($result['__mock_result'])) {
            $index = $result['index'] ?? 0;
            if (isset(self::$queryResults[$index])) {
                $row = self::$queryResults[$index];
                unset(self::$queryResults[$index]);
                return $row;
            }
        }

        return null;
    }

    // =========================================================================
    // get_query_val mock (enhanced)
    // =========================================================================

    /**
     * Set mock result for get_query_val
     *
     * @param string $table Table name
     * @param string $field Field name
     * @param string $whereKey Key from where clause (first key used for lookup)
     * @param mixed $value Value to return
     */
    public static function setGetQueryValResult(string $table, string $field, string $whereKey, mixed $value): void
    {
        $key = "{$table}.{$field}.{$whereKey}";
        self::$getQueryValResults[$key] = $value;
    }

    /**
     * Set multiple get_query_val results at once
     */
    public static function setGetQueryValResults(array $results): void
    {
        self::$getQueryValResults = array_merge(self::$getQueryValResults, $results);
    }

    /**
     * Enhanced get_query_val function with configurable results
     */
    public static function get_query_val_enhanced(string $table, string $field, array $where): mixed
    {
        // Try to find a matching mock result
        foreach ($where as $whereKey => $whereValue) {
            $key = "{$table}.{$field}.{$whereKey}";
            if (isset(self::$getQueryValResults[$key])) {
                return self::$getQueryValResults[$key];
            }
        }

        // Default: return 'ascio' for registrar checks (backward compatible)
        if ($table === 'tbldomains' && $field === 'registrar') {
            return 'ascio';
        }

        return null;
    }
}
