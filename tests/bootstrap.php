<?php
/**
 * PHPUnit Bootstrap File for Ascio WHMCS Plugin Tests
 *
 * Sets up WHMCS function mocks and autoloading for unit tests.
 * IMPORTANT: Class aliases MUST be set up BEFORE loading any classes that use them.
 */

define('WHMCS_UNIT_TEST', true);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load mock classes FIRST (before any aliases)
require_once __DIR__ . '/Mocks/WhmcsFunctionsMock.php';
require_once __DIR__ . '/Mocks/SoapClientMock.php';
require_once __DIR__ . '/Mocks/CapsuleMock.php';
require_once __DIR__ . '/Mocks/WhmcsClassMocks.php';
require_once __DIR__ . '/Mocks/MockAscioClientV3.php';
require_once __DIR__ . '/Mocks/MockParamsV3.php';

// ============================================================================
// SET UP ALL CLASS ALIASES BEFORE LOADING ANY ASCIO CLASSES
// ============================================================================

// Mock WHMCS Database Capsule
if (!class_exists('WHMCS\Database\Capsule')) {
    class_alias(\Ascio\Tests\Mocks\CapsuleMock::class, 'WHMCS\Database\Capsule');
}

// Mock Illuminate Capsule for direct usage (used by AutoExpireService, etc.)
if (!class_exists('Illuminate\Database\Capsule\Manager')) {
    class_alias(\Ascio\Tests\Mocks\CapsuleMock::class, 'Illuminate\Database\Capsule\Manager');
}

// Mock WHMCS Domain classes
if (!class_exists('WHMCS\Carbon')) {
    class_alias(\Ascio\Tests\Mocks\CarbonMock::class, 'WHMCS\Carbon');
}

if (!class_exists('WHMCS\Domain\Registrar\Domain')) {
    class_alias(\Ascio\Tests\Mocks\DomainMock::class, 'WHMCS\Domain\Registrar\Domain');
}

if (!class_exists('WHMCS\Domains\DomainLookup\ResultsList')) {
    class_alias(\Ascio\Tests\Mocks\ResultsListMock::class, 'WHMCS\Domains\DomainLookup\ResultsList');
}

if (!class_exists('WHMCS\Domains\DomainLookup\SearchResult')) {
    class_alias(\Ascio\Tests\Mocks\SearchResultMock::class, 'WHMCS\Domains\DomainLookup\SearchResult');
}

if (!class_exists('WHMCS\Domain\TopLevel\ImportItem')) {
    class_alias(\Ascio\Tests\Mocks\ImportItemMock::class, 'WHMCS\Domain\TopLevel\ImportItem');
}

if (!class_exists('WHMCS\Results\ResultsList')) {
    class_alias(\Ascio\Tests\Mocks\PriceResultsListMock::class, 'WHMCS\Results\ResultsList');
}

// ============================================================================
// MOCK WHMCS GLOBAL FUNCTIONS
// ============================================================================

if (!function_exists('logActivity')) {
    function logActivity($message) {
        \Ascio\Tests\Mocks\WhmcsFunctionsMock::logActivity($message);
    }
}

if (!function_exists('logModuleCall')) {
    function logModuleCall($module, $action, $requestData, $responseData, $processedData = null, $replaceVars = []) {
        \Ascio\Tests\Mocks\WhmcsFunctionsMock::logModuleCall($module, $action, $requestData, $responseData, $processedData, $replaceVars);
    }
}

if (!function_exists('localAPI')) {
    function localAPI($command, $values = [], $adminUser = '') {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::localAPI($command, $values, $adminUser);
    }
}

if (!function_exists('get_query_val')) {
    function get_query_val($table, $field, $where) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::get_query_val($table, $field, $where);
    }
}

// Mock legacy mysql functions (deprecated but still used in code)
if (!function_exists('mysql_query')) {
    function mysql_query($query) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::mysql_query($query);
    }
}

if (!function_exists('mysql_fetch_assoc')) {
    function mysql_fetch_assoc($result) {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::mysql_fetch_assoc($result);
    }
}

if (!function_exists('mysql_error')) {
    function mysql_error() {
        return \Ascio\Tests\Mocks\WhmcsFunctionsMock::mysql_error();
    }
}

// ============================================================================
// NOW LOAD ASCIO CLASSES (after aliases are set up)
// ============================================================================

// Load Ascio v3 service classes (if available)
// Check if the autoload function already exists to avoid redeclaration
if (!function_exists('autoload_ca5124cc493862de39cebdb26d543f92')) {
    $v3AutoloadPath = __DIR__ . '/../ssl/v3/service/autoload.php';
    if (file_exists($v3AutoloadPath)) {
        require_once $v3AutoloadPath;
    }
}

// Request and RequestV3 classes are loaded on-demand via ApiVersion.php or autoloader
// Don't load them here to avoid duplicate class declarations

// AutoExpireService is now autoloaded via composer (namespace ascio)
