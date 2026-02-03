<?php

/**
 * PHPUnit bootstrap file for Ascio Core tests.
 *
 * Supports both unit tests and integration tests.
 * For integration tests with live API, set environment variables:
 *   - ASCIO_TEST_ACCOUNT: Test account username
 *   - ASCIO_TEST_PASSWORD: Test account password
 *   - ASCIO_TEST_ORDER_ID: (optional) Known order ID for GetOrder tests
 *   - ASCIO_TEST_DOMAIN_HANDLE: (optional) Known domain handle for GetDomain tests
 *   - ASCIO_TEST_MESSAGE_ID: (optional) Known message ID for queue tests
 */

// Autoload core library FIRST (so interfaces are available)
require_once dirname(__DIR__) . '/lib/autoload.php';

// Autoload test helpers (after interfaces are loaded)
require_once __DIR__ . '/MockDatabase.php';
require_once __DIR__ . '/MockAscioClient.php';
require_once __DIR__ . '/MockParams.php';

// Mock WHMCS functions that may be called
if (!function_exists('logModuleCall')) {
    function logModuleCall($module, $action, $request, $response, $errors = null) {
        // No-op for tests
    }
}

if (!function_exists('logActivity')) {
    function logActivity($message) {
        // No-op for tests - optionally write to stderr for debugging
        // fwrite(STDERR, "[logActivity] {$message}\n");
    }
}

if (!function_exists('localAPI')) {
    function localAPI($command, $params = [], $adminUser = null) {
        return ['result' => 'success'];
    }
}

if (!function_exists('get_query_val')) {
    function get_query_val($table, $field, $where) {
        // Mock for database queries
        return null;
    }
}

if (!function_exists('mysql_query')) {
    function mysql_query($query) {
        // Mock for legacy mysql_query calls
        return false;
    }
}

if (!function_exists('mysql_fetch_assoc')) {
    function mysql_fetch_assoc($result) {
        // Mock for legacy mysql_fetch_assoc calls
        return false;
    }
}
