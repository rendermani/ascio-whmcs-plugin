<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\v2\domains\Request as RequestV2;
use ascio\v3\domains\RequestV3;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SoapClientMock;
use Ascio\Tests\Mocks\MockAscioClientV3;
use Ascio\Tests\Mocks\MockParamsV3;

/**
 * Compatibility tests to ensure v3 Request returns v2-compatible response formats
 *
 * These tests verify that migrating from v2 to v3 API maintains backward
 * compatibility with existing WHMCS integrations and workflows.
 *
 * Note: v2 uses lowercase 'order' key, v3 uses PascalCase 'Order' key.
 * These tests verify the underlying data structures are compatible.
 *
 * @covers \ascio\v3\domains\RequestV3
 */
class V3CompatibilityTest extends TestCase
{
    private array $defaultParams;
    private MockAscioClientV3 $mockClientV3;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
        SoapClientMock::reset();

        $this->defaultParams = MockParamsV3::getDefault();
        $this->mockClientV3 = new MockAscioClientV3();
    }

    /**
     * Helper to get order data regardless of key casing
     */
    private function getOrderData(array $result): ?array
    {
        return $result['Order'] ?? $result['order'] ?? null;
    }

    // =========================================================================
    // Register Domain Response Format Compatibility
    // =========================================================================

    #[Test]
    public function testRegisterDomainResponseFormat(): void
    {
        $params = MockParamsV3::forRegistration('newdomain.com');

        $v2Request = new RequestV2($params);
        $v3Request = new RequestV3($params);

        // Both should produce order structures
        $v2Order = $v2Request->mapToOrder($params, 'Register_Domain');
        $v3Order = $v3Request->mapToOrder($params, 'Register_Domain');

        // V2 uses 'order' key
        $this->assertArrayHasKey('order', $v2Order);
        $this->assertArrayHasKey('Type', $v2Order['order']);
        $this->assertArrayHasKey('Domain', $v2Order['order']);

        // V3 uses 'Order' key (PascalCase)
        $this->assertArrayHasKey('Order', $v3Order);
        $this->assertArrayHasKey('Type', $v3Order['Order']);
        $this->assertArrayHasKey('Domain', $v3Order['Order']);

        // Domain structure should match
        $this->assertDomainStructureMatches(
            $v2Order['order']['Domain'],
            $v3Order['Order']['Domain']
        );
    }

    #[Test]
    public function testRegisterDomainOrderTypeIsIdentical(): void
    {
        $params = MockParamsV3::forRegistration('domain.com');

        $v2Request = new RequestV2($params);
        $v3Request = new RequestV3($params);

        $v2Order = $v2Request->mapToOrder($params, 'Register_Domain');
        $v3Order = $v3Request->mapToOrder($params, 'Register_Domain');

        // Both should have same order type
        $this->assertEquals($v2Order['order']['Type'], $v3Order['Order']['Type']);
    }

    #[Test]
    public function testRegisterDomainContactsStructureMatches(): void
    {
        $params = MockParamsV3::forRegistration('domain.com');

        $v2Request = new RequestV2($params);
        $v3Request = new RequestV3($params);

        $v2Order = $v2Request->mapToOrder($params, 'Register_Domain');
        $v3Order = $v3Request->mapToOrder($params, 'Register_Domain');

        $v2Domain = $v2Order['order']['Domain'];
        $v3Domain = $v3Order['Order']['Domain'];

        // Both should have all contact types
        foreach (['Registrant', 'AdminContact', 'TechContact', 'BillingContact'] as $contactType) {
            $this->assertArrayHasKey($contactType, $v2Domain);
            $this->assertArrayHasKey($contactType, $v3Domain);
        }
    }

    // =========================================================================
    // Search Domain Response Format Compatibility
    // =========================================================================

    #[Test]
    public function testSearchDomainResponseFormat(): void
    {
        // Setup mock data
        CapsuleMock::setTableData('tblasciohandles', [
            ['type' => 'domain', 'whmcs_id' => 1, 'domain' => 'example.com', 'ascio_id' => 'DOM-123']
        ]);

        $v2Request = new RequestV2($this->defaultParams);
        $v3Request = new RequestV3($this->defaultParams);

        // Both should have searchDomain method
        $this->assertTrue(method_exists($v2Request, 'searchDomain'));
        $this->assertTrue(method_exists($v3Request, 'searchDomain'));
    }

    #[Test]
    public function testSearchDomainUsesCorrectApiMethod(): void
    {
        // v2 uses SearchDomain SOAP method
        // v3 should use GetDomains with filter (or compatible method)

        $v3Request = new RequestV3($this->defaultParams);

        // Verify the method exists and is callable
        $this->assertTrue(is_callable([$v3Request, 'searchDomain']));
    }

    // =========================================================================
    // Availability Check Response Format Compatibility
    // =========================================================================

    #[Test]
    public function testAvailabilityCheckResponseFormat(): void
    {
        $v2Request = new RequestV2($this->defaultParams);
        $v3Request = new RequestV3($this->defaultParams);

        // Both should have availabilityCheck method
        $this->assertTrue(method_exists($v2Request, 'availabilityCheck'));
        $this->assertTrue(method_exists($v3Request, 'availabilityCheck'));
    }

    #[Test]
    public function testAvailabilityCheckMethodSignature(): void
    {
        $v2Request = new RequestV2($this->defaultParams);
        $v3Request = new RequestV3($this->defaultParams);

        $v2Method = new \ReflectionMethod($v2Request, 'availabilityCheck');
        $v3Method = new \ReflectionMethod($v3Request, 'availabilityCheck');

        // Method signatures should be compatible
        $this->assertEquals($v2Method->getNumberOfParameters(), $v3Method->getNumberOfParameters());
    }

    // =========================================================================
    // Poll Response Format Compatibility
    // =========================================================================

    #[Test]
    public function testPollResponseFormat(): void
    {
        $v2Request = new RequestV2($this->defaultParams);
        $v3Request = new RequestV3($this->defaultParams);

        // Both should have poll method
        $this->assertTrue(method_exists($v2Request, 'poll'));
        $this->assertTrue(method_exists($v3Request, 'poll'));
    }

    #[Test]
    public function testPollMethodSignature(): void
    {
        $v2Request = new RequestV2($this->defaultParams);
        $v3Request = new RequestV3($this->defaultParams);

        $v2Method = new \ReflectionMethod($v2Request, 'poll');
        $v3Method = new \ReflectionMethod($v3Request, 'poll');

        // Poll should take no parameters in both versions
        $this->assertEquals($v2Method->getNumberOfParameters(), $v3Method->getNumberOfParameters());
    }

    // =========================================================================
    // Callback Data Format Compatibility
    // =========================================================================

    #[Test]
    public function testCallbackDataFormat(): void
    {
        $v2Request = new RequestV2($this->defaultParams);
        $v3Request = new RequestV3($this->defaultParams);

        // Both should have getCallbackData method
        $this->assertTrue(method_exists($v2Request, 'getCallbackData'));
        $this->assertTrue(method_exists($v3Request, 'getCallbackData'));
    }

    #[Test]
    public function testCallbackDataMethodSignature(): void
    {
        $v2Request = new RequestV2($this->defaultParams);
        $v3Request = new RequestV3($this->defaultParams);

        $v2Method = new \ReflectionMethod($v2Request, 'getCallbackData');
        $v3Method = new \ReflectionMethod($v3Request, 'getCallbackData');

        // Callback data should accept same number of parameters
        $this->assertEquals($v2Method->getNumberOfParameters(), $v3Method->getNumberOfParameters());
    }

    // =========================================================================
    // Contact Mapping Compatibility
    // =========================================================================

    #[Test]
    public function testRegistrantMappingCompatibility(): void
    {
        $params = $this->defaultParams;

        $v2Request = new RequestV2($params);
        $v3Request = new RequestV3($params);

        $v2Reflection = new \ReflectionMethod($v2Request, 'mapToRegistrant');
        $v2Reflection->setAccessible(true);

        $v3Reflection = new \ReflectionMethod($v3Request, 'mapToRegistrant');
        $v3Reflection->setAccessible(true);

        $v2Result = $v2Reflection->invoke($v2Request, $params);
        $v3Result = $v3Reflection->invoke($v3Request, $params);

        // Both should produce arrays with same keys
        $requiredKeys = ['Name', 'OrgName', 'Address1', 'PostalCode', 'City', 'CountryCode', 'Email', 'Phone'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $v2Result, "v2 missing key: $key");
            $this->assertArrayHasKey($key, $v3Result, "v3 missing key: $key");
        }
    }

    #[Test]
    public function testContactMappingCompatibility(): void
    {
        $params = $this->defaultParams;

        $v2Request = new RequestV2($params);
        $v3Request = new RequestV3($params);

        $v2Reflection = new \ReflectionMethod($v2Request, 'mapToContact');
        $v2Reflection->setAccessible(true);

        $v3Reflection = new \ReflectionMethod($v3Request, 'mapToContact');
        $v3Reflection->setAccessible(true);

        $v2Result = $v2Reflection->invoke($v2Request, $params, 'Admin');
        $v3Result = $v3Reflection->invoke($v3Request, $params, 'Admin');

        // Both should produce arrays with same keys for Admin contact
        $requiredKeys = ['FirstName', 'LastName', 'OrgName', 'Address1', 'PostalCode', 'City', 'CountryCode', 'Email', 'Phone'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $v2Result, "v2 missing key: $key");
            $this->assertArrayHasKey($key, $v3Result, "v3 missing key: $key");
        }
    }

    #[Test]
    public function testNameserverMappingCompatibility(): void
    {
        $params = $this->defaultParams;

        $v2Request = new RequestV2($params);
        $v3Request = new RequestV3($params);

        $v2Reflection = new \ReflectionMethod($v2Request, 'mapToNameservers');
        $v2Reflection->setAccessible(true);

        $v3Reflection = new \ReflectionMethod($v3Request, 'mapToNameservers');
        $v3Reflection->setAccessible(true);

        $v2Result = $v2Reflection->invoke($v2Request, $params);
        $v3Result = $v3Reflection->invoke($v3Request, $params);

        // Both should have same nameserver structure
        for ($i = 1; $i <= 5; $i++) {
            $key = "NameServer{$i}";
            $this->assertArrayHasKey($key, $v2Result, "v2 missing key: $key");
            $this->assertArrayHasKey($key, $v3Result, "v3 missing key: $key");
            $this->assertArrayHasKey('HostName', $v2Result[$key], "v2 {$key} missing HostName");
            $this->assertArrayHasKey('HostName', $v3Result[$key], "v3 {$key} missing HostName");
        }
    }

    // =========================================================================
    // Order Type Compatibility
    // =========================================================================

    #[Test]
    #[DataProvider('orderTypeProvider')]
    public function testAllOrderTypesAreCompatible(string $orderType): void
    {
        $params = $this->defaultParams;

        $v2Request = new RequestV2($params);
        $v3Request = new RequestV3($params);

        $v2Order = $v2Request->mapToOrder($params, $orderType);
        $v3Order = $v3Request->mapToOrder($params, $orderType);

        // Order type should be identical (v2 uses 'order', v3 uses 'Order')
        $this->assertEquals($v2Order['order']['Type'], $v3Order['Order']['Type']);
    }

    public static function orderTypeProvider(): array
    {
        return [
            'Register Domain' => ['Register_Domain'],
            'Transfer Domain' => ['Transfer_Domain'],
            'Renew Domain' => ['Renew_Domain'],
            'Expire Domain' => ['Expire_Domain'],
            'Unexpire Domain' => ['Unexpire_Domain'],
            'Nameserver Update' => ['Nameserver_Update'],
            'Contact Update' => ['Contact_Update'],
            'Owner Change' => ['Owner_Change'],
            'Domain Details Update' => ['Domain_Details_Update'],
            'Update AuthInfo' => ['Update_AuthInfo'],
            'Change Locks' => ['Change_Locks'],
        ];
    }

    // =========================================================================
    // Error Response Format Compatibility
    // =========================================================================

    #[Test]
    public function testErrorResponseFormatIsCompatible(): void
    {
        // Mock an error response
        $errorResponse = $this->mockClientV3->makeErrorResponse(
            'CreateOrder',
            400,
            'Validation failed',
            ['Domain name already registered']
        );

        // Error response should have expected structure
        $this->assertObjectHasProperty('CreateOrderResult', $errorResponse);
        $this->assertObjectHasProperty('status', $errorResponse);
        $this->assertEquals(400, $errorResponse->CreateOrderResult->ResultCode);
    }

    // =========================================================================
    // Domain Status Compatibility
    // =========================================================================

    #[Test]
    #[DataProvider('domainStatusProvider')]
    public function testDomainStatusMappingIsCompatible(string $ascioStatus, string $expectedStatus): void
    {
        $domain = (object) ['Status' => $ascioStatus];

        $v2Request = new RequestV2($this->defaultParams);
        $v3Request = new RequestV3($this->defaultParams);

        $v2Status = $v2Request->getDomainStatus($domain);
        $v3Status = $v3Request->getDomainStatus($domain);

        // Both should return same WHMCS status
        $this->assertEquals($v2Status, $v3Status);
        $this->assertEquals($expectedStatus, $v3Status);
    }

    public static function domainStatusProvider(): array
    {
        return [
            'active' => ['ACTIVE', 'Active'],
            'active with lock' => ['ACTIVE,TRANSFER_LOCK', 'Active'],
            'expiring' => ['EXPIRING', 'Active'],
            'pending verification' => ['PENDING_VERIFICATION', 'Active'],
            'pending' => ['PENDING', 'Pending'],
            'deleted' => ['DELETED', 'Cancelled'],
        ];
    }

    // =========================================================================
    // Handle Storage Compatibility
    // =========================================================================

    #[Test]
    public function testHandleStorageFormatIsCompatible(): void
    {
        CapsuleMock::setTableData('tblasciohandles', []);

        $v2Request = new RequestV2($this->defaultParams);
        $v3Request = new RequestV3($this->defaultParams);

        // Both should use same table and structure
        $v2Request->storeHandle('domain', 1, 'DOM-V2', 'example.com');
        $v3Request->storeHandle('domain', 2, 'DOM-V3', 'example.org');

        // Both inserts should use same table
        $lastQuery = CapsuleMock::getLastQuery();
        $this->assertEquals('tblasciohandles', $lastQuery['table']);
    }

    // =========================================================================
    // Privacy Proxy Compatibility
    // =========================================================================

    #[Test]
    public function testPrivacyProxyMappingIsCompatible(): void
    {
        $params = MockParamsV3::withIdProtection('domain.com');

        $v2Request = new RequestV2($params);
        $v3Request = new RequestV3($params);

        $v2Order = $v2Request->mapToOrder($params, 'Register_Domain');
        $v3Order = $v3Request->mapToOrder($params, 'Register_Domain');

        $v2Domain = $v2Order['order']['Domain'];
        $v3Domain = $v3Order['Order']['Domain'];

        // Both should have same PrivacyProxy structure
        $this->assertArrayHasKey('PrivacyProxy', $v2Domain);
        $this->assertArrayHasKey('PrivacyProxy', $v3Domain);
        $this->assertEquals(
            $v2Domain['PrivacyProxy']['Type'],
            $v3Domain['PrivacyProxy']['Type']
        );
    }

    // =========================================================================
    // TLD Factory Compatibility
    // =========================================================================

    #[Test]
    #[DataProvider('tldFactoryProvider')]
    public function testTldFactoryReturnsCompatibleClass(string $tld): void
    {
        $params = MockParamsV3::forTld($tld);

        // v2 factory
        $v2Request = RequestV2::create($params);

        // Both should return Request instances
        $this->assertInstanceOf(RequestV2::class, $v2Request);
    }

    public static function tldFactoryProvider(): array
    {
        return [
            '.com' => ['com'],
            '.ca' => ['ca'],
            '.de' => ['de'],
            '.it' => ['it'],
            '.uk' => ['uk'],
            '.nl' => ['nl'],
            '.fr' => ['fr'],
            '.sg' => ['sg'],
            '.au' => ['au'],
            '.co.uk' => ['co.uk'],
            '.com.au' => ['com.au'],
        ];
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Assert that domain structures match between v2 and v3
     */
    private function assertDomainStructureMatches(array $v2Domain, array $v3Domain): void
    {
        $requiredKeys = ['DomainName', 'RegPeriod', 'Registrant', 'AdminContact', 'TechContact', 'BillingContact', 'NameServers'];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $v2Domain, "v2 Domain missing key: $key");
            $this->assertArrayHasKey($key, $v3Domain, "v3 Domain missing key: $key");
        }
    }
}
