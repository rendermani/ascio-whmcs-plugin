<?php

/**
 * PHPUnit Bootstrap for Ascio Defensive Unit Tests
 *
 * Sets up mock dependencies and autoloading for isolated unit tests.
 * Does NOT require real API credentials.
 */

declare(strict_types=1);

// Define WHMCS constant to prevent direct access checks
if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// Define test mode indicator
define('DEFENSIVE_UNIT_TEST', true);

// ============================================================================
// Load Core Library FIRST (so interfaces are available)
// ============================================================================

$coreLibPath = dirname(__DIR__, 3) . '/core/lib/autoload.php';
if (file_exists($coreLibPath)) {
    require_once $coreLibPath;
}

// ============================================================================
// Load Mock Classes from Core Tests (after interfaces are loaded)
// ============================================================================

$coreTestsPath = dirname(__DIR__, 3) . '/core/tests';
if (is_dir($coreTestsPath)) {
    require_once $coreTestsPath . '/MockDatabase.php';
    require_once $coreTestsPath . '/MockAscioClient.php';
    require_once $coreTestsPath . '/MockParams.php';
}

// ============================================================================
// Load Defensive Library
// ============================================================================

require_once dirname(__DIR__, 2) . '/lib/Defensive.php';
require_once dirname(__DIR__, 2) . '/lib/DefensiveCallback.php';

// ============================================================================
// Mock WHMCS Functions
// ============================================================================

if (!function_exists('logModuleCall')) {
    function logModuleCall($module, $action, $request, $response, $processed = null, $replaceVars = []) {
        // No-op for unit tests
    }
}

if (!function_exists('logActivity')) {
    function logActivity($message) {
        // No-op for unit tests
    }
}

if (!function_exists('localAPI')) {
    function localAPI($command, $values = [], $adminUser = '') {
        return ['result' => 'success'];
    }
}

// ============================================================================
// Mock Capsule Manager
// ============================================================================

// If Capsule is not available, provide a minimal mock
if (!class_exists('Illuminate\Database\Capsule\Manager')) {
    // The core MockDatabase will be used instead via dependency injection
}
