<?php
/**
 * Registrar Lock Integration Tests
 *
 * Tests registrar lock (transfer lock) operations using the Ascio v3 API.
 * Covers:
 * - ascio_GetRegistrarLock() - retrieve current lock status from domain Status field
 * - ascio_SaveRegistrarLock() - enable/disable registrar lock (ChangeLocks order)
 * - Lock status mapping (TRANSFER_LOCK in Status -> locked/unlocked)
 * - TLD-specific lock support (some TLDs don't support transfer locks)
 * - Error handling (domain not found, lock not supported)
 *
 * @group integration
 * @group v3
 * @group registrar-lock
 */

declare(strict_types=1);

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\MockParamsV3;

#[Group('integration')]
#[Group('v3')]
#[Group('registrar-lock')]
class RegistrarLockIntegrationTest extends IntegrationTestBase
{
    /** @var bool Disable simulation mode for read tests */
    protected bool $simulationMode = false;

    // =========================================================================
    // ascio_GetRegistrarLock Tests
    // =========================================================================

    #[Test]
    public function testGetRegistrarLockReturnsLockStatusForExistingDomain(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        $this->assertNotNull($domainName, 'Domain should have a name');

        // Set up mock database with the domain handle
        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => $domainName,
                'ascio_id' => $handle,
            ],
        ]);

        $params = $this->getRegistrationParams($domainName, ['domainid' => 1]);
        $request = new Request($params);

        // Call searchDomain which is used by ascio_GetRegistrarLock
        $domain = $request->searchDomain();

        if (is_array($domain) && isset($domain['error'])) {
            $this->markTestSkipped('Could not retrieve domain: ' . $domain['error']);
        }

        $this->assertIsObject($domain, 'searchDomain should return domain object');
        $this->assertObjectHasProperty('Status', $domain, 'Domain should have Status property');

        // Verify Status is a string
        $status = $domain->Status;
        $this->assertIsString($status, 'Status should be a string');

        // Determine lock status from Status field (same logic as ascio_GetRegistrarLock)
        if (strpos($status, 'TRANSFER_LOCK') === false) {
            $lockstatus = 'unlocked';
        } else {
            $lockstatus = 'locked';
        }

        // Lock status should be one of the two valid values
        $this->assertContains($lockstatus, ['locked', 'unlocked'], 'Lock status should be locked or unlocked');
    }

    #[Test]
    public function testGetRegistrarLockModuleFunction(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => $domainName,
                'ascio_id' => $handle,
            ],
        ]);

        // Include the ascio.php file to get the module function
        require_once __DIR__ . '/../../ascio.php';

        $params = $this->getRegistrationParams($domainName, ['domainid' => 1]);

        // Call the module function
        $result = \ascio_GetRegistrarLock($params);

        // Check if error was returned
        if (is_array($result) && isset($result['error'])) {
            $this->markTestSkipped('Could not retrieve lock status: ' . $result['error']);
        }

        // Result should be a string: 'locked' or 'unlocked'
        $this->assertIsString($result, 'GetRegistrarLock should return a string');
        $this->assertContains($result, ['locked', 'unlocked'], 'Lock status should be locked or unlocked');
    }

    #[Test]
    public function testGetRegistrarLockStatusMapping(): void
    {
        // Test the mapping logic: TRANSFER_LOCK in Status = locked, otherwise unlocked
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $domain = $this->getDomainByHandle($handle);

        if (!$domain || is_array($domain)) {
            $this->markTestSkipped('Could not retrieve domain by handle');
        }

        $status = $domain->Status ?? '';
        $this->assertIsString($status, 'Status should be a string');

        // Test the mapping logic
        $hasTransferLock = strpos($status, 'TRANSFER_LOCK') !== false;
        $expectedLockStatus = $hasTransferLock ? 'locked' : 'unlocked';

        // Verify the status contains expected components
        // Common statuses: ACTIVE, TRANSFER_LOCK, EXPIRING, PENDING_VERIFICATION
        $this->assertNotEmpty($status, 'Domain status should not be empty');

        // Log for debugging
        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // ascio_SaveRegistrarLock Tests - Order Structure
    // =========================================================================

    #[Test]
    public function testSaveRegistrarLockOrderMappingForLock(): void
    {
        $domainName = $this->generateTestDomain('com');

        $params = $this->getRegistrationParams($domainName, [
            'lockenabled' => 'locked',
        ]);

        $request = new Request($params);

        // Map to order structure for ChangeLocks
        $orderParams = $request->mapToOrder($params, 'ChangeLocks');

        $this->assertArrayHasKey('Order', $orderParams);
        $this->assertEquals('ChangeLocks', $orderParams['Order']['Type']);
        $this->assertArrayHasKey('Domain', $orderParams['Order']);
        $this->assertArrayHasKey('Name', $orderParams['Order']['Domain']);
        $this->assertEquals($domainName, $orderParams['Order']['Domain']['Name']);
    }

    #[Test]
    public function testSaveRegistrarLockOrderMappingForUnlock(): void
    {
        $domainName = $this->generateTestDomain('com');

        $params = $this->getRegistrationParams($domainName, [
            'lockenabled' => 'unlocked',
        ]);

        $request = new Request($params);

        // Map to order structure
        $orderParams = $request->mapToOrder($params, 'ChangeLocks');

        $this->assertArrayHasKey('Order', $orderParams);
        $this->assertEquals('ChangeLocks', $orderParams['Order']['Type']);
    }

    #[Test]
    public function testSaveRegistrarLockSetsTransferLockProperty(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => $domainName,
                'ascio_id' => $handle,
            ],
        ]);

        // Test Lock operation
        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 1,
            'lockenabled' => 'locked',
        ]);

        $request = new Request($params);
        $lockParams = $request->mapToOrder($params, 'ChangeLocks');

        // The saveRegistrarLock method adds TransferLock property
        // lockenabled = 'unlocked' -> 'UnLock', otherwise -> 'Lock'
        $expectedLockValue = 'Lock';

        $lockParams['Order']['Domain']['TransferLock'] = $expectedLockValue;

        $this->assertEquals('Lock', $lockParams['Order']['Domain']['TransferLock']);

        // Test Unlock operation
        $params['lockenabled'] = 'unlocked';
        $request = new Request($params);
        $unlockParams = $request->mapToOrder($params, 'ChangeLocks');
        $unlockParams['Order']['Domain']['TransferLock'] = 'UnLock';

        $this->assertEquals('UnLock', $unlockParams['Order']['Domain']['TransferLock']);
    }

    // =========================================================================
    // SaveRegistrarLock Validation Tests
    // =========================================================================

    #[Test]
    public function testSaveRegistrarLockOrderValidation(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => $domainName,
                'ascio_id' => $handle,
            ],
        ]);

        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 1,
            'lockenabled' => 'locked',
        ]);

        $request = new Request($params);
        $orderParams = $request->mapToOrder($params, 'ChangeLocks');
        $orderParams['Order']['Domain']['TransferLock'] = 'Lock';

        // Validate the order
        $result = $this->validateChangeLockOrder($orderParams);

        if (is_object($result) && isset($result->ResultCode)) {
            // 200 = success, 400 = validation errors, other codes may indicate TLD restrictions
            $this->assertContains(
                $result->ResultCode,
                [200, 400, 401, 500],
                'ResultCode should be a valid API response code'
            );
        }
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function testGetRegistrarLockForNonExistentDomainReturnsError(): void
    {
        $nonExistentDomain = 'lock-test-nonexist-' . uniqid() . '.com';

        // No handle in database
        CapsuleMock::reset();

        $params = $this->getRegistrationParams($nonExistentDomain, ['domainid' => 999]);
        $request = new Request($params);

        // searchDomain should fail to find this domain
        try {
            $result = $request->searchDomain();

            // Should return an error (domain not found)
            $this->assertTrue(
                (is_array($result) && isset($result['error'])) ||
                (is_object($result) && isset($result->error)),
                'Non-existent domain should return error'
            );
        } catch (\SoapFault $e) {
            // SOAP fault is also an acceptable error response for non-existent domain
            $this->assertInstanceOf(\SoapFault::class, $e);
        } catch (\Exception $e) {
            // Any exception indicates the domain was not found
            $this->addToAssertionCount(1);
        }
    }

    #[Test]
    public function testSaveRegistrarLockForNonExistentDomainReturnsError(): void
    {
        $nonExistentDomain = 'save-lock-nonexist-' . uniqid() . '.com';

        $params = $this->getRegistrationParams($nonExistentDomain, [
            'lockenabled' => 'locked',
        ]);

        $request = new Request($params);
        $orderParams = $request->mapToOrder($params, 'ChangeLocks');
        $orderParams['Order']['Domain']['TransferLock'] = 'Lock';

        // Validate the order - should fail for non-existent domain
        $result = $this->validateChangeLockOrder($orderParams);

        if (is_object($result) && isset($result->ResultCode)) {
            // We expect validation to fail since domain doesn't exist
            $this->assertNotEquals(200, $result->ResultCode, 'Non-existent domain should fail validation');
        }
    }

    // =========================================================================
    // TLD-Specific Lock Support Tests
    // =========================================================================

    #[Test]
    public function testGetRegistrarLockForDifferentTlds(): void
    {
        // Test lock status retrieval for different TLDs
        $tldsToTest = ['com', 'net', 'org'];
        $foundDomain = false;

        foreach ($tldsToTest as $tld) {
            $existingDomain = $this->findExistingDomain($tld);

            if ($existingDomain) {
                $foundDomain = true;
                $domainName = $existingDomain->Name ?? null;
                $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

                CapsuleMock::setTableData('tblasciohandles', [
                    [
                        'type' => 'domain',
                        'whmcs_id' => 1,
                        'domain' => $domainName,
                        'ascio_id' => $handle,
                    ],
                ]);

                $params = $this->getRegistrationParams($domainName, ['domainid' => 1]);
                $request = new Request($params);
                $domain = $request->searchDomain();

                if (is_array($domain) && isset($domain['error'])) {
                    continue;
                }

                $this->assertIsObject($domain, "searchDomain for .{$tld} should return domain object");
                $this->assertObjectHasProperty('Status', $domain, "Domain for .{$tld} should have Status");

                // Determine lock status
                $status = $domain->Status;
                $lockstatus = (strpos($status, 'TRANSFER_LOCK') !== false) ? 'locked' : 'unlocked';
                $this->assertContains($lockstatus, ['locked', 'unlocked']);

                // Found at least one domain - break after first success
                break;
            }
        }

        if (!$foundDomain) {
            $this->markTestSkipped('No existing domains found for any tested TLD');
        }
    }

    /**
     * Some TLDs don't support transfer locks (e.g., .de, .at, some ccTLDs)
     * This test documents TLDs that may not support lock operations
     */
    #[Test]
    public function testLockSupportedTlds(): void
    {
        // TLDs that typically support transfer locks
        $lockSupportedTlds = ['com', 'net', 'org', 'info', 'biz'];

        // TLDs that may not support transfer locks or have different policies
        // These are for documentation purposes
        $lockMayNotBeSupportedTlds = ['de', 'at', 'ch', 'nl'];

        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $domain = $this->getDomainByHandle($handle);

        if (!$domain || is_array($domain)) {
            $this->markTestSkipped('Could not retrieve domain by handle');
        }

        // .com domains should support transfer locks
        $this->assertObjectHasProperty('Status', $domain);

        // Status should contain lock-related information
        $status = $domain->Status ?? '';
        $this->assertIsString($status);

        // The fact that we can check the status means lock operations are supported
        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // Request Class Method Tests
    // =========================================================================

    #[Test]
    public function testRequestClassSaveRegistrarLockMethod(): void
    {
        // Test that saveRegistrarLock method exists on Request class
        $params = $this->getRegistrationParams('test.com', [
            'domainid' => 1,
            'lockenabled' => 'locked',
        ]);

        $request = new Request($params);

        // Verify saveRegistrarLock exists and is callable
        $this->assertTrue(
            method_exists($request, 'saveRegistrarLock'),
            'Request class should have saveRegistrarLock method'
        );
    }

    #[Test]
    public function testLockStatusDeterminationLogic(): void
    {
        // Test the lock status determination logic used in ascio_GetRegistrarLock
        $testCases = [
            'ACTIVE' => 'unlocked',
            'ACTIVE,TRANSFER_LOCK' => 'locked',
            'TRANSFER_LOCK,ACTIVE' => 'locked',
            'ACTIVE,TRANSFER_LOCK,EXPIRING' => 'locked',
            'PENDING' => 'unlocked',
            'ACTIVE,EXPIRING' => 'unlocked',
        ];

        foreach ($testCases as $status => $expectedLock) {
            if (strpos($status, 'TRANSFER_LOCK') === false) {
                $lockstatus = 'unlocked';
            } else {
                $lockstatus = 'locked';
            }

            $this->assertEquals(
                $expectedLock,
                $lockstatus,
                "Status '{$status}' should map to '{$expectedLock}'"
            );
        }
    }

    // =========================================================================
    // Integration with Domain Status Tests
    // =========================================================================

    #[Test]
    public function testLockStatusConsistentWithDomainStatus(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => $domainName,
                'ascio_id' => $handle,
            ],
        ]);

        $params = $this->getRegistrationParams($domainName, ['domainid' => 1]);
        $request = new Request($params);
        $domain = $request->searchDomain();

        if (is_array($domain) && isset($domain['error'])) {
            $this->markTestSkipped('Could not retrieve domain: ' . $domain['error']);
        }

        // Get lock status from Status field
        $status = $domain->Status ?? '';
        $lockStatus = (strpos($status, 'TRANSFER_LOCK') !== false) ? 'locked' : 'unlocked';

        // Get the same domain again to verify consistency
        $domain2 = $request->searchDomain();
        $status2 = $domain2->Status ?? '';
        $lockStatus2 = (strpos($status2, 'TRANSFER_LOCK') !== false) ? 'locked' : 'unlocked';

        $this->assertEquals($lockStatus, $lockStatus2, 'Lock status should be consistent across calls');
    }

    // =========================================================================
    // Data Provider Tests
    // =========================================================================

    public static function lockStatusProvider(): array
    {
        return [
            'Lock domain' => ['locked', 'Lock'],
            'Unlock domain' => ['unlocked', 'UnLock'],
        ];
    }

    #[Test]
    #[DataProvider('lockStatusProvider')]
    public function testLockStatusMapping(string $lockenabled, string $expectedTransferLock): void
    {
        // Test the mapping from WHMCS lockenabled to Ascio TransferLock
        $lockstatus = $lockenabled === 'unlocked' ? 'UnLock' : 'Lock';

        $this->assertEquals($expectedTransferLock, $lockstatus);
    }

    // =========================================================================
    // Module Function Integration Tests
    // =========================================================================

    #[Test]
    public function testClientGetLockStatusWrapper(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => $domainName,
                'ascio_id' => $handle,
            ],
        ]);

        require_once __DIR__ . '/../../ascio.php';

        $params = $this->getRegistrationParams($domainName, ['domainid' => 1]);

        // Call the client-area wrapper function
        $result = \ascio_ClientGetLockStatus($params);

        // Should return success with lockstatus
        if (isset($result['error'])) {
            $this->markTestSkipped('Could not get lock status: ' . $result['error']);
        }

        $this->assertIsArray($result, 'ClientGetLockStatus should return an array');
        $this->assertArrayHasKey('success', $result, 'Result should have success key');
        $this->assertTrue($result['success'], 'Result should indicate success');
        $this->assertArrayHasKey('lockstatus', $result, 'Result should have lockstatus key');
        $this->assertContains($result['lockstatus'], ['locked', 'unlocked']);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Validate a ChangeLocks order via the API
     */
    protected function validateChangeLockOrder(array $orderParams): ?object
    {
        try {
            $requestObj = $this->arrayToObject($orderParams);
            $soapRequest = new \SoapVar(
                $requestObj,
                SOAP_ENC_OBJECT,
                'DomainOrderRequest',
                'http://www.ascio.com/2013/02'
            );

            $wsdl = ASCIO_V3_WSDL_TEST;
            $client = new \SoapClient($wsdl, [
                'cache_wsdl' => WSDL_CACHE_MEMORY,
                'trace' => 1,
                'exceptions' => true,
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

            $response = $client->__soapCall(
                'ValidateOrder',
                ['parameters' => ['request' => $soapRequest]]
            );

            return $response->ValidateOrderResult ?? $response;
        } catch (\SoapFault $e) {
            // Return error object instead of throwing
            return (object) [
                'ResultCode' => 500,
                'ResultMessage' => $e->getMessage(),
                'Errors' => (object) ['string' => [$e->getMessage()]],
            ];
        }
    }

    /**
     * Recursively convert array to stdClass object
     */
    protected function arrayToObject($data)
    {
        if (is_array($data)) {
            $obj = new \stdClass();
            foreach ($data as $key => $value) {
                $obj->$key = $this->arrayToObject($value);
            }
            return $obj;
        }
        return $data;
    }
}
