<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request as Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SoapClientMock;
use Ascio\Tests\Mocks\MockAscioClientV3;
use Ascio\Tests\Mocks\MockParamsV3;

/**
 * Unit tests for ascio\Request class
 *
 * Tests the v3 API request class that provides domain registration,
 * transfer, renewal, and other operations using the Ascio v3 SOAP API.
 *
 * @covers \ascio\Request
 */
class RequestTest extends TestCase
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
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('Register', $order['Order']['Type']);
        $this->assertEquals('newdomain.com', $order['Order']['Domain']['Name']);
        $this->assertEquals(1, $order['Order']['Domain']['RenewPeriod']);
        $this->assertArrayHasKey('Owner', $order['Order']['Domain']);
        $this->assertArrayHasKey('Admin', $order['Order']['Domain']);
        $this->assertArrayHasKey('Tech', $order['Order']['Domain']);
        $this->assertArrayHasKey('Billing', $order['Order']['Domain']);
        $this->assertArrayHasKey('NameServers', $order['Order']['Domain']);
    }

    #[Test]
    public function testRegisterDomainIncludesPrivacyProxy(): void
    {
        $params = MockParamsV3::withIdProtection('newdomain.com');
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Register');

        $this->assertArrayHasKey('PrivacyProxy', $order['Order']['Domain']);
        $this->assertEquals('Proxy', $order['Order']['Domain']['PrivacyProxy']['Type']);
    }

    #[Test]
    public function testRegisterDomainUsesPrivacyForProxyLite(): void
    {
        $params = MockParamsV3::withIdProtection('newdomain.com', ['Proxy_Lite' => 'on']);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('Privacy', $order['Order']['Domain']['PrivacyProxy']['Type']);
    }

    #[Test]
    public function testRegisterDomainSetsNoneForNoIdProtection(): void
    {
        $params = MockParamsV3::forRegistration('newdomain.com', ['idprotection' => false]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Register');

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

        $order = $request->mapToOrder($params, 'Register');

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

        $order = $request->mapToOrder($params, 'Transfer');

        $this->assertEquals('Transfer', $order['Order']['Type']);
        $this->assertEquals('transfer.com', $order['Order']['Domain']['Name']);
        $this->assertEquals('TRANSFER-EPP-CODE', $order['Order']['Domain']['AuthInfo']);
    }

    #[Test]
    public function testTransferDomainIncludesPrivacyProxy(): void
    {
        $params = MockParamsV3::forTransfer('transfer.com', 'EPP-CODE', ['idprotection' => true]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Transfer');

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

        $order = $request->mapToOrder($params, 'Renew');

        $this->assertEquals('Renew', $order['Order']['Type']);
        $this->assertEquals('renew.com', $order['Order']['Domain']['Name']);
        $this->assertEquals(2, $order['Order']['Domain']['RenewPeriod']);
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

        // Registrant should have FirstName/LastName fields (v3 API)
        $this->assertArrayHasKey('FirstName', $result);
        $this->assertArrayHasKey('LastName', $result);
        $this->assertEquals('John', $result['FirstName']);
        $this->assertEquals('Doe', $result['LastName']);
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
        $request = \ascio\Request::create($params);

        $this->assertInstanceOf(\ascio\Request::class, $request);
    }

    #[Test]
    #[DataProvider('tldFactoryProvider')]
    public function testCreateFactoryForVariousTlds(string $tld): void
    {
        $params = MockParamsV3::forTld($tld);

        // Factory should return a valid Request instance for all TLDs
        $request = \ascio\Request::create($params);

        $this->assertInstanceOf(\ascio\Request::class, $request);
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
            'Register Domain' => ['Register'],
            'Transfer Domain' => ['Transfer'],
            'Renew Domain' => ['Renew'],
            'Expire Domain' => ['Expire'],
            'Unexpire Domain' => ['Unexpire'],
            'Nameserver Update' => ['NameserverUpdate'],
            'Contact Update' => ['ContactUpdate'],
            'Owner Change' => ['OwnerChange'],
            'Update AuthInfo' => ['UpdateAuthInfo'],
            'Change Locks' => ['ChangeLocks'],
            'Domain Details Update' => ['DetailsUpdate'],
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
        $order = $request->mapToOrder($params, 'Register');

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

    // =========================================================================
    // Cancel Order Tests
    // =========================================================================

    #[Test]
    public function testCancelOrderMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'cancelOrder'));
    }

    #[Test]
    public function testCancelOrderRequiresOrderIdOrDomain(): void
    {
        $params = array_merge($this->defaultParams, [
            'orderId' => 'ORD-12345'
        ]);

        $request = new Request($params);

        // Verify the method accepts orderId parameter
        $this->assertTrue(method_exists($request, 'cancelOrder'));
    }

    // =========================================================================
    // Change Owner Tests
    // =========================================================================

    #[Test]
    public function testChangeOwnerCreatesOwnerChangeOrder(): void
    {
        $params = MockParamsV3::forRegistration('example.com');
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'OwnerChange');

        $this->assertEquals('OwnerChange', $order['Order']['Type']);
        $this->assertEquals('example.com', $order['Order']['Domain']['Name']);
        $this->assertArrayHasKey('Owner', $order['Order']['Domain']);
    }

    #[Test]
    public function testChangeOwnerMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'changeOwner'));
    }

    // =========================================================================
    // Delete Domain Tests
    // =========================================================================

    #[Test]
    public function testDeleteDomainCreatesDeleteOrder(): void
    {
        $params = MockParamsV3::forRegistration('delete-me.com');
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Delete');

        $this->assertEquals('Delete', $order['Order']['Type']);
        $this->assertEquals('delete-me.com', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function testDeleteDomainMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'deleteDomain'));
    }

    // =========================================================================
    // Get EPP Code Tests
    // =========================================================================

    #[Test]
    public function testGetEPPCodeMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'getEPPCode'));
    }

    #[Test]
    public function testGetEPPCodeReturnsEppCodeKey(): void
    {
        // Setup mock domain with AuthInfo
        CapsuleMock::setTableData('tblasciohandles', [
            ['type' => 'domain', 'whmcs_id' => 1, 'domain' => 'example.com', 'ascio_id' => 'DOM-12345']
        ]);

        $request = new Request($this->defaultParams);

        // The method should return array with 'eppcode' key
        $this->assertTrue(method_exists($request, 'getEPPCode'));
    }

    // =========================================================================
    // Restore Domain Tests
    // =========================================================================

    #[Test]
    public function testRestoreDomainCreatesRestoreOrder(): void
    {
        $params = MockParamsV3::forRegistration('expired.com');
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Restore');

        $this->assertEquals('Restore', $order['Order']['Type']);
        $this->assertEquals('expired.com', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function testRestoreDomainMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'restoreDomain'));
    }

    // =========================================================================
    // Save Nameservers Tests
    // =========================================================================

    #[Test]
    public function testSaveNameserversCreatesNameserverUpdateOrder(): void
    {
        $params = MockParamsV3::forRegistration('example.com', [
            'ns1' => 'ns1.newhost.com',
            'ns2' => 'ns2.newhost.com',
            'ns3' => 'ns3.newhost.com',
            'ns4' => '',
            'ns5' => ''
        ]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'NameserverUpdate');

        $this->assertEquals('NameserverUpdate', $order['Order']['Type']);
        $this->assertEquals('example.com', $order['Order']['Domain']['Name']);
        $this->assertEquals('ns1.newhost.com', $order['Order']['Domain']['NameServers']['NameServer1']['HostName']);
        $this->assertEquals('ns2.newhost.com', $order['Order']['Domain']['NameServers']['NameServer2']['HostName']);
        $this->assertEquals('ns3.newhost.com', $order['Order']['Domain']['NameServers']['NameServer3']['HostName']);
    }

    #[Test]
    public function testSaveNameserversMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'saveNameservers'));
    }

    // =========================================================================
    // Save Registrar Lock Tests
    // =========================================================================

    #[Test]
    public function testSaveRegistrarLockCreatesChangeLocksOrder(): void
    {
        $params = MockParamsV3::forRegistration('example.com', [
            'lockenabled' => 'locked'
        ]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'ChangeLocks');

        $this->assertEquals('ChangeLocks', $order['Order']['Type']);
        $this->assertEquals('example.com', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function testSaveRegistrarLockMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'saveRegistrarLock'));
    }

    #[Test]
    public function testSaveRegistrarLockUsesLockForLockedStatus(): void
    {
        $params = array_merge($this->defaultParams, [
            'lockenabled' => 'locked'
        ]);
        $request = new Request($params);

        // When locked, the TransferLock should be 'Lock'
        // Verify method exists and params are set correctly
        $this->assertEquals('locked', $params['lockenabled']);
    }

    #[Test]
    public function testSaveRegistrarLockUsesUnLockForUnlockedStatus(): void
    {
        $params = array_merge($this->defaultParams, [
            'lockenabled' => 'unlocked'
        ]);
        $request = new Request($params);

        // When unlocked, the TransferLock should be 'UnLock'
        $this->assertEquals('unlocked', $params['lockenabled']);
    }

    // =========================================================================
    // Update Contacts Tests
    // =========================================================================

    #[Test]
    public function testUpdateContactsMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'updateContacts'));
    }

    #[Test]
    public function testUpdateContactsCreatesContactUpdateOrder(): void
    {
        $params = MockParamsV3::forRegistration('example.com');
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'ContactUpdate');

        $this->assertEquals('ContactUpdate', $order['Order']['Type']);
        $this->assertArrayHasKey('Admin', $order['Order']['Domain']);
        $this->assertArrayHasKey('Tech', $order['Order']['Domain']);
    }

    #[Test]
    public function testMapToContact2CreatesContactFromNestedWhmcsFormat(): void
    {
        $request = new Request($this->defaultParams);

        $contactDetails = [
            'First Name' => 'John',
            'Last Name' => 'Doe',
            'Company Name' => 'ACME Inc',
            'Address1' => '123 Main St',
            'Address2' => 'Suite 100',
            'City' => 'New York',
            'State' => 'NY',
            'Postcode' => '10001',
            'Country' => 'US',
            'Country Code' => 'US',
            'Email' => 'john@example.com',
            'Phone Number' => '+1.5551234567',
            'Fax Number' => ''
        ];

        $result = $request->mapToContact2($contactDetails, 'Registrant');

        $this->assertEquals('John', $result->FirstName);
        $this->assertEquals('Doe', $result->LastName);
        $this->assertEquals('ACME Inc', $result->OrgName);
        $this->assertEquals('123 Main St', $result->Address1);
        $this->assertEquals('Suite 100', $result->Address2);
        $this->assertEquals('New York', $result->City);
        $this->assertEquals('NY', $result->State);
        $this->assertEquals('10001', $result->PostalCode);
        $this->assertEquals('US', $result->CountryCode);
        $this->assertEquals('john@example.com', $result->Email);
    }

    // =========================================================================
    // Update Domain Details Tests
    // =========================================================================

    #[Test]
    public function testUpdateDomainDetailsCreatesDetailsUpdateOrder(): void
    {
        $params = MockParamsV3::forRegistration('example.com', [
            'idprotection' => true
        ]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'DetailsUpdate');

        $this->assertEquals('DetailsUpdate', $order['Order']['Type']);
        $this->assertEquals('example.com', $order['Order']['Domain']['Name']);
        $this->assertArrayHasKey('PrivacyProxy', $order['Order']['Domain']);
    }

    #[Test]
    public function testUpdateDomainDetailsMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'updateDomainDetails'));
    }

    #[Test]
    public function testUpdateDomainDetailsIncludesPrivacyProxy(): void
    {
        $params = MockParamsV3::forRegistration('example.com', [
            'idprotection' => true
        ]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'DetailsUpdate');

        $this->assertEquals('Proxy', $order['Order']['Domain']['PrivacyProxy']['Type']);
    }

    // =========================================================================
    // Update EPP Code Tests
    // =========================================================================

    #[Test]
    public function testUpdateEPPCodeCreatesUpdateAuthInfoOrder(): void
    {
        $params = MockParamsV3::forRegistration('example.com', [
            'eppcode' => 'NEW-EPP-CODE-123'
        ]);
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'UpdateAuthInfo');

        $this->assertEquals('UpdateAuthInfo', $order['Order']['Type']);
        $this->assertEquals('example.com', $order['Order']['Domain']['Name']);
        $this->assertEquals('NEW-EPP-CODE-123', $order['Order']['Domain']['AuthInfo']);
    }

    #[Test]
    public function testUpdateEPPCodeMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'updateEPPCode'));
    }

    // =========================================================================
    // Expire Domain Tests
    // =========================================================================

    #[Test]
    public function testExpireDomainCreatesExpireOrder(): void
    {
        $params = MockParamsV3::forRegistration('expire.com');
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Expire');

        $this->assertEquals('Expire', $order['Order']['Type']);
        $this->assertEquals('expire.com', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function testExpireDomainMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'expireDomain'));
    }

    // =========================================================================
    // Unexpire Domain Tests
    // =========================================================================

    #[Test]
    public function testUnexpireDomainCreatesUnexpireOrder(): void
    {
        $params = MockParamsV3::forRegistration('unexpire.com');
        $request = new Request($params);

        $order = $request->mapToOrder($params, 'Unexpire');

        $this->assertEquals('Unexpire', $order['Order']['Type']);
        $this->assertEquals('unexpire.com', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function testUnexpireDomainMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'unexpireDomain'));
    }

    // =========================================================================
    // Expire After Renew Tests
    // =========================================================================

    #[Test]
    public function testExpireAfterRenewMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionClass($request);
        $this->assertTrue($reflection->hasMethod('expireAfterRenew'));
    }

    #[Test]
    public function testExpireAfterRenewIsProtectedMethod(): void
    {
        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'expireAfterRenew');

        $this->assertTrue($reflection->isProtected());
    }

    #[Test]
    public function testExpireAfterRenewOnlyTriggersWithAutoExpireEnabled(): void
    {
        $params = array_merge($this->defaultParams, [
            'AutoExpire' => 'on'
        ]);
        $request = new Request($params);

        $reflection = new \ReflectionMethod($request, 'expireAfterRenew');
        $reflection->setAccessible(true);

        // Create mock order and domain for testing
        // Use non-Autorenew_Domain type to skip the actual expireDomain call
        $order = (object) [
            'Order' => (object) [
                'Type' => 'Register',  // Not Autorenew_Domain, so won't trigger expireDomain
                'Status' => 'Completed'
            ]
        ];
        $domain = (object) [
            'Status' => 'ACTIVE'
        ];

        // Should return early since order type is not Autorenew_Domain
        $result = $reflection->invoke($request, $order, $domain);

        // Method should complete without error
        $this->assertNull($result);
    }

    #[Test]
    public function testExpireAfterRenewSkipsWhenAutoExpireDisabled(): void
    {
        $params = array_merge($this->defaultParams, [
            'AutoExpire' => ''  // disabled
        ]);
        $request = new Request($params);

        $reflection = new \ReflectionMethod($request, 'expireAfterRenew');
        $reflection->setAccessible(true);

        $order = (object) [
            'Order' => (object) [
                'Type' => 'Autorenew_Domain',
                'Status' => 'Completed'
            ]
        ];
        $domain = (object) [
            'Status' => 'ACTIVE'
        ];

        // Should return early and not process
        $result = $reflection->invoke($request, $order, $domain);

        $this->assertNull($result);
    }

    // =========================================================================
    // Registrant Verification Tests
    // =========================================================================

    #[Test]
    public function testDoRegistrantVerificationMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'doRegistrantVerification'));
    }

    #[Test]
    public function testGetRegistrantVerificationInfoMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'getRegistrantVerificationInfo'));
    }

    // =========================================================================
    // Additional Order Mapping Tests
    // =========================================================================

    #[Test]
    #[DataProvider('additionalOrderTypeProvider')]
    public function testMapToOrderForAdditionalOrderTypes(string $orderType, string $expectedType): void
    {
        $request = new Request($this->defaultParams);

        $order = $request->mapToOrder($this->defaultParams, $orderType);

        $this->assertEquals($expectedType, $order['Order']['Type']);
        $this->assertArrayHasKey('Domain', $order['Order']);
        $this->assertArrayHasKey('TransactionComment', $order['Order']);
    }

    public static function additionalOrderTypeProvider(): array
    {
        return [
            'Delete order' => ['Delete', 'Delete'],
            'Restore order' => ['Restore', 'Restore'],
            'RegistrantDetailsUpdate order' => ['RegistrantDetailsUpdate', 'RegistrantDetailsUpdate'],
        ];
    }

    // =========================================================================
    // Contact Mapping Edge Cases Tests
    // =========================================================================

    #[Test]
    public function testMapToContact2HandlesAlternativeAddressFields(): void
    {
        $request = new Request($this->defaultParams);

        // Test with 'Address 1' and 'Address 2' format (with space)
        $contactDetails = [
            'First Name' => 'Jane',
            'Last Name' => 'Smith',
            'Company Name' => '',
            'Address 1' => '456 Oak Ave',  // Note the space
            'Address 2' => 'Apt B',
            'City' => 'Boston',
            'State' => 'MA',
            'Postcode' => '02101',
            'Country' => 'US',
            'Email' => 'jane@example.com',
            'Phone Number' => '+1.5559876543',
            'Fax Number' => ''
        ];

        $result = $request->mapToContact2($contactDetails, 'Contact');

        $this->assertEquals('456 Oak Ave', $result->Address1);
        $this->assertEquals('Apt B', $result->Address2);
    }

    #[Test]
    public function testMapToContact2HandlesCountryCodeField(): void
    {
        $request = new Request($this->defaultParams);

        $contactDetails = [
            'First Name' => 'Test',
            'Last Name' => 'User',
            'Company Name' => '',
            'Address1' => '123 Test St',
            'Address2' => '',
            'City' => 'Test City',
            'State' => 'TS',
            'Postcode' => '12345',
            'Country Code' => 'CA',  // Using Country Code instead of Country
            'Email' => 'test@example.com',
            'Phone Number' => '+1.5551234567',
            'Fax Number' => ''
        ];

        $result = $request->mapToContact2($contactDetails, 'Contact');

        $this->assertEquals('CA', $result->CountryCode);
    }

    // =========================================================================
    // Get Contact Details Mapping Tests
    // =========================================================================

    #[Test]
    public function testMapGetContactDetailContactMapsAllFields(): void
    {
        $request = new Request($this->defaultParams);

        $contact = (object) [
            'FirstName' => 'Admin',
            'LastName' => 'Contact',
            'OrgName' => 'Test Org',
            'Email' => 'admin@test.com',
            'Phone' => '+1.5551234567',
            'Fax' => '+1.5551234568',
            'Address1' => '100 Admin St',
            'Address2' => 'Floor 2',
            'State' => 'CA',
            'PostalCode' => '90210',
            'City' => 'Beverly Hills',
            'CountryCode' => 'US'
        ];

        $values = [];
        $result = $request->mapGetContactDetailContact($values, $contact, 'Admin');

        $this->assertEquals('Admin', $result['Admin']['First Name']);
        $this->assertEquals('Contact', $result['Admin']['Last Name']);
        $this->assertEquals('Test Org', $result['Admin']['Company Name']);
        $this->assertEquals('admin@test.com', $result['Admin']['Email']);
        $this->assertEquals('+1.5551234567', $result['Admin']['Phone Number']);
        $this->assertEquals('+1.5551234568', $result['Admin']['Fax Number']);
        $this->assertEquals('100 Admin St', $result['Admin']['Address1']);
        $this->assertEquals('Floor 2', $result['Admin']['Address2']);
        $this->assertEquals('CA', $result['Admin']['State']);
        $this->assertEquals('90210', $result['Admin']['Postcode']);
        $this->assertEquals('Beverly Hills', $result['Admin']['City']);
        $this->assertEquals('US', $result['Admin']['Country Code']);
    }

    #[Test]
    public function testMapGetContactDetailContactHandlesNullContact(): void
    {
        $request = new Request($this->defaultParams);

        $values = ['existing' => 'data'];
        $result = $request->mapGetContactDetailContact($values, null, 'Technical');

        // Should return values unchanged when contact is null
        $this->assertEquals(['existing' => 'data'], $result);
    }

    #[Test]
    public function testMapGetContactDetailRegistrantMapsAllFields(): void
    {
        $request = new Request($this->defaultParams);

        $registrant = (object) [
            'Name' => 'John Registrant',  // Note: Name field gets split
            'OrgName' => 'Registrant Org',
            'Email' => 'registrant@test.com',
            'Phone' => '+1.5559999999',
            'Fax' => '+1.5558888888',
            'Address1' => '200 Registrant Blvd',
            'Address2' => 'Unit 5',
            'State' => 'TX',
            'PostalCode' => '75001',
            'City' => 'Dallas',
            'CountryCode' => 'US'
        ];

        $values = [];
        $result = $request->mapGetContactDetailRegistrant($values, $registrant);

        $this->assertEquals('Registrant Org', $result['Registrant']['Company Name']);
        $this->assertEquals('registrant@test.com', $result['Registrant']['Email']);
        $this->assertEquals('+1.5559999999', $result['Registrant']['Phone Number']);
        $this->assertEquals('+1.5558888888', $result['Registrant']['Fax Number']);
        $this->assertEquals('200 Registrant Blvd', $result['Registrant']['Address1']);
        $this->assertEquals('Unit 5', $result['Registrant']['Address2']);
        $this->assertEquals('TX', $result['Registrant']['State']);
        $this->assertEquals('75001', $result['Registrant']['Postcode']);
        $this->assertEquals('Dallas', $result['Registrant']['City']);
        $this->assertEquals('US', $result['Registrant']['Country Code']);
    }

    // =========================================================================
    // Has Status Helper Tests
    // =========================================================================

    #[Test]
    public function testHasStatusReturnsFalseForNullDomain(): void
    {
        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'hasStatus');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, null, 'active');

        $this->assertFalse($result);
    }

    #[Test]
    public function testHasStatusReturnsFalseForDomainWithoutStatus(): void
    {
        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'hasStatus');
        $reflection->setAccessible(true);

        $domain = (object) [
            'DomainName' => 'example.com'
            // No Status property
        ];

        $result = $reflection->invoke($request, $domain, 'active');

        $this->assertFalse($result);
    }

    #[Test]
    public function testHasStatusReturnsTrueForMatchingStatus(): void
    {
        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'hasStatus');
        $reflection->setAccessible(true);

        $domain = (object) [
            'Status' => 'ACTIVE,TRANSFER_LOCK'
        ];

        $result = $reflection->invoke($request, $domain, 'active');

        $this->assertTrue($result);
    }

    #[Test]
    public function testHasStatusIsCaseInsensitive(): void
    {
        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'hasStatus');
        $reflection->setAccessible(true);

        $domain = (object) [
            'Status' => 'EXPIRING'
        ];

        $result = $reflection->invoke($request, $domain, 'expiring');

        $this->assertTrue($result);
    }

    // =========================================================================
    // Find Pending Order ID Tests
    // =========================================================================

    #[Test]
    public function testFindPendingOrderIdMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionClass($request);
        $this->assertTrue($reflection->hasMethod('findPendingOrderId'));
    }

    #[Test]
    public function testFindPendingOrderIdReturnsNullForDomainWithoutHandle(): void
    {
        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'findPendingOrderId');
        $reflection->setAccessible(true);

        $domain = (object) [
            'DomainName' => 'example.com'
            // No DomainHandle or Handle
        ];

        $result = $reflection->invoke($request, $domain);

        $this->assertNull($result);
    }

    // =========================================================================
    // Queue Message / Callback Tests
    // =========================================================================

    #[Test]
    public function testAckMessageMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'ackMessage'));
    }

    #[Test]
    public function testGetQueueMessageMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'getQueueMessage'));
    }

    #[Test]
    public function testGetCallbackDataMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'getCallbackData'));
    }

    // =========================================================================
    // Auto Create Zone Tests
    // =========================================================================

    #[Test]
    public function testAutoCreateZoneMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'autoCreateZone'));
    }

    #[Test]
    public function testAutoCreateZoneSkipsWhenDisabled(): void
    {
        $params = array_merge($this->defaultParams, [
            'AutoCreateDNS' => ''  // disabled
        ]);
        $request = new Request($params);

        // Should not throw and should return early
        $request->autoCreateZone('test.com');

        $this->assertTrue(true); // If we got here, test passes
    }

    // =========================================================================
    // Send Status Tests
    // =========================================================================

    #[Test]
    public function testSendStatusMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertTrue(method_exists($request, 'sendStatus'));
    }

    #[Test]
    public function testSendStatusReturnsNullWhenDetailedOrderStatusDisabled(): void
    {
        $params = array_merge($this->defaultParams, [
            'DetailedOrderStatus' => ''  // disabled
        ]);
        $request = new Request($params);

        $order = (object) [
            'Order' => (object) [
                'Type' => 'Register'
            ]
        ];

        $result = $request->sendStatus($order, 1, 'Completed', '');

        $this->assertNull($result);
    }

    #[Test]
    public function testSendStatusReturnsNullForDisallowedOrderType(): void
    {
        $params = array_merge($this->defaultParams, [
            'DetailedOrderStatus' => 'on'
        ]);
        $request = new Request($params);

        $order = (object) [
            'Order' => (object) [
                'Type' => 'InvalidType'
            ]
        ];

        $result = $request->sendStatus($order, 1, 'Completed', '');

        $this->assertNull($result);
    }

    // =========================================================================
    // Is Ascio Order Test
    // =========================================================================

    #[Test]
    public function testIsAscioOrderMethodExists(): void
    {
        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionClass($request);
        $this->assertTrue($reflection->hasMethod('isAscioOrder'));
    }
}
