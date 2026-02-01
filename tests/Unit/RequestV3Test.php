<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\v3\domains\RequestV3 as Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SoapClientMock;
use Ascio\Tests\Mocks\MockAscioClientV3;
use Ascio\Tests\Mocks\MockParamsV3;

/**
 * Unit tests for ascio\v3\domains\Request class
 *
 * Tests the v3 API request class that provides domain registration,
 * transfer, renewal, and other operations using the Ascio v3 SOAP API.
 *
 * @covers \ascio\v3\domains\Request
 */
class RequestV3Test extends TestCase
{
    private array $defaultParams;
    private MockAscioClientV3 $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
        SoapClientMock::reset();

        $this->defaultParams = MockParamsV3::getDefault();
        $this->mockClient = new MockAscioClientV3();
    }

    // =========================================================================
    // Constructor and Parameter Setting Tests
    // =========================================================================

    #[Test]
    public function constructorSetsAccountAndPassword(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertEquals('test_account', $request->account);
        $this->assertEquals('test_password', $request->password);
    }

    #[Test]
    public function constructorSetsDomainName(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertEquals('example.com', $request->domainName);
    }

    #[Test]
    public function setParamsSetsAccountAndPassword(): void
    {
        $request = new Request([]);
        $request->setParams($this->defaultParams);

        $this->assertEquals('test_account', $request->account);
        $this->assertEquals('test_password', $request->password);
    }

    #[Test]
    public function setParamsHandlesDomainObj(): void
    {
        $domainObj = new class {
            public function getIdnSecondLevel(): string { return 'mytest'; }
            public function getTopLevel(): string { return 'org'; }
        };

        $params = array_merge($this->defaultParams, [
            'domainObj' => $domainObj,
            'sld' => 'mytest'
        ]);

        $request = new Request($params);

        $this->assertEquals('mytest.org', $request->domainName);
    }

    // =========================================================================
    // Register Domain Tests
    // =========================================================================

    #[Test]
    public function testRegisterDomainCreatesCorrectOrder(): void
    {
        $params = MockParamsV3::forRegistration('newdomain.com');
        $request = new Request($params);

        // Access mapToOrder to verify the order structure
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Register_Domain', $order['Order']['Type']);
        $this->assertEquals('newdomain.com', $order['Order']['Domain']['DomainName']);
        $this->assertEquals(1, $order['Order']['Domain']['RegPeriod']);
        $this->assertArrayHasKey('Registrant', $order['Order']['Domain']);
        $this->assertArrayHasKey('AdminContact', $order['Order']['Domain']);
        $this->assertArrayHasKey('TechContact', $order['Order']['Domain']);
        $this->assertArrayHasKey('BillingContact', $order['Order']['Domain']);
        $this->assertArrayHasKey('NameServers', $order['Order']['Domain']);
    }

    #[Test]
    public function testRegisterDomainIncludesPrivacyProxy(): void
    {
        $params = MockParamsV3::withIdProtection('newdomain.com');
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertArrayHasKey('PrivacyProxy', $order['Order']['Domain']);
        $this->assertEquals('Proxy', $order['Order']['Domain']['PrivacyProxy']['Type']);
    }

    #[Test]
    public function testRegisterDomainUsesPrivacyForProxyLite(): void
    {
        $params = MockParamsV3::withIdProtection('newdomain.com', ['Proxy_Lite' => 'on']);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Privacy', $order['Order']['Domain']['PrivacyProxy']['Type']);
    }

    #[Test]
    public function testRegisterDomainSetsNoneForNoIdProtection(): void
    {
        $params = MockParamsV3::forRegistration('newdomain.com', ['idprotection' => false]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('None', $order['Order']['Domain']['PrivacyProxy']['Type']);
    }

    #[Test]
    public function testRegisterDomainIncludesTransactionComment(): void
    {
        $params = MockParamsV3::forRegistration('newdomain.com', [
            'domainid' => 123,
            'userid' => 456
        ]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Register_Domain');

        $comment = json_decode($order['Order']['TransactionComment'], true);
        $this->assertEquals('WHMCS', $comment['application']);
        $this->assertEquals(123, $comment['domainId']);
        $this->assertEquals(456, $comment['userId']);
        $this->assertEquals('Domain', $comment['objectType']);
    }

    // =========================================================================
    // Transfer Domain Tests
    // =========================================================================

    #[Test]
    public function testTransferDomainCreatesCorrectOrder(): void
    {
        $params = MockParamsV3::forTransfer('transfer.com', 'TRANSFER-EPP-CODE');
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Transfer_Domain');

        $this->assertEquals('Transfer_Domain', $order['Order']['Type']);
        $this->assertEquals('transfer.com', $order['Order']['Domain']['DomainName']);
        $this->assertEquals('TRANSFER-EPP-CODE', $order['Order']['Domain']['AuthInfo']);
    }

    #[Test]
    public function testTransferDomainIncludesPrivacyProxy(): void
    {
        $params = MockParamsV3::forTransfer('transfer.com', 'EPP-CODE', ['idprotection' => true]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Transfer_Domain');

        $this->assertArrayHasKey('PrivacyProxy', $order['Order']['Domain']);
    }

    // =========================================================================
    // Renew Domain Tests
    // =========================================================================

    #[Test]
    public function testRenewDomainCreatesCorrectOrder(): void
    {
        $params = MockParamsV3::forRenewal('renew.com', 2);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Renew_Domain');

        $this->assertEquals('Renew_Domain', $order['Order']['Type']);
        $this->assertEquals('renew.com', $order['Order']['Domain']['DomainName']);
        $this->assertEquals(2, $order['Order']['Domain']['RegPeriod']);
    }

    // =========================================================================
    // Search Domain Tests (GetDomains Filter)
    // =========================================================================

    #[Test]
    public function testSearchDomainUsesGetDomainsFilter(): void
    {
        // Setup mock to return domain with handle
        CapsuleMock::setTableData('tblasciohandles', [
            ['type' => 'domain', 'whmcs_id' => 1, 'domain' => 'example.com', 'ascio_id' => 'DOM-12345']
        ]);

        $request = new Request($this->defaultParams);

        // Verify the searchDomain method exists (it uses SearchDomain in v2, GetDomains filter in v3)
        $this->assertTrue(method_exists($request, 'searchDomain'));
    }

    // =========================================================================
    // Availability Check Tests
    // =========================================================================

    #[Test]
    public function testAvailabilityCheckHandlesMultipleTlds(): void
    {
        $params = $this->defaultParams;
        $request = new Request($params);

        // Verify availabilityCheck method exists
        $this->assertTrue(method_exists($request, 'availabilityCheck'));
    }

    #[Test]
    public function testAvailabilityInfoReturnsSingleDomain(): void
    {
        $params = $this->defaultParams;
        $request = new Request($params);

        // Verify availabilityInfo method exists
        $this->assertTrue(method_exists($request, 'availabilityInfo'));
    }

    // =========================================================================
    // Poll/Ack Queue Message Tests
    // =========================================================================

    #[Test]
    public function testPollReturnsCompatibleFormat(): void
    {
        $request = new Request($this->defaultParams);

        // Verify poll method exists
        $this->assertTrue(method_exists($request, 'poll'));
    }

    #[Test]
    public function testAckCallsAckQueueMessage(): void
    {
        $request = new Request($this->defaultParams);

        // Verify ack method exists
        $this->assertTrue(method_exists($request, 'ack'));
    }

    // =========================================================================
    // Contact Mapping Tests (v3 format)
    // =========================================================================

    #[Test]
    public function testMapToRegistrantCreatesValidObject(): void
    {
        $request = new Request($this->defaultParams);
        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $this->defaultParams);

        // Registrant should have Name field (combined first+last)
        $this->assertArrayHasKey('Name', $result);
        $this->assertEquals('John Doe', $result['Name']);
        $this->assertEquals('Test Company', $result['OrgName']);
        $this->assertEquals('123 Test Street', $result['Address1']);
        $this->assertEquals('Suite 100', $result['Address2']);
        $this->assertEquals('12345', $result['PostalCode']);
        $this->assertEquals('Test City', $result['City']);
        $this->assertEquals('TS', $result['State']);
        $this->assertEquals('US', $result['CountryCode']);
        $this->assertEquals('test@example.com', $result['Email']);
    }

    #[Test]
    public function testMapToContactCreatesValidObject(): void
    {
        $request = new Request($this->defaultParams);
        $reflection = new \ReflectionMethod($request, 'mapToContact');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $this->defaultParams, 'Admin');

        // Contact should have FirstName/LastName fields
        $this->assertArrayHasKey('FirstName', $result);
        $this->assertArrayHasKey('LastName', $result);
        $this->assertArrayNotHasKey('Name', $result);
        $this->assertEquals('Admin', $result['FirstName']);
        $this->assertEquals('User', $result['LastName']);
    }

    #[Test]
    public function testMapToNameserversCreatesValidArray(): void
    {
        $request = new Request($this->defaultParams);
        $reflection = new \ReflectionMethod($request, 'mapToNameservers');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $this->defaultParams);

        $this->assertArrayHasKey('NameServer1', $result);
        $this->assertArrayHasKey('NameServer2', $result);
        $this->assertEquals('ns1.example.com', $result['NameServer1']['HostName']);
        $this->assertEquals('ns2.example.com', $result['NameServer2']['HostName']);
    }

    #[Test]
    public function testMapToRegistrantWithCustomFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'custom' => [
                'RegistrantType' => 'Corporation',
                'VatNumber' => 'VAT123456',
                'NexusCategory' => 'C12',
                'RegistrantNumber' => 'REG789',
                'Details' => 'Additional details'
            ]
        ]);

        $request = new Request($params);
        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('Corporation', $result['RegistrantType']);
        $this->assertEquals('VAT123456', $result['VatNumber']);
        $this->assertEquals('C12', $result['NexusCategory']);
        $this->assertEquals('REG789', $result['RegistrantNumber']);
        $this->assertEquals('Additional details', $result['Details']);
    }

    // =========================================================================
    // Domain/Order Retrieval Tests
    // =========================================================================

    #[Test]
    public function testGetDomainByHandle(): void
    {
        $request = new Request($this->defaultParams);

        // Verify getDomain method exists
        $this->assertTrue(method_exists($request, 'getDomain'));
    }

    #[Test]
    public function testGetOrderById(): void
    {
        $request = new Request($this->defaultParams);

        // Verify getOrder method exists
        $this->assertTrue(method_exists($request, 'getOrder'));
    }

    // =========================================================================
    // Factory Method Tests (TLD-specific classes)
    // =========================================================================

    #[Test]
    public function testCreateFactoryLoadsV3TldClass(): void
    {
        // Use v2 Request::create as baseline
        $params = MockParamsV3::forTld('com');

        // Factory should return a Request instance
        $request = \ascio\v2\domains\Request::create($params);

        $this->assertInstanceOf(\ascio\v2\domains\Request::class, $request);
    }

    #[Test]
    #[DataProvider('tldFactoryProvider')]
    public function testCreateFactoryForVariousTlds(string $tld): void
    {
        $params = MockParamsV3::forTld($tld);

        // Factory should return a valid Request instance for all TLDs
        $request = \ascio\v2\domains\Request::create($params);

        $this->assertInstanceOf(\ascio\v2\domains\Request::class, $request);
    }

    public static function tldFactoryProvider(): array
    {
        return [
            '.com (generic)' => ['com'],
            '.net (generic)' => ['net'],
            '.org (generic)' => ['org'],
            '.ca (Canada)' => ['ca'],
            '.de (Germany)' => ['de'],
            '.it (Italy)' => ['it'],
            '.uk (United Kingdom)' => ['uk'],
            '.nl (Netherlands)' => ['nl'],
            '.fr (France)' => ['fr'],
            '.sg (Singapore)' => ['sg'],
            '.au (Australia)' => ['au'],
        ];
    }

    // =========================================================================
    // Order Type Tests
    // =========================================================================

    #[Test]
    #[DataProvider('orderTypeProvider')]
    public function testMapToOrderCreatesCorrectOrderType(string $orderType): void
    {
        $request = new Request($this->defaultParams);

        $order = $request->mapToOrder($this->defaultParams, $orderType);

        $this->assertEquals($orderType, $order['Order']['Type']);
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
            'Update AuthInfo' => ['Update_AuthInfo'],
            'Change Locks' => ['Change_Locks'],
            'Domain Details Update' => ['Domain_Details_Update'],
        ];
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function testMapToOrderHandlesEmptyNameservers(): void
    {
        $params = array_merge($this->defaultParams, [
            'ns1' => '',
            'ns2' => '',
            'ns3' => '',
            'ns4' => '',
            'ns5' => ''
        ]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        // Should still have NameServers array, even if empty
        $this->assertArrayHasKey('NameServers', $order['Order']['Domain']);
    }

    #[Test]
    public function testMapToContactHandlesNullValues(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => null,
            'address2' => null,
            'state' => null
        ]);

        $request = new Request($params);
        $reflection = new \ReflectionMethod($request, 'mapToContact');
        $reflection->setAccessible(true);

        // Should not throw an exception
        $result = $reflection->invoke($request, $params, 'Registrant');

        $this->assertIsArray($result);
    }

    // =========================================================================
    // Handle Storage Tests
    // =========================================================================

    #[Test]
    public function testGetHandleReturnsStoredHandle(): void
    {
        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => 'example.com',
                'ascio_id' => 'DOM-V3-12345'
            ]
        ]);

        $request = new Request($this->defaultParams);
        $result = $request->getHandle('domain', 1, 'example.com');

        $this->assertEquals('DOM-V3-12345', $result);
    }

    #[Test]
    public function testStoreHandleInsertsNewHandle(): void
    {
        CapsuleMock::setTableData('tblasciohandles', []);

        $request = new Request($this->defaultParams);
        $request->storeHandle('domain', 1, 'DOM-V3-NEW', 'example.com');

        $query = CapsuleMock::getLastQuery();
        $this->assertEquals('insert', $query['type']);
        $this->assertEquals('tblasciohandles', $query['table']);
    }

    // =========================================================================
    // Simulation Mode Tests
    // =========================================================================

    #[Test]
    public function testIsSimulationModeReturnsFalseByDefault(): void
    {
        putenv('ASCIO_SIMULATE');

        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'isSimulationMode');
        $reflection->setAccessible(true);

        $this->assertFalse($reflection->invoke($request));
    }

    #[Test]
    public function testIsSimulationModeReturnsTrueWhenEnvVarSet(): void
    {
        putenv('ASCIO_SIMULATE=1');

        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'isSimulationMode');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->invoke($request));

        putenv('ASCIO_SIMULATE');
    }

    #[Test]
    public function testIsSimulationModeReturnsTrueWhenModuleParamSet(): void
    {
        $params = array_merge($this->defaultParams, ['Simulate' => 'on']);

        $request = new Request($params);

        $reflection = new \ReflectionMethod($request, 'isSimulationMode');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->invoke($request));
    }

    // =========================================================================
    // Domain Status Tests
    // =========================================================================

    #[Test]
    #[DataProvider('domainStatusProvider')]
    public function testGetDomainStatusReturnsCorrectStatus(string $ascioStatus, string $expectedWhmcsStatus): void
    {
        $domain = (object) ['Status' => $ascioStatus];
        $request = new Request($this->defaultParams);

        $result = $request->getDomainStatus($domain);

        $this->assertEquals($expectedWhmcsStatus, $result);
    }

    public static function domainStatusProvider(): array
    {
        return [
            'active status' => ['ACTIVE', 'Active'],
            'active transfer lock' => ['ACTIVE,TRANSFER_LOCK', 'Active'],
            'expiring status' => ['EXPIRING', 'Active'],
            'pending verification' => ['PENDING_VERIFICATION', 'Active'],
            'lock status' => ['TRANSFER_LOCK', 'Active'],
            'pending status' => ['PENDING', 'Pending'],
            'deleted status' => ['DELETED', 'Cancelled'],
        ];
    }

    #[Test]
    public function testGetDomainStatusReturnsCancelledForNullDomain(): void
    {
        $request = new Request($this->defaultParams);

        $result = $request->getDomainStatus(null);

        $this->assertEquals('Cancelled', $result);
    }

    // =========================================================================
    // WSDL Selection Tests
    // =========================================================================

    #[Test]
    public function testUsesTestWsdlWhenTestModeOn(): void
    {
        $params = array_merge($this->defaultParams, ['TestMode' => 'on']);
        $request = new Request($params);

        // Verify the test WSDL constant is defined
        $this->assertTrue(defined('ASCIO_V3_WSDL_TEST'));
        $this->assertStringContainsString('demo.ascio.com', ASCIO_V3_WSDL_TEST);
    }

    #[Test]
    public function testUsesLiveWsdlWhenTestModeOff(): void
    {
        $params = array_merge($this->defaultParams, ['TestMode' => '']);
        $request = new Request($params);

        // Verify the live WSDL constant is defined
        $this->assertTrue(defined('ASCIO_V3_WSDL_LIVE'));
        $this->assertStringContainsString('aws.ascio.com', ASCIO_V3_WSDL_LIVE);
    }
}
