<?php

namespace Ascio\Tests\Unit\Tlds;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Unit tests for .CA TLD plugin
 *
 * Tests all 16 registrant types and trademark handling
 *
 * @covers \ascio\ca
 */
class CaTldTest extends TestCase
{
    private array $defaultParams;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();

        $this->defaultParams = [
            'Username' => 'testuser',
            'Password' => 'testpass',
            'TestMode' => 'on',
            'domainid' => 1,
            'domainname' => 'example.ca',
            'sld' => 'example',
            'tld' => 'ca',
            'regperiod' => 1,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'companyname' => 'Test Company',
            'address1' => '123 Test Street',
            'address2' => '',
            'city' => 'Toronto',
            'state' => 'ON',
            'postcode' => 'M5V 1A1',
            'country' => 'CA',
            'email' => 'test@example.com',
            'fullphonenumber' => '+1.4165551234',
            'adminfirstname' => 'Admin',
            'adminlastname' => 'User',
            'admincompanyname' => 'Test Company',
            'adminaddress1' => '123 Test Street',
            'adminaddress2' => '',
            'admincity' => 'Toronto',
            'adminstate' => 'ON',
            'adminpostcode' => 'M5V 1A1',
            'admincountry' => 'CA',
            'adminemail' => 'admin@example.com',
            'adminfullphonenumber' => '+1.4165551234',
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
    // Registrant Type Mapping Tests
    // =========================================================================

    #[Test]
    #[DataProvider('registrantTypeProvider')]
    public function mapToRegistrantSetsCorrectRegistrantType(string $legalType, string $expectedCode): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Legal Type' => $legalType
            ]
        ]);

        $request = Request::create($params);

        // Access protected method via reflection
        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals($expectedCode, $result['RegistrantType']);
    }

    public static function registrantTypeProvider(): array
    {
        return [
            'Corporation' => ['Corporation', 'CCO'],
            'Canadian Citizen' => ['Canadian Citizen', 'CCT'],
            'Permanent Resident' => ['Permanent Resident of Canada', 'RES'],
            'Government' => ['Government', 'GOV'],
            'Educational Institution' => ['Canadian Educational Institution', 'EDU'],
            'Unincorporated Association' => ['Canadian Unincorporated Association', 'ASS'],
            'Hospital' => ['Canadian Hospital', 'HOP'],
            'Partnership' => ['Partnership Registered in Canada', 'PRT'],
            'Trademark' => ['Trade-mark registered in Canada', 'TDM'],
            'Trade Union' => ['Canadian Trade Union', 'TRD'],
            'Political Party' => ['Canadian Political Party', 'PLT'],
            'Library Archive Museum' => ['Canadian Library Archive or Museum', 'LAM'],
            'Trust' => ['Trust established in Canada', 'TRS'],
            'Aboriginal Peoples' => ['Aboriginal Peoples', 'ABO'],
            'Legal Representative' => ['Legal Representative of a Canadian Citizen', 'LGR'],
            'Official Mark' => ['Official mark registered in Canada', 'OMK'],
        ];
    }

    // =========================================================================
    // Trademark Mapping Tests
    // =========================================================================

    #[Test]
    public function mapToTrademarkSetsCanadaForCanadianCitizen(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Legal Type' => 'Canadian Citizen',
                'Canadian Citizen' => true
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToTrademark');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('CA', $result['Country']);
    }

    #[Test]
    public function mapToTrademarkSetsTrademarkDetails(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Legal Type' => 'Trade-mark registered in Canada',
                'Trademark Number' => 'TM123456',
                'Trademark Name' => 'My Trademark',
                'Trademark Country' => 'CA'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToTrademark');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('TM123456', $result['Number']);
        $this->assertEquals('My Trademark', $result['Name']);
        $this->assertEquals('CA', $result['Country']);
    }

    #[Test]
    public function mapToTrademarkReturnsNullCountryWithoutTrademarkName(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Legal Type' => 'Corporation',
                'Trademark Number' => '',
                'Trademark Name' => ''
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToTrademark');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertNull($result['Country']);
    }

    // =========================================================================
    // Order Creation Tests
    // =========================================================================

    #[Test]
    public function createOrderIncludesRegistrantType(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Legal Type' => 'Corporation'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('CCO', $order['Order']['Domain']['Registrant']['RegistrantType']);
    }

    #[Test]
    public function createOrderIncludesTrademark(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Legal Type' => 'Trade-mark registered in Canada',
                'Trademark Number' => 'TM789',
                'Trademark Name' => 'Brand Name',
                'Trademark Country' => 'CA'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertArrayHasKey('Trademark', $order['Order']['Domain']);
        $this->assertEquals('TM789', $order['Order']['Domain']['Trademark']['Number']);
    }

    // =========================================================================
    // Validation Tests
    // =========================================================================

    #[Test]
    public function registrantIncludesCanadianAddress(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Legal Type' => 'Canadian Citizen'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('CA', $result['CountryCode']);
        $this->assertEquals('Toronto', $result['City']);
        $this->assertEquals('ON', $result['State']);
    }
}
