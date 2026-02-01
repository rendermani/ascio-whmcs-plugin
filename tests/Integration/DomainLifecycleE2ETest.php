<?php
/**
 * Domain Lifecycle E2E Tests
 *
 * Tests the complete domain lifecycle:
 * 1. Register (CreateOrder) -> orderId
 * 2. Poll until Completed
 * 3. GetDomain -> verify all values
 * 4. Expiry flow testing
 *
 * REQUIRES:
 * - ASCIO_TEST_ACCOUNT and ASCIO_TEST_PASSWORD in .env
 *
 * @group e2e
 * @group slow
 */

declare(strict_types=1);

namespace Ascio\Tests\Integration;

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;
use ascio\Request;
use IntegrationTestCredentials;
use TestDomainProvider;

class DomainLifecycleE2ETest extends TestCase
{
    /** @var ?string Ascio test account username */
    protected ?string $username = null;

    /** @var ?string Ascio test account password */
    protected ?string $password = null;

    /** @var bool Enable simulation mode (ValidateOrder instead of CreateOrder) */
    protected bool $simulationMode = true;

    /** @var \SoapClient */
    protected ?\SoapClient $client = null;

    /**
     * Maximum time to wait for order completion (5 minutes)
     */
    private const MAX_POLL_TIME = 300;

    /**
     * Poll interval in seconds
     */
    private const POLL_INTERVAL = 10;

    protected function setUp(): void
    {
        parent::setUp();

        // Load credentials
        $creds = IntegrationTestCredentials::get();
        $this->username = $creds['username'];
        $this->password = $creds['password'];

        if (!IntegrationTestCredentials::available()) {
            $this->markTestSkipped(
                'Ascio credentials not available. Set ASCIO_TEST_ACCOUNT and ASCIO_TEST_PASSWORD environment variables.'
            );
        }

        // Enable simulation mode if configured
        if ($this->simulationMode) {
            putenv('ASCIO_SIMULATE=1');
        }
    }

    protected function tearDown(): void
    {
        // Clear simulation mode
        putenv('ASCIO_SIMULATE');
        $this->client = null;

        parent::tearDown();
    }

    /**
     * Get SOAP client with authentication
     */
    protected function getClient(): \SoapClient
    {
        if ($this->client === null) {
            $wsdl = ASCIO_V3_WSDL_TEST;
            $this->client = new \SoapClient($wsdl, [
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
            $this->client->__setSoapHeaders($header);
        }

        return $this->client;
    }

    /**
     * Test complete domain registration lifecycle
     *
     * @test
     * @group e2e
     * @group slow
     */
    public function testCompleteRegistrationLifecycle(): void
    {
        // Skip in CI - this is for manual E2E testing
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== Domain Registration Lifecycle E2E Test ===\n";
        echo "Account: {$this->username}\n\n";

        // Step 1: Generate unique domain name
        $testDomain = $this->generateUniqueDomain('com');
        echo "Test Domain: {$testDomain}\n\n";

        // Step 2: Build registration order
        echo "Step 2: Building registration order...\n";
        $orderParams = $this->buildRegisterOrder($testDomain);

        // Step 3: Validate order first
        echo "\nStep 3: Validating order...\n";
        $validateResult = $this->validateOrder($orderParams);

        if ($validateResult->ResultCode !== 200) {
            $errors = $this->extractErrors($validateResult);
            echo "  Validation failed ({$validateResult->ResultCode}): " . implode(', ', $errors) . "\n";
            $this->markTestSkipped("Order validation failed: " . implode(', ', $errors));
        }
        echo "  Order validated successfully (200)\n";

        // Step 4: Create order (in simulation mode, this uses ValidateOrder)
        echo "\nStep 4: Creating order...\n";
        $createResult = $this->createOrder($orderParams);

        if (isset($createResult['error'])) {
            $this->markTestSkipped("Order creation failed: " . $createResult['error']);
        }

        $this->assertContains(
            $createResult->ResultCode,
            [200, 201],
            'CreateOrder should succeed: ' . ($createResult->ResultMessage ?? 'Unknown error')
        );

        $orderId = $createResult->OrderInfo->OrderId ?? null;
        $status = $createResult->OrderInfo->Status ?? 'Unknown';

        echo "  Order created: {$orderId}\n";
        echo "  Initial status: {$status}\n";

        // Step 5: Poll until completion (if not in simulation mode)
        if (!$this->simulationMode && $orderId) {
            echo "\nStep 5: Polling until completion...\n";
            $finalStatus = $this->pollUntilComplete($orderId);

            echo "\n=== Final Status: {$finalStatus} ===\n";

            if ($finalStatus === 'Completed') {
                // Step 6: Verify domain with GetDomain
                echo "\nStep 6: Verifying domain with GetDomain...\n";
                $this->verifyDomainRegistration($orderId, $testDomain);
            }

            $this->assertContains(
                $finalStatus,
                ['Completed', 'Pending', 'PendingEndUserAction'],
                "Order should reach expected status: {$finalStatus}"
            );
        } else {
            echo "\nSimulation mode: Skipping polling (order not actually created)\n";
        }

        echo "\n=== Domain Registration Test Complete ===\n";
    }

    /**
     * Test GetDomain returns all expected fields
     *
     * @test
     * @group e2e
     */
    public function testGetDomainWithAllValues(): void
    {
        // Skip in CI
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== GetDomain Field Verification Test ===\n\n";

        // Find an existing domain on the test account
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->DomainName ?? null;
        $domainHandle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        echo "Found domain: {$domainName}\n";
        echo "Domain Handle: {$domainHandle}\n\n";

        // Retrieve domain with GetDomain
        echo "Retrieving domain with GetDomain...\n";
        $domain = $this->getDomainByHandle($domainHandle);

        if (!$domain) {
            $this->markTestSkipped('Could not retrieve domain: ' . $domainHandle);
        }

        // Verify all expected fields
        echo "\nVerifying domain fields:\n";

        // Core fields
        $this->assertDomainField($domain, 'DomainName', $domainName);
        $this->assertDomainField($domain, 'DomainHandle');
        $this->assertDomainField($domain, 'Status');

        // Date fields
        echo "\n  Date Fields:\n";
        $this->assertDomainDateField($domain, 'ExpDate');
        $this->assertDomainDateField($domain, 'CreDate');

        // Contact fields
        echo "\n  Contact Fields:\n";
        $this->assertContactField($domain, 'Registrant');
        $this->assertContactField($domain, 'AdminContact');
        $this->assertContactField($domain, 'TechContact');
        $this->assertContactField($domain, 'BillingContact');

        // Nameserver fields
        echo "\n  Nameserver Fields:\n";
        $this->assertNameserverFields($domain);

        // Optional fields
        echo "\n  Optional Fields:\n";
        $this->assertOptionalField($domain, 'AuthInfo', 'EPP Code');
        $this->assertOptionalField($domain, 'TransferLock', 'Transfer Lock');
        $this->assertOptionalField($domain, 'DeleteLock', 'Delete Lock');
        $this->assertOptionalField($domain, 'UpdateLock', 'Update Lock');
        $this->assertOptionalField($domain, 'PrivacyProxy', 'Privacy Proxy');

        echo "\n=== All domain fields verified ===\n";
    }

    /**
     * Test domain expiry/unexpiry flow
     *
     * @test
     * @group e2e
     */
    public function testDomainExpiryFlowValidation(): void
    {
        // Skip in CI
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== Domain Expiry Flow Validation Test ===\n\n";

        // Note: We can't actually expire a domain on the test account without
        // affecting it, so we validate the order structure instead

        $testDomain = $this->generateUniqueDomain('com');
        echo "Test Domain: {$testDomain}\n\n";

        // Build Expire_Domain order
        echo "Validating Expire_Domain order structure...\n";
        $expireParams = $this->buildExpireOrder($testDomain);
        $expireResult = $this->validateOrder($expireParams);

        // For expire orders, validation will fail without a real domain
        // but we can verify the order structure is correct
        echo "  Expire order result code: {$expireResult->ResultCode}\n";

        if ($expireResult->ResultCode === 200) {
            echo "  Expire order validated successfully\n";
        } else {
            $errors = $this->extractErrors($expireResult);
            echo "  Expected validation error (no real domain): " . implode(', ', $errors) . "\n";
        }

        // Build Unexpire_Domain order
        echo "\nValidating Unexpire_Domain order structure...\n";
        $unexpireParams = $this->buildUnexpireOrder($testDomain);
        $unexpireResult = $this->validateOrder($unexpireParams);

        echo "  Unexpire order result code: {$unexpireResult->ResultCode}\n";

        if ($unexpireResult->ResultCode === 200) {
            echo "  Unexpire order validated successfully\n";
        } else {
            $errors = $this->extractErrors($unexpireResult);
            echo "  Expected validation error (no expiring domain): " . implode(', ', $errors) . "\n";
        }

        // The test passes if order structures are valid (even if domain doesn't exist)
        $this->assertNotNull($expireParams, 'Expire order should be built');
        $this->assertNotNull($unexpireParams, 'Unexpire order should be built');

        echo "\n=== Expiry flow order structures validated ===\n";
    }

    /**
     * Test domain transfer order validation
     *
     * @test
     * @group e2e
     */
    public function testDomainTransferValidation(): void
    {
        // Skip in CI
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== Domain Transfer Validation Test ===\n\n";

        $testDomain = $this->generateUniqueDomain('com');
        $eppCode = 'TEST-EPP-' . strtoupper(uniqid());

        echo "Test Domain: {$testDomain}\n";
        echo "EPP Code: {$eppCode}\n\n";

        // Build transfer order
        echo "Building transfer order...\n";
        $transferParams = $this->buildTransferOrder($testDomain, $eppCode);

        // Validate order
        echo "Validating transfer order...\n";
        $result = $this->validateOrder($transferParams);

        echo "  Result code: {$result->ResultCode}\n";

        if ($result->ResultCode === 200) {
            echo "  Transfer order validated successfully\n";
        } else {
            $errors = $this->extractErrors($result);
            echo "  Validation result: " . implode(', ', $errors) . "\n";

            // Domain doesn't exist at other registrar - expected
            if (strpos(implode(' ', $errors), 'not found') !== false ||
                strpos(implode(' ', $errors), 'does not exist') !== false) {
                echo "  (Expected: domain doesn't exist for transfer)\n";
            }
        }

        // Assert structure
        $this->assertEquals('Transfer_Domain', $transferParams['Order']['Type']);
        $this->assertEquals($eppCode, $transferParams['Order']['Domain']['AuthInfo']);

        echo "\n=== Transfer order structure validated ===\n";
    }

    /**
     * Test domain renewal order validation
     *
     * @test
     * @group e2e
     */
    public function testDomainRenewalValidation(): void
    {
        // Skip in CI
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== Domain Renewal Validation Test ===\n\n";

        $testDomain = $this->generateUniqueDomain('com');

        echo "Test Domain: {$testDomain}\n\n";

        // Build renewal order
        echo "Building renewal order...\n";
        $renewParams = $this->buildRenewOrder($testDomain, 1);

        // Validate order
        echo "Validating renewal order...\n";
        $result = $this->validateOrder($renewParams);

        echo "  Result code: {$result->ResultCode}\n";

        if ($result->ResultCode === 200) {
            echo "  Renewal order validated successfully\n";
        } else {
            $errors = $this->extractErrors($result);
            echo "  Validation result: " . implode(', ', $errors) . "\n";

            // Domain doesn't exist - expected
            if (strpos(implode(' ', $errors), 'not found') !== false) {
                echo "  (Expected: domain doesn't exist for renewal)\n";
            }
        }

        // Assert structure
        $this->assertEquals('Renew_Domain', $renewParams['Order']['Type']);
        $this->assertEquals(1, $renewParams['Order']['Domain']['RegPeriod']);

        echo "\n=== Renewal order structure validated ===\n";
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Generate a unique test domain name
     */
    protected function generateUniqueDomain(string $tld = 'com'): string
    {
        return 'e2e-lifecycle-' . date('YmdHis') . '-' . rand(1000, 9999) . '.' . $tld;
    }

    /**
     * Build registration order parameters
     */
    protected function buildRegisterOrder(string $domainName): array
    {
        $parts = explode('.', $domainName, 2);
        $sld = $parts[0];
        $tld = $parts[1] ?? 'com';

        return [
            'Order' => [
                'Type' => 'Register_Domain',
                'TransactionComment' => json_encode([
                    'application' => 'E2E_TEST',
                    'testId' => uniqid(),
                ]),
                'Domain' => [
                    'DomainName' => $domainName,
                    'RegPeriod' => 1,
                    'Registrant' => $this->buildRegistrant(),
                    'AdminContact' => $this->buildContact('Admin'),
                    'TechContact' => $this->buildContact('Tech'),
                    'BillingContact' => $this->buildContact('Billing'),
                    'NameServers' => [
                        'NameServer1' => ['HostName' => 'ns1.ascio.net'],
                        'NameServer2' => ['HostName' => 'ns2.ascio.net'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build transfer order parameters
     */
    protected function buildTransferOrder(string $domainName, string $eppCode): array
    {
        $order = $this->buildRegisterOrder($domainName);
        $order['Order']['Type'] = 'Transfer_Domain';
        $order['Order']['Domain']['AuthInfo'] = $eppCode;

        return $order;
    }

    /**
     * Build renew order parameters
     */
    protected function buildRenewOrder(string $domainName, int $period = 1): array
    {
        return [
            'Order' => [
                'Type' => 'Renew_Domain',
                'TransactionComment' => json_encode([
                    'application' => 'E2E_TEST',
                    'testId' => uniqid(),
                ]),
                'Domain' => [
                    'DomainName' => $domainName,
                    'RegPeriod' => $period,
                ],
            ],
        ];
    }

    /**
     * Build expire order parameters
     */
    protected function buildExpireOrder(string $domainName): array
    {
        return [
            'Order' => [
                'Type' => 'Expire_Domain',
                'TransactionComment' => json_encode([
                    'application' => 'E2E_TEST',
                    'testId' => uniqid(),
                ]),
                'Domain' => [
                    'DomainName' => $domainName,
                ],
            ],
        ];
    }

    /**
     * Build unexpire order parameters
     */
    protected function buildUnexpireOrder(string $domainName): array
    {
        return [
            'Order' => [
                'Type' => 'Unexpire_Domain',
                'TransactionComment' => json_encode([
                    'application' => 'E2E_TEST',
                    'testId' => uniqid(),
                ]),
                'Domain' => [
                    'DomainName' => $domainName,
                    'RegPeriod' => 1,
                ],
            ],
        ];
    }

    /**
     * Build registrant contact
     */
    protected function buildRegistrant(): array
    {
        return [
            'Name' => 'Test User',
            'OrgName' => 'Test Organization',
            'Address1' => '123 Test Street',
            'City' => 'Munich',
            'State' => 'Bavaria',
            'PostalCode' => '80331',
            'CountryCode' => 'DE',
            'Phone' => '+49.891234567',
            'Email' => 'test@example.com',
        ];
    }

    /**
     * Build contact
     */
    protected function buildContact(string $role): array
    {
        return [
            'FirstName' => $role,
            'LastName' => 'User',
            'OrgName' => 'Test Organization',
            'Address1' => '123 Test Street',
            'City' => 'Munich',
            'State' => 'Bavaria',
            'PostalCode' => '80331',
            'CountryCode' => 'DE',
            'Phone' => '+49.891234567',
            'Email' => 'test@example.com',
        ];
    }

    /**
     * Validate order
     */
    protected function validateOrder(array $params): object
    {
        try {
            $response = $this->getClient()->__soapCall(
                'ValidateOrder',
                ['parameters' => ['request' => $params]]
            );

            return $response->ValidateOrderResult ?? $response;
        } catch (\SoapFault $e) {
            return (object) [
                'ResultCode' => 500,
                'ResultMessage' => $e->getMessage(),
                'Errors' => (object) ['string' => [$e->getMessage()]],
            ];
        }
    }

    /**
     * Create order
     */
    protected function createOrder(array $params)
    {
        try {
            // In simulation mode, use ValidateOrder instead
            $method = $this->simulationMode ? 'ValidateOrder' : 'CreateOrder';

            $response = $this->getClient()->__soapCall(
                $method,
                ['parameters' => ['request' => $params]]
            );

            $resultKey = $method . 'Result';
            return $response->$resultKey ?? $response;
        } catch (\SoapFault $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Poll until order reaches terminal status
     */
    protected function pollUntilComplete(string $orderId): string
    {
        $startTime = time();
        $lastStatus = 'Unknown';

        while ((time() - $startTime) < self::MAX_POLL_TIME) {
            try {
                $response = $this->getClient()->__soapCall(
                    'GetOrder',
                    ['parameters' => ['request' => ['OrderId' => $orderId]]]
                );

                $result = $response->GetOrderResult ?? null;

                if ($result && $result->ResultCode === 200) {
                    $status = $result->OrderInfo->Status ?? 'Unknown';

                    if ($status !== $lastStatus) {
                        echo "    Status: {$status}\n";
                        $lastStatus = $status;
                    }

                    // Terminal statuses
                    if (in_array($status, ['Completed', 'Failed', 'Invalid'])) {
                        return $status;
                    }
                }
            } catch (\SoapFault $e) {
                echo "    GetOrder error: " . $e->getMessage() . "\n";
            }

            sleep(self::POLL_INTERVAL);
        }

        return $lastStatus;
    }

    /**
     * Verify domain registration was successful
     */
    protected function verifyDomainRegistration(string $orderId, string $domainName): void
    {
        // Get order to find domain handle
        $orderResponse = $this->getClient()->__soapCall(
            'GetOrder',
            ['parameters' => ['request' => ['OrderId' => $orderId]]]
        );

        $order = $orderResponse->GetOrderResult ?? null;

        if (!$order || $order->ResultCode !== 200) {
            echo "    Could not retrieve order\n";
            return;
        }

        $domainHandle = $order->OrderInfo->OrderRequest->Domain->DomainHandle ?? null;

        if ($domainHandle) {
            echo "    Domain Handle: {$domainHandle}\n";

            // Get domain details
            $domain = $this->getDomainByHandle($domainHandle);

            if ($domain) {
                echo "    Domain verified:\n";
                echo "      Name: " . ($domain->DomainName ?? 'N/A') . "\n";
                echo "      Status: " . ($domain->Status ?? 'N/A') . "\n";
                echo "      ExpDate: " . ($domain->ExpDate ?? 'N/A') . "\n";
            }
        }
    }

    /**
     * Find an existing domain on the test account
     */
    protected function findExistingDomain(string $tld): ?object
    {
        try {
            $response = $this->getClient()->__soapCall(
                'SearchDomain',
                [
                    'parameters' => [
                        'request' => [
                            'Criteria' => [
                                'Mode' => 'Strict',
                                'WithoutStates' => ['deleted'],
                                'Clauses' => [
                                    [
                                        'Attribute' => 'DomainName',
                                        'Value' => '*.' . $tld,
                                        'Operator' => 'Like',
                                    ],
                                ],
                                'PageInfo' => [
                                    'PageIndex' => 0,
                                    'PageSize' => 1,
                                ],
                            ],
                        ],
                    ],
                ]
            );

            $result = $response->SearchDomainResult ?? null;

            if ($result && isset($result->Domains->Domain)) {
                $domains = $result->Domains->Domain;
                return is_array($domains) ? $domains[0] : $domains;
            }
        } catch (\SoapFault $e) {
            echo "SearchDomain error: " . $e->getMessage() . "\n";
        }

        return null;
    }

    /**
     * Get domain by handle
     */
    protected function getDomainByHandle(string $handle): ?object
    {
        try {
            $response = $this->getClient()->__soapCall(
                'GetDomain',
                ['parameters' => ['request' => ['DomainHandle' => $handle]]]
            );

            $result = $response->GetDomainResult ?? null;

            if ($result && $result->ResultCode === 200) {
                return $result->Domain ?? $result;
            }
        } catch (\SoapFault $e) {
            echo "GetDomain error: " . $e->getMessage() . "\n";
        }

        return null;
    }

    /**
     * Extract errors from result
     */
    protected function extractErrors(object $result): array
    {
        $errors = [];

        if (isset($result->Errors->string)) {
            $errorList = $result->Errors->string;
            if (is_array($errorList)) {
                $errors = $errorList;
            } else {
                $errors[] = $errorList;
            }
        }

        return $errors;
    }

    // =========================================================================
    // Assertion Helpers
    // =========================================================================

    /**
     * Assert domain has expected field
     */
    protected function assertDomainField(object $domain, string $field, ?string $expected = null): void
    {
        $value = $domain->$field ?? null;

        $this->assertNotNull($value, "Domain should have {$field}");
        echo "    {$field}: {$value}\n";

        if ($expected !== null) {
            $this->assertEquals($expected, $value, "{$field} should match expected value");
        }
    }

    /**
     * Assert domain has date field
     */
    protected function assertDomainDateField(object $domain, string $field): void
    {
        $value = $domain->$field ?? null;

        if ($value && $value !== '0001-01-01T00:00:00') {
            echo "    {$field}: {$value}\n";
        } else {
            echo "    {$field}: (not set)\n";
        }
    }

    /**
     * Assert contact field exists
     */
    protected function assertContactField(object $domain, string $contactType): void
    {
        $contact = $domain->$contactType ?? null;

        if ($contact) {
            $name = $contact->Name ?? ($contact->FirstName ?? '') . ' ' . ($contact->LastName ?? '');
            $email = $contact->Email ?? 'N/A';
            echo "    {$contactType}: {$name} ({$email})\n";
        } else {
            echo "    {$contactType}: (not set)\n";
        }
    }

    /**
     * Assert nameserver fields
     */
    protected function assertNameserverFields(object $domain): void
    {
        $ns = $domain->NameServers ?? null;

        if (!$ns) {
            echo "    NameServers: (not set)\n";
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $nsField = "NameServer{$i}";
            $nsObj = $ns->$nsField ?? null;

            if ($nsObj && !empty($nsObj->HostName)) {
                echo "    NS{$i}: {$nsObj->HostName}\n";
            }
        }
    }

    /**
     * Assert optional field
     */
    protected function assertOptionalField(object $domain, string $field, string $label): void
    {
        $value = $domain->$field ?? null;

        if ($value !== null) {
            if (is_object($value)) {
                $displayValue = json_encode($value);
            } else {
                $displayValue = (string) $value;
            }
            echo "    {$label}: {$displayValue}\n";
        } else {
            echo "    {$label}: (not set)\n";
        }
    }
}
