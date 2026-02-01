<?php
/**
 * Integration Test Bootstrap for Ascio v3 API Tests
 *
 * Loads WHMCS mock environment and initializes test database connection.
 * Test credentials are loaded from environment variables or .env file.
 *
 * Environment Variables:
 *   ASCIO_TEST_USERNAME - Ascio test account username
 *   ASCIO_TEST_PASSWORD - Ascio test account password
 *   ASCIO_TEST_MODE     - Set to 'true' for demo API (default: true)
 */

declare(strict_types=1);

// Mark as integration test environment
define('WHMCS_INTEGRATION_TEST', true);
define('ASCIO_INTEGRATION_TEST', true);

// Load main test bootstrap (sets up mocks and autoloading)
require_once __DIR__ . '/../bootstrap.php';

// Load v3 service classes
$v3AutoloadPath = __DIR__ . '/../../ssl/v3/service/autoload.php';
if (file_exists($v3AutoloadPath)) {
    require_once $v3AutoloadPath;
}

// Load Request class
require_once __DIR__ . '/../../lib/Request.php';

/**
 * Helper class to load test credentials
 */
class IntegrationTestCredentials
{
    private static ?string $username = null;
    private static ?string $password = null;
    private static bool $loaded = false;

    /**
     * Get test credentials
     *
     * @return array{username: ?string, password: ?string}
     */
    public static function get(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return [
            'username' => self::$username,
            'password' => self::$password,
        ];
    }

    /**
     * Check if credentials are available
     */
    public static function available(): bool
    {
        $creds = self::get();
        return !empty($creds['username']) && !empty($creds['password']);
    }

    /**
     * Load credentials from environment or .env files
     */
    private static function load(): void
    {
        self::$loaded = true;

        // Try environment variables first
        self::$username = getenv('ASCIO_TEST_ACCOUNT') ?: null;
        self::$password = getenv('ASCIO_TEST_PASSWORD') ?: null;

        // If not found, try .env files
        if (!self::$username || !self::$password) {
            $envFiles = [
                __DIR__ . '/../../.env',           // ascio/domains/.env
                __DIR__ . '/../../../.env',        // ascio/.env
                __DIR__ . '/../../../../.env',     // whmcs-tucows-dev/.env
            ];

            foreach ($envFiles as $envFile) {
                if (file_exists($envFile)) {
                    self::parseEnvFile($envFile);
                    if (self::$username && self::$password) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Parse .env file for credentials
     */
    private static function parseEnvFile(string $path): void
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            if ($key === 'ASCIO_TEST_ACCOUNT' && !self::$username) {
                self::$username = $value;
            }
            if ($key === 'ASCIO_TEST_PASSWORD' && !self::$password) {
                self::$password = $value;
            }
        }
    }
}

/**
 * Test data provider for existing domains on Ascio test account
 */
class TestDomainProvider
{
    /**
     * Known test domains on Ascio demo account
     * These domains can be used for GetDomain/SearchDomain tests
     */
    public static function getExistingDomains(): array
    {
        return [
            'com' => 'ascio-test.com',
            'net' => 'ascio-test.net',
            'org' => 'ascio-test.org',
        ];
    }

    /**
     * Generate a unique domain name for testing
     */
    public static function generateTestDomain(string $tld = 'com'): string
    {
        return 'integration-test-' . uniqid() . '-' . time() . '.' . $tld;
    }
}
