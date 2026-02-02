<?php

namespace Ascio\Tests\Unit\Tlds;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Unit tests for simple TLD plugins (minimal customization)
 *
 * Tests TLDs: at, ch, fi, fr, pl, pro, ru, xxx, asia, jobs, nu, se, es, com.au
 */
class SimpleTldsTest extends TestCase
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
    // AT TLD Tests
    // =========================================================================

    #[Test]
    public function atTldSetsZeroRegperiodForOneYearTransfer(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'at',
            'domainname' => 'example.at',
            'regperiod' => 1
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Transfer');

        $this->assertEquals(0, $order['Order']['Domain']['RenewPeriod']);
    }

    #[Test]
    public function atTldKeepsRegperiodForTwoYearTransfer(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'at',
            'domainname' => 'example.at',
            'regperiod' => 2
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Transfer');

        $this->assertEquals(2, $order['Order']['Domain']['RenewPeriod']);
    }

    #[Test]
    public function atTldKeepsRegperiodForRegistration(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'at',
            'domainname' => 'example.at',
            'regperiod' => 1
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals(1, $order['Order']['Domain']['RenewPeriod']);
    }

    // =========================================================================
    // CH TLD Tests
    // =========================================================================

    #[Test]
    public function chTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ch',
            'domainname' => 'example.ch'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.ch', $order['Order']['Domain']['Name']);
        $this->assertEquals('Register', $order['Order']['Type']);
    }

    // =========================================================================
    // FI TLD Tests
    // =========================================================================

    #[Test]
    public function fiTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'fi',
            'domainname' => 'example.fi'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.fi', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // FR TLD Tests
    // =========================================================================

    #[Test]
    public function frTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'fr',
            'domainname' => 'example.fr'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.fr', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // PL TLD Tests
    // =========================================================================

    #[Test]
    public function plTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pl',
            'domainname' => 'example.pl'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.pl', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // PRO TLD Tests
    // =========================================================================

    #[Test]
    public function proTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'pro',
            'domainname' => 'example.pro'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.pro', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // RU TLD Tests
    // =========================================================================

    #[Test]
    public function ruTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ru',
            'domainname' => 'example.ru'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.ru', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // XXX TLD Tests
    // =========================================================================

    #[Test]
    public function xxxTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'xxx',
            'domainname' => 'example.xxx'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.xxx', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // ASIA TLD Tests
    // =========================================================================

    #[Test]
    public function asiaTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'asia',
            'domainname' => 'example.asia'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.asia', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // JOBS TLD Tests
    // =========================================================================

    #[Test]
    public function jobsTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'jobs',
            'domainname' => 'example.jobs'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.jobs', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // NU TLD Tests
    // =========================================================================

    #[Test]
    public function nuTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'nu',
            'domainname' => 'example.nu'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.nu', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // SE TLD Tests
    // =========================================================================

    #[Test]
    public function seTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'se',
            'domainname' => 'example.se'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.se', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // ES TLD Tests
    // =========================================================================

    #[Test]
    public function esTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'es',
            'domainname' => 'example.es'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.es', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // COM.AU TLD Tests
    // =========================================================================

    #[Test]
    public function comAuTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'com.au',
            'domainname' => 'example.com.au'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.com.au', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // DK TLD Tests
    // =========================================================================

    #[Test]
    public function dkTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'dk',
            'domainname' => 'example.dk'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.dk', $order['Order']['Domain']['Name']);
    }

    // =========================================================================
    // Generic TLD format validation
    // =========================================================================

    #[Test]
    #[DataProvider('tldListProvider')]
    public function tldCreatesCorrectDomainName(string $tld): void
    {
        $domainName = "example.{$tld}";
        $params = array_merge($this->defaultParams, [
            'tld' => $tld,
            'domainname' => $domainName
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals($domainName, $order['Order']['Domain']['Name']);
    }

    public static function tldListProvider(): array
    {
        return [
            'at' => ['at'],
            'ch' => ['ch'],
            'fi' => ['fi'],
            'fr' => ['fr'],
            'pl' => ['pl'],
            'pro' => ['pro'],
            'ru' => ['ru'],
            'xxx' => ['xxx'],
            'asia' => ['asia'],
            'jobs' => ['jobs'],
            'nu' => ['nu'],
            'se' => ['se'],
            'es' => ['es'],
            'dk' => ['dk'],
            'al' => ['al'],
            'ba' => ['ba'],
            'by' => ['by'],
            'cn' => ['cn'],
            'fm' => ['fm'],
            'is' => ['is'],
            'kr' => ['kr'],
            'lotto' => ['lotto'],
            'lv' => ['lv'],
            'mk' => ['mk'],
            'my' => ['my'],
            'nc' => ['nc'],
            'no' => ['no'],
            'rio' => ['rio'],
            'sk' => ['sk'],
            'swiss' => ['swiss'],
            'travel' => ['travel'],
        ];
    }

    // =========================================================================
    // AL TLD Tests (Albania - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function alTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'al',
            'domainname' => 'example.al'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.al', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function alTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'al',
            'domainname' => 'example.al',
            'additionalfields' => [
                'Registrant Number' => 'AL123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('AL123456789', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // BA TLD Tests (Bosnia and Herzegovina - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function baTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ba',
            'domainname' => 'example.ba'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.ba', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function baTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'ba',
            'domainname' => 'example.ba',
            'additionalfields' => [
                'Registrant Number' => 'BA987654321'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('BA987654321', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // BY TLD Tests (Belarus - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function byTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'by',
            'domainname' => 'example.by'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.by', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function byTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'by',
            'domainname' => 'example.by',
            'additionalfields' => [
                'Registrant Number' => 'BY555666777'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('BY555666777', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // CN TLD Tests (China - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function cnTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'cn',
            'domainname' => 'example.cn'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.cn', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function cnTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'cn',
            'domainname' => 'example.cn',
            'additionalfields' => [
                'Registrant Number' => 'CN110101199003076534'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('CN110101199003076534', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // FM TLD Tests (Federated States of Micronesia - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function fmTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'fm',
            'domainname' => 'example.fm'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.fm', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function fmTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'fm',
            'domainname' => 'example.fm',
            'additionalfields' => [
                'Registrant Number' => 'FM12345'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('FM12345', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // IS TLD Tests (Iceland - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function isTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'is',
            'domainname' => 'example.is'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.is', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function isTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'is',
            'domainname' => 'example.is',
            'additionalfields' => [
                'Registrant Number' => 'IS1234567890'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('IS1234567890', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // NO TLD Tests (Norway - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function noTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'no',
            'domainname' => 'example.no'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.no', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function noTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'no',
            'domainname' => 'example.no',
            'additionalfields' => [
                'Registrant Number' => 'NO987654321'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('NO987654321', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // RIO TLD Tests (.rio - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function rioTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'rio',
            'domainname' => 'example.rio'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.rio', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function rioTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'rio',
            'domainname' => 'example.rio',
            'additionalfields' => [
                'Registrant Number' => 'RIO123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('RIO123456789', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // SK TLD Tests (Slovakia - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function skTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'sk',
            'domainname' => 'example.sk'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.sk', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function skTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'sk',
            'domainname' => 'example.sk',
            'additionalfields' => [
                'Registrant Number' => 'SK12345678'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('SK12345678', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // SWISS TLD Tests (.swiss - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function swissTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'swiss',
            'domainname' => 'example.swiss'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.swiss', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function swissTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'swiss',
            'domainname' => 'example.swiss',
            'additionalfields' => [
                'Registrant Number' => 'CHE123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('CHE123456789', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // TRAVEL TLD Tests (.travel - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function travelTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'travel',
            'domainname' => 'example.travel'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.travel', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function travelTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'travel',
            'domainname' => 'example.travel',
            'additionalfields' => [
                'Registrant Number' => 'TRAVEL987654'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('TRAVEL987654', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // KR TLD Tests (South Korea - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function krTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'kr',
            'domainname' => 'example.kr'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.kr', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function krTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'kr',
            'domainname' => 'example.kr',
            'additionalfields' => [
                'Registrant Number' => 'KR123456789012'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('KR123456789012', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // LOTTO TLD Tests (.lotto - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function lottoTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'lotto',
            'domainname' => 'example.lotto'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.lotto', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function lottoTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'lotto',
            'domainname' => 'example.lotto',
            'additionalfields' => [
                'Registrant Number' => 'LOTTO123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('LOTTO123456789', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // LV TLD Tests (Latvia - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function lvTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'lv',
            'domainname' => 'example.lv'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.lv', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function lvTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'lv',
            'domainname' => 'example.lv',
            'additionalfields' => [
                'Registrant Number' => 'LV12345678901'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('LV12345678901', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // MK TLD Tests (North Macedonia - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function mkTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'mk',
            'domainname' => 'example.mk'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.mk', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function mkTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'mk',
            'domainname' => 'example.mk',
            'additionalfields' => [
                'Registrant Number' => 'MK1234567890123'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('MK1234567890123', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // MY TLD Tests (Malaysia - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function myTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'my',
            'domainname' => 'example.my'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.my', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function myTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'my',
            'domainname' => 'example.my',
            'additionalfields' => [
                'Registrant Number' => 'MY890101145678'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('MY890101145678', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }

    // =========================================================================
    // NC TLD Tests (New Caledonia - requires Registrant Number)
    // =========================================================================

    #[Test]
    public function ncTldCreatesValidOrder(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'nc',
            'domainname' => 'example.nc'
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('example.nc', $order['Order']['Domain']['Name']);
    }

    #[Test]
    public function ncTldSetsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'tld' => 'nc',
            'domainname' => 'example.nc',
            'additionalfields' => [
                'Registrant Number' => 'NC123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('NC123456789', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }
}
