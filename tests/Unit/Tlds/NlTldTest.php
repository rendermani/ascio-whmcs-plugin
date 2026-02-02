<?php

namespace Ascio\Tests\Unit\Tlds;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request;
use ascio\AscioException;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Unit tests for .NL TLD plugin
 *
 * Tests Organisation Number requirement and registrant type mapping
 *
 * @covers \ascio\nl
 */
class NlTldTest extends TestCase
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
            'domainname' => 'voorbeeld.nl',
            'sld' => 'voorbeeld',
            'tld' => 'nl',
            'regperiod' => 1,
            'firstname' => 'Jan',
            'lastname' => 'de Vries',
            'companyname' => '',
            'address1' => 'Keizersgracht 1',
            'address2' => '',
            'city' => 'Amsterdam',
            'state' => 'NH',
            'postcode' => '1015 CN',
            'country' => 'NL',
            'email' => 'jan@example.nl',
            'fullphonenumber' => '+31.201234567',
            'adminfirstname' => 'Jan',
            'adminlastname' => 'de Vries',
            'admincompanyname' => '',
            'adminaddress1' => 'Keizersgracht 1',
            'adminaddress2' => '',
            'admincity' => 'Amsterdam',
            'adminstate' => 'NH',
            'adminpostcode' => '1015 CN',
            'admincountry' => 'NL',
            'adminemail' => 'admin@example.nl',
            'adminfullphonenumber' => '+31.201234567',
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'eppcode' => 'EPP123456',
            'idprotection' => false,
            'custom' => [],
            'additionalfields' => [
                'Organisation Number' => ''
            ]
        ];
    }

    // =========================================================================
    // Registrant Type Mapping Tests
    // =========================================================================

    #[Test]
    public function mapToRegistrantSetsPERSOONForIndividual(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => '',
            'additionalfields' => [
                'Organisation Number' => ''
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('PERSOON', $result['RegistrantType']);
    }

    #[Test]
    public function mapToRegistrantSetsBVForDutchCompany(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => 'Test BV',
            'country' => 'NL',
            'additionalfields' => [
                'Organisation Number' => '12345678'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('BV', $result['RegistrantType']);
    }

    #[Test]
    public function mapToRegistrantSetsBGGForForeignCompany(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => 'Foreign Corp',
            'country' => 'DE',
            'additionalfields' => [
                'Organisation Number' => 'DE123456'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('BGG', $result['RegistrantType']);
    }

    // =========================================================================
    // Organisation Number Tests
    // =========================================================================

    #[Test]
    public function mapToRegistrantSetsOrganisationNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => 'Test BV',
            'additionalfields' => [
                'Organisation Number' => 'KVK12345678'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('KVK12345678', $result['RegistrantNumber']);
    }

    #[Test]
    public function mapToRegistrantThrowsExceptionForCompanyWithoutOrgNumber(): void
    {
        $this->expectException(\ascio\AscioException::class);
        $this->expectExceptionMessage('Please enter a valid Organization Number');

        $params = array_merge($this->defaultParams, [
            'companyname' => 'Test BV',
            'country' => 'NL',
            'additionalfields' => [
                'Organisation Number' => ''
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $reflection->invoke($request, $params);
    }

    #[Test]
    public function mapToRegistrantAllowsIndividualWithoutOrgNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => '',
            'additionalfields' => [
                'Organisation Number' => ''
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        // Should not throw exception
        $result = $reflection->invoke($request, $params);

        $this->assertEquals('PERSOON', $result['RegistrantType']);
    }

    // =========================================================================
    // Contact Type Tests
    // =========================================================================

    #[Test]
    public function mapToAdminSetsBGGType(): void
    {
        $params = $this->defaultParams;

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToAdmin');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('BGG', $result['Type']);
    }

    #[Test]
    public function mapToTechSetsBGGType(): void
    {
        $params = $this->defaultParams;

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToTech');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('BGG', $result['Type']);
    }

    #[Test]
    public function mapToBillingSetsBGGType(): void
    {
        $params = $this->defaultParams;

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToBilling');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('BGG', $result['Type']);
    }

    // =========================================================================
    // Order Structure Tests
    // =========================================================================

    #[Test]
    public function orderIncludesCorrectStructureForIndividual(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => '',
            'additionalfields' => [
                'Organisation Number' => ''
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('voorbeeld.nl', $order['Order']['Domain']['Name']);
        $this->assertEquals('PERSOON', $order['Order']['Domain']['Owner']['RegistrantType']);
    }

    #[Test]
    public function orderIncludesCorrectStructureForDutchCompany(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => 'Test BV',
            'country' => 'NL',
            'additionalfields' => [
                'Organisation Number' => 'KVK12345678'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('BV', $order['Order']['Domain']['Owner']['RegistrantType']);
        $this->assertEquals('KVK12345678', $order['Order']['Domain']['Owner']['RegistrantNumber']);
        $this->assertEquals('Test BV', $order['Order']['Domain']['Owner']['OrgName']);
    }

    #[Test]
    public function orderIncludesCorrectStructureForForeignCompany(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => 'German GmbH',
            'country' => 'DE',
            'additionalfields' => [
                'Organisation Number' => 'HRB123456'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('BGG', $order['Order']['Domain']['Owner']['RegistrantType']);
        $this->assertEquals('HRB123456', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // All Contacts Have BGG Type Tests
    // =========================================================================

    #[Test]
    public function orderIncludesBGGTypeForAllContacts(): void
    {
        $params = array_merge($this->defaultParams, [
            'companyname' => 'Test BV',
            'additionalfields' => [
                'Organisation Number' => 'KVK12345678'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('BGG', $order['Order']['Domain']['Admin']['Type']);
        $this->assertEquals('BGG', $order['Order']['Domain']['Tech']['Type']);
        $this->assertEquals('BGG', $order['Order']['Domain']['Billing']['Type']);
    }
}
