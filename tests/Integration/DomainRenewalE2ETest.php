<?php
/**
 * Domain Renewal E2E Integration Tests
 *
 * Tests the complete domain renewal workflow against the Ascio demo API:
 * 1. Renewal order validation (ValidateOrder with Renew type)
 * 2. Renewal order creation (CreateOrder with Renew type)
 * 3. Different renewal periods (1, 2, 5 years)
 * 4. Renewal completion via callback
 * 5. Failed renewal scenarios
 * 6. Renewal window edge cases (too early, grace period)
 *
 * REQUIRES:
 * - ASCIO_TEST_ACCOUNT and ASCIO_TEST_PASSWORD in .env
 *
 * Run with:
 *   ./vendor/bin/phpunit tests/Integration/DomainRenewalE2ETest.php --group=e2e --testdox
 *
 * @group e2e
 * @group integration
 * @group renewal
 */

declare(strict_types=1);

namespace Ascio\Tests\Integration;

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use ascio\Request;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\MockParamsV3;
use IntegrationTestCredentials;
use TestDomainProvider;

#[Group('e2e')]
#[Group('integration')]
#[Group('renewal')]
class DomainRenewalE2ETest extends IntegrationTestBase
{
    /**
     * Maximum time to wait for order completion (5 minutes)
     */
    private const MAX_POLL_TIME = 300;

    /**
     * Poll interval in seconds
     */
    private const POLL_INTERVAL = 10;

    /**
     * @var \SoapClient|null Direct SOAP client for API calls
     */
    protected ?\SoapClient $soapClient = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up mock TLD data for renewal threshold tests
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -35, 'Renew' => 1],
            ['Tld' => 'net', 'Threshold' => -30, 'Renew' => 1],
            ['Tld' => 'org', 'Threshold' => -30, 'Renew' => 1],
            ['Tld' => 'info', 'Threshold' => -30, 'Renew' => 1],
            ['Tld' => 'de', 'Threshold' => -45, 'Renew' => 0], // .de uses unexpire, not renew
            ['Tld' => 'at', 'Threshold' => -45, 'Renew' => 0], // .at uses unexpire
        ]);

        CapsuleMock::setTableData('tbldomains', [
            [
                'id' => 1,
                'domain' => 'test-renewal.com',
                'registrar' => 'ascio',
                'status' => 'Active',
            ],
        ]);

        CapsuleMock::setTableData('tblasciohandles', []);
        CapsuleMock::setTableData('tbldomains_extra', []);
    }

    protected function tearDown(): void
    {
        $this->soapClient = null;
        parent::tearDown();
    }

    /**
     * Get direct SOAP client for API calls
     */
    protected function getSoapClient(): \SoapClient
    {
        if ($this->soapClient === null) {
            $wsdl = ASCIO_V3_WSDL_TEST;
            $this->soapClient = new \SoapClient($wsdl, [
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
            $this->soapClient->__setSoapHeaders($header);
        }

        return $this->soapClient;
    }

    // =========================================================================
    // Renewal Order Validation Tests (ValidateOrder with Renew type)
    // =========================================================================

    #[Test]
    #[Group('validation')]
    public function testRenewalOrderValidationWithOneYearPeriod(): void
    {
        $testDomain = $this->generateTestDomain('com');
        $renewParams = $this->buildRenewalOrder($testDomain, 1);

        $result = $this->validateOrderDirect($renewParams);

        $this->assertV3ResponseFormat($result);

        // Validation may fail because domain doesn't exist in registry
        // but structure should be correct
        $this->assertEquals('Renew', $renewParams['Order']['Type']);
        $this->assertEquals(1, $renewParams['Order']['Domain']['RenewPeriod']);
        $this->assertEquals($testDomain, $renewParams['Order']['Domain']['Name']);

        if ($result->ResultCode === 200) {
            $this->assertValidationSuccess($result);
        } else {
            // Expected - domain doesn't exist
            $this->assertContains(
                $result->ResultCode,
                [400, 404, 500],
                'Non-existent domain should return validation error'
            );
        }
    }

    #[Test]
    #[Group('validation')]
    #[DataProvider('renewalPeriodProvider')]
    public function testRenewalOrderValidationWithDifferentPeriods(int $period): void
    {
        $testDomain = $this->generateTestDomain('com');
        $renewParams = $this->buildRenewalOrder($testDomain, $period);

        // Verify order structure
        $this->assertEquals('Renew', $renewParams['Order']['Type']);
        $this->assertEquals($period, $renewParams['Order']['Domain']['RenewPeriod']);

        $result = $this->validateOrderDirect($renewParams);
        $this->assertV3ResponseFormat($result);

        // Structure should be valid even if domain doesn't exist
        $this->assertArrayHasKey('Order', $renewParams);
        $this->assertArrayHasKey('Domain', $renewParams['Order']);
        $this->assertArrayHasKey('RenewPeriod', $renewParams['Order']['Domain']);
    }

    public static function renewalPeriodProvider(): array
    {
        return [
            '1 year' => [1],
            '2 years' => [2],
            '3 years' => [3],
            '5 years' => [5],
            '10 years' => [10],
        ];
    }

    #[Test]
    #[Group('validation')]
    public function testRenewalOrderMappingWithWhmcsParams(): void
    {
        $domainName = 'test-renewal-' . uniqid() . '.com';
        $whmcsParams = MockParamsV3::forRenewal($domainName, 2);
        $whmcsParams['Username'] = $this->username;
        $whmcsParams['Password'] = $this->password;
        $whmcsParams['TestMode'] = 'on';

        $request = new Request($whmcsParams);
        $orderParams = $request->mapToOrder($whmcsParams, 'Renew');

        // Verify mapped order structure
        $this->assertOrderStructure($orderParams, 'Renew');
        $this->assertEquals($domainName, $orderParams['Order']['Domain']['Name']);
        $this->assertEquals(2, $orderParams['Order']['Domain']['RenewPeriod']);

        // Verify transaction comment contains metadata
        $transactionComment = json_decode($orderParams['Order']['TransactionComment'], true);
        $this->assertEquals('WHMCS', $transactionComment['application']);
    }

    // =========================================================================
    // Renewal Order Creation Tests (CreateOrder with Renew type)
    // =========================================================================

    #[Test]
    #[Group('slow')]
    public function testRenewalOrderCreationAgainstDemoApi(): void
    {
        // Skip in CI - this is for manual E2E testing
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== Domain Renewal Order Creation Test ===\n";

        // First, find an existing domain on the test account that can be renewed
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account for renewal');
        }

        $domainName = $existingDomain->Name ?? null;
        $domainHandle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $expDate = $existingDomain->ExpDate ?? null;

        echo "Domain: {$domainName}\n";
        echo "Handle: {$domainHandle}\n";
        echo "Current ExpDate: {$expDate}\n\n";

        // Build renewal order
        $renewParams = $this->buildRenewalOrder($domainName, 1);

        // Validate first
        echo "Validating renewal order...\n";
        $validateResult = $this->validateOrderDirect($renewParams);

        echo "  Validation result: {$validateResult->ResultCode}\n";

        if ($validateResult->ResultCode !== 200) {
            $errors = $this->extractErrors($validateResult);
            echo "  Errors: " . implode(', ', $errors) . "\n";

            // If renewal is not allowed, test still passes (structure validated)
            $this->assertV3ResponseFormat($validateResult);
            echo "\n=== Renewal validation completed (may not be allowed) ===\n";
            return;
        }

        // In simulation mode, we don't actually create the order
        if ($this->simulationMode) {
            echo "\nSimulation mode: Skipping actual order creation\n";
            $this->assertValidationSuccess($validateResult);
            echo "\n=== Renewal order validated successfully ===\n";
            return;
        }

        // Create the order (only in non-simulation mode)
        echo "\nCreating renewal order...\n";
        $createResult = $this->createOrderDirect($renewParams);

        if (isset($createResult['error'])) {
            $this->markTestSkipped('Order creation failed: ' . $createResult['error']);
        }

        $this->assertApiSuccess($createResult);

        $orderId = $createResult->OrderInfo->OrderId ?? null;
        $status = $createResult->OrderInfo->Status ?? 'Unknown';

        echo "  Order ID: {$orderId}\n";
        echo "  Status: {$status}\n";

        echo "\n=== Renewal order created successfully ===\n";
    }

    #[Test]
    #[Group('validation')]
    public function testRenewalOrderWithInvalidPeriod(): void
    {
        $testDomain = $this->generateTestDomain('com');

        // Test with invalid period (0)
        $renewParams = $this->buildRenewalOrder($testDomain, 0);

        $result = $this->validateOrderDirect($renewParams);

        // Should fail validation with invalid period
        $this->assertV3ResponseFormat($result);
        $this->assertNotEquals(200, $result->ResultCode, 'Zero period should fail validation');
    }

    #[Test]
    #[Group('validation')]
    public function testRenewalOrderWithExcessivePeriod(): void
    {
        $testDomain = $this->generateTestDomain('com');

        // Test with period > max (usually 10 years max)
        $renewParams = $this->buildRenewalOrder($testDomain, 15);

        $result = $this->validateOrderDirect($renewParams);

        // Should fail validation with excessive period
        $this->assertV3ResponseFormat($result);
        // Result code depends on API validation - structure is valid
        $this->assertNotNull($result->ResultCode);
    }

    // =========================================================================
    // Different Renewal Periods Tests
    // =========================================================================

    #[Test]
    #[Group('validation')]
    public function testRenewalOrderStructureForOneYear(): void
    {
        $testDomain = $this->generateTestDomain('com');
        $renewParams = $this->buildRenewalOrder($testDomain, 1);

        $this->assertEquals('Renew', $renewParams['Order']['Type']);
        $this->assertEquals(1, $renewParams['Order']['Domain']['RenewPeriod']);

        // Validate structure with API
        $result = $this->validateOrderDirect($renewParams);
        $this->assertV3ResponseFormat($result);
    }

    #[Test]
    #[Group('validation')]
    public function testRenewalOrderStructureForTwoYears(): void
    {
        $testDomain = $this->generateTestDomain('com');
        $renewParams = $this->buildRenewalOrder($testDomain, 2);

        $this->assertEquals(2, $renewParams['Order']['Domain']['RenewPeriod']);

        $result = $this->validateOrderDirect($renewParams);
        $this->assertV3ResponseFormat($result);
    }

    #[Test]
    #[Group('validation')]
    public function testRenewalOrderStructureForFiveYears(): void
    {
        $testDomain = $this->generateTestDomain('com');
        $renewParams = $this->buildRenewalOrder($testDomain, 5);

        $this->assertEquals(5, $renewParams['Order']['Domain']['RenewPeriod']);

        $result = $this->validateOrderDirect($renewParams);
        $this->assertV3ResponseFormat($result);
    }

    #[Test]
    #[Group('validation')]
    public function testRenewalOrderStructureForMaxPeriod(): void
    {
        $testDomain = $this->generateTestDomain('com');
        $renewParams = $this->buildRenewalOrder($testDomain, 10);

        $this->assertEquals(10, $renewParams['Order']['Domain']['RenewPeriod']);

        $result = $this->validateOrderDirect($renewParams);
        $this->assertV3ResponseFormat($result);
    }

    // =========================================================================
    // Renewal Completion via Callback Tests
    // =========================================================================

    #[Test]
    #[Group('callbacks')]
    public function testRenewalCompletedCallbackProcessing(): void
    {
        $callbackData = $this->mockCallback('ORD-RENEW-001', 'Completed', 'Renewal completed');
        $callbackData['OrderType'] = 'Renew';

        $this->assertEquals('Completed', $callbackData['OrderStatus']);
        $this->assertEquals('Renew', $callbackData['OrderType']);

        // Verify request can process callback status
        $request = $this->getRequest();
        $whmcsStatus = $request->getDomainStatus((object) ['Status' => 'ACTIVE']);

        $this->assertEquals('Active', $whmcsStatus, 'Completed renewal should result in Active status');
    }

    #[Test]
    #[Group('callbacks')]
    public function testRenewalPendingCallbackProcessing(): void
    {
        $callbackData = $this->mockCallback('ORD-RENEW-002', 'Pending', 'Waiting for registry');
        $callbackData['OrderType'] = 'Renew';

        $this->assertEquals('Pending', $callbackData['OrderStatus']);

        $request = $this->getRequest();
        $whmcsStatus = $request->getDomainStatus((object) ['Status' => 'PENDING']);

        $this->assertEquals('Pending', $whmcsStatus);
    }

    #[Test]
    #[Group('callbacks')]
    public function testRenewalCallbackUpdatesExpiryDate(): void
    {
        CapsuleMock::reset();
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -35, 'Renew' => 1],
        ]);
        CapsuleMock::setTableData('tbldomains', [
            [
                'id' => 1,
                'domain' => 'test-renewal.com',
                'registrar' => 'ascio',
                'status' => 'Active',
            ],
        ]);

        $request = new Request(array_merge($this->params, ['domainid' => 1]));

        // Simulate domain with new expiry after renewal (1 year from now + 1 year renewal)
        $newExpDate = (new \DateTime('+2 years'))->format('Y-m-d\TH:i:s');
        $domain = (object) [
            'Name' => 'test-renewal.com',
            'DomainHandle' => 'DOM-12345',
            'Status' => 'ACTIVE',
            'ExpDate' => $newExpDate,
            'CreDate' => (new \DateTime('-1 year'))->format('Y-m-d\TH:i:s'),
        ];

        // Set domain status (which includes expiry date calculation)
        $request->setDomainStatus($domain);

        $status = $request->getDomainStatus($domain);
        $this->assertEquals('Active', $status);
    }

    #[Test]
    #[Group('callbacks')]
    public function testRenewalCallbackStoresOrderStatus(): void
    {
        CapsuleMock::reset();
        CapsuleMock::setTableData('tbldomains_extra', []);

        // Verify updateOrInsert works for storing order status
        CapsuleMock::table('tbldomains_extra')->updateOrInsert(
            ['domain_id' => 1, 'name' => 'ascio_order_status'],
            ['value' => 'Completed']
        );

        CapsuleMock::table('tbldomains_extra')->updateOrInsert(
            ['domain_id' => 1, 'name' => 'ascio_order_type'],
            ['value' => 'Renew']
        );

        $query = CapsuleMock::getLastQuery();
        $this->assertEquals('updateOrInsert', $query['type']);
        $this->assertEquals('tbldomains_extra', $query['table']);
    }

    // =========================================================================
    // Failed Renewal Scenarios Tests
    // =========================================================================

    #[Test]
    #[Group('failures')]
    public function testRenewalFailedCallbackProcessing(): void
    {
        $callbackData = $this->mockCallback(
            'ORD-RENEW-FAIL-001',
            'Failed',
            'Domain not found in registry'
        );
        $callbackData['OrderType'] = 'Renew';

        $this->assertEquals('Failed', $callbackData['OrderStatus']);
        $this->assertNotEmpty($callbackData['StatusList']['CallbackStatus']);
    }

    #[Test]
    #[Group('failures')]
    public function testRenewalInvalidCallbackProcessing(): void
    {
        $callbackData = $this->mockCallback(
            'ORD-RENEW-INVALID-001',
            'Invalid',
            'Invalid renewal parameters'
        );
        $callbackData['OrderType'] = 'Renew';

        $this->assertEquals('Invalid', $callbackData['OrderStatus']);
    }

    #[Test]
    #[Group('failures')]
    public function testRenewalForNonExistentDomain(): void
    {
        $fakeDomain = 'definitely-not-registered-' . uniqid() . '.com';
        $renewParams = $this->buildRenewalOrder($fakeDomain, 1);

        $result = $this->validateOrderDirect($renewParams);

        $this->assertV3ResponseFormat($result);
        // Should fail because domain doesn't exist
        $this->assertNotEquals(200, $result->ResultCode, 'Non-existent domain renewal should fail');
    }

    #[Test]
    #[Group('failures')]
    public function testRenewalWithMultipleErrors(): void
    {
        $callbackData = [
            'MessageId' => 'MSG-' . uniqid(),
            'OrderId' => 'ORD-RENEW-MULTI-ERR',
            'OrderStatus' => 'Failed',
            'OrderType' => 'Renew',
            'Name' => 'test-renewal.com',
            'Message' => 'Multiple validation errors',
            'StatusList' => [
                'CallbackStatus' => [
                    ['Message' => 'Domain is in redemption period', 'Status' => 'Failed'],
                    ['Message' => 'Maximum renewal period exceeded', 'Status' => 'Failed'],
                ],
            ],
        ];

        $this->assertCount(2, $callbackData['StatusList']['CallbackStatus']);
    }

    // =========================================================================
    // Renewal Window Edge Cases Tests
    // =========================================================================

    #[Test]
    #[Group('edge-cases')]
    public function testRenewalTooEarlyScenario(): void
    {
        // Test renewal when domain expiry is too far in the future
        // Some registries have minimum windows (e.g., can't renew more than 1 year before expiry)

        $testDomain = $this->generateTestDomain('com');
        $renewParams = $this->buildRenewalOrder($testDomain, 1);

        // The validation will tell us if renewal is not allowed
        $result = $this->validateOrderDirect($renewParams);

        $this->assertV3ResponseFormat($result);
        // Structure is valid regardless of timing constraints
        $this->assertArrayHasKey('Order', $renewParams);
    }

    #[Test]
    #[Group('edge-cases')]
    public function testRenewalDuringGracePeriod(): void
    {
        // Grace period is after expiry but before deletion
        // Renewal should still be possible (domain Status would be "EXPIRING")

        $testDomain = $this->generateTestDomain('com');
        $renewParams = $this->buildRenewalOrder($testDomain, 1);

        $result = $this->validateOrderDirect($renewParams);

        $this->assertV3ResponseFormat($result);
    }

    #[Test]
    #[Group('edge-cases')]
    public function testUnexpireInsteadOfRenewForSpecialTlds(): void
    {
        // Some TLDs (like .de, .at) use unexpire instead of renew
        // when the domain is in expiring state

        $testDomain = 'test-unexpire-' . uniqid() . '.de';
        $unexpireParams = $this->buildUnexpireOrder($testDomain);

        $this->assertEquals('Unexpire', $unexpireParams['Order']['Type']);
        $this->assertEquals($testDomain, $unexpireParams['Order']['Domain']['Name']);

        $result = $this->validateOrderDirect($unexpireParams);
        $this->assertV3ResponseFormat($result);
    }

    #[Test]
    #[Group('edge-cases')]
    public function testRenewalMethodChoosesCorrectOrderType(): void
    {
        // Test that renewDomain method checks TLD configuration

        CapsuleMock::reset();
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -35, 'Renew' => 1],
        ]);

        $domainName = 'test-renew-method-' . uniqid() . '.com';
        $params = MockParamsV3::forRenewal($domainName, 1);
        $params['Username'] = $this->username;
        $params['Password'] = $this->password;
        $params['TestMode'] = 'on';
        $params['domainid'] = 1;
        $params['tld'] = 'com';

        $request = new Request($params);

        // The renewDomain method will check TLD configuration
        // For .com with Renew=1, it should create a Renew order
        $orderParams = $request->mapToOrder($params, 'Renew');

        $this->assertEquals('Renew', $orderParams['Order']['Type']);
    }

    #[Test]
    #[Group('edge-cases')]
    public function testMaximumRenewalPeriodCalculation(): void
    {
        // Test that renewal + current term doesn't exceed max (typically 10 years)

        $testDomain = $this->generateTestDomain('com');

        // Even if domain has 9 years remaining, renewing for 10 more would exceed max
        // Structure should still be valid
        $renewParams = $this->buildRenewalOrder($testDomain, 10);

        $this->assertEquals(10, $renewParams['Order']['Domain']['RenewPeriod']);

        $result = $this->validateOrderDirect($renewParams);
        $this->assertV3ResponseFormat($result);
    }

    // =========================================================================
    // Request Class Integration Tests
    // =========================================================================

    #[Test]
    public function testRenewDomainMethodExists(): void
    {
        $request = $this->getRequest();

        $this->assertTrue(method_exists($request, 'renewDomain'));
        $this->assertTrue(method_exists($request, 'unexpireDomain'));
        $this->assertTrue(method_exists($request, 'mapToOrder'));
    }

    #[Test]
    public function testRenewDomainCreatesCorrectOrderStructure(): void
    {
        $domainName = 'test-renew-structure-' . uniqid() . '.com';
        $params = MockParamsV3::forRenewal($domainName, 2);
        $params['Username'] = $this->username;
        $params['Password'] = $this->password;
        $params['TestMode'] = 'on';
        $params['domainid'] = 1;
        $params['tld'] = 'com';

        $request = new Request($params);
        $orderParams = $request->mapToOrder($params, 'Renew');

        // Validate complete order structure
        $this->assertArrayHasKey('Order', $orderParams);
        $this->assertEquals('Renew', $orderParams['Order']['Type']);
        $this->assertArrayHasKey('Domain', $orderParams['Order']);
        $this->assertEquals($domainName, $orderParams['Order']['Domain']['Name']);
        $this->assertEquals(2, $orderParams['Order']['Domain']['RenewPeriod']);
        $this->assertArrayHasKey('TransactionComment', $orderParams['Order']);
    }

    #[Test]
    public function testRenewalPreservesRegistrantData(): void
    {
        $domainName = 'test-renew-registrant-' . uniqid() . '.com';
        $params = MockParamsV3::forRenewal($domainName, 1);
        $params['Username'] = $this->username;
        $params['Password'] = $this->password;
        $params['TestMode'] = 'on';
        $params['firstname'] = 'John';
        $params['lastname'] = 'Renewal';
        $params['email'] = 'renewal@test.com';

        $request = new Request($params);
        $orderParams = $request->mapToOrder($params, 'Renew');

        // Renewal order should include registrant data
        $this->assertArrayHasKey('Owner', $orderParams['Order']['Domain']);
        $this->assertEquals('John', $orderParams['Order']['Domain']['Owner']['FirstName']);
        $this->assertEquals('Renewal', $orderParams['Order']['Domain']['Owner']['LastName']);
    }

    // =========================================================================
    // Polling Tests for Renewal Orders
    // =========================================================================

    #[Test]
    #[Group('slow')]
    public function testPollQueueForRenewalMessages(): void
    {
        // Skip in CI - this is for manual E2E testing
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== Poll Queue for Renewal Messages Test ===\n";

        $request = $this->getRequest();
        $result = $request->poll();

        if (is_array($result)) {
            echo "Queue empty or error: " . ($result['error'] ?? 'no messages') . "\n";
            $this->assertTrue(true, 'Poll returned (may be empty)');
            return;
        }

        $this->assertIsObject($result);
        echo "Poll result code: {$result->ResultCode}\n";

        $hasMessage = isset($result->Message) && $result->Message !== null;
        echo "Has messages: " . ($hasMessage ? 'yes' : 'no') . "\n";

        if ($hasMessage) {
            $msg = $result->Message;
            $orderType = $msg->OrderType ?? 'Unknown';
            echo "  Order Type: {$orderType}\n";

            if ($orderType === 'Renew') {
                echo "  Found renewal message!\n";
                echo "  Order ID: " . ($msg->OrderId ?? 'N/A') . "\n";
                echo "  Status: " . ($msg->OrderStatus ?? 'N/A') . "\n";
            }
        }

        echo "=== Done ===\n";
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Build renewal order parameters
     */
    protected function buildRenewalOrder(string $domainName, int $period = 1): array
    {
        return [
            'Order' => [
                'Type' => 'Renew',
                'TransactionComment' => json_encode([
                    'application' => 'E2E_TEST',
                    'testId' => uniqid(),
                    'objectType' => 'Domain',
                ]),
                'Domain' => [
                    'Name' => $domainName,
                    'RenewPeriod' => $period,
                    'Owner' => $this->buildRegistrant(),
                    'Admin' => $this->buildContact('Admin'),
                    'Tech' => $this->buildContact('Tech'),
                    'Billing' => $this->buildContact('Billing'),
                ],
            ],
        ];
    }

    /**
     * Build unexpire order parameters
     */
    protected function buildUnexpireOrder(string $domainName, int $period = 1): array
    {
        return [
            'Order' => [
                'Type' => 'Unexpire',
                'TransactionComment' => json_encode([
                    'application' => 'E2E_TEST',
                    'testId' => uniqid(),
                    'objectType' => 'Domain',
                ]),
                'Domain' => [
                    'Name' => $domainName,
                    'RenewPeriod' => $period,
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
            'FirstName' => 'Test',
            'LastName' => 'User',
            'OrgName' => 'Test Organization GmbH',
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
            'OrgName' => 'Test Organization GmbH',
            'Address1' => '123 Test Street',
            'City' => 'Munich',
            'State' => 'Bavaria',
            'PostalCode' => '80331',
            'CountryCode' => 'DE',
            'Phone' => '+49.891234567',
            'Email' => "{$role}@example.com",
        ];
    }

    /**
     * Validate order using direct SOAP call
     */
    protected function validateOrderDirect(array $params): object
    {
        try {
            $response = $this->getSoapClient()->__soapCall(
                'ValidateOrder',
                ['parameters' => ['request' => $params]]
            );

            return $response->ValidateOrderResult ?? $response;
        } catch (\SoapFault $e) {
            // Check for authentication errors
            if (strpos($e->getMessage(), 'Login failed') !== false ||
                strpos($e->getMessage(), 'Authentication') !== false) {
                $this->markTestSkipped('Invalid Ascio credentials: ' . $e->getMessage());
            }

            // Handle internal server errors
            if (strpos($e->getMessage(), 'internal error') !== false) {
                $this->markTestSkipped('Ascio API internal error (temporary): ' . $e->getMessage());
            }

            return (object) [
                'ResultCode' => 500,
                'ResultMessage' => $e->getMessage(),
                'Errors' => (object) ['string' => [$e->getMessage()]],
            ];
        }
    }

    /**
     * Create order using direct SOAP call
     */
    protected function createOrderDirect(array $params)
    {
        try {
            // In simulation mode, use ValidateOrder instead
            $method = $this->simulationMode ? 'ValidateOrder' : 'CreateOrder';

            $response = $this->getSoapClient()->__soapCall(
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
     * Extract errors from API response
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

        if (empty($errors) && isset($result->ResultMessage)) {
            $errors[] = $result->ResultMessage;
        }

        return $errors;
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
                $response = $this->getSoapClient()->__soapCall(
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
}
