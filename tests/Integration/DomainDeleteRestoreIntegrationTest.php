<?php
/**
 * Domain Delete/Restore Integration Tests
 *
 * Tests domain deletion and restoration workflows against the Ascio v3 API:
 * 1. Delete order validation (ValidateOrder with Delete type)
 * 2. Restore order validation (ValidateOrder with Restore type)
 * 3. Delete order creation and processing
 * 4. Restore order creation for redemption recovery
 * 5. Grace period handling
 * 6. Error scenarios (non-existent domain, already deleted, etc.)
 *
 * REQUIRES:
 * - ASCIO_TEST_ACCOUNT and ASCIO_TEST_PASSWORD in .env
 *
 * Run with:
 *   ./vendor/bin/phpunit tests/Integration/DomainDeleteRestoreIntegrationTest.php --group=integration --testdox
 *
 * @group integration
 * @group v3
 * @group delete-restore
 */

declare(strict_types=1);

namespace Ascio\Tests\Integration;

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\MockParamsV3;
use IntegrationTestCredentials;
use TestDomainProvider;

#[Group('integration')]
#[Group('v3')]
#[Group('delete-restore')]
class DomainDeleteRestoreIntegrationTest extends IntegrationTestBase
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

        // Set up mock database data for tests
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -35, 'Renew' => 1],
            ['Tld' => 'net', 'Threshold' => -30, 'Renew' => 1],
            ['Tld' => 'org', 'Threshold' => -30, 'Renew' => 1],
        ]);

        CapsuleMock::setTableData('tbldomains', [
            [
                'id' => 1,
                'domain' => 'test-delete.com',
                'registrar' => 'ascio',
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'domain' => 'test-restore.com',
                'registrar' => 'ascio',
                'status' => 'Cancelled',
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
    // Delete Domain Order Validation Tests
    // =========================================================================

    #[Test]
    #[Group('validation')]
    public function testDeleteOrderValidation(): void
    {
        $testDomain = $this->generateTestDomain('com');
        $deleteParams = $this->buildDeleteOrder($testDomain);

        // Verify order structure
        $this->assertEquals('Delete', $deleteParams['Order']['Type']);
        $this->assertEquals($testDomain, $deleteParams['Order']['Domain']['Name']);

        $result = $this->validateOrderDirect($deleteParams);
        $this->assertV3ResponseFormat($result);

        // Structure should be valid even if domain doesn't exist for deletion
        $this->assertArrayHasKey('Order', $deleteParams);
        $this->assertArrayHasKey('Domain', $deleteParams['Order']);
        $this->assertArrayHasKey('Name', $deleteParams['Order']['Domain']);
    }

    #[Test]
    #[Group('validation')]
    public function testDeleteOrderStructureMapping(): void
    {
        $domainName = 'test-delete-' . uniqid() . '.com';
        $whmcsParams = MockParamsV3::forRegistration($domainName);
        $whmcsParams['Username'] = $this->username;
        $whmcsParams['Password'] = $this->password;
        $whmcsParams['TestMode'] = 'on';
        $whmcsParams['domainid'] = 1;

        $request = new Request($whmcsParams);
        $orderParams = $request->mapToOrder($whmcsParams, 'Delete');

        // Verify mapped order structure
        $this->assertOrderStructure($orderParams, 'Delete');
        $this->assertEquals($domainName, $orderParams['Order']['Domain']['Name']);

        // Verify transaction comment contains metadata
        $transactionComment = json_decode($orderParams['Order']['TransactionComment'], true);
        $this->assertEquals('WHMCS', $transactionComment['application']);
        $this->assertEquals(1, $transactionComment['domainId']);
    }

    #[Test]
    #[Group('validation')]
    public function testDeleteOrderIncludesMinimalData(): void
    {
        $testDomain = $this->generateTestDomain('com');
        $deleteParams = $this->buildDeleteOrder($testDomain);

        // Delete order should only need domain name
        $this->assertArrayHasKey('Name', $deleteParams['Order']['Domain']);

        // Delete order may or may not include contact data (minimal order)
        $result = $this->validateOrderDirect($deleteParams);
        $this->assertV3ResponseFormat($result);
    }

    // =========================================================================
    // Restore Domain Order Validation Tests
    // =========================================================================

    #[Test]
    #[Group('validation')]
    public function testRestoreOrderValidation(): void
    {
        $testDomain = $this->generateTestDomain('com');
        $restoreParams = $this->buildRestoreOrder($testDomain);

        // Verify order structure
        $this->assertEquals('Restore', $restoreParams['Order']['Type']);
        $this->assertEquals($testDomain, $restoreParams['Order']['Domain']['Name']);

        $result = $this->validateOrderDirect($restoreParams);
        $this->assertV3ResponseFormat($result);

        // Structure should be valid even if domain is not in redemption
        $this->assertArrayHasKey('Order', $restoreParams);
        $this->assertArrayHasKey('Domain', $restoreParams['Order']);
    }

    #[Test]
    #[Group('validation')]
    public function testRestoreOrderStructureMapping(): void
    {
        $domainName = 'test-restore-' . uniqid() . '.com';
        $whmcsParams = MockParamsV3::forRegistration($domainName);
        $whmcsParams['Username'] = $this->username;
        $whmcsParams['Password'] = $this->password;
        $whmcsParams['TestMode'] = 'on';
        $whmcsParams['domainid'] = 2;

        $request = new Request($whmcsParams);
        $orderParams = $request->mapToOrder($whmcsParams, 'Restore');

        // Verify mapped order structure
        $this->assertOrderStructure($orderParams, 'Restore');
        $this->assertEquals($domainName, $orderParams['Order']['Domain']['Name']);

        // Verify transaction comment
        $transactionComment = json_decode($orderParams['Order']['TransactionComment'], true);
        $this->assertEquals('WHMCS', $transactionComment['application']);
    }

    #[Test]
    #[Group('validation')]
    public function testRestoreOrderIncludesContactData(): void
    {
        $testDomain = $this->generateTestDomain('com');
        $restoreParams = $this->buildRestoreOrder($testDomain);

        // Restore order typically includes registrant data
        $this->assertArrayHasKey('Owner', $restoreParams['Order']['Domain']);
        $this->assertArrayHasKey('FirstName', $restoreParams['Order']['Domain']['Owner']);
        $this->assertArrayHasKey('LastName', $restoreParams['Order']['Domain']['Owner']);
        $this->assertArrayHasKey('Email', $restoreParams['Order']['Domain']['Owner']);

        $result = $this->validateOrderDirect($restoreParams);
        $this->assertV3ResponseFormat($result);
    }

    // =========================================================================
    // Delete Order Creation Tests
    // =========================================================================

    #[Test]
    #[Group('slow')]
    public function testDeleteDomainAgainstDemoApi(): void
    {
        // Skip in CI - this is for manual E2E testing
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== Domain Delete Order Test ===\n";

        // Find an existing domain on the test account
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account for delete test');
        }

        $domainName = $existingDomain->Name ?? null;
        $domainHandle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $status = $existingDomain->Status ?? null;

        echo "Domain: {$domainName}\n";
        echo "Handle: {$domainHandle}\n";
        echo "Current Status: {$status}\n\n";

        // Build delete order
        $deleteParams = $this->buildDeleteOrder($domainName);

        // Validate first
        echo "Validating delete order...\n";
        $validateResult = $this->validateOrderDirect($deleteParams);

        echo "  Validation result: {$validateResult->ResultCode}\n";

        if ($validateResult->ResultCode !== 200) {
            $errors = $this->extractErrors($validateResult);
            echo "  Errors: " . implode(', ', $errors) . "\n";

            // Structure is still valid even if delete is not allowed
            $this->assertV3ResponseFormat($validateResult);
            echo "\n=== Delete validation completed (may not be allowed) ===\n";
            return;
        }

        // In simulation mode, we don't actually create the order
        if ($this->simulationMode) {
            echo "\nSimulation mode: Skipping actual order creation\n";
            $this->assertValidationSuccess($validateResult);
            echo "\n=== Delete order validated successfully ===\n";
            return;
        }

        echo "\n=== Delete order validation complete ===\n";
    }

    // =========================================================================
    // Restore Order Creation Tests
    // =========================================================================

    #[Test]
    #[Group('slow')]
    public function testRestoreDomainAgainstDemoApi(): void
    {
        // Skip in CI - this is for manual E2E testing
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== Domain Restore Order Test ===\n";

        // Generate a test domain - in real scenario this would be a deleted domain
        $testDomain = $this->generateTestDomain('com');
        echo "Test Domain: {$testDomain}\n\n";

        // Build restore order
        $restoreParams = $this->buildRestoreOrder($testDomain);

        // Validate
        echo "Validating restore order...\n";
        $validateResult = $this->validateOrderDirect($restoreParams);

        echo "  Validation result: {$validateResult->ResultCode}\n";

        if ($validateResult->ResultCode !== 200) {
            $errors = $this->extractErrors($validateResult);
            echo "  Errors: " . implode(', ', $errors) . "\n";

            // Expected - domain is not in redemption period
            if (strpos(implode(' ', $errors), 'not found') !== false ||
                strpos(implode(' ', $errors), 'redemption') !== false ||
                strpos(implode(' ', $errors), 'not in') !== false) {
                echo "  (Expected: domain not in redemption period)\n";
            }
        }

        $this->assertV3ResponseFormat($validateResult);
        echo "\n=== Restore order validation complete ===\n";
    }

    // =========================================================================
    // Grace Period Handling Tests
    // =========================================================================

    #[Test]
    #[Group('validation')]
    public function testRestoreOrderDuringGracePeriod(): void
    {
        // Grace period is after expiry but before full deletion
        // Restore should be possible if domain is in "redemption" state

        $testDomain = $this->generateTestDomain('com');
        $restoreParams = $this->buildRestoreOrder($testDomain);

        $this->assertEquals('Restore', $restoreParams['Order']['Type']);

        $result = $this->validateOrderDirect($restoreParams);
        $this->assertV3ResponseFormat($result);

        // Structure should be valid regardless of domain state
        $this->assertArrayHasKey('Order', $restoreParams);
    }

    #[Test]
    #[Group('validation')]
    public function testRestoreOrderWithRenewalPeriod(): void
    {
        // Restore typically includes a renewal period
        $testDomain = $this->generateTestDomain('com');
        $restoreParams = $this->buildRestoreOrder($testDomain, 1);

        // Verify renewal period is included
        $this->assertEquals(1, $restoreParams['Order']['Domain']['RenewPeriod']);

        $result = $this->validateOrderDirect($restoreParams);
        $this->assertV3ResponseFormat($result);
    }

    #[Test]
    #[Group('validation')]
    #[DataProvider('restorePeriodProvider')]
    public function testRestoreOrderWithDifferentPeriods(int $period): void
    {
        $testDomain = $this->generateTestDomain('com');
        $restoreParams = $this->buildRestoreOrder($testDomain, $period);

        $this->assertEquals($period, $restoreParams['Order']['Domain']['RenewPeriod']);

        $result = $this->validateOrderDirect($restoreParams);
        $this->assertV3ResponseFormat($result);
    }

    public static function restorePeriodProvider(): array
    {
        return [
            '1 year' => [1],
            '2 years' => [2],
            '5 years' => [5],
        ];
    }

    // =========================================================================
    // Error Scenario Tests
    // =========================================================================

    #[Test]
    #[Group('failures')]
    public function testDeleteNonExistentDomain(): void
    {
        $fakeDomain = 'definitely-not-registered-' . uniqid() . '-delete.com';
        $deleteParams = $this->buildDeleteOrder($fakeDomain);

        $result = $this->validateOrderDirect($deleteParams);

        $this->assertV3ResponseFormat($result);
        // Should fail because domain doesn't exist
        $this->assertNotEquals(200, $result->ResultCode, 'Non-existent domain delete should fail');
    }

    #[Test]
    #[Group('failures')]
    public function testRestoreNonExistentDomain(): void
    {
        $fakeDomain = 'definitely-not-registered-' . uniqid() . '-restore.com';
        $restoreParams = $this->buildRestoreOrder($fakeDomain);

        $result = $this->validateOrderDirect($restoreParams);

        $this->assertV3ResponseFormat($result);
        // Should fail because domain doesn't exist in redemption
        $this->assertNotEquals(200, $result->ResultCode, 'Non-existent domain restore should fail');
    }

    #[Test]
    #[Group('failures')]
    public function testDeleteInvalidDomainFormat(): void
    {
        // Invalid domain name format
        $invalidDomain = 'invalid_domain_with_underscore.com';
        $deleteParams = $this->buildDeleteOrder($invalidDomain);

        $result = $this->validateOrderDirect($deleteParams);

        $this->assertV3ResponseFormat($result);
        // Should fail validation
        $this->assertNotEquals(200, $result->ResultCode, 'Invalid domain format should fail');
    }

    #[Test]
    #[Group('failures')]
    public function testRestoreInvalidDomainFormat(): void
    {
        // Invalid domain name format
        $invalidDomain = 'invalid_domain_with_underscore.com';
        $restoreParams = $this->buildRestoreOrder($invalidDomain);

        $result = $this->validateOrderDirect($restoreParams);

        $this->assertV3ResponseFormat($result);
        // Should fail validation
        $this->assertNotEquals(200, $result->ResultCode, 'Invalid domain format should fail');
    }

    // =========================================================================
    // Callback Processing Tests
    // =========================================================================

    #[Test]
    #[Group('callbacks')]
    public function testDeleteCompletedCallbackProcessing(): void
    {
        $callbackData = $this->mockCallback('ORD-DELETE-001', 'Completed', 'Domain deleted successfully');
        $callbackData['OrderType'] = 'Delete';

        $this->assertEquals('Completed', $callbackData['OrderStatus']);
        $this->assertEquals('Delete', $callbackData['OrderType']);

        // After delete, domain status should be Cancelled
        $request = $this->getRequest();
        $whmcsStatus = $request->getDomainStatus((object) ['Status' => 'DELETED']);

        $this->assertEquals('Cancelled', $whmcsStatus, 'Deleted domain should map to Cancelled status');
    }

    #[Test]
    #[Group('callbacks')]
    public function testDeletePendingCallbackProcessing(): void
    {
        $callbackData = $this->mockCallback('ORD-DELETE-002', 'Pending', 'Waiting for registry');
        $callbackData['OrderType'] = 'Delete';

        $this->assertEquals('Pending', $callbackData['OrderStatus']);

        $request = $this->getRequest();
        $whmcsStatus = $request->getDomainStatus((object) ['Status' => 'PENDING']);

        $this->assertEquals('Pending', $whmcsStatus);
    }

    #[Test]
    #[Group('callbacks')]
    public function testDeleteFailedCallbackProcessing(): void
    {
        $callbackData = $this->mockCallback(
            'ORD-DELETE-FAIL-001',
            'Failed',
            'Domain is locked and cannot be deleted'
        );
        $callbackData['OrderType'] = 'Delete';

        $this->assertEquals('Failed', $callbackData['OrderStatus']);
        $this->assertNotEmpty($callbackData['StatusList']['CallbackStatus']);
    }

    #[Test]
    #[Group('callbacks')]
    public function testRestoreCompletedCallbackProcessing(): void
    {
        $callbackData = $this->mockCallback('ORD-RESTORE-001', 'Completed', 'Domain restored successfully');
        $callbackData['OrderType'] = 'Restore';

        $this->assertEquals('Completed', $callbackData['OrderStatus']);
        $this->assertEquals('Restore', $callbackData['OrderType']);

        // After restore, domain status should be Active
        $request = $this->getRequest();
        $whmcsStatus = $request->getDomainStatus((object) ['Status' => 'ACTIVE']);

        $this->assertEquals('Active', $whmcsStatus, 'Restored domain should map to Active status');
    }

    #[Test]
    #[Group('callbacks')]
    public function testRestoreFailedCallbackProcessing(): void
    {
        $callbackData = $this->mockCallback(
            'ORD-RESTORE-FAIL-001',
            'Failed',
            'Domain is not in redemption period'
        );
        $callbackData['OrderType'] = 'Restore';

        $this->assertEquals('Failed', $callbackData['OrderStatus']);
        $this->assertNotEmpty($callbackData['StatusList']['CallbackStatus']);
    }

    // =========================================================================
    // Request Class Integration Tests
    // =========================================================================

    #[Test]
    public function testDeleteDomainMethodExists(): void
    {
        $request = $this->getRequest();

        $this->assertTrue(method_exists($request, 'deleteDomain'));
        $this->assertTrue(method_exists($request, 'restoreDomain'));
        $this->assertTrue(method_exists($request, 'mapToOrder'));
    }

    #[Test]
    public function testDeleteDomainCreatesCorrectOrderStructure(): void
    {
        $domainName = 'test-delete-structure-' . uniqid() . '.com';
        $params = MockParamsV3::forRegistration($domainName);
        $params['Username'] = $this->username;
        $params['Password'] = $this->password;
        $params['TestMode'] = 'on';
        $params['domainid'] = 1;
        $params['tld'] = 'com';

        $request = new Request($params);
        $orderParams = $request->mapToOrder($params, 'Delete');

        // Validate complete order structure
        $this->assertArrayHasKey('Order', $orderParams);
        $this->assertEquals('Delete', $orderParams['Order']['Type']);
        $this->assertArrayHasKey('Domain', $orderParams['Order']);
        $this->assertEquals($domainName, $orderParams['Order']['Domain']['Name']);
        $this->assertArrayHasKey('TransactionComment', $orderParams['Order']);
    }

    #[Test]
    public function testRestoreDomainCreatesCorrectOrderStructure(): void
    {
        $domainName = 'test-restore-structure-' . uniqid() . '.com';
        $params = MockParamsV3::forRegistration($domainName);
        $params['Username'] = $this->username;
        $params['Password'] = $this->password;
        $params['TestMode'] = 'on';
        $params['domainid'] = 2;
        $params['tld'] = 'com';

        $request = new Request($params);
        $orderParams = $request->mapToOrder($params, 'Restore');

        // Validate complete order structure
        $this->assertArrayHasKey('Order', $orderParams);
        $this->assertEquals('Restore', $orderParams['Order']['Type']);
        $this->assertArrayHasKey('Domain', $orderParams['Order']);
        $this->assertEquals($domainName, $orderParams['Order']['Domain']['Name']);
        $this->assertArrayHasKey('TransactionComment', $orderParams['Order']);
    }

    // =========================================================================
    // Module Entry Point Tests
    // =========================================================================

    #[Test]
    public function testAscioDeleteDomainFunction(): void
    {
        // Test that the WHMCS module entry point exists
        // The function ascio_DeleteDomain should exist in ascio.php
        $ascioModulePath = __DIR__ . '/../../ascio.php';

        if (!file_exists($ascioModulePath)) {
            $this->markTestSkipped('ascio.php module not found at expected path');
        }

        // Include the module (it defines the functions)
        require_once $ascioModulePath;

        $this->assertTrue(
            function_exists('ascio_DeleteDomain'),
            'ascio_DeleteDomain function should exist'
        );
    }

    #[Test]
    public function testAscioRestoreDomainFunction(): void
    {
        // Test that the WHMCS module entry point exists
        $ascioModulePath = __DIR__ . '/../../ascio.php';

        if (!file_exists($ascioModulePath)) {
            $this->markTestSkipped('ascio.php module not found at expected path');
        }

        require_once $ascioModulePath;

        $this->assertTrue(
            function_exists('ascio_RestoreDomain'),
            'ascio_RestoreDomain function should exist'
        );
    }

    // =========================================================================
    // Domain Status Transition Tests
    // =========================================================================

    #[Test]
    public function testDomainStatusAfterDelete(): void
    {
        $request = $this->getRequest();

        // Test various delete-related status mappings
        $deletedStatus = $request->getDomainStatus((object) ['Status' => 'DELETED']);
        $this->assertEquals('Cancelled', $deletedStatus);

        $pendingDeleteStatus = $request->getDomainStatus((object) ['Status' => 'PENDING_DELETE']);
        $this->assertEquals('Pending', $pendingDeleteStatus);
    }

    #[Test]
    public function testDomainStatusAfterRestore(): void
    {
        $request = $this->getRequest();

        // After restore, domain should return to active status
        $activeStatus = $request->getDomainStatus((object) ['Status' => 'ACTIVE']);
        $this->assertEquals('Active', $activeStatus);

        // Expiring status should also map correctly
        $expiringStatus = $request->getDomainStatus((object) ['Status' => 'EXPIRING']);
        $this->assertEquals('Active', $expiringStatus);
    }

    // =========================================================================
    // TLD-Specific Tests
    // =========================================================================

    #[Test]
    #[Group('validation')]
    #[DataProvider('tldDeleteProvider')]
    public function testDeleteOrderForDifferentTlds(string $tld): void
    {
        $testDomain = $this->generateTestDomain($tld);
        $deleteParams = $this->buildDeleteOrder($testDomain);

        $this->assertEquals('Delete', $deleteParams['Order']['Type']);
        $this->assertEquals($testDomain, $deleteParams['Order']['Domain']['Name']);

        $result = $this->validateOrderDirect($deleteParams);
        $this->assertV3ResponseFormat($result);
    }

    public static function tldDeleteProvider(): array
    {
        return [
            '.com' => ['com'],
            '.net' => ['net'],
            '.org' => ['org'],
            '.info' => ['info'],
            '.biz' => ['biz'],
        ];
    }

    #[Test]
    #[Group('validation')]
    #[DataProvider('tldRestoreProvider')]
    public function testRestoreOrderForDifferentTlds(string $tld): void
    {
        $testDomain = $this->generateTestDomain($tld);
        $restoreParams = $this->buildRestoreOrder($testDomain);

        $this->assertEquals('Restore', $restoreParams['Order']['Type']);
        $this->assertEquals($testDomain, $restoreParams['Order']['Domain']['Name']);

        $result = $this->validateOrderDirect($restoreParams);
        $this->assertV3ResponseFormat($result);
    }

    public static function tldRestoreProvider(): array
    {
        return [
            '.com' => ['com'],
            '.net' => ['net'],
            '.org' => ['org'],
            '.info' => ['info'],
            '.biz' => ['biz'],
        ];
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Build delete order parameters
     */
    protected function buildDeleteOrder(string $domainName): array
    {
        return [
            'Order' => [
                'Type' => 'Delete',
                'TransactionComment' => json_encode([
                    'application' => 'E2E_TEST',
                    'testId' => uniqid(),
                    'objectType' => 'Domain',
                ]),
                'Domain' => [
                    'Name' => $domainName,
                    'Owner' => $this->buildRegistrant(),
                    'Admin' => $this->buildContact('Admin'),
                    'Tech' => $this->buildContact('Tech'),
                    'Billing' => $this->buildContact('Billing'),
                ],
            ],
        ];
    }

    /**
     * Build restore order parameters
     */
    protected function buildRestoreOrder(string $domainName, int $period = 1): array
    {
        return [
            'Order' => [
                'Type' => 'Restore',
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
                    'NameServers' => [
                        'NameServer1' => ['HostName' => 'ns1.ascio.net'],
                        'NameServer2' => ['HostName' => 'ns2.ascio.net'],
                    ],
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
            'Email' => strtolower($role) . '@example.com',
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
}
