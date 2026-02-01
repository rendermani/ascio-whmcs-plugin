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
    public static function localAPI(string $command, array $values = [], string $adminUser = ''): array
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
        // Handle session INSERT/UPDATE for mod_asciosession
        if (preg_match('/INSERT INTO\s+mod_asciosession.*VALUES\s*\(\s*[\'"]([^\'"]+)[\'"].*[\'"]([^\'"]+)[\'"]\s*\)/i', $query, $matches)) {
            $account = $matches[1];
            $sessionId = $matches[2];
            \Ascio\Tests\Mocks\CapsuleMock::storeSession($account, $sessionId);
        }

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
}
