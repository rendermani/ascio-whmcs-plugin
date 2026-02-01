<?php

namespace Ascio\Tests\Unit\Tlds;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Unit tests for complex TLD plugins (4+ fields)
 *
 * Tests TLDs: amsterdam, cat, ee, moscow, pt
 */
class ComplexTldsTest extends TestCase
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
            'domainname' => 'example.com',
            'sld' => 'example',
            'tld' => 'com',
            'regperiod' => 1,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'companyname' => 'Test Company',
            'address1' => '123 Test Street',
            'address2' => '',
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
    // AMSTERDAM TLD Tests
    // Required: Registrant.Type, Registrant.Nr., Admin.Type, Tech.Type
    // =========================================================================

    #[Test]
    public function amsterdamTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'amsterdam',
            'domainname' => 'example.amsterdam'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.amsterdam', $order['Order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['Order']['Type']);
    }

    #[Test]
    public function amsterdamTldCreatesCorrectClass(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'amsterdam',
            'domainname' => 'example.amsterdam'
        ]);

        $request = Request::create($params);

        $this->assertInstanceOf(\ascio\amsterdam::class, $request);
    }

    #[Test]
    public function amsterdamTldSetsRegistrantType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'amsterdam',
            'domainname' => 'example.amsterdam',
            'additionalfields' => [
                'Registrant Type' => 'ORGANIZATION'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ORGANIZATION', $order['Order']['Domain']['Registrant']['RegistrantType']);
    }

    #[Test]
    public function amsterdamTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'amsterdam',
            'domainname' => 'example.amsterdam',
            'additionalfields' => [
                'Registrant Number' => 'NL123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('NL123456789', $order['Order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function amsterdamTldSetsAdminType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'amsterdam',
            'domainname' => 'example.amsterdam',
            'additionalfields' => [
                'Admin Type' => 'LEGAL'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('LEGAL', $order['Order']['Domain']['AdminContact']['Type']);
    }

    #[Test]
    public function amsterdamTldSetsTechType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'amsterdam',
            'domainname' => 'example.amsterdam',
            'additionalfields' => [
                'Tech Type' => 'TECH'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('TECH', $order['Order']['Domain']['TechContact']['Type']);
    }

    #[Test]
    public function amsterdamTldSetsAllFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'amsterdam',
            'domainname' => 'example.amsterdam',
            'additionalfields' => [
                'Registrant Type' => 'ORGANIZATION',
                'Registrant Number' => 'NL123456789',
                'Admin Type' => 'LEGAL',
                'Tech Type' => 'TECH'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ORGANIZATION', $order['Order']['Domain']['Registrant']['RegistrantType']);
        $this->assertEquals('NL123456789', $order['Order']['Domain']['Registrant']['RegistrantNumber']);
        $this->assertEquals('LEGAL', $order['Order']['Domain']['AdminContact']['Type']);
        $this->assertEquals('TECH', $order['Order']['Domain']['TechContact']['Type']);
    }

    // =========================================================================
    // CAT TLD Tests
    // Required: Domain.Purpose, Domain.AuthInfo, Registrant.Details, TM.Name
    // =========================================================================

    #[Test]
    public function catTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'cat',
            'domainname' => 'example.cat'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.cat', $order['Order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['Order']['Type']);
    }

    #[Test]
    public function catTldCreatesCorrectClass(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'cat',
            'domainname' => 'example.cat'
        ]);

        $request = Request::create($params);

        $this->assertInstanceOf(\ascio\cat::class, $request);
    }

    #[Test]
    public function catTldSetsDomainPurpose(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'cat',
            'domainname' => 'example.cat',
            'additionalfields' => [
                'Domain Purpose' => 'P1'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('P1', $order['Order']['Domain']['DomainPurpose']);
    }

    #[Test]
    public function catTldSetsAuthCode(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'cat',
            'domainname' => 'example.cat',
            'additionalfields' => [
                'Auth Code' => 'CAT-AUTH-12345'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('CAT-AUTH-12345', $order['Order']['Domain']['AuthInfo']);
    }

    #[Test]
    public function catTldSetsRegistrantDetails(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'cat',
            'domainname' => 'example.cat',
            'additionalfields' => [
                'Registrant Details' => 'Catalan language and culture organization'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Catalan language and culture organization', $order['Order']['Domain']['Registrant']['Details']);
    }

    #[Test]
    public function catTldSetsTrademarkName(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'cat',
            'domainname' => 'example.cat',
            'additionalfields' => [
                'Trademark Name' => 'My Catalan Trademark'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('My Catalan Trademark', $order['Order']['Domain']['Trademark']['Name']);
    }

    #[Test]
    public function catTldSetsAllFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'cat',
            'domainname' => 'example.cat',
            'additionalfields' => [
                'Domain Purpose' => 'P2',
                'Auth Code' => 'CAT-AUTH-99999',
                'Registrant Details' => 'Catalan cultural entity',
                'Trademark Name' => 'My Catalan Brand'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('P2', $order['Order']['Domain']['DomainPurpose']);
        $this->assertEquals('CAT-AUTH-99999', $order['Order']['Domain']['AuthInfo']);
        $this->assertEquals('Catalan cultural entity', $order['Order']['Domain']['Registrant']['Details']);
        $this->assertEquals('My Catalan Brand', $order['Order']['Domain']['Trademark']['Name']);
    }

    // =========================================================================
    // EE TLD Tests
    // Required: Registrant.Phone, Registrant.Type, Registrant.Nr., Admin.Type,
    //           Admin.Nr., Tech.Type, Tech.Nr.
    // =========================================================================

    #[Test]
    public function eeTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ee',
            'domainname' => 'example.ee'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.ee', $order['Order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['Order']['Type']);
    }

    #[Test]
    public function eeTldCreatesCorrectClass(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ee',
            'domainname' => 'example.ee'
        ]);

        $request = Request::create($params);

        $this->assertInstanceOf(\ascio\ee::class, $request);
    }

    #[Test]
    public function eeTldSetsRegistrantType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ee',
            'domainname' => 'example.ee',
            'additionalfields' => [
                'Registrant Type' => 'PRIV'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('PRIV', $order['Order']['Domain']['Registrant']['RegistrantType']);
    }

    #[Test]
    public function eeTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ee',
            'domainname' => 'example.ee',
            'additionalfields' => [
                'Registrant Number' => 'EE12345678901'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('EE12345678901', $order['Order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function eeTldSetsAdminType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ee',
            'domainname' => 'example.ee',
            'additionalfields' => [
                'Admin Type' => 'PRIV'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('PRIV', $order['Order']['Domain']['AdminContact']['Type']);
    }

    #[Test]
    public function eeTldSetsAdminNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ee',
            'domainname' => 'example.ee',
            'additionalfields' => [
                'Admin Number' => 'ADM-EE-12345'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ADM-EE-12345', $order['Order']['Domain']['AdminContact']['OrganisationNumber']);
    }

    #[Test]
    public function eeTldSetsTechType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ee',
            'domainname' => 'example.ee',
            'additionalfields' => [
                'Tech Type' => 'ORG'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ORG', $order['Order']['Domain']['TechContact']['Type']);
    }

    #[Test]
    public function eeTldSetsTechNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ee',
            'domainname' => 'example.ee',
            'additionalfields' => [
                'Tech Number' => 'TECH-EE-67890'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('TECH-EE-67890', $order['Order']['Domain']['TechContact']['OrganisationNumber']);
    }

    #[Test]
    public function eeTldSetsAllFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ee',
            'domainname' => 'example.ee',
            'additionalfields' => [
                'Registrant Type' => 'ORG',
                'Registrant Number' => 'EE99887766554',
                'Admin Type' => 'PRIV',
                'Admin Number' => 'ADM-EE-99999',
                'Tech Type' => 'ORG',
                'Tech Number' => 'TECH-EE-88888'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ORG', $order['Order']['Domain']['Registrant']['RegistrantType']);
        $this->assertEquals('EE99887766554', $order['Order']['Domain']['Registrant']['RegistrantNumber']);
        $this->assertEquals('PRIV', $order['Order']['Domain']['AdminContact']['Type']);
        $this->assertEquals('ADM-EE-99999', $order['Order']['Domain']['AdminContact']['OrganisationNumber']);
        $this->assertEquals('ORG', $order['Order']['Domain']['TechContact']['Type']);
        $this->assertEquals('TECH-EE-88888', $order['Order']['Domain']['TechContact']['OrganisationNumber']);
    }

    // =========================================================================
    // MOSCOW TLD Tests
    // Required: Registrant.VAT, Registrant.Nr., Registrant.Details, Admin.Type,
    //           Admin.Details, Admin.Nr., Tech.Type, Tech.Details, Tech.Nr.
    // =========================================================================

    #[Test]
    public function moscowTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.moscow', $order['Order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['Order']['Type']);
    }

    #[Test]
    public function moscowTldCreatesCorrectClass(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow'
        ]);

        $request = Request::create($params);

        $this->assertInstanceOf(\ascio\moscow::class, $request);
    }

    #[Test]
    public function moscowTldSetsRegistrantVAT(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'VAT Number' => 'RU1234567890'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('RU1234567890', $order['Order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function moscowTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'Registrant Number' => 'OGRN1234567890123'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('OGRN1234567890123', $order['Order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function moscowTldSetsRegistrantDetails(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'Registrant Details' => 'Moscow-based organization'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Moscow-based organization', $order['Order']['Domain']['Registrant']['Details']);
    }

    #[Test]
    public function moscowTldSetsAdminType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'Admin Type' => 'ADMIN'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ADMIN', $order['Order']['Domain']['AdminContact']['Type']);
    }

    #[Test]
    public function moscowTldSetsAdminDetails(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'Admin Details' => 'Administrative contact details'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Administrative contact details', $order['Order']['Domain']['AdminContact']['Details']);
    }

    #[Test]
    public function moscowTldSetsAdminNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'Admin Number' => 'ADM-MSK-12345'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ADM-MSK-12345', $order['Order']['Domain']['AdminContact']['OrganisationNumber']);
    }

    #[Test]
    public function moscowTldSetsTechType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'Tech Type' => 'TECH'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('TECH', $order['Order']['Domain']['TechContact']['Type']);
    }

    #[Test]
    public function moscowTldSetsTechDetails(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'Tech Details' => 'Technical contact details'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Technical contact details', $order['Order']['Domain']['TechContact']['Details']);
    }

    #[Test]
    public function moscowTldSetsTechNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'Tech Number' => 'TECH-MSK-67890'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('TECH-MSK-67890', $order['Order']['Domain']['TechContact']['OrganisationNumber']);
    }

    #[Test]
    public function moscowTldSetsAllFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'moscow',
            'domainname' => 'example.moscow',
            'additionalfields' => [
                'VAT Number' => 'RU9999999999',
                'Registrant Number' => 'OGRN9999999999999',
                'Registrant Details' => 'Moscow enterprise',
                'Admin Type' => 'ADMIN',
                'Admin Details' => 'Admin contact info',
                'Admin Number' => 'ADM-MSK-99999',
                'Tech Type' => 'TECH',
                'Tech Details' => 'Tech contact info',
                'Tech Number' => 'TECH-MSK-88888'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('RU9999999999', $order['Order']['Domain']['Registrant']['VatNumber']);
        $this->assertEquals('OGRN9999999999999', $order['Order']['Domain']['Registrant']['RegistrantNumber']);
        $this->assertEquals('Moscow enterprise', $order['Order']['Domain']['Registrant']['Details']);
        $this->assertEquals('ADMIN', $order['Order']['Domain']['AdminContact']['Type']);
        $this->assertEquals('Admin contact info', $order['Order']['Domain']['AdminContact']['Details']);
        $this->assertEquals('ADM-MSK-99999', $order['Order']['Domain']['AdminContact']['OrganisationNumber']);
        $this->assertEquals('TECH', $order['Order']['Domain']['TechContact']['Type']);
        $this->assertEquals('Tech contact info', $order['Order']['Domain']['TechContact']['Details']);
        $this->assertEquals('TECH-MSK-88888', $order['Order']['Domain']['TechContact']['OrganisationNumber']);
    }

    // =========================================================================
    // PT TLD Tests
    // Required: Registrant.VAT, Registrant.Nr., Admin.Nr., Tech.Nr.
    // =========================================================================

    #[Test]
    public function ptTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pt',
            'domainname' => 'example.pt'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.pt', $order['Order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['Order']['Type']);
    }

    #[Test]
    public function ptTldCreatesCorrectClass(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pt',
            'domainname' => 'example.pt'
        ]);

        $request = Request::create($params);

        $this->assertInstanceOf(\ascio\pt::class, $request);
    }

    #[Test]
    public function ptTldSetsRegistrantVAT(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pt',
            'domainname' => 'example.pt',
            'additionalfields' => [
                'VAT Number' => 'PT123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('PT123456789', $order['Order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function ptTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pt',
            'domainname' => 'example.pt',
            'additionalfields' => [
                'Registrant Number' => 'NIF123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('NIF123456789', $order['Order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function ptTldSetsAdminNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pt',
            'domainname' => 'example.pt',
            'additionalfields' => [
                'Admin Number' => 'ADM-PT-12345'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ADM-PT-12345', $order['Order']['Domain']['AdminContact']['OrganisationNumber']);
    }

    #[Test]
    public function ptTldSetsTechNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pt',
            'domainname' => 'example.pt',
            'additionalfields' => [
                'Tech Number' => 'TECH-PT-67890'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('TECH-PT-67890', $order['Order']['Domain']['TechContact']['OrganisationNumber']);
    }

    #[Test]
    public function ptTldSetsAllFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pt',
            'domainname' => 'example.pt',
            'additionalfields' => [
                'VAT Number' => 'PT999999999',
                'Registrant Number' => 'NIF999999999',
                'Admin Number' => 'ADM-PT-99999',
                'Tech Number' => 'TECH-PT-88888'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('PT999999999', $order['Order']['Domain']['Registrant']['VatNumber']);
        $this->assertEquals('NIF999999999', $order['Order']['Domain']['Registrant']['RegistrantNumber']);
        $this->assertEquals('ADM-PT-99999', $order['Order']['Domain']['AdminContact']['OrganisationNumber']);
        $this->assertEquals('TECH-PT-88888', $order['Order']['Domain']['TechContact']['OrganisationNumber']);
    }

    #[Test]
    public function ptTldHandlesComPtSubTld(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'com.pt',
            'domainname' => 'example.com.pt',
            'additionalfields' => [
                'VAT Number' => 'PT777777777',
                'Registrant Number' => 'NIF777777777',
                'Admin Number' => 'ADM-PT-77777',
                'Tech Number' => 'TECH-PT-66666'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.com.pt', $order['Order']['Domain']['DomainName']);
        $this->assertEquals('PT777777777', $order['Order']['Domain']['Registrant']['VatNumber']);
        $this->assertEquals('NIF777777777', $order['Order']['Domain']['Registrant']['RegistrantNumber']);
        $this->assertEquals('ADM-PT-77777', $order['Order']['Domain']['AdminContact']['OrganisationNumber']);
        $this->assertEquals('TECH-PT-66666', $order['Order']['Domain']['TechContact']['OrganisationNumber']);
    }

    // =========================================================================
    // Cross-TLD Integration Tests for Complex TLDs
    // =========================================================================

    #[Test]
    #[DataProvider('complexTldListProvider')]
    public function complexTldCreatesCorrectDomainName(string $tld): void
    {
        $domainName = "example.{$tld}";
        $params = array_merge($this->defaultParams, [
            'tld' => $tld,
            'domainname' => $domainName
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals($domainName, $order['Order']['Domain']['DomainName']);
    }

    public static function complexTldListProvider(): array
    {
        return [
            'amsterdam' => ['amsterdam'],
            'cat' => ['cat'],
            'ee' => ['ee'],
            'moscow' => ['moscow'],
            'pt' => ['pt'],
        ];
    }

    #[Test]
    public function complexTldsCreateCorrectRequestClass(): void
    {
        $tlds = [
            'amsterdam' => \ascio\amsterdam::class,
            'cat' => \ascio\cat::class,
            'ee' => \ascio\ee::class,
            'moscow' => \ascio\moscow::class,
            'pt' => \ascio\pt::class,
        ];

        foreach ($tlds as $tld => $expectedClass) {
            $params = array_merge($this->defaultParams, [
                'tld' => $tld,
                'domainname' => "example.{$tld}"
            ]);

            $request = Request::create($params);

            $this->assertInstanceOf(
                $expectedClass,
                $request,
                "TLD .{$tld} should create instance of {$expectedClass}"
            );
        }
    }

    #[Test]
    public function complexTldsProduceValidOrders(): void
    {
        $tldConfigs = [
            'amsterdam' => [
                'Registrant Type' => 'ORGANIZATION',
                'Registrant Number' => 'NL123',
                'Admin Type' => 'LEGAL',
                'Tech Type' => 'TECH'
            ],
            'cat' => [
                'Domain Purpose' => 'P1',
                'Auth Code' => 'CAT-AUTH',
                'Registrant Details' => 'Catalan entity',
                'Trademark Name' => 'Brand'
            ],
            'ee' => [
                'Registrant Type' => 'PRIV',
                'Registrant Number' => 'EE123',
                'Admin Type' => 'PRIV',
                'Admin Number' => 'ADM123',
                'Tech Type' => 'ORG',
                'Tech Number' => 'TECH123'
            ],
            'moscow' => [
                'VAT Number' => 'RU123',
                'Registrant Number' => 'OGRN123',
                'Registrant Details' => 'Moscow entity',
                'Admin Type' => 'ADMIN',
                'Admin Details' => 'Admin info',
                'Admin Number' => 'ADM123',
                'Tech Type' => 'TECH',
                'Tech Details' => 'Tech info',
                'Tech Number' => 'TECH123'
            ],
            'pt' => [
                'VAT Number' => 'PT123',
                'Registrant Number' => 'NIF123',
                'Admin Number' => 'ADM123',
                'Tech Number' => 'TECH123'
            ],
        ];

        foreach ($tldConfigs as $tld => $additionalfields) {
            $params = array_merge($this->defaultParams, [
                'tld' => $tld,
                'domainname' => "example.{$tld}",
                'additionalfields' => $additionalfields
            ]);

            $request = Request::create($params);
            $order = $request->mapToOrder($params, 'Register_Domain');

            $this->assertEquals("example.{$tld}", $order['Order']['Domain']['DomainName']);
            $this->assertEquals('Register_Domain', $order['Order']['Type']);
            $this->assertArrayHasKey('Registrant', $order['Order']['Domain']);
            $this->assertArrayHasKey('AdminContact', $order['Order']['Domain']);
        }
    }
}
