<?php
/**
 * Base SSL Integration Test
 *
 * Abstract base class providing common functionality for SSL integration tests.
 * All SSL integration tests should extend this class.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ascio\v3 as v3;

require_once __DIR__ . '/bootstrap.php';

abstract class SslIntegrationTestBase extends TestCase
{
    protected ?v3\AscioService $client = null;
    protected TestConfig $config;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = TestConfig::getInstance();

        // Skip tests if no credentials configured
        if (empty($this->config->account) || $this->config->account === 'ascio_test') {
            $this->markTestSkipped(
                'Ascio test credentials not configured. Set ASCIO_TEST_ACCOUNT and ASCIO_TEST_PASSWORD environment variables.'
            );
        }

        $this->client = $this->config->createClient();
    }

    /**
     * Tear down test fixtures
     */
    protected function tearDown(): void
    {
        $this->client = null;
        parent::tearDown();
    }

    /**
     * Validate an SSL certificate order without creating it
     *
     * Uses ValidateOrder API to check if order would be accepted
     * without actually creating the order.
     *
     * @param v3\SslCertificateOrderRequest $request The order request to validate
     * @return v3\ValidateOrderResponse
     */
    protected function validateSslOrder(v3\SslCertificateOrderRequest $request): object
    {
        $validateOrder = new v3\ValidateOrder($request);
        return $this->client->ValidateOrder($validateOrder);
    }

    /**
     * Find an existing SSL certificate on the test account
     *
     * Useful for GetSslCertificate tests that need existing certificates.
     *
     * @param string|null $handle Optional specific certificate handle to retrieve
     * @return v3\SslCertificateInfo|null
     */
    protected function findExistingCertificate(?string $handle = null): ?v3\SslCertificateInfo
    {
        if ($handle !== null) {
            try {
                $request = new v3\GetSslCertificateRequest();
                $request->setHandle($handle);
                $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));

                if ($response->GetSslCertificateResult->getResultCode() === 200) {
                    return $response->GetSslCertificateResult->getSslCertificateInfo();
                }
            } catch (\Exception $e) {
                // Certificate not found
            }
        }

        return null;
    }

    /**
     * Mock a callback for testing callback processing logic
     *
     * @param string $orderId The order ID
     * @param string $status The order status (Pending, Pending_End_User_Action, Completed, Failed)
     * @param string|null $message Optional callback message
     * @return array Mocked callback data
     */
    protected function mockCallback(string $orderId, string $status, ?string $message = null): array
    {
        return [
            'orderId' => $orderId,
            'status' => $status,
            'messageId' => 'MSG' . uniqid(),
            'message' => $message ?? $this->getDefaultCallbackMessage($status),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get default callback message for a status
     */
    private function getDefaultCallbackMessage(string $status): string
    {
        return match ($status) {
            'Pending' => 'Order is being processed.',
            'Pending_End_User_Action' => "AuthName: _ascio-validation.example.com\nAuthValue: ascio-validation-token-" . uniqid(),
            'Completed' => 'SSL Certificate has been issued successfully.',
            'Failed' => 'Order validation failed. Please check the submitted data.',
            'Invalid' => 'Order is invalid. Missing required fields.',
            default => 'Status update: ' . $status,
        };
    }

    /**
     * Build an SslCertificate object for order requests
     *
     * @param array $params Certificate parameters
     * @return v3\SslCertificate
     */
    protected function buildSslCertificate(array $params): v3\SslCertificate
    {
        $certificate = new v3\SslCertificate();

        if (!empty($params['handle'])) {
            $certificate->setHandle($params['handle']);
        }

        if (!empty($params['commonName'])) {
            $certificate->setCommonName($params['commonName']);
        }

        $certificate->setProductCode($params['productCode'] ?? 'positivessl');
        $certificate->setWebServerType($params['webServerType'] ?? v3\WebServerType::ApacheSsl);
        $certificate->setApproverEmail($params['approverEmail'] ?? 'admin@' . ($params['commonName'] ?? 'example.com'));
        $certificate->setCSR($params['csr'] ?? '');
        $certificate->setValidationType($params['validationType'] ?? v3\SslDomainValidationType::Dns);

        if (!empty($params['sanNames'])) {
            $certificate->setSanNames($params['sanNames']);
        }

        if (!empty($params['objectComment'])) {
            $certificate->setObjectComment($params['objectComment']);
        }

        return $certificate;
    }

    /**
     * Build an SslCertificateOrderRequest for validation/creation
     *
     * @param array $params Order parameters
     * @return v3\SslCertificateOrderRequest
     */
    protected function buildSslCertificateOrderRequest(array $params): v3\SslCertificateOrderRequest
    {
        $request = new v3\SslCertificateOrderRequest();
        $request->setType($params['orderType'] ?? v3\OrderType::Register);
        $request->setPeriod($params['period'] ?? 1);
        $request->setTransactionComment($params['transactionComment'] ?? 'PHPUnit Integration Test');

        // Build certificate
        $certificate = $this->buildSslCertificate($params);

        // Add contacts
        if (!empty($params['owner'])) {
            $certificate->setOwner($params['owner']);
        } else {
            $certificate->setOwner(TestDataFactory::buildRegistrant(TestDataFactory::createContactData('owner')));
        }

        if (!empty($params['admin'])) {
            $certificate->setAdmin($params['admin']);
        } else {
            $certificate->setAdmin(TestDataFactory::buildContact(TestDataFactory::createContactData('admin')));
        }

        if (!empty($params['tech'])) {
            $certificate->setTech($params['tech']);
        } else {
            $certificate->setTech(TestDataFactory::buildContact(TestDataFactory::createContactData('tech')));
        }

        $request->setSslCertificate($certificate);

        return $request;
    }

    /**
     * Build contacts from WHMCS-style parameters
     *
     * @param array $params WHMCS contact parameters
     * @return array Array with owner, admin, tech contacts
     */
    protected function buildContacts(array $params): array
    {
        $contacts = [];

        // Build owner (Registrant)
        $owner = new v3\Registrant();
        $owner->setFirstName($params['ownerFirstName'] ?? 'Test');
        $owner->setLastName($params['ownerLastName'] ?? 'Owner');
        $owner->setOrgName($params['ownerCompanyName'] ?? '');
        $owner->setAddress1($params['ownerAddress1'] ?? 'Test Street 1');
        $owner->setAddress2($params['ownerAddress2'] ?? '');
        $owner->setCity($params['ownerCity'] ?? 'Munich');
        $owner->setState($params['ownerState'] ?? 'Bavaria');
        $owner->setPostalCode($params['ownerPostcode'] ?? '80331');
        $owner->setCountryCode($params['ownerCountry'] ?? 'DE');
        $owner->setPhone($this->formatPhone($params['ownerPhonePrefix'] ?? '+49', $params['phonenumberowner'] ?? '891234567'));
        $owner->setEmail($params['ownerEmail'] ?? 'owner@example.com');
        $contacts['owner'] = $owner;

        // Build admin (Contact)
        $admin = new v3\Contact();
        $admin->setFirstName($params['adminFirstName'] ?? 'Test');
        $admin->setLastName($params['adminLastName'] ?? 'Admin');
        $admin->setOrgName($params['adminCompanyName'] ?? '');
        $admin->setAddress1($params['adminAddress1'] ?? 'Test Street 1');
        $admin->setAddress2($params['adminAddress2'] ?? '');
        $admin->setCity($params['adminCity'] ?? 'Munich');
        $admin->setState($params['adminState'] ?? 'Bavaria');
        $admin->setPostalCode($params['adminPostcode'] ?? '80331');
        $admin->setCountryCode($params['adminCountry'] ?? 'DE');
        $admin->setPhone($this->formatPhone($params['adminPhonePrefix'] ?? '+49', $params['phonenumberadmin'] ?? '891234567'));
        $admin->setEmail($params['adminEmail'] ?? 'admin@example.com');
        $contacts['admin'] = $admin;

        // Build tech (Contact)
        $tech = new v3\Contact();
        $tech->setFirstName($params['techFirstName'] ?? 'Test');
        $tech->setLastName($params['techLastName'] ?? 'Tech');
        $tech->setOrgName($params['techCompanyName'] ?? '');
        $tech->setAddress1($params['techAddress1'] ?? 'Test Street 1');
        $tech->setAddress2($params['techAddress2'] ?? '');
        $tech->setCity($params['techCity'] ?? 'Munich');
        $tech->setState($params['techState'] ?? 'Bavaria');
        $tech->setPostalCode($params['techPostcode'] ?? '80331');
        $tech->setCountryCode($params['techCountry'] ?? 'DE');
        $tech->setPhone($this->formatPhone($params['techPhonePrefix'] ?? '+49', $params['phonenumbertech'] ?? '891234567'));
        $tech->setEmail($params['techEmail'] ?? 'tech@example.com');
        $contacts['tech'] = $tech;

        return $contacts;
    }

    /**
     * Format phone number to Ascio format (+CC.number)
     *
     * @param string $prefix Country calling code with + prefix
     * @param string $number Phone number
     * @return string Formatted phone number
     */
    protected function formatPhone(string $prefix, string $number): string
    {
        // Remove any spaces from number
        $number = preg_replace('/\s+/', '', $number);

        // Ensure prefix starts with +
        if (!str_starts_with($prefix, '+')) {
            $prefix = '+' . $prefix;
        }

        // Remove + and add dot separator
        $prefix = ltrim($prefix, '+');

        return '+' . $prefix . '.' . $number;
    }

    /**
     * Assert that a response has a successful result code
     *
     * @param object $response The API response
     * @param int $expectedCode Expected result code (default: 200)
     */
    protected function assertSuccessResponse(object $response, int $expectedCode = 200): void
    {
        $resultProperty = $this->findResultProperty($response);
        $this->assertNotNull($resultProperty, 'Response does not contain a result property');

        $result = $response->$resultProperty;
        $this->assertEquals(
            $expectedCode,
            $result->getResultCode(),
            'Expected result code ' . $expectedCode . ', got ' . $result->getResultCode() . ': ' . $result->getResultMessage()
        );
    }

    /**
     * Assert that a response has an error result code
     *
     * @param object $response The API response
     * @param int|null $expectedCode Optional expected error code
     */
    protected function assertErrorResponse(object $response, ?int $expectedCode = null): void
    {
        $resultProperty = $this->findResultProperty($response);
        $this->assertNotNull($resultProperty, 'Response does not contain a result property');

        $result = $response->$resultProperty;
        $resultCode = $result->getResultCode();

        if ($expectedCode !== null) {
            $this->assertEquals(
                $expectedCode,
                $resultCode,
                'Expected error code ' . $expectedCode . ', got ' . $resultCode
            );
        } else {
            $this->assertNotEquals(
                200,
                $resultCode,
                'Expected error response, got success (200)'
            );
        }
    }

    /**
     * Find the result property in a response object
     */
    private function findResultProperty(object $response): ?string
    {
        $reflection = new \ReflectionClass($response);
        foreach ($reflection->getProperties() as $property) {
            if (str_ends_with($property->getName(), 'Result')) {
                return $property->getName();
            }
        }
        return null;
    }

    /**
     * Generate a test CSR for a domain
     *
     * @param string $commonName The common name (domain) for the certificate
     * @return string The CSR in PEM format
     */
    protected function generateTestCsr(string $commonName): string
    {
        $csrData = TestCsrGenerator::generate($commonName);
        return $csrData['csr'];
    }

    /**
     * Generate a wildcard test CSR
     *
     * @param string $domain The base domain (without wildcard prefix)
     * @return string The CSR in PEM format
     */
    protected function generateWildcardCsr(string $domain): string
    {
        $csrData = TestCsrGenerator::generateWildcard($domain);
        return $csrData['csr'];
    }

    /**
     * Get a unique test domain name
     *
     * @param string $prefix Optional prefix for the domain
     * @return string A unique domain name
     */
    protected function getTestDomain(string $prefix = 'ssl-test'): string
    {
        return TestDataFactory::generateDomain($prefix);
    }
}
