<?php
/**
 * Integration Test Bootstrap
 *
 * Loads WHMCS environment, SSL module classes, and v3 service classes
 * for integration testing against Ascio demo API.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Integration;

// Load environment variables from .env if available
// Try multiple paths to support both monorepo and standalone setups
$envPaths = [
    __DIR__ . '/../../../.env',         // domains/.env (monorepo root)
    __DIR__ . '/../../../../.env',      // ascio/.env (parent dir)
    __DIR__ . '/../../../../../.env',   // whmcs-tucows-dev/.env
];
$envFile = null;
foreach ($envPaths as $path) {
    if (file_exists($path)) {
        $envFile = $path;
        break;
    }
}
if ($envFile === null) {
    $envFile = __DIR__ . '/../../../.env'; // Default fallback
}
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!getenv($name)) {
                putenv("$name=$value");
            }
        }
    }
}

// Load v3 service autoloader
require_once __DIR__ . '/../../v3/service/autoload.php';

// Load SSL module library classes - use include guards to prevent double loading
// Note: Some of these files have their own require_once calls
if (!class_exists('ascio\\whmcs\\ssl\\Error')) {
    require_once __DIR__ . '/../../lib/Error.php';
}
if (!class_exists('ascio\\whmcs\\ssl\\Fqdn')) {
    require_once __DIR__ . '/../../lib/Fqdn.php';
}
if (!class_exists('ssl\\Params')) {
    require_once __DIR__ . '/../../lib/Params.php';
}
if (!class_exists('ascio\\whmcs\\ssl\\Sans')) {
    require_once __DIR__ . '/../../lib/Sans.php';
}
if (!class_exists('ascio\\whmcs\\ssl\\Status')) {
    require_once __DIR__ . '/../../lib/Status.php';
}
if (!class_exists('ascio\\whmcs\\ssl\\Ssl')) {
    require_once __DIR__ . '/../../lib/Ssl.php';
}
// Callback and SslCallback will be loaded by their own require_once chains if needed

/**
 * Test Configuration
 *
 * Credentials and settings can be overridden via environment variables:
 * - ASCIO_TEST_ACCOUNT: Ascio test account username
 * - ASCIO_TEST_PASSWORD: Ascio test account password
 * - ASCIO_TEST_MODE: Set to 'true' to use demo API (default: true)
 */
class TestConfig
{
    private static ?TestConfig $instance = null;

    public string $account;
    public string $password;
    public bool $testMode;
    public string $wsdlUrl;

    private function __construct()
    {
        $this->account = getenv('ASCIO_TEST_ACCOUNT') ?: 'ascio_test';
        $this->password = getenv('ASCIO_TEST_PASSWORD') ?: 'test_password';

        // Handle various truthy values: 'true', '1', 1, true
        $testModeEnv = getenv('ASCIO_TEST_MODE') ?: 'true';
        $this->testMode = in_array(strtolower((string)$testModeEnv), ['true', '1', 'yes', 'on'], true);

        // v3 WSDL: demo uses aws.demo.ascio.com, live uses aws.ascio.com
        $prefix = $this->testMode ? 'demo.' : '';
        $this->wsdlUrl = "https://aws.{$prefix}ascio.com/v3/aws.wsdl";
    }

    public static function getInstance(): TestConfig
    {
        if (self::$instance === null) {
            self::$instance = new TestConfig();
        }
        return self::$instance;
    }

    /**
     * Get SOAP credentials array for Ascio API
     */
    public function getCredentials(): array
    {
        return [
            'Account' => $this->account,
            'Password' => $this->password,
        ];
    }

    /**
     * Create configured Ascio SOAP client
     */
    public function createClient(): \ascio\v3\AscioService
    {
        $header = new \SoapHeader(
            "http://www.ascio.com/2013/02",
            "SecurityHeaderDetails",
            $this->getCredentials(),
            false
        );

        $client = new \ascio\v3\AscioService(
            ['trace' => true],
            $this->wsdlUrl
        );
        $client->__setSoapHeaders($header);

        return $client;
    }
}

/**
 * Sample CSR Generator for Tests
 */
class TestCsrGenerator
{
    /**
     * Generate a test CSR for the given common name
     */
    public static function generate(string $commonName, array $options = []): array
    {
        $dn = array_merge([
            'countryName' => 'DE',
            'stateOrProvinceName' => 'Bavaria',
            'localityName' => 'Munich',
            'organizationName' => 'Test Organization',
            'commonName' => $commonName,
            'emailAddress' => 'admin@' . $commonName,
        ], $options);

        $privkey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);

        openssl_csr_export($csr, $csrOut);
        openssl_pkey_export($privkey, $privateKeyOut);

        return [
            'csr' => $csrOut,
            'privateKey' => $privateKeyOut,
            'commonName' => $commonName,
            'dn' => $dn,
        ];
    }

    /**
     * Generate a wildcard CSR
     */
    public static function generateWildcard(string $domain): array
    {
        return self::generate('*.' . $domain);
    }
}

/**
 * Test Data Factory
 */
class TestDataFactory
{
    /**
     * Generate a unique test domain name
     */
    public static function generateDomain(string $prefix = 'test'): string
    {
        return sprintf('%s-%d-%s.example.com', $prefix, time(), substr(md5(uniqid()), 0, 6));
    }

    /**
     * Create standard test contact data
     */
    public static function createContactData(string $type = 'owner'): array
    {
        return [
            'firstName' => 'Test',
            'lastName' => 'User',
            'orgName' => 'Test Organization GmbH',
            'address1' => 'Test Street 123',
            'address2' => '',
            'city' => 'Munich',
            'state' => 'Bavaria',
            'postalCode' => '80331',
            'countryCode' => 'DE',
            'phone' => '+49.891234567',
            'fax' => '',
            'email' => 'test@example.com',
            'type' => 'Organization',
        ];
    }

    /**
     * Build v3 Registrant object from contact data
     */
    public static function buildRegistrant(array $data): \ascio\v3\Registrant
    {
        $registrant = new \ascio\v3\Registrant();
        $registrant->setFirstName($data['firstName'] ?? 'Test');
        $registrant->setLastName($data['lastName'] ?? 'User');
        $registrant->setOrgName($data['orgName'] ?? '');
        $registrant->setAddress1($data['address1'] ?? 'Test Street 1');
        $registrant->setAddress2($data['address2'] ?? '');
        $registrant->setCity($data['city'] ?? 'Munich');
        $registrant->setState($data['state'] ?? 'Bavaria');
        $registrant->setPostalCode($data['postalCode'] ?? '80331');
        $registrant->setCountryCode($data['countryCode'] ?? 'DE');
        $registrant->setPhone($data['phone'] ?? '+49.891234567');
        $registrant->setFax($data['fax'] ?? '');
        $registrant->setEmail($data['email'] ?? 'test@example.com');
        $registrant->setType($data['type'] ?? 'Organization');

        return $registrant;
    }

    /**
     * Build v3 Contact object from contact data
     */
    public static function buildContact(array $data): \ascio\v3\Contact
    {
        $contact = new \ascio\v3\Contact();
        $contact->setFirstName($data['firstName'] ?? 'Test');
        $contact->setLastName($data['lastName'] ?? 'User');
        $contact->setOrgName($data['orgName'] ?? '');
        $contact->setAddress1($data['address1'] ?? 'Test Street 1');
        $contact->setAddress2($data['address2'] ?? '');
        $contact->setCity($data['city'] ?? 'Munich');
        $contact->setState($data['state'] ?? 'Bavaria');
        $contact->setPostalCode($data['postalCode'] ?? '80331');
        $contact->setCountryCode($data['countryCode'] ?? 'DE');
        $contact->setPhone($data['phone'] ?? '+49.891234567');
        $contact->setFax($data['fax'] ?? '');
        $contact->setEmail($data['email'] ?? 'test@example.com');
        $contact->setType($data['type'] ?? 'Organization');

        return $contact;
    }
}
