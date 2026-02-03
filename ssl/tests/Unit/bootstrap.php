<?php
/**
 * Unit Test Bootstrap
 *
 * Sets up mocks for WHMCS dependencies to allow unit testing
 * without requiring a full WHMCS installation.
 *
 * Note: This bootstrap uses the approach of loading a mock Capsule Manager
 * BEFORE any module code that uses it. The mock must be loaded first to be
 * registered with PHP's class loading mechanism.
 */

declare(strict_types=1);

// Mock logModuleCall function in global namespace
if (!function_exists('logModuleCall')) {
    function logModuleCall(string $module, string $action, $request, $response, $data = null): void
    {
        // No-op for unit tests
    }
}

// Mock WHMCS localAPI function in global namespace
if (!function_exists('localAPI')) {
    function localAPI(string $command, array $params): array
    {
        return match ($command) {
            'UpdateClientProduct' => ['result' => 'success'],
            'GetContacts' => ['contacts' => ['contact' => []]],
            'GetClientsDetails' => ['result' => 'success', 'client' => []],
            default => ['result' => 'success'],
        };
    }
}

// Check if the Capsule Manager exists - if not, load our mock
if (!class_exists('Illuminate\\Database\\Capsule\\Manager')) {
    require_once __DIR__ . '/Mocks/CapsuleManager.php';
}

// Load the test utilities
require_once __DIR__ . '/Mocks/MockQueryBuilder.php';

// Load Error classes (base classes) - these don't use Capsule
require_once __DIR__ . '/../../lib/Error.php';

// Load CertificateConfig - it uses Error.php but not Capsule directly
// Note: CertificateConfig.php has a require_once for Error.php but uses a relative path,
// which may not work from this context. We've already loaded Error.php above so it's fine.
require_once __DIR__ . '/../../lib/CertificateConfig.php';
