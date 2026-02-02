<?php
/**
 * Base Integration Test Class for Ascio v3 API
 *
 * Provides common setup, helper methods, and utilities for integration tests.
 * All integration tests should extend this class.
 */

namespace Ascio\Tests\Integration;

// Load integration test bootstrap
require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;
use ascio\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\MockParamsV3;
use IntegrationTestCredentials;
use TestDomainProvider;

/**
 * Abstract base class for Ascio v3 API integration tests
 *
 * @group integration
 */
abstract class IntegrationTestBase extends TestCase
{
    /** @var array Base WHMCS module parameters */
    protected array $params;

    /** @var ?string Ascio test account username */
    protected ?string $username;

    /** @var ?string Ascio test account password */
    protected ?string $password;

    /** @var ?Request Request instance (created lazily) */
    protected ?Request $request = null;

    /** @var bool Enable simulation mode (ValidateOrder instead of CreateOrder) */
    protected bool $simulationMode = true;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset mock state
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();

        // Load credentials
        $creds = IntegrationTestCredentials::get();
        $this->username = $creds['username'];
        $this->password = $creds['password'];

        if (!IntegrationTestCredentials::available()) {
            $this->markTestSkipped(
                'Ascio credentials not available. Set ASCIO_TEST_USERNAME and ASCIO_TEST_PASSWORD environment variables.'
            );
        }

        // Enable simulation mode if configured
        if ($this->simulationMode) {
            putenv('ASCIO_SIMULATE=1');
        }

        // Set up base parameters
        $this->params = $this->getBaseParams();
    }

    /**
     * Tear down test environment
     */
    protected function tearDown(): void
    {
        // Clear simulation mode
        putenv('ASCIO_SIMULATE');

        parent::tearDown();
    }

    /**
     * Get base WHMCS module parameters with test credentials
     */
    protected function getBaseParams(): array
    {
        return array_merge(MockParamsV3::getDefault(), [
            'Username' => $this->username,
            'Password' => $this->password,
            'TestMode' => 'on',
            'Simulate' => $this->simulationMode ? 'on' : 'off',
        ]);
    }

    /**
     * Get a configured Request instance
     */
    protected function getRequest(?array $params = null): Request
    {
        $params = $params ?? $this->params;
        return new Request($params);
    }

    /**
     * Create parameters for a specific domain registration
     */
    protected function getRegistrationParams(string $domainName, array $overrides = []): array
    {
        $parts = explode('.', $domainName, 2);
        $sld = $parts[0];
        $tld = $parts[1] ?? 'com';

        return array_merge($this->params, MockParamsV3::forTld($tld, [
            'domainname' => $domainName,
            'sld' => $sld,
            'tld' => $tld,
            'Username' => $this->username,
            'Password' => $this->password,
            'TestMode' => 'on',
        ]), $overrides);
    }

    /**
     * Create parameters for a domain transfer
     */
    protected function getTransferParams(string $domainName, string $eppCode, array $overrides = []): array
    {
        return $this->getRegistrationParams($domainName, array_merge([
            'eppcode' => $eppCode,
        ], $overrides));
    }

    // =========================================================================
    // API Helper Methods
    // =========================================================================

    /**
     * Call ValidateOrder API to validate an order without creating it
     *
     * @param array $orderData The order data to validate
     * @return object|array API response
     */
    protected function validateOrder(array $orderData)
    {
        $request = $this->getRequest($orderData);

        // Build the order using mapToOrder
        $orderType = $orderData['orderType'] ?? 'Register';
        $ascioParams = $request->mapToOrder($orderData, $orderType);

        // Call ValidateOrder API
        return $this->callApiMethod('ValidateOrder', $ascioParams);
    }

    /**
     * Call a raw Ascio API method
     *
     * @param string $method API method name
     * @param array $params API parameters
     * @return object|array API response
     * @throws \SoapFault If SOAP call fails
     */
    protected function callApiMethod(string $method, array $params)
    {
        if (!$this->username || !$this->password) {
            $this->markTestSkipped('Ascio credentials not configured');
        }

        try {
            $wsdl = ASCIO_V3_WSDL_TEST;
            $client = new \SoapClient($wsdl, [
                'cache_wsdl' => WSDL_CACHE_MEMORY,
                'trace' => 1,
                'exceptions' => true,
                'connection_timeout' => 30,
            ]);

            $credentials = [
                'Account' => $this->username,
                'Password' => $this->password,
            ];
            $header = new \SoapHeader(
                'http://www.ascio.com/2013/02',
                'SecurityHeaderDetails',
                $credentials,
                false
            );
            $client->__setSoapHeaders($header);

            $response = $client->__soapCall($method, ['parameters' => ['request' => $params]]);
            $resultName = $method . 'Result';

            return $response->$resultName ?? $response;
        } catch (\SoapFault $e) {
            // Check if it's an authentication error
            if (strpos($e->getMessage(), 'Login failed') !== false ||
                strpos($e->getMessage(), 'Authentication') !== false) {
                $this->markTestSkipped('Invalid Ascio credentials: ' . $e->getMessage());
            }

            // Handle internal server errors - skip test rather than fail
            if (strpos($e->getMessage(), 'internal error') !== false) {
                $this->markTestSkipped('Ascio API internal error (temporary): ' . $e->getMessage());
            }

            // Handle unsupported methods - skip test
            if (strpos($e->getMessage(), 'is not a valid method') !== false) {
                $this->markTestSkipped('API method not supported: ' . $e->getMessage());
            }

            // Re-throw for other SOAP faults - these indicate real API issues
            throw $e;
        }
    }

    /**
     * Call API method with graceful error handling for integration tests
     * Returns the result or null if the API is unavailable
     *
     * @param string $method API method name
     * @param array $params API parameters
     * @return object|array|null API response or null on transient failure
     */
    protected function callApiMethodSafe(string $method, array $params)
    {
        try {
            return $this->callApiMethod($method, $params);
        } catch (\SoapFault $e) {
            // Return a mock error response for testing error handling
            return (object) [
                'ResultCode' => 500,
                'ResultMessage' => 'API Error: ' . $e->getMessage(),
                'Errors' => (object) ['string' => [$e->getMessage()]],
            ];
        }
    }

    /**
     * Find an existing domain on the test account by TLD
     *
     * @param string $tld TLD to search for (e.g., 'com', 'net')
     * @return object|null Domain object if found
     */
    protected function findExistingDomain(string $tld): ?object
    {
        $request = $this->getRequest();

        // Try searching with GetDomains filter
        $criteria = [
            'Mode' => 'Strict',
            'WithoutStates' => ['deleted'],
            'Clauses' => [
                [
                    'Attribute' => 'Name',
                    'Value' => '*.' . $tld,
                    'Operator' => 'Like',
                ],
            ],
            'PageInfo' => [
                'PageIndex' => 0,
                'PageSize' => 1,
            ],
        ];

        try {
            $result = $this->callApiMethod('SearchDomain', ['Criteria' => $criteria]);

            if (isset($result->Domains->Domain)) {
                $domains = $result->Domains->Domain;
                return is_array($domains) ? $domains[0] : $domains;
            }
        } catch (\SoapFault $e) {
            // Log but don't fail - domain may not exist
            error_log('SearchDomain failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get domain by handle
     *
     * @param string $handle Domain handle
     * @return object|null Domain object if found
     */
    protected function getDomainByHandle(string $handle): ?object
    {
        try {
            $result = $this->callApiMethod('GetDomain', ['DomainHandle' => $handle]);

            if (isset($result->Domain)) {
                return $result->Domain;
            }

            return $result;
        } catch (\SoapFault $e) {
            error_log('GetDomain failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Mock a callback processing scenario
     *
     * @param string $orderId Order ID
     * @param string $status Order status (e.g., 'Completed', 'Failed', 'Pending_End_User_Action')
     * @param string $message Optional message
     * @return array Mocked callback data
     */
    protected function mockCallback(string $orderId, string $status, string $message = ''): array
    {
        return [
            'MessageId' => 'MSG-' . uniqid(),
            'OrderId' => $orderId,
            'OrderStatus' => $status,
            'OrderType' => 'Register',
            'Name' => 'test-callback.com',
            'Message' => $message,
            'StatusList' => [
                'CallbackStatus' => $status === 'Failed' ? [
                    [
                        'Message' => $message ?: 'Order failed due to validation error',
                        'Status' => 'Failed',
                    ],
                ] : [],
            ],
        ];
    }

    // =========================================================================
    // Assertion Helpers
    // =========================================================================

    /**
     * Assert v3 API response has valid format
     *
     * @param mixed $response API response
     * @param string $expectedMethod Expected method name (for result key lookup)
     */
    protected function assertV3ResponseFormat($response, string $expectedMethod = ''): void
    {
        $this->assertNotNull($response, 'Response should not be null');

        if (is_array($response)) {
            // Error response
            if (isset($response['error'])) {
                $this->assertIsString($response['error'], 'Error message should be a string');
            }
            return;
        }

        $this->assertIsObject($response, 'Response should be an object');

        // Check for ResultCode
        if (isset($response->ResultCode)) {
            $this->assertIsInt($response->ResultCode, 'ResultCode should be an integer');
        }

        // Check for ResultMessage
        if (isset($response->ResultMessage)) {
            $this->assertIsString($response->ResultMessage, 'ResultMessage should be a string');
        }
    }

    /**
     * Assert API call was successful (ResultCode 200 or 201)
     *
     * @param mixed $response API response
     */
    protected function assertApiSuccess($response): void
    {
        $this->assertV3ResponseFormat($response);

        if (is_array($response)) {
            $this->assertArrayNotHasKey('error', $response, 'Response should not contain error: ' . ($response['error'] ?? ''));
        } else {
            $resultCode = $response->ResultCode ?? 0;
            $this->assertContains(
                $resultCode,
                [200, 201, 413], // 200=success, 201=pending, 413=waiting for registry
                'ResultCode should indicate success: ' . ($response->ResultMessage ?? 'Unknown error') . ' (Code: ' . $resultCode . ')'
            );
        }
    }

    /**
     * Assert API call returned validation success
     *
     * @param mixed $response ValidateOrder API response
     */
    protected function assertValidationSuccess($response): void
    {
        $this->assertV3ResponseFormat($response);

        if (is_array($response)) {
            $this->assertArrayNotHasKey('error', $response, 'Validation should not return error: ' . ($response['error'] ?? ''));
        } else {
            $resultCode = $response->ResultCode ?? 0;
            // 200 = valid, 400 = validation errors
            $this->assertEquals(
                200,
                $resultCode,
                'Validation should succeed (ResultCode 200): ' . ($response->ResultMessage ?? 'Unknown error') . $this->formatErrors($response)
            );
        }
    }

    /**
     * Assert order structure is correct
     *
     * @param array $order Mapped order array
     * @param string $expectedType Expected order type
     */
    protected function assertOrderStructure(array $order, string $expectedType): void
    {
        $this->assertArrayHasKey('Order', $order, 'Order should have Order key');
        $this->assertArrayHasKey('Type', $order['Order'], 'Order should have Type');
        $this->assertEquals($expectedType, $order['Order']['Type'], 'Order type should match');
        $this->assertArrayHasKey('Domain', $order['Order'], 'Order should have Domain');
        $this->assertArrayHasKey('Name', $order['Order']['Domain'], 'Domain should have Name');
    }

    /**
     * Format errors from API response for assertion messages
     *
     * @param object $response API response
     * @return string Formatted error messages
     */
    protected function formatErrors($response): string
    {
        if (!isset($response->Errors)) {
            return '';
        }

        $errors = $response->Errors->string ?? [];
        if (!is_array($errors)) {
            $errors = [$errors];
        }

        if (empty($errors)) {
            return '';
        }

        return "\nErrors: " . implode('; ', $errors);
    }

    // =========================================================================
    // Test Data Generators
    // =========================================================================

    /**
     * Generate a unique test domain name
     */
    protected function generateTestDomain(string $tld = 'com'): string
    {
        return TestDomainProvider::generateTestDomain($tld);
    }

    /**
     * Get valid contact data for a specific country
     */
    protected function getContactDataForCountry(string $countryCode): array
    {
        $contacts = [
            'US' => [
                'firstname' => 'John',
                'lastname' => 'Doe',
                'companyname' => 'Test Company Inc',
                'address1' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'postcode' => '10001',
                'country' => 'US',
                'fullphonenumber' => '+1.2125551234',
                'email' => 'test@example.com',
            ],
            'DE' => [
                'firstname' => 'Hans',
                'lastname' => 'Mueller',
                'companyname' => 'Test GmbH',
                'address1' => 'Hauptstrasse 1',
                'city' => 'Berlin',
                'state' => 'BE',
                'postcode' => '10115',
                'country' => 'DE',
                'fullphonenumber' => '+49.301234567',
                'email' => 'test@example.de',
            ],
            'GB' => [
                'firstname' => 'James',
                'lastname' => 'Smith',
                'companyname' => 'Test Ltd',
                'address1' => '1 High Street',
                'city' => 'London',
                'state' => 'England',
                'postcode' => 'SW1A 1AA',
                'country' => 'GB',
                'fullphonenumber' => '+44.2012345678',
                'email' => 'test@example.co.uk',
            ],
        ];

        return $contacts[$countryCode] ?? $contacts['US'];
    }
}
