<?php

namespace Ascio\Tests\Unit\Tlds;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\v2\domains\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Unit tests for medium complexity TLD plugins (2-3 fields)
 *
 * Tests TLDs: aero, az, br, ec, et, hk, hr, hu, ie, nyc, rs, si, su, tel, us
 */
class MediumTldsTest extends TestCase
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
    // AERO TLD Tests
    // =========================================================================

    #[Test]
    public function aeroTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'aero',
            'domainname' => 'example.aero'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.aero', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function aeroTldSetsAuthInfoFromAdditionalFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'aero',
            'domainname' => 'example.aero',
            'additionalfields' => [
                'Auth Code' => 'AERO-AUTH-12345'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('AERO-AUTH-12345', $order['order']['Domain']['AuthInfo']);
    }

    #[Test]
    public function aeroTldHandlesEmptyAuthInfo(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'aero',
            'domainname' => 'example.aero',
            'additionalfields' => []
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.aero', $order['order']['Domain']['DomainName']);
    }

    // =========================================================================
    // AZ TLD Tests
    // =========================================================================

    #[Test]
    public function azTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'az',
            'domainname' => 'example.az'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.az', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function azTldSetsRegistrantTypeFromAdditionalFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'az',
            'domainname' => 'example.az',
            'additionalfields' => [
                'Registrant Type' => 'Organization'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Organization', $order['order']['Domain']['Registrant']['RegistrantType']);
    }

    #[Test]
    public function azTldSetsRegistrantVATFromAdditionalFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'az',
            'domainname' => 'example.az',
            'additionalfields' => [
                'VAT Number' => 'AZ123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('AZ123456789', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function azTldSetsRegistrantVATFromAlternativeField(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'az',
            'domainname' => 'example.az',
            'additionalfields' => [
                'Registrant VAT' => 'AZ987654321'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('AZ987654321', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function azTldSetsBothRegistrantTypeAndVAT(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'az',
            'domainname' => 'example.az',
            'additionalfields' => [
                'Registrant Type' => 'Individual',
                'VAT Number' => 'AZ111222333'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Individual', $order['order']['Domain']['Registrant']['RegistrantType']);
        $this->assertEquals('AZ111222333', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    // =========================================================================
    // BR TLD Tests
    // =========================================================================

    #[Test]
    public function brTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'br',
            'domainname' => 'example.br'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.br', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function brTldSetsRegistrantVATFromAdditionalFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'br',
            'domainname' => 'example.br',
            'additionalfields' => [
                'VAT Number' => 'BR12345678901234'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('BR12345678901234', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function brTldSetsRegistrantVATFromAlternativeField(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'br',
            'domainname' => 'example.br',
            'additionalfields' => [
                'Registrant VAT' => 'BR98765432109876'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('BR98765432109876', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function brTldHandlesComBrSubTld(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'com.br',
            'domainname' => 'example.com.br',
            'additionalfields' => [
                'VAT Number' => 'BR55555555555555'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.com.br', $order['order']['Domain']['DomainName']);
        $this->assertEquals('BR55555555555555', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    // =========================================================================
    // EC TLD Tests
    // =========================================================================

    #[Test]
    public function ecTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ec',
            'domainname' => 'example.ec'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.ec', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function ecTldSetsRegistrantVATFromAdditionalFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ec',
            'domainname' => 'example.ec',
            'additionalfields' => [
                'VAT Number' => 'EC1234567890'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('EC1234567890', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function ecTldSetsRegistrantNumberFromAdditionalFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ec',
            'domainname' => 'example.ec',
            'additionalfields' => [
                'Registrant Number' => 'ECREGNUM12345'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ECREGNUM12345', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function ecTldSetsBothVATAndRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ec',
            'domainname' => 'example.ec',
            'additionalfields' => [
                'VAT Number' => 'EC9999999999',
                'Registrant Number' => 'ECNUM98765'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('EC9999999999', $order['order']['Domain']['Registrant']['VatNumber']);
        $this->assertEquals('ECNUM98765', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function ecTldHandlesComEcSubTld(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'com.ec',
            'domainname' => 'example.com.ec',
            'additionalfields' => [
                'VAT Number' => 'EC7777777777',
                'Registrant Number' => 'ECNUM77777'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.com.ec', $order['order']['Domain']['DomainName']);
        $this->assertEquals('EC7777777777', $order['order']['Domain']['Registrant']['VatNumber']);
        $this->assertEquals('ECNUM77777', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    // =========================================================================
    // ET TLD Tests
    // =========================================================================

    #[Test]
    public function etTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'et',
            'domainname' => 'example.et'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.et', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function etTldSetsRegistrantVATFromAdditionalFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'et',
            'domainname' => 'example.et',
            'additionalfields' => [
                'VAT Number' => 'ET1234567890'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ET1234567890', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function etTldSetsRegistrantVATFromAlternativeField(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'et',
            'domainname' => 'example.et',
            'additionalfields' => [
                'Registrant VAT' => 'ET0987654321'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ET0987654321', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function etTldHandlesComEtSubTld(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'com.et',
            'domainname' => 'example.com.et',
            'additionalfields' => [
                'VAT Number' => 'ET5555555555'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.com.et', $order['order']['Domain']['DomainName']);
        $this->assertEquals('ET5555555555', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    // =========================================================================
    // HK TLD Tests (.hk requires Registrant Type and Registrant Number)
    // =========================================================================

    #[Test]
    public function hkTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hk',
            'domainname' => 'example.hk'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.hk', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function hkTldMapsRegistrantType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hk',
            'domainname' => 'example.hk',
            'additionalfields' => [
                'Registrant Type' => 'ind'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ind', $order['order']['Domain']['Registrant']['RegistrantType']);
    }

    #[Test]
    public function hkTldMapsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hk',
            'domainname' => 'example.hk',
            'additionalfields' => [
                'Registrant Number' => 'A1234567'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('A1234567', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function hkTldMapsAllFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hk',
            'domainname' => 'example.hk',
            'additionalfields' => [
                'Registrant Type' => 'org',
                'Registrant Number' => 'BR12345678'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('org', $order['order']['Domain']['Registrant']['RegistrantType']);
        $this->assertEquals('BR12345678', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    // =========================================================================
    // HR TLD Tests (.hr requires Registrant VAT)
    // =========================================================================

    #[Test]
    public function hrTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hr',
            'domainname' => 'example.hr'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.hr', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function hrTldMapsVatNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hr',
            'domainname' => 'example.hr',
            'additionalfields' => [
                'VAT Number' => 'HR12345678901'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('HR12345678901', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    // =========================================================================
    // HU TLD Tests (.hu requires Registrant VAT, Registrant Number, TM Name)
    // =========================================================================

    #[Test]
    public function huTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hu',
            'domainname' => 'example.hu'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.hu', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function huTldMapsVatNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hu',
            'domainname' => 'example.hu',
            'additionalfields' => [
                'VAT Number' => 'HU12345678'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('HU12345678', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function huTldMapsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hu',
            'domainname' => 'example.hu',
            'additionalfields' => [
                'Registrant Number' => '12345678-1-23'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('12345678-1-23', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function huTldMapsTrademarkName(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hu',
            'domainname' => 'example.hu',
            'additionalfields' => [
                'Trademark Name' => 'My Trademark'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('My Trademark', $order['order']['Domain']['Trademark']['Name']);
    }

    #[Test]
    public function huTldMapsAllFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'hu',
            'domainname' => 'example.hu',
            'additionalfields' => [
                'VAT Number' => 'HU12345678',
                'Registrant Number' => '12345678-1-23',
                'Trademark Name' => 'My Trademark'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('HU12345678', $order['order']['Domain']['Registrant']['VatNumber']);
        $this->assertEquals('12345678-1-23', $order['order']['Domain']['Registrant']['RegistrantNumber']);
        $this->assertEquals('My Trademark', $order['order']['Domain']['Trademark']['Name']);
    }

    // =========================================================================
    // IE TLD Tests (.ie requires Registrant Type, Registrant Number, TM Name)
    // =========================================================================

    #[Test]
    public function ieTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ie',
            'domainname' => 'example.ie'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.ie', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function ieTldMapsRegistrantType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ie',
            'domainname' => 'example.ie',
            'additionalfields' => [
                'Registrant Type' => 'CRO'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('CRO', $order['order']['Domain']['Registrant']['RegistrantType']);
    }

    #[Test]
    public function ieTldMapsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ie',
            'domainname' => 'example.ie',
            'additionalfields' => [
                'Registrant Number' => '123456'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('123456', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function ieTldMapsTrademarkName(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ie',
            'domainname' => 'example.ie',
            'additionalfields' => [
                'Trademark Name' => 'Irish Trademark'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Irish Trademark', $order['order']['Domain']['Trademark']['Name']);
    }

    #[Test]
    public function ieTldMapsAllFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ie',
            'domainname' => 'example.ie',
            'additionalfields' => [
                'Registrant Type' => 'CRO',
                'Registrant Number' => '123456',
                'Trademark Name' => 'Irish Trademark'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('CRO', $order['order']['Domain']['Registrant']['RegistrantType']);
        $this->assertEquals('123456', $order['order']['Domain']['Registrant']['RegistrantNumber']);
        $this->assertEquals('Irish Trademark', $order['order']['Domain']['Trademark']['Name']);
    }

    // =========================================================================
    // NYC TLD Tests (.nyc requires Domain Purpose)
    // =========================================================================

    #[Test]
    public function nycTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'nyc',
            'domainname' => 'example.nyc'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.nyc', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function nycTldMapsDomainPurpose(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'nyc',
            'domainname' => 'example.nyc',
            'additionalfields' => [
                'Domain Purpose' => 'P1'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('P1', $order['order']['Domain']['DomainPurpose']);
    }

    #[Test]
    public function nycTldMapsResidentialPurpose(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'nyc',
            'domainname' => 'example.nyc',
            'additionalfields' => [
                'Domain Purpose' => 'P2'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('P2', $order['order']['Domain']['DomainPurpose']);
    }

    // =========================================================================
    // Generic TLD format validation
    // =========================================================================

    #[Test]
    #[DataProvider('mediumTldListProvider')]
    public function mediumTldCreatesCorrectDomainName(string $tld): void
    {
        $domainName = "example.{$tld}";
        $params = array_merge($this->defaultParams, [
            'tld' => $tld,
            'domainname' => $domainName
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals($domainName, $order['order']['Domain']['DomainName']);
    }

    public static function mediumTldListProvider(): array
    {
        return [
            'aero' => ['aero'],
            'az' => ['az'],
            'br' => ['br'],
            'ec' => ['ec'],
            'et' => ['et'],
            'hk' => ['hk'],
            'hr' => ['hr'],
            'hu' => ['hu'],
            'ie' => ['ie'],
            'nyc' => ['nyc'],
        ];
    }

    #[Test]
    #[DataProvider('mediumTldWithVatProvider')]
    public function mediumTldWithVatSetsVatNumber(string $tld, string $vatField): void
    {
        $domainName = "example.{$tld}";
        $params = array_merge($this->defaultParams, [
            'tld' => $tld,
            'domainname' => $domainName,
            'additionalfields' => [
                $vatField => 'TEST-VAT-123'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('TEST-VAT-123', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    public static function mediumTldWithVatProvider(): array
    {
        return [
            'az with VAT Number' => ['az', 'VAT Number'],
            'az with Registrant VAT' => ['az', 'Registrant VAT'],
            'br with VAT Number' => ['br', 'VAT Number'],
            'br with Registrant VAT' => ['br', 'Registrant VAT'],
            'ec with VAT Number' => ['ec', 'VAT Number'],
            'ec with Registrant VAT' => ['ec', 'Registrant VAT'],
            'et with VAT Number' => ['et', 'VAT Number'],
            'et with Registrant VAT' => ['et', 'Registrant VAT'],
        ];
    }

    // =========================================================================
    // RS TLD Tests (.rs - Serbia)
    // =========================================================================

    #[Test]
    public function rsTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'rs',
            'domainname' => 'primer.rs',
            'additionalfields' => [
                'Registrant Type' => 'Organization',
                'Registrant Number' => 'RS12345678',
                'Admin Number' => 'ADM987654'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('primer.rs', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function rsTldSetsRegistrantType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'rs',
            'domainname' => 'primer.rs',
            'additionalfields' => [
                'Registrant Type' => 'Organization',
                'Registrant Number' => 'RS12345678',
                'Admin Number' => 'ADM987654'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Organization', $order['order']['Domain']['Registrant']['RegistrantType']);
    }

    #[Test]
    public function rsTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'rs',
            'domainname' => 'primer.rs',
            'additionalfields' => [
                'Registrant Type' => 'Organization',
                'Registrant Number' => 'RS12345678',
                'Admin Number' => 'ADM987654'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('RS12345678', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function rsTldSetsAdminNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'rs',
            'domainname' => 'primer.rs',
            'additionalfields' => [
                'Registrant Type' => 'Organization',
                'Registrant Number' => 'RS12345678',
                'Admin Number' => 'ADM987654'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('ADM987654', $order['order']['Domain']['AdminContact']['OrganisationNumber']);
    }

    #[Test]
    public function rsTldMapToRegistrantReturnsCorrectFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'rs',
            'domainname' => 'primer.rs',
            'additionalfields' => [
                'Registrant Type' => 'Individual',
                'Registrant Number' => 'JMBG1234567890123',
                'Admin Number' => ''
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('Individual', $result['RegistrantType']);
        $this->assertEquals('JMBG1234567890123', $result['RegistrantNumber']);
    }

    // =========================================================================
    // SI TLD Tests (.si - Slovenia)
    // =========================================================================

    #[Test]
    public function siTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'si',
            'domainname' => 'primer.si',
            'additionalfields' => [
                'VAT Number' => 'SI12345678',
                'Registrant Number' => 'REG123456'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('primer.si', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function siTldSetsRegistrantVAT(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'si',
            'domainname' => 'primer.si',
            'additionalfields' => [
                'VAT Number' => 'SI12345678',
                'Registrant Number' => 'REG123456'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('SI12345678', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function siTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'si',
            'domainname' => 'primer.si',
            'additionalfields' => [
                'VAT Number' => 'SI12345678',
                'Registrant Number' => 'REG123456'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('REG123456', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function siTldMapToRegistrantReturnsCorrectFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'si',
            'domainname' => 'primer.si',
            'additionalfields' => [
                'VAT Number' => 'SI87654321',
                'Registrant Number' => 'EMSO1234567890123'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('SI87654321', $result['VatNumber']);
        $this->assertEquals('EMSO1234567890123', $result['RegistrantNumber']);
    }

    // =========================================================================
    // SU TLD Tests (.su - Soviet Union legacy)
    // =========================================================================

    #[Test]
    public function suTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'su',
            'domainname' => 'primer.su',
            'additionalfields' => [
                'Registrant Type' => 'Organization',
                'VAT Number' => 'RU1234567890',
                'Registrant Number' => 'OGRN1234567890123'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('primer.su', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function suTldSetsRegistrantType(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'su',
            'domainname' => 'primer.su',
            'additionalfields' => [
                'Registrant Type' => 'Organization',
                'VAT Number' => 'RU1234567890',
                'Registrant Number' => 'OGRN1234567890123'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Organization', $order['order']['Domain']['Registrant']['RegistrantType']);
    }

    #[Test]
    public function suTldSetsRegistrantVAT(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'su',
            'domainname' => 'primer.su',
            'additionalfields' => [
                'Registrant Type' => 'Organization',
                'VAT Number' => 'RU1234567890',
                'Registrant Number' => 'OGRN1234567890123'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('RU1234567890', $order['order']['Domain']['Registrant']['VatNumber']);
    }

    #[Test]
    public function suTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'su',
            'domainname' => 'primer.su',
            'additionalfields' => [
                'Registrant Type' => 'Organization',
                'VAT Number' => 'RU1234567890',
                'Registrant Number' => 'OGRN1234567890123'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('OGRN1234567890123', $order['order']['Domain']['Registrant']['RegistrantNumber']);
    }

    #[Test]
    public function suTldMapToRegistrantReturnsCorrectFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'su',
            'domainname' => 'primer.su',
            'additionalfields' => [
                'Registrant Type' => 'Individual',
                'VAT Number' => 'INN123456789012',
                'Registrant Number' => 'PASSPORT1234567890'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('Individual', $result['RegistrantType']);
        $this->assertEquals('INN123456789012', $result['VatNumber']);
        $this->assertEquals('PASSPORT1234567890', $result['RegistrantNumber']);
    }

    // =========================================================================
    // TEL TLD Tests (.tel)
    // =========================================================================

    #[Test]
    public function telTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'tel',
            'domainname' => 'example.tel',
            'additionalfields' => [
                'Registrant Details' => 'Business contact directory'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.tel', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function telTldSetsRegistrantDetails(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'tel',
            'domainname' => 'example.tel',
            'additionalfields' => [
                'Registrant Details' => 'Personal contact information'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('Personal contact information', $order['order']['Domain']['Registrant']['Details']);
    }

    #[Test]
    public function telTldMapToRegistrantReturnsCorrectFields(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'tel',
            'domainname' => 'contact.tel',
            'additionalfields' => [
                'Registrant Details' => 'Company directory service'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('Company directory service', $result['Details']);
    }

    // =========================================================================
    // US TLD Tests (.us - United States)
    // =========================================================================

    #[Test]
    public function usTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'us',
            'domainname' => 'example.us',
            'additionalfields' => [
                'Domain Purpose' => 'P1'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('example.us', $order['order']['Domain']['DomainName']);
        $this->assertEquals('Register_Domain', $order['order']['Type']);
    }

    #[Test]
    public function usTldSetsDomainPurpose(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'us',
            'domainname' => 'example.us',
            'additionalfields' => [
                'Domain Purpose' => 'P1'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('P1', $order['order']['Domain']['DomainPurpose']);
    }

    #[Test]
    public function usTldSetsDomainPurposeP2(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'us',
            'domainname' => 'nonprofit.us',
            'additionalfields' => [
                'Domain Purpose' => 'P2'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('P2', $order['order']['Domain']['DomainPurpose']);
    }

    #[Test]
    public function usTldSetsDomainPurposeP3(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'us',
            'domainname' => 'personal.us',
            'additionalfields' => [
                'Domain Purpose' => 'P3'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('P3', $order['order']['Domain']['DomainPurpose']);
    }

    #[Test]
    public function usTldMapToOrderSetsDomainPurpose(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'us',
            'domainname' => 'business.us',
            'additionalfields' => [
                'Domain Purpose' => 'P1'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertArrayHasKey('DomainPurpose', $order['order']['Domain']);
        $this->assertEquals('P1', $order['order']['Domain']['DomainPurpose']);
    }

    // =========================================================================
    // Cross-TLD Integration Tests for Batch 3
    // =========================================================================

    #[Test]
    public function batch3TldsCreateCorrectRequestClass(): void
    {
        $tlds = [
            'rs' => \ascio\v2\domains\rs::class,
            'si' => \ascio\v2\domains\si::class,
            'su' => \ascio\v2\domains\su::class,
            'tel' => \ascio\v2\domains\tel::class,
            'us' => \ascio\v2\domains\us::class,
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
    public function batch3TldsProduceValidOrders(): void
    {
        $tldConfigs = [
            'rs' => ['Registrant Type' => 'Organization', 'Registrant Number' => 'RS123', 'Admin Number' => 'ADM123'],
            'si' => ['VAT Number' => 'SI12345678', 'Registrant Number' => 'REG123'],
            'su' => ['Registrant Type' => 'Organization', 'VAT Number' => 'RU123', 'Registrant Number' => 'OGRN123'],
            'tel' => ['Registrant Details' => 'Contact info'],
            'us' => ['Domain Purpose' => 'P1'],
        ];

        foreach ($tldConfigs as $tld => $additionalfields) {
            $params = array_merge($this->defaultParams, [
                'tld' => $tld,
                'domainname' => "example.{$tld}",
                'additionalfields' => $additionalfields
            ]);

            $request = Request::create($params);
            $order = $request->mapToOrder($params, 'Register_Domain');

            $this->assertEquals("example.{$tld}", $order['order']['Domain']['DomainName']);
            $this->assertEquals('Register_Domain', $order['order']['Type']);
            $this->assertArrayHasKey('Registrant', $order['order']['Domain']);
            $this->assertArrayHasKey('AdminContact', $order['order']['Domain']);
        }
    }
}
