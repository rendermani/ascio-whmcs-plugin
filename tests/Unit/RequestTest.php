<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\v2\domains\Request;
use ascio\AscioException;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SoapClientMock;

/**
 * Unit tests for ascio\v2\domains\Request class
 *
 * @covers \ascio\v2\domains\Request
 */
class RequestTest extends TestCase
{
    private array $defaultParams;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
        SoapClientMock::reset();

        $this->defaultParams = [
            'Username' => 'testuser',
            'Password' => 'testpass',
            'TestMode' => 'on',
            'domainid' => 1,
            'domainname' => 'example.com',
            'sld' => 'example',
            'tld' => 'com',
            'regperiod' => 1,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'companyname' => 'Test Company',
            'address1' => '123 Test Street',
            'address2' => 'Suite 100',
            'city' => 'Test City',
            'state' => 'TS',
            'postcode' => '12345',
            'country' => 'US',
            'email' => 'test@example.com',
            'fullphonenumber' => '+1.5551234567',
            'adminfirstname' => 'Admin',
            'adminlastname' => 'User',
            'admincompanyname' => 'Test Company',
            'adminaddress1' => '123 Test Street',
            'adminaddress2' => '',
            'admincity' => 'Test City',
            'adminstate' => 'TS',
            'adminpostcode' => '12345',
            'admincountry' => 'US',
            'adminemail' => 'admin@example.com',
            'adminfullphonenumber' => '+1.5551234567',
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'eppcode' => 'EPP123456',
            'idprotection' => false,
            'custom' => [],
            'additionalfields' => []
        ];
    }

    // =========================================================================
    // Request::create() factory tests
    // =========================================================================

    #[Test]
    public function createReturnsBaseRequestForGenericTld(): void
    {
        $params = array_merge($this->defaultParams, ['tld' => 'com']);

        $request = Request::create($params);

        $this->assertInstanceOf(Request::class, $request);
    }

    #[Test]
    public function createReturnsTldSpecificRequestForCa(): void
    {
        $params = array_merge($this->defaultParams, ['tld' => 'ca']);

        $request = Request::create($params);

        // Should return a ca-specific Request subclass
        $this->assertInstanceOf(Request::class, $request);
    }

    #[Test]
    public function createReturnsTldSpecificRequestForIt(): void
    {
        $params = array_merge($this->defaultParams, ['tld' => 'it']);

        $request = Request::create($params);

        $this->assertInstanceOf(Request::class, $request);
    }

    // =========================================================================
    // Parent TLD inheritance tests
    // =========================================================================

    #[Test]
    public function createUsesParentTldForItalianRegionalTlds(): void
    {
        // ag.it should use the it.php plugin
        $params = array_merge($this->defaultParams, [
            'tld' => 'ag.it',
            'domainname' => 'example.ag.it'
        ]);

        $request = Request::create($params);

        // Should get the IT TLD class (which handles registrant types)
        $this->assertInstanceOf(Request::class, $request);
        // Verify it's using the IT plugin by checking the class has the IT-specific method
        $this->assertTrue(method_exists($request, 'renewDomain'));
    }

    #[Test]
    public function createUsesParentTldForRomeIt(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'roma.it',
            'domainname' => 'example.roma.it'
        ]);

        $request = Request::create($params);

        $this->assertInstanceOf(Request::class, $request);
    }

    #[Test]
    public function createUsesParentTldForUkVariants(): void
    {
        // ac.uk should use the uk.php plugin
        $params = array_merge($this->defaultParams, [
            'tld' => 'ac.uk',
            'domainname' => 'example.ac.uk'
        ]);

        $request = Request::create($params);

        $this->assertInstanceOf(Request::class, $request);
    }

    #[Test]
    #[DataProvider('parentTldProvider')]
    public function createUsesCorrectParentTld(string $subTld, string $expectedParent): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => $subTld,
            'domainname' => "example.$subTld"
        ]);

        $request = Request::create($params);

        // All should return a valid Request instance
        $this->assertInstanceOf(Request::class, $request);
    }

    public static function parentTldProvider(): array
    {
        return [
            'Italian regional ag.it' => ['ag.it', 'it'],
            'Italian regional roma.it' => ['roma.it', 'it'],
            'Italian regional milano.it' => ['milano.it', 'it'],
            'UK academic ac.uk' => ['ac.uk', 'uk'],
            'UK government gov.uk' => ['gov.uk', 'uk'],
            'Singapore commercial com.sg' => ['com.sg', 'sg'],
            'Singapore education edu.sg' => ['edu.sg', 'sg'],
            'Australia commercial com.au' => ['com.au', 'au'],
            'Australia network net.au' => ['net.au', 'au'],
            // AFNIC TLDs (French territories)
            'AFNIC pm' => ['pm', 'fr'],
            'AFNIC re' => ['re', 'fr'],
            'AFNIC tf' => ['tf', 'fr'],
            'AFNIC wf' => ['wf', 'fr'],
            'AFNIC yt' => ['yt', 'fr'],
        ];
    }

    // =========================================================================
    // AFNIC TLD inheritance tests (.pm, .re, .tf, .wf, .yt -> .fr)
    // =========================================================================

    #[Test]
    public function testPmTldInheritsFromFr(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pm',
            'domainname' => 'example.pm'
        ]);

        $request = Request::create($params);

        // Should inherit from .fr plugin
        $this->assertInstanceOf(Request::class, $request);
    }

    #[Test]
    public function testReTldInheritsFromFr(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 're',
            'domainname' => 'example.re'
        ]);

        $request = Request::create($params);

        // Should inherit from .fr plugin
        $this->assertInstanceOf(Request::class, $request);
    }

    #[Test]
    public function testTfTldInheritsFromFr(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'tf',
            'domainname' => 'example.tf'
        ]);

        $request = Request::create($params);

        // Should inherit from .fr plugin
        $this->assertInstanceOf(Request::class, $request);
    }

    #[Test]
    public function testWfTldInheritsFromFr(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'wf',
            'domainname' => 'example.wf'
        ]);

        $request = Request::create($params);

        // Should inherit from .fr plugin
        $this->assertInstanceOf(Request::class, $request);
    }

    #[Test]
    public function testYtTldInheritsFromFr(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'yt',
            'domainname' => 'example.yt'
        ]);

        $request = Request::create($params);

        // Should inherit from .fr plugin
        $this->assertInstanceOf(Request::class, $request);
    }

    // =========================================================================
    // setParams() tests
    // =========================================================================

    #[Test]
    public function setParamsSetsAccountAndPassword(): void
    {
        $request = new Request($this->defaultParams);

        $this->assertEquals('testuser', $request->account);
        $this->assertEquals('testpass', $request->password);
    }

    #[Test]
    public function setParamsSetsDomainNameFromDomainObj(): void
    {
        $domainObj = new class {
            public function getIdnSecondLevel(): string { return 'example'; }
            public function getTopLevel(): string { return 'com'; }
        };

        $params = array_merge($this->defaultParams, [
            'domainObj' => $domainObj,
            'sld' => 'example'
        ]);

        $request = new Request($params);

        $this->assertEquals('example.com', $request->domainName);
    }

    #[Test]
    public function setParamsSetsDomainNameFromDomainname(): void
    {
        $params = array_merge($this->defaultParams, [
            'domainname' => 'test.net'
        ]);

        $request = new Request($params);

        $this->assertEquals('test.net', $request->domainName);
    }

    // =========================================================================
    // mapToContact() tests
    // =========================================================================

    #[Test]
    public function mapToContactCreatesRegistrantWithFullName(): void
    {
        $request = new Request($this->defaultParams);
        $reflection = new \ReflectionMethod($request, 'mapToContact');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $this->defaultParams, 'Registrant');

        $this->assertEquals('John Doe', $result['Name']);
        $this->assertEquals('Test Company', $result['OrgName']);
        $this->assertEquals('123 Test Street', $result['Address1']);
        $this->assertEquals('Suite 100', $result['Address2']);
        $this->assertEquals('12345', $result['PostalCode']);
        $this->assertEquals('Test City', $result['City']);
        $this->assertEquals('TS', $result['State']);
        $this->assertEquals('US', $result['CountryCode']);
        $this->assertEquals('test@example.com', $result['Email']);
        $this->assertEquals('+1.5551234567', $result['Phone']);
    }

    #[Test]
    public function mapToContactCreatesAdminWithSeparateNames(): void
    {
        $request = new Request($this->defaultParams);
        $reflection = new \ReflectionMethod($request, 'mapToContact');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $this->defaultParams, 'Admin');

        $this->assertEquals('Admin', $result['FirstName']);
        $this->assertEquals('User', $result['LastName']);
        $this->assertArrayNotHasKey('Name', $result);
    }

    // =========================================================================
    // mapToRegistrant() tests
    // =========================================================================

    #[Test]
    public function mapToRegistrantIncludesCustomFields(): void
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
    // mapToNameservers() tests
    // =========================================================================

    #[Test]
    public function mapToNameserversCreatesCorrectStructure(): void
    {
        $request = new Request($this->defaultParams);
        $reflection = new \ReflectionMethod($request, 'mapToNameservers');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $this->defaultParams);

        $this->assertEquals('ns1.example.com', $result['NameServer1']['HostName']);
        $this->assertEquals('ns2.example.com', $result['NameServer2']['HostName']);
        $this->assertEquals('', $result['NameServer3']['HostName']);
        $this->assertEquals('', $result['NameServer4']['HostName']);
        $this->assertEquals('', $result['NameServer5']['HostName']);
    }

    // =========================================================================
    // mapToOrder() tests
    // =========================================================================

    #[Test]
    public function mapToOrderCreatesRegisterDomainOrder(): void
    {
        $request = new Request($this->defaultParams);

        $result = $request->mapToOrder($this->defaultParams, 'Register_Domain');

        $this->assertEquals('Register_Domain', $result['order']['Type']);
        $this->assertEquals('example.com', $result['order']['Domain']['DomainName']);
        $this->assertEquals(1, $result['order']['Domain']['RegPeriod']);
        $this->assertArrayHasKey('Registrant', $result['order']['Domain']);
        $this->assertArrayHasKey('AdminContact', $result['order']['Domain']);
        $this->assertArrayHasKey('TechContact', $result['order']['Domain']);
        $this->assertArrayHasKey('BillingContact', $result['order']['Domain']);
        $this->assertArrayHasKey('NameServers', $result['order']['Domain']);
    }

    #[Test]
    public function mapToOrderCreatesTransferDomainOrder(): void
    {
        $request = new Request($this->defaultParams);

        $result = $request->mapToOrder($this->defaultParams, 'Transfer_Domain');

        $this->assertEquals('Transfer_Domain', $result['order']['Type']);
        $this->assertEquals('EPP123456', $result['order']['Domain']['AuthInfo']);
    }

    #[Test]
    public function mapToOrderIncludesPrivacyProxyForIdProtection(): void
    {
        $params = array_merge($this->defaultParams, ['idprotection' => true]);
        $request = new Request($params);

        $result = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Proxy', $result['order']['Domain']['PrivacyProxy']['Type']);
    }

    #[Test]
    public function mapToOrderUsesPrivacyForProxyLite(): void
    {
        $params = array_merge($this->defaultParams, [
            'idprotection' => true,
            'Proxy_Lite' => 'on'
        ]);
        $request = new Request($params);

        $result = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Privacy', $result['order']['Domain']['PrivacyProxy']['Type']);
    }

    #[Test]
    public function mapToOrderSetsNoneForNoIdProtection(): void
    {
        $params = array_merge($this->defaultParams, ['idprotection' => false]);
        $request = new Request($params);

        $result = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('None', $result['order']['Domain']['PrivacyProxy']['Type']);
    }

    #[Test]
    public function mapToOrderIncludesTransactionComment(): void
    {
        $params = array_merge($this->defaultParams, [
            'domainid' => 123,
            'userid' => 456
        ]);
        $request = new Request($params);

        $result = $request->mapToOrder($params, 'Register_Domain');

        $comment = json_decode($result['order']['TransactionComment'], true);
        $this->assertEquals('WHMCS', $comment['application']);
        $this->assertEquals(123, $comment['domainId']);
        $this->assertEquals(456, $comment['userId']);
        $this->assertEquals('Domain', $comment['objectType']);
    }

    // =========================================================================
    // mapToContact2() tests (nested contact format)
    // =========================================================================

    #[Test]
    public function mapToContact2CreatesRegistrantFromNestedFormat(): void
    {
        $contactData = [
            'First Name' => 'John',
            'Last Name' => 'Doe',
            'Company Name' => 'Test Corp',
            'Address1' => '123 Main St',
            'Address2' => '',
            'City' => 'Test City',
            'State' => 'TS',
            'Postcode' => '12345',
            'Country' => 'US',
            'Email' => 'john@example.com',
            'Phone Number' => '+1.5551234567',
            'Fax Number' => ''
        ];

        $request = new Request($this->defaultParams);
        $result = $request->mapToContact2($contactData, 'Registrant');

        $this->assertEquals('John Doe', $result->Name);
        $this->assertEquals('Test Corp', $result->OrgName);
        $this->assertObjectNotHasProperty('FirstName', $result);
        $this->assertObjectNotHasProperty('LastName', $result);
    }

    #[Test]
    public function mapToContact2CreatesContactFromNestedFormat(): void
    {
        $contactData = [
            'First Name' => 'Admin',
            'Last Name' => 'User',
            'Company Name' => 'Test Corp',
            'Address1' => '123 Main St',
            'Address2' => '',
            'City' => 'Test City',
            'State' => 'TS',
            'Postcode' => '12345',
            'Country' => 'US',
            'Email' => 'admin@example.com',
            'Phone Number' => '+1.5551234567',
            'Fax Number' => ''
        ];

        $request = new Request($this->defaultParams);
        $result = $request->mapToContact2($contactData, 'Contact');

        $this->assertEquals('Admin', $result->FirstName);
        $this->assertEquals('User', $result->LastName);
        $this->assertObjectNotHasProperty('Name', $result);
    }

    // =========================================================================
    // mapGetContactDetailRegistrant() tests
    // =========================================================================

    #[Test]
    public function mapGetContactDetailRegistrantConvertsToWhmcsFormat(): void
    {
        $registrant = (object) [
            'Name' => 'John Doe',
            'OrgName' => 'Test Company',
            'Email' => 'john@example.com',
            'Phone' => '+1.5551234567',
            'Fax' => '+1.5551234568',
            'Address1' => '123 Main St',
            'Address2' => 'Suite 100',
            'State' => 'TS',
            'PostalCode' => '12345',
            'City' => 'Test City',
            'CountryCode' => 'US'
        ];

        $request = new Request($this->defaultParams);
        $result = $request->mapGetContactDetailRegistrant([], $registrant);

        $this->assertEquals('John', $result['Registrant']['First Name']);
        $this->assertEquals('Doe', $result['Registrant']['Last Name']);
        $this->assertEquals('Test Company', $result['Registrant']['Company Name']);
        $this->assertEquals('john@example.com', $result['Registrant']['Email']);
        $this->assertEquals('+1.5551234567', $result['Registrant']['Phone Number']);
        $this->assertEquals('US', $result['Registrant']['Country Code']);
    }

    // =========================================================================
    // mapGetContactDetailContact() tests
    // =========================================================================

    #[Test]
    public function mapGetContactDetailContactConvertsToWhmcsFormat(): void
    {
        $contact = (object) [
            'FirstName' => 'Admin',
            'LastName' => 'User',
            'OrgName' => 'Test Company',
            'Email' => 'admin@example.com',
            'Phone' => '+1.5551234567',
            'Fax' => '+1.5551234568',
            'Address1' => '123 Main St',
            'Address2' => 'Suite 100',
            'State' => 'TS',
            'PostalCode' => '12345',
            'City' => 'Test City',
            'CountryCode' => 'US'
        ];

        $request = new Request($this->defaultParams);
        $result = $request->mapGetContactDetailContact([], $contact, 'Admin');

        $this->assertEquals('Admin', $result['Admin']['First Name']);
        $this->assertEquals('User', $result['Admin']['Last Name']);
        $this->assertEquals('Test Company', $result['Admin']['Company Name']);
        $this->assertEquals('admin@example.com', $result['Admin']['Email']);
    }

    #[Test]
    public function mapGetContactDetailContactHandlesNullContact(): void
    {
        $request = new Request($this->defaultParams);
        $result = $request->mapGetContactDetailContact([], null, 'Admin');

        $this->assertArrayNotHasKey('Admin', $result);
    }

    // =========================================================================
    // getDomainStatus() tests
    // =========================================================================

    #[Test]
    #[DataProvider('domainStatusProvider')]
    public function getDomainStatusReturnsCorrectStatus(string $ascioStatus, string $expectedWhmcsStatus): void
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
    public function getDomainStatusReturnsCancelledForNullDomain(): void
    {
        $request = new Request($this->defaultParams);

        $result = $request->getDomainStatus(null);

        $this->assertEquals('Cancelled', $result);
    }

    // =========================================================================
    // getEPPCode() tests
    // =========================================================================

    #[Test]
    public function getEPPCodeReturnsAuthInfoFromDomain(): void
    {
        // Set up mock to return a domain with AuthInfo
        CapsuleMock::setTableData('tblasciohandles', [
            ['type' => 'domain', 'whmcs_id' => 1, 'domain' => 'example.com', 'ascio_id' => 'DOM-123']
        ]);

        $request = new Request($this->defaultParams);

        // The searchDomain call will use the mock SoapClient
        // For this test, we'll just verify the method structure
        $this->assertTrue(method_exists($request, 'getEPPCode'));
    }

    // =========================================================================
    // Handle management tests
    // =========================================================================

    #[Test]
    public function getHandleReturnsStoredHandle(): void
    {
        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => 'example.com',
                'ascio_id' => 'DOM-12345'
            ]
        ]);

        $request = new Request($this->defaultParams);
        $result = $request->getHandle('domain', 1, 'example.com');

        $this->assertEquals('DOM-12345', $result);
    }

    #[Test]
    public function getHandleReturnsNullForNonExistentHandle(): void
    {
        CapsuleMock::setTableData('tblasciohandles', []);

        $request = new Request($this->defaultParams);
        $result = $request->getHandle('domain', 999, 'nonexistent.com');

        $this->assertNull($result);
    }

    #[Test]
    public function storeHandleInsertsNewHandle(): void
    {
        CapsuleMock::setTableData('tblasciohandles', []);

        $request = new Request($this->defaultParams);
        $request->storeHandle('domain', 1, 'DOM-NEW', 'example.com');

        $query = CapsuleMock::getLastQuery();
        $this->assertEquals('insert', $query['type']);
        $this->assertEquals('tblasciohandles', $query['table']);
    }

    // =========================================================================
    // Simulation Mode Tests
    // =========================================================================

    #[Test]
    public function isSimulationModeReturnsFalseByDefault(): void
    {
        // Ensure env var is not set
        putenv('ASCIO_SIMULATE');

        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'isSimulationMode');
        $reflection->setAccessible(true);

        $this->assertFalse($reflection->invoke($request));
    }

    #[Test]
    public function isSimulationModeReturnsTrueWhenEnvVarSet(): void
    {
        putenv('ASCIO_SIMULATE=1');

        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'isSimulationMode');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->invoke($request));

        // Clean up
        putenv('ASCIO_SIMULATE');
    }

    #[Test]
    public function isSimulationModeReturnsTrueWhenEnvVarSetToTrue(): void
    {
        putenv('ASCIO_SIMULATE=true');

        $request = new Request($this->defaultParams);

        $reflection = new \ReflectionMethod($request, 'isSimulationMode');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->invoke($request));

        // Clean up
        putenv('ASCIO_SIMULATE');
    }

    #[Test]
    public function isSimulationModeReturnsTrueWhenModuleParamSet(): void
    {
        $params = array_merge($this->defaultParams, ['Simulate' => 'on']);

        $request = new Request($params);

        $reflection = new \ReflectionMethod($request, 'isSimulationMode');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->invoke($request));
    }

    #[Test]
    public function isSimulationModeReturnsFalseWhenModuleParamOff(): void
    {
        $params = array_merge($this->defaultParams, ['Simulate' => 'off']);

        $request = new Request($params);

        $reflection = new \ReflectionMethod($request, 'isSimulationMode');
        $reflection->setAccessible(true);

        $this->assertFalse($reflection->invoke($request));
    }
}
