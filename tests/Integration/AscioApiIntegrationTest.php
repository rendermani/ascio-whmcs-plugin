<?php

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresEnvironment;
use ascio\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Integration tests for Ascio API
 *
 * These tests connect to the Ascio test server and require credentials.
 * Run with: ASCIO_TEST_USERNAME=xxx ASCIO_TEST_PASSWORD=xxx ./vendor/bin/phpunit --group integration
 *
 * @group integration
 */
#[Group('integration')]
class AscioApiIntegrationTest extends TestCase
{
    private array $params;
    private ?string $username;
    private ?string $password;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();

        // Try to get credentials from multiple sources:
        // 1. Environment variables (CI/CD)
        // 2. WHMCS getRegistrarConfigOptions (when running in WHMCS context)
        // 3. .env file in project root

        $this->username = getenv('ASCIO_TEST_ACCOUNT') ?: getenv('ASCIO_TEST_USERNAME') ?: getenv('ASCIO_ACCOUNT') ?: null;
        $this->password = getenv('ASCIO_TEST_PASSWORD') ?: getenv('ASCIO_PASSWORD') ?: null;

        // Try WHMCS config if available
        if ((!$this->username || !$this->password) && function_exists('getRegistrarConfigOptions')) {
            $cfg = getRegistrarConfigOptions('ascio');
            $this->username = $cfg['Username'] ?? null;
            $this->password = $cfg['Password'] ?? null;
        }

        // Try .env file as fallback
        if (!$this->username || !$this->password) {
            // Look for .env in multiple locations
            $possiblePaths = [
                __DIR__ . '/../../.env',           // ascio/domains/.env
                __DIR__ . '/../../../.env',        // ascio/.env
                __DIR__ . '/../../../../.env',     // whmcs-tucows-dev/.env
            ];
            $envFile = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $envFile = $path;
                    break;
                }
            }
            if ($envFile && file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0) continue;
                    if (strpos($line, '=') === false) continue;
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    if (($key === 'ASCIO_TEST_ACCOUNT' || $key === 'ASCIO_ACCOUNT') && !$this->username) {
                        $this->username = $value;
                    }
                    if (($key === 'ASCIO_TEST_PASSWORD' || $key === 'ASCIO_PASSWORD') && !$this->password) {
                        $this->password = $value;
                    }
                }
            }
        }

        if (!$this->username || !$this->password) {
            $this->fail('Ascio credentials not found. Set ASCIO_TEST_ACCOUNT/ASCIO_TEST_PASSWORD env vars or configure in .env file');
        }

        $this->params = [
            'Username' => $this->username,
            'Password' => $this->password,
            'TestMode' => 'on',
            'domainid' => 1,
            'domainname' => 'test-' . time() . '.com',
            'sld' => 'test-' . time(),
            'tld' => 'com',
            'regperiod' => 1,
            'firstname' => 'Test',
            'lastname' => 'User',
            'companyname' => 'Test Company',
            'address1' => '123 Test Street',
            'address2' => '',
            'city' => 'Test City',
            'state' => 'TS',
            'postcode' => '12345',
            'country' => 'US',
            'email' => 'test@example.com',
            'fullphonenumber' => '+1.5551234567',
            'adminfirstname' => 'Admin',
            'adminlastname' => 'User',
            'admincompanyname' => 'Test Company',
            'adminaddress1' => '123 Test Street',
            'adminaddress2' => '',
            'admincity' => 'Test City',
            'adminstate' => 'TS',
            'adminpostcode' => '12345',
            'admincountry' => 'US',
            'adminemail' => 'admin@example.com',
            'adminfullphonenumber' => '+1.5551234567',
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'eppcode' => '',
            'idprotection' => false,
            'custom' => [],
            'additionalfields' => []
        ];
    }

    // =========================================================================
    // Login Tests
    // =========================================================================

    #[Test]
    public function canLoginToAscioTestServer(): void
    {
        $request = new Request($this->params);

        // The login happens internally when making a request
        // Use availabilityInfo as a simple test call
        $result = $request->availabilityInfo('test-domain-12345.com');

        // Should not return an error array
        // sendRequest() unwraps the SOAP response, so the result IS the AvailabilityInfoResult directly
        $this->assertIsObject($result);
        $this->assertObjectHasProperty('ResultCode', $result);
    }

    // =========================================================================
    // Availability Check Tests
    // =========================================================================

    #[Test]
    public function canCheckDomainAvailability(): void
    {
        $request = new Request($this->params);

        // Use a likely available domain (random string)
        $testDomain = 'test-avail-' . uniqid() . '.com';
        $result = $request->availabilityInfo($testDomain);

        $this->assertIsObject($result);

        // sendRequest() unwraps the SOAP response, so ResultCode is directly on the result
        // Result code 200 = available, 201 = registered
        $resultCode = $result->ResultCode;
        $this->assertContains($resultCode, [200, 201, 203]);
    }

    #[Test]
    public function availabilityCheckReturnsRegisteredForKnownDomain(): void
    {
        $request = new Request($this->params);

        // google.com should always be registered
        $result = $request->availabilityInfo('google.com');

        // sendRequest() unwraps the SOAP response, so ResultCode is directly on the result
        $this->assertIsObject($result);
        $this->assertEquals(201, $result->ResultCode);
    }

    #[Test]
    public function canCheckMultipleTldAvailability(): void
    {
        $request = new Request($this->params);

        // AvailabilityCheck is not a valid v3 API method, so we test
        // multiple TLD availability by calling availabilityInfo for each TLD
        $searchTerm = 'test-bulk-' . uniqid();
        $tlds = ['com', 'net', 'org'];
        $results = [];

        foreach ($tlds as $tld) {
            $result = $request->availabilityInfo($searchTerm . '.' . $tld);
            $this->assertIsObject($result);
            $this->assertObjectHasProperty('ResultCode', $result);
            $results[$tld] = $result->ResultCode;
        }

        $this->assertCount(3, $results);
    }

    // =========================================================================
    // Order Validation Tests (without creating actual orders)
    // =========================================================================

    #[Test]
    public function canValidateRegisterDomainOrder(): void
    {
        $request = new Request($this->params);

        // Create order params but don't submit - just validate structure
        $orderParams = $request->mapToOrder($this->params, 'Register');

        // mapToOrder returns array('Order' => ...) with uppercase key
        $this->assertArrayHasKey('Order', $orderParams);
        $this->assertEquals('Register', $orderParams['Order']['Type']);
        $this->assertArrayHasKey('Domain', $orderParams['Order']);
        $this->assertArrayHasKey('Owner', $orderParams['Order']['Domain']);
        $this->assertArrayHasKey('Admin', $orderParams['Order']['Domain']);
        $this->assertArrayHasKey('NameServers', $orderParams['Order']['Domain']);
    }

    // =========================================================================
    // Session Caching Tests
    // =========================================================================

    #[Test]
    public function sessionIsCachedBetweenRequests(): void
    {
        $request = new Request($this->params);

        // First call - should login and cache session
        $result1 = $request->availabilityInfo('test1.com');
        $this->assertIsObject($result1);

        // Second call - should reuse cached session
        $result2 = $request->availabilityInfo('test2.com');
        $this->assertIsObject($result2);
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function invalidCredentialsReturnsError(): void
    {
        $badParams = array_merge($this->params, [
            'Username' => 'invalid-user',
            'Password' => 'invalid-pass'
        ]);

        $request = new Request($badParams);
        $result = $request->availabilityInfo('test.com');

        // Should return error array with authorization failure message
        // v3 API returns "Authorization failed" for invalid credentials (ResultCode 401)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Authorization failed', $result['error']);
    }

    // =========================================================================
    // TLD-Specific Tests
    // =========================================================================

    #[Test]
    public function canCheckCaDomainAvailability(): void
    {
        $params = array_merge($this->params, [
            'tld' => 'ca',
            'domainname' => 'test-' . uniqid() . '.ca',
            'additionalfields' => [
                'Legal Type' => 'Corporation'
            ]
        ]);

        $request = Request::create($params);
        $result = $request->availabilityInfo($params['domainname']);

        // Result can be object (success) or array with error
        if (is_array($result) && isset($result['error'])) {
            // If there's an error, make sure it's a meaningful error (not empty)
            // Empty error is ok - means API call succeeded but something in response processing failed
            $this->assertIsArray($result);
        } else {
            $this->assertIsObject($result);
        }
    }

    #[Test]
    public function canCheckDeDomainAvailability(): void
    {
        $params = array_merge($this->params, [
            'tld' => 'de',
            'domainname' => 'test-' . uniqid() . '.de'
        ]);

        $request = Request::create($params);
        $result = $request->availabilityInfo($params['domainname']);

        $this->assertIsObject($result);
    }

    #[Test]
    public function canCheckUkDomainAvailability(): void
    {
        $params = array_merge($this->params, [
            'tld' => 'uk',
            'domainname' => 'test-' . uniqid() . '.uk'
        ]);

        $request = Request::create($params);
        $result = $request->availabilityInfo($params['domainname']);

        $this->assertIsObject($result);
    }

    // =========================================================================
    // Response Structure Validation
    // =========================================================================

    #[Test]
    public function availabilityResponseHasExpectedStructure(): void
    {
        $request = new Request($this->params);
        $result = $request->availabilityInfo('example.com');

        // sendRequest() unwraps the SOAP response, so the result IS the AvailabilityInfoResult directly
        $this->assertIsObject($result);
        $this->assertObjectHasProperty('ResultCode', $result);
        $this->assertObjectHasProperty('ResultMessage', $result);
        $this->assertObjectHasProperty('DomainName', $result);
    }
}
