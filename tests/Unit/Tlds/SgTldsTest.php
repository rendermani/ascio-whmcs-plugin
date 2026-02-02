<?php

namespace Ascio\Tests\Unit\Tlds;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Unit tests for Singapore TLD plugins (.sg, .com.sg, .edu.sg, .org.sg)
 *
 * Tests Registrant ID, Admin ID, and LocalPresence handling
 *
 * @covers \ascio\sg
 * @covers \ascio\com_sg
 * @covers \ascio\edu_sg
 * @covers \ascio\org_sg
 */
class SgTldsTest extends TestCase
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
            'domainname' => 'example.sg',
            'sld' => 'example',
            'tld' => 'sg',
            'regperiod' => 1,
            'firstname' => 'Wei Ming',
            'lastname' => 'Tan',
            'companyname' => 'Singapore Pte Ltd',
            'address1' => '1 Raffles Place',
            'address2' => '#20-01',
            'city' => 'Singapore',
            'state' => '',
            'postcode' => '048616',
            'country' => 'SG',
            'email' => 'weiming@example.sg',
            'fullphonenumber' => '+65.61234567',
            'adminfirstname' => 'Wei Ming',
            'adminlastname' => 'Tan',
            'admincompanyname' => 'Singapore Pte Ltd',
            'adminaddress1' => '1 Raffles Place',
            'adminaddress2' => '#20-01',
            'admincity' => 'Singapore',
            'adminstate' => '',
            'adminpostcode' => '048616',
            'admincountry' => 'SG',
            'adminemail' => 'admin@example.sg',
            'adminfullphonenumber' => '+65.61234567',
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'eppcode' => 'EPP123456',
            'idprotection' => false,
            'custom' => [],
            'additionalfields' => [
                'Registrant ID' => 'S1234567A',
                'Admin ID' => 'S7654321B',
                'Local Presence' => ''
            ]
        ];
    }

    // =========================================================================
    // Registrant ID Tests
    // =========================================================================

    #[Test]
    public function mapToRegistrantSetsRegistrantId(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Registrant ID' => 'S1234567A',
                'Admin ID' => 'S7654321B'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('S1234567A', $result['RegistrantNumber']);
    }

    #[Test]
    public function mapToRegistrantHandlesCompanyRegistrationNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Registrant ID' => '200012345K',  // Singapore company number format
                'Admin ID' => 'S7654321B'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('200012345K', $result['RegistrantNumber']);
    }

    // =========================================================================
    // Admin ID Tests
    // =========================================================================

    #[Test]
    public function mapToAdminSetsOrganisationNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Registrant ID' => 'S1234567A',
                'Admin ID' => 'S7654321B'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToAdmin');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('S7654321B', $result['OrganisationNumber']);
    }

    // =========================================================================
    // Local Presence Tests
    // =========================================================================

    #[Test]
    public function mapToOrderSetsLocalPresenceWhenEnabled(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Registrant ID' => 'S1234567A',
                'Admin ID' => 'S7654321B',
                'Local Presence' => 'on'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('LocalPresenceAdmin', $order['Order']['LocalPresence']);
    }

    #[Test]
    public function mapToOrderDoesNotSetLocalPresenceWhenDisabled(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Registrant ID' => 'S1234567A',
                'Admin ID' => 'S7654321B',
                'Local Presence' => ''
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertArrayNotHasKey('LocalPresence', $order['Order']);
    }

    // =========================================================================
    // COM.SG TLD Tests
    // =========================================================================

    #[Test]
    public function comSgTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'com.sg',
            'domainname' => 'example.com.sg',
            'additionalfields' => [
                'Registrant ID' => '200012345K',
                'Admin ID' => 'S7654321B'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.com.sg', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function comSgTldSetsRegistrantId(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'com.sg',
            'domainname' => 'example.com.sg',
            'additionalfields' => [
                'Registrant ID' => '200012345K',
                'Admin ID' => 'S7654321B'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('200012345K', $result['RegistrantNumber']);
    }

    // =========================================================================
    // EDU.SG TLD Tests
    // =========================================================================

    #[Test]
    public function eduSgTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'edu.sg',
            'domainname' => 'school.edu.sg',
            'additionalfields' => [
                'Registrant ID' => 'T08GB0001G',  // Educational institution
                'Admin ID' => 'S7654321B'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('school.edu.sg', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // ORG.SG TLD Tests
    // =========================================================================

    #[Test]
    public function orgSgTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'org.sg',
            'domainname' => 'charity.org.sg',
            'additionalfields' => [
                'Registrant ID' => 'S99SS0001G',  // Society/Association
                'Admin ID' => 'S7654321B'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('charity.org.sg', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // Order Structure Tests
    // =========================================================================

    #[Test]
    public function orderIncludesCorrectStructureForSgDomain(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Registrant ID' => 'S1234567A',
                'Admin ID' => 'S7654321B',
                'Local Presence' => 'on'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.sg', $order['Order']['Domain']['Name']);
        $this->assertEquals('S1234567A', $order['Order']['Domain']['Owner']['RegistrantNumber']);
        $this->assertEquals('S7654321B', $order['Order']['Domain']['Admin']['OrganisationNumber']);
        $this->assertEquals('LocalPresenceAdmin', $order['Order']['LocalPresence']);
    }

    #[Test]
    #[DataProvider('sgTldListProvider')]
    public function sgSubdomainTldsCreateValidOrders(string $tld): void
    {
        $domainName = "example.{$tld}";
        $params = array_merge($this->defaultParams, [
            'tld' => $tld,
            'domainname' => $domainName,
            'additionalfields' => [
                'Registrant ID' => 'S1234567A',
                'Admin ID' => 'S7654321B'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals($domainName, $order['Order']['Domain']['Name']);
    }

    public static function sgTldListProvider(): array
    {
        return [
            'sg' => ['sg'],
            'com.sg' => ['com.sg'],
            'edu.sg' => ['edu.sg'],
            'org.sg' => ['org.sg'],
        ];
    }
}
