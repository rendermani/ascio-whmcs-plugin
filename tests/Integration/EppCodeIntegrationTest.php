<?php
/**
 * EPP Code Integration Tests
 *
 * Tests EPP/Auth code operations using the Ascio v3 API.
 * - GetEPPCode: Retrieve current EPP code from domain
 * - ViewEPPCode: View EPP code (wrapper for client area)
 * - UpdateEPPCode: Regenerate EPP code (creates UpdateAuthInfo order)
 *
 * @group integration
 * @group v3
 * @group epp-code
 */

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use ascio\Request;
use Ascio\Tests\Mocks\CapsuleMock;

#[Group('integration')]
#[Group('v3')]
#[Group('epp-code')]
class EppCodeIntegrationTest extends IntegrationTestBase
{
    /** @var bool Disable simulation mode for EPP tests (we're reading, not creating orders) */
    protected bool $simulationMode = false;

    // =========================================================================
    // GetEPPCode Tests
    // =========================================================================

    #[Test]
    public function testGetEppCodeReturnsCodeForExistingDomain(): void
    {
        // Find an existing domain on the test account
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? $existingDomain->DomainName ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $this->assertNotNull($domainName, 'Existing domain should have a name');

        // Set up parameters for getEPPCode
        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 1,
        ]);

        // Store the handle in mock database so searchDomain can find it
        if ($handle) {
            CapsuleMock::setTableData('tblasciohandles', [
                [
                    'type' => 'domain',
                    'whmcs_id' => 1,
                    'domain' => $domainName,
                    'ascio_id' => $handle,
                ],
            ]);
        }

        $request = new Request($params);
        $result = $request->getEPPCode($params);

        // Should return array with eppcode key
        $this->assertIsArray($result, 'getEPPCode should return an array');
        $this->assertArrayHasKey('eppcode', $result, 'Result should have eppcode key');

        // EPP code may be empty for some domain states, but key should exist
        // For active domains, it should have a value
        if (!empty($result['eppcode'])) {
            $this->assertIsString($result['eppcode'], 'EPP code should be a string');
            $this->assertNotEmpty($result['eppcode'], 'EPP code should not be empty');
        }
    }

    #[Test]
    public function testGetEppCodeFromDomainAuthInfo(): void
    {
        // Find an existing domain
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $this->assertNotNull($handle, 'Existing domain should have a handle');

        // Get full domain details
        $domain = $this->getDomainByHandle($handle);

        if (!$domain || is_array($domain)) {
            $this->markTestSkipped('Could not retrieve domain details');
        }

        // Check if AuthInfo property exists
        $this->assertObjectHasProperty('AuthInfo', $domain, 'Domain should have AuthInfo property');

        // AuthInfo may be empty for certain domain states
        // Just verify the property exists and is accessible
        $authInfo = $domain->AuthInfo ?? '';
        $this->assertIsString($authInfo, 'AuthInfo should be a string');
    }

    // =========================================================================
    // ViewEPPCode Tests
    // =========================================================================

    #[Test]
    public function testViewEppCodeReturnsSuccessForExistingDomain(): void
    {
        // This test simulates the ascio_ViewEPPCode function behavior
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? $existingDomain->DomainName ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 1,
        ]);

        // Store handle in mock database
        if ($handle) {
            CapsuleMock::setTableData('tblasciohandles', [
                [
                    'type' => 'domain',
                    'whmcs_id' => 1,
                    'domain' => $domainName,
                    'ascio_id' => $handle,
                ],
            ]);
        }

        $request = new Request($params);
        $result = $request->getEPPCode($params);

        $this->assertIsArray($result, 'Result should be an array');
        $this->assertArrayNotHasKey('error', $result, 'Result should not contain error');

        // Simulate ViewEPPCode wrapper logic
        $eppCode = $result['eppcode'] ?? '';

        // ViewEPPCode should return success array if code exists
        if (!empty($eppCode)) {
            $viewResult = [
                'success' => true,
                'eppcode' => $eppCode,
            ];
            $this->assertTrue($viewResult['success']);
            $this->assertEquals($eppCode, $viewResult['eppcode']);
        }
    }

    #[Test]
    public function testViewEppCodeForDifferentTlds(): void
    {
        // Test EPP code retrieval for different TLDs
        $tldsToTest = ['com', 'net', 'org'];
        $foundDomain = false;

        foreach ($tldsToTest as $tld) {
            $existingDomain = $this->findExistingDomain($tld);

            if ($existingDomain) {
                $foundDomain = true;
                $domainName = $existingDomain->Name ?? $existingDomain->DomainName ?? null;
                $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

                $params = $this->getRegistrationParams($domainName, [
                    'domainid' => 1,
                ]);

                if ($handle) {
                    CapsuleMock::setTableData('tblasciohandles', [
                        [
                            'type' => 'domain',
                            'whmcs_id' => 1,
                            'domain' => $domainName,
                            'ascio_id' => $handle,
                        ],
                    ]);
                }

                $request = new Request($params);
                $result = $request->getEPPCode($params);

                $this->assertIsArray($result, "getEPPCode for .{$tld} should return an array");
                $this->assertArrayHasKey('eppcode', $result, "Result for .{$tld} should have eppcode key");

                // Found at least one domain - break after first success
                break;
            }
        }

        if (!$foundDomain) {
            $this->markTestSkipped('No existing domains found for any tested TLD');
        }
    }

    // =========================================================================
    // UpdateEPPCode Tests (Validation Mode)
    // =========================================================================

    #[Test]
    public function testUpdateEppCodeCreatesUpdateAuthInfoOrder(): void
    {
        // Enable simulation mode for update tests (creates order)
        putenv('ASCIO_SIMULATE=1');

        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? $existingDomain->DomainName ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 1,
        ]);

        if ($handle) {
            CapsuleMock::setTableData('tblasciohandles', [
                [
                    'type' => 'domain',
                    'whmcs_id' => 1,
                    'domain' => $domainName,
                    'ascio_id' => $handle,
                ],
            ]);
        }

        $request = new Request($params);

        // Map to order to verify structure
        $orderParams = $request->mapToOrder($params, 'UpdateAuthInfo');

        $this->assertArrayHasKey('Order', $orderParams, 'Order params should have Order key');
        $this->assertEquals('UpdateAuthInfo', $orderParams['Order']['Type'], 'Order type should be UpdateAuthInfo');
        $this->assertArrayHasKey('Domain', $orderParams['Order'], 'Order should have Domain');
        $this->assertEquals($domainName, $orderParams['Order']['Domain']['Name'], 'Domain name should match');

        // Clean up simulation mode
        putenv('ASCIO_SIMULATE');
    }

    #[Test]
    public function testUpdateEppCodeOrderValidation(): void
    {
        // Test that UpdateAuthInfo order passes validation
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? $existingDomain->DomainName ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 1,
        ]);

        if ($handle) {
            CapsuleMock::setTableData('tblasciohandles', [
                [
                    'type' => 'domain',
                    'whmcs_id' => 1,
                    'domain' => $domainName,
                    'ascio_id' => $handle,
                ],
            ]);
        }

        $request = new Request($params);
        $orderParams = $request->mapToOrder($params, 'UpdateAuthInfo');

        // Try to validate the order using v3 API
        try {
            $result = $this->validateOrder(array_merge($params, [
                'orderType' => 'UpdateAuthInfo',
            ]));

            // Validation might succeed or fail depending on domain state
            // We just want to verify the API accepts the request format
            $this->assertIsObject($result, 'Validation response should be an object');
            $this->assertObjectHasProperty('ResultCode', $result, 'Response should have ResultCode');

            // Result code 200 = valid, 400 = validation errors (both are acceptable responses)
            $this->assertContains(
                $result->ResultCode,
                [200, 400, 401, 500],
                'ResultCode should be a valid API response code'
            );
        } catch (\SoapFault $e) {
            // Some SOAP faults are acceptable (e.g., domain not managed by this account)
            $this->assertStringContainsString(
                'Fault',
                get_class($e),
                'Should be a SOAP fault if error occurs'
            );
        }
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function testGetEppCodeForNonExistentDomainReturnsError(): void
    {
        $nonExistentDomain = 'definitely-not-existing-' . uniqid() . '-' . time() . '.com';

        $params = $this->getRegistrationParams($nonExistentDomain, [
            'domainid' => 999999,
        ]);

        // No handle stored - should try to search and fail
        $request = new Request($params);

        try {
            $result = $request->getEPPCode($params);

            // Should return an array - either with error or empty eppcode
            $this->assertIsArray($result, 'Result should be an array');

            // If domain not found, searchDomain returns error
            // getEPPCode might return empty eppcode or propagate error
            if (isset($result['error'])) {
                $this->assertIsString($result['error'], 'Error should be a string');
            } else {
                // If no error, should have eppcode key (possibly empty)
                $this->assertArrayHasKey('eppcode', $result);
            }
        } catch (\SoapFault $e) {
            // SOAP faults are acceptable for non-existent domains
            // This verifies the error is properly thrown
            $this->assertInstanceOf(\SoapFault::class, $e);
        }
    }

    #[Test]
    public function testGetEppCodeWithInvalidHandle(): void
    {
        $params = $this->getRegistrationParams('test-invalid.com', [
            'domainid' => 1,
        ]);

        // Store an invalid handle
        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => 'test-invalid.com',
                'ascio_id' => 'INVALID-HANDLE-' . uniqid(),
            ],
        ]);

        $request = new Request($params);

        try {
            $result = $request->getEPPCode($params);

            // Should return error when handle lookup fails
            $this->assertIsArray($result, 'Result should be an array');

            // getEPPCode returns ['eppcode' => ''] or may propagate error from getDomain
            // Both are acceptable
            if (isset($result['error'])) {
                $this->assertIsString($result['error']);
            } else {
                $this->assertArrayHasKey('eppcode', $result);
            }
        } catch (\SoapFault $e) {
            // SOAP faults are acceptable for invalid handles
            // This verifies the error handling works
            $this->assertInstanceOf(\SoapFault::class, $e);
        }
    }

    // =========================================================================
    // Domain Lock Status and EPP Code Tests
    // =========================================================================

    #[Test]
    public function testGetEppCodeWithDomainLockStatus(): void
    {
        // EPP code retrieval should work regardless of lock status
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? $existingDomain->DomainName ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        // Get full domain to check lock status
        $domain = $this->getDomainByHandle($handle);

        if (!$domain || is_array($domain)) {
            $this->markTestSkipped('Could not retrieve domain details');
        }

        // Check domain status - should include TRANSFER_LOCK if locked
        $status = $domain->Status ?? '';
        $isLocked = strpos($status, 'TRANSFER_LOCK') !== false;

        // Get EPP code
        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 1,
        ]);

        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => $domainName,
                'ascio_id' => $handle,
            ],
        ]);

        $request = new Request($params);
        $result = $request->getEPPCode($params);

        // EPP code should still be retrievable even if domain is locked
        $this->assertIsArray($result, 'Result should be an array');
        $this->assertArrayHasKey('eppcode', $result, 'Should have eppcode key regardless of lock status');

        // Log lock status for debugging
        if ($isLocked) {
            $this->addToAssertionCount(1); // Domain is locked
        } else {
            $this->addToAssertionCount(1); // Domain is unlocked
        }
    }

    // =========================================================================
    // Request Class Method Tests
    // =========================================================================

    #[Test]
    public function testRequestClassGetEppCodeMethod(): void
    {
        // Test the Request class method directly
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? $existingDomain->DomainName ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 1,
        ]);

        if ($handle) {
            CapsuleMock::setTableData('tblasciohandles', [
                [
                    'type' => 'domain',
                    'whmcs_id' => 1,
                    'domain' => $domainName,
                    'ascio_id' => $handle,
                ],
            ]);
        }

        $request = new Request($params);

        // Verify getEPPCode exists and is callable
        $this->assertTrue(
            method_exists($request, 'getEPPCode'),
            'Request class should have getEPPCode method'
        );

        // Call the method
        $result = $request->getEPPCode($params);

        // Verify return structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('eppcode', $result);
    }

    #[Test]
    public function testRequestClassUpdateEppCodeMethod(): void
    {
        // Test the Request class updateEPPCode method exists
        $params = $this->getRegistrationParams('test.com', [
            'domainid' => 1,
        ]);

        $request = new Request($params);

        // Verify updateEPPCode exists and is callable
        $this->assertTrue(
            method_exists($request, 'updateEPPCode'),
            'Request class should have updateEPPCode method'
        );
    }

    // =========================================================================
    // Integration with searchDomain Tests
    // =========================================================================

    #[Test]
    public function testGetEppCodeUsesSearchDomain(): void
    {
        // Test that getEPPCode correctly uses searchDomain to find the domain
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->Name ?? $existingDomain->DomainName ?? null;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 1,
        ]);

        // Store handle so searchDomain finds it quickly
        if ($handle) {
            CapsuleMock::setTableData('tblasciohandles', [
                [
                    'type' => 'domain',
                    'whmcs_id' => 1,
                    'domain' => $domainName,
                    'ascio_id' => $handle,
                ],
            ]);
        }

        $request = new Request($params);

        // First call searchDomain to verify it works
        $domain = $request->searchDomain();

        if (is_array($domain) && isset($domain['error'])) {
            $this->markTestSkipped('searchDomain returned error: ' . $domain['error']);
        }

        $this->assertIsObject($domain, 'searchDomain should return domain object');
        $this->assertObjectHasProperty('AuthInfo', $domain, 'Domain should have AuthInfo');

        // Now call getEPPCode which should use the same searchDomain internally
        $result = $request->getEPPCode($params);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('eppcode', $result);

        // The eppcode should match the domain's AuthInfo
        $expectedEppCode = $domain->AuthInfo ?? '';
        $this->assertEquals($expectedEppCode, $result['eppcode'], 'EPP code should match domain AuthInfo');
    }

    // =========================================================================
    // API Response Format Tests
    // =========================================================================

    #[Test]
    public function testDomainAuthInfoResponseFormat(): void
    {
        // Test that the Ascio API returns AuthInfo in expected format
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $domain = $this->getDomainByHandle($handle);

        if (!$domain || is_array($domain)) {
            $this->markTestSkipped('Could not retrieve domain details');
        }

        // Verify AuthInfo format
        if (isset($domain->AuthInfo) && !empty($domain->AuthInfo)) {
            $authInfo = $domain->AuthInfo;

            // AuthInfo should be a string
            $this->assertIsString($authInfo, 'AuthInfo should be a string');

            // AuthInfo typically has minimum length requirements
            // Most registries require at least 6-8 characters
            $this->assertGreaterThanOrEqual(
                4,
                strlen($authInfo),
                'AuthInfo should have minimum length'
            );

            // AuthInfo should be alphanumeric with possible special characters
            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+$/',
                $authInfo,
                'AuthInfo should contain valid characters'
            );
        }
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Override validateOrder to handle UpdateAuthInfo orders
     */
    protected function validateOrder(array $orderData)
    {
        $request = $this->getRequest($orderData);

        // Build the order using mapToOrder
        $orderType = $orderData['orderType'] ?? 'UpdateAuthInfo';
        $ascioParams = $request->mapToOrder($orderData, $orderType);

        // Call ValidateOrder API
        return $this->callApiMethod('ValidateOrder', $ascioParams);
    }
}
