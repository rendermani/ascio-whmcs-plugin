<?php
/**
 * Domain Search Integration Tests
 *
 * Tests domain search and retrieval operations using existing domains
 * on the Ascio test account.
 *
 * @group integration
 * @group v3
 * @group domain-search
 */

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use ascio\v3\domains\RequestV3;
use Ascio\Tests\Mocks\CapsuleMock;

#[Group('integration')]
#[Group('v3')]
#[Group('domain-search')]
class DomainSearchTest extends IntegrationTestBase
{
    /** @var bool Disable simulation mode for search tests */
    protected bool $simulationMode = false;

    // =========================================================================
    // GetDomain Tests
    // =========================================================================

    #[Test]
    public function testGetDomainByHandle(): void
    {
        // First, find an existing domain to get its handle
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $this->assertNotNull($handle, 'Existing domain should have a handle');

        // Now test GetDomain with this handle
        $request = $this->getRequest();
        $result = $request->getDomain($handle);

        $this->assertNotNull($result, 'GetDomain should return a result');
        $this->assertNotIsArray($result, 'GetDomain should not return an error array');

        if (!is_array($result)) {
            $this->assertIsObject($result, 'GetDomain should return a domain object');
            $this->assertObjectHasProperty('DomainName', $result, 'Domain should have DomainName');
            $this->assertObjectHasProperty('DomainHandle', $result, 'Domain should have DomainHandle');
            $this->assertEquals($handle, $result->DomainHandle, 'Handle should match');
        }
    }

    #[Test]
    public function testGetDomainReturnsContactInfo(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $result = $this->getDomainByHandle($handle);

        if ($result && !is_array($result)) {
            // Verify contact objects are present
            $this->assertObjectHasProperty('Registrant', $result, 'Domain should have Registrant');
            $this->assertObjectHasProperty('AdminContact', $result, 'Domain should have AdminContact');
            $this->assertObjectHasProperty('TechContact', $result, 'Domain should have TechContact');

            // Verify registrant has expected fields
            if (isset($result->Registrant)) {
                $this->assertObjectHasProperty('Email', $result->Registrant);
            }
        }
    }

    #[Test]
    public function testGetDomainReturnsNameservers(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $result = $this->getDomainByHandle($handle);

        if ($result && !is_array($result)) {
            // Verify nameservers are present
            $this->assertObjectHasProperty('NameServers', $result, 'Domain should have NameServers');

            if (isset($result->NameServers)) {
                $this->assertObjectHasProperty('NameServer1', $result->NameServers, 'Should have NameServer1');
            }
        }
    }

    #[Test]
    public function testGetDomainReturnsStatus(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $result = $this->getDomainByHandle($handle);

        if ($result && !is_array($result)) {
            // Verify status is present
            $this->assertObjectHasProperty('Status', $result, 'Domain should have Status');

            // Status should be a non-empty string
            $this->assertNotEmpty($result->Status, 'Status should not be empty');
        }
    }

    #[Test]
    public function testGetDomainReturnsDates(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;
        $result = $this->getDomainByHandle($handle);

        if ($result && !is_array($result)) {
            // Verify dates are present
            $this->assertObjectHasProperty('ExpDate', $result, 'Domain should have ExpDate');

            // ExpDate should be in valid format
            if (isset($result->ExpDate) && $result->ExpDate !== '0001-01-01T00:00:00') {
                $this->assertMatchesRegularExpression(
                    '/\d{4}-\d{2}-\d{2}/',
                    $result->ExpDate,
                    'ExpDate should be in date format'
                );
            }
        }
    }

    // =========================================================================
    // SearchDomain Tests
    // =========================================================================

    #[Test]
    public function testSearchDomainByName(): void
    {
        // First find an existing domain
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->DomainName ?? null;
        $this->assertNotNull($domainName, 'Existing domain should have a name');

        // Test SearchDomain API
        $criteria = [
            'Mode' => 'Strict',
            'WithoutStates' => ['deleted'],
            'Clauses' => [
                [
                    'Attribute' => 'DomainName',
                    'Value' => $domainName,
                    'Operator' => 'Is',
                ],
            ],
        ];

        $result = $this->callApiMethod('SearchDomain', ['Criteria' => $criteria]);

        $this->assertIsObject($result);
        $this->assertEquals(200, $result->ResultCode, 'SearchDomain should succeed');

        if (isset($result->Domains->Domain)) {
            $domains = is_array($result->Domains->Domain) ? $result->Domains->Domain : [$result->Domains->Domain];
            $this->assertNotEmpty($domains, 'Should find at least one domain');

            $foundDomain = $domains[0];
            $this->assertEquals($domainName, $foundDomain->DomainName, 'Found domain should match search');
        }
    }

    #[Test]
    public function testSearchDomainWithWildcard(): void
    {
        // Search for domains matching pattern
        $criteria = [
            'Mode' => 'Fuzzy',
            'WithoutStates' => ['deleted'],
            'Clauses' => [
                [
                    'Attribute' => 'DomainName',
                    'Value' => '*.com',
                    'Operator' => 'Like',
                ],
            ],
            'PageInfo' => [
                'PageIndex' => 0,
                'PageSize' => 5,
            ],
        ];

        $result = $this->callApiMethod('SearchDomain', ['Criteria' => $criteria]);

        $this->assertIsObject($result);
        $this->assertContains($result->ResultCode, [200, 201], 'SearchDomain should succeed');
    }

    #[Test]
    public function testSearchDomainNotFound(): void
    {
        // Search for a domain that definitely doesn't exist
        $nonExistentDomain = 'definitely-not-existing-' . uniqid() . '-' . time() . '.com';

        $criteria = [
            'Mode' => 'Strict',
            'WithoutStates' => ['deleted'],
            'Clauses' => [
                [
                    'Attribute' => 'DomainName',
                    'Value' => $nonExistentDomain,
                    'Operator' => 'Is',
                ],
            ],
        ];

        $result = $this->callApiMethod('SearchDomain', ['Criteria' => $criteria]);

        $this->assertIsObject($result);
        // Should return success but with no domains
        $this->assertContains($result->ResultCode, [200, 201]);

        // Verify no domains found
        $domainCount = 0;
        if (isset($result->Domains->Domain)) {
            $domains = is_array($result->Domains->Domain) ? $result->Domains->Domain : [$result->Domains->Domain];
            $domainCount = count($domains);
        }

        $this->assertEquals(0, $domainCount, 'Should find no domains for non-existent name');
    }

    // =========================================================================
    // GetDomains Filter Tests
    // =========================================================================

    #[Test]
    public function testGetDomainsWithFilter(): void
    {
        // v3 GetDomains with filter criteria
        $criteria = [
            'Mode' => 'Fuzzy',
            'WithoutStates' => ['deleted'],
            'Clauses' => [
                [
                    'Attribute' => 'DomainName',
                    'Value' => '*.com',
                    'Operator' => 'Like',
                ],
            ],
            'PageInfo' => [
                'PageIndex' => 0,
                'PageSize' => 10,
            ],
        ];

        $result = $this->callApiMethod('SearchDomain', ['Criteria' => $criteria]);

        $this->assertIsObject($result);
        $this->assertV3ResponseFormat($result);

        // Verify pagination info if available
        if (isset($result->TotalCount)) {
            $this->assertIsInt($result->TotalCount);
        }
    }

    #[Test]
    public function testGetDomainsWithStatusFilter(): void
    {
        // Search only active domains
        $criteria = [
            'Mode' => 'Fuzzy',
            'WithStates' => ['active'],
            'Clauses' => [],
            'PageInfo' => [
                'PageIndex' => 0,
                'PageSize' => 5,
            ],
        ];

        $result = $this->callApiMethod('SearchDomain', ['Criteria' => $criteria]);

        $this->assertIsObject($result);
        $this->assertContains($result->ResultCode, [200, 201]);

        // If domains found, verify they are active
        if (isset($result->Domains->Domain)) {
            $domains = is_array($result->Domains->Domain) ? $result->Domains->Domain : [$result->Domains->Domain];

            foreach ($domains as $domain) {
                $this->assertStringContainsStringIgnoringCase(
                    'active',
                    $domain->Status ?? '',
                    'Domain should have active status'
                );
            }
        }
    }

    // =========================================================================
    // GetOrder Tests
    // =========================================================================

    #[Test]
    public function testGetOrder(): void
    {
        // First, we need to have an existing order ID
        // Try to get recent orders using GetOrders
        $criteria = [
            'OrderType' => 'Register_Domain',
            'PageInfo' => [
                'PageIndex' => 0,
                'PageSize' => 1,
            ],
        ];

        try {
            $ordersResult = $this->callApiMethod('GetOrders', ['Criteria' => $criteria]);

            if (isset($ordersResult->Orders->Order)) {
                $orders = is_array($ordersResult->Orders->Order)
                    ? $ordersResult->Orders->Order
                    : [$ordersResult->Orders->Order];

                if (!empty($orders)) {
                    $orderId = $orders[0]->OrderId ?? null;

                    if ($orderId) {
                        // Now test GetOrder
                        $request = $this->getRequest();
                        $result = $request->getOrder($orderId);

                        $this->assertNotNull($result);

                        if (!is_array($result)) {
                            $this->assertIsObject($result);
                            $this->assertObjectHasProperty('Order', $result);
                        }

                        return;
                    }
                }
            }
        } catch (\SoapFault $e) {
            // GetOrders might not be available or no orders exist
        }

        $this->markTestSkipped('No existing orders found on test account');
    }

    #[Test]
    public function testGetOrderReturnsOrderType(): void
    {
        try {
            $criteria = [
                'PageInfo' => [
                    'PageIndex' => 0,
                    'PageSize' => 1,
                ],
            ];

            $ordersResult = $this->callApiMethod('GetOrders', ['Criteria' => $criteria]);

            if (isset($ordersResult->Orders->Order)) {
                $orders = is_array($ordersResult->Orders->Order)
                    ? $ordersResult->Orders->Order
                    : [$ordersResult->Orders->Order];

                if (!empty($orders)) {
                    $orderId = $orders[0]->OrderId ?? null;

                    if ($orderId) {
                        $result = $this->callApiMethod('GetOrder', ['OrderId' => $orderId]);

                        $this->assertIsObject($result);

                        if (isset($result->Order)) {
                            $this->assertObjectHasProperty('Type', $result->Order, 'Order should have Type');
                            $this->assertObjectHasProperty('Status', $result->Order, 'Order should have Status');
                        }

                        return;
                    }
                }
            }
        } catch (\SoapFault $e) {
            // Ignore
        }

        $this->markTestSkipped('No existing orders found on test account');
    }

    // =========================================================================
    // RequestV3::searchDomain Integration Tests
    // =========================================================================

    #[Test]
    public function testSearchDomainMethodUsesHandle(): void
    {
        // Set up mock database with a stored handle
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing .com domain found on test account');
        }

        $domainName = $existingDomain->DomainName;
        $handle = $existingDomain->DomainHandle ?? $existingDomain->Handle ?? null;

        if (!$handle) {
            $this->markTestSkipped('Existing domain has no handle');
        }

        // Set up handle in mock database
        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => $domainName,
                'ascio_id' => $handle,
            ],
        ]);

        $params = $this->getRegistrationParams($domainName);
        $request = new RequestV3($params);

        // This should use the cached handle
        $storedHandle = $request->getHandle('domain', 1, $domainName);
        $this->assertEquals($handle, $storedHandle, 'Should retrieve stored handle');
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function testGetDomainWithInvalidHandle(): void
    {
        $request = $this->getRequest();
        $result = $request->getDomain('INVALID-HANDLE-' . uniqid());

        // Should return error or empty result, not throw exception
        $this->assertTrue(
            is_array($result) || (is_object($result) && isset($result->error)),
            'Invalid handle should return error'
        );
    }

    #[Test]
    public function testSearchDomainWithEmptyCriteria(): void
    {
        $criteria = [
            'Mode' => 'Strict',
            'Clauses' => [],
            'PageInfo' => [
                'PageIndex' => 0,
                'PageSize' => 1,
            ],
        ];

        $result = $this->callApiMethod('SearchDomain', ['Criteria' => $criteria]);

        // Should succeed but return limited results
        $this->assertIsObject($result);
        $this->assertContains($result->ResultCode, [200, 201, 400], 'Empty criteria may succeed or return validation error');
    }
}
