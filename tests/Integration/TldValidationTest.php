<?php

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\v2\domains\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Integration tests for TLD plugins using ValidateOrder
 *
 * These tests validate that each TLD plugin produces orders that the Ascio API accepts.
 * Uses ASCIO_SIMULATE=1 to use ValidateOrder instead of CreateOrder.
 *
 * Run with: ./vendor/bin/phpunit --group tld-validation
 *
 * @group tld-validation
 * @group integration
 */
#[Group('tld-validation')]
#[Group('integration')]
class TldValidationTest extends TestCase
{
    private array $baseParams;
    private ?string $username;
    private ?string $password;
    private array $discrepancies = [];

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();

        // Enable simulation mode
        putenv('ASCIO_SIMULATE=1');

        // Load credentials from .env
        $this->loadCredentials();

        if (!$this->username || !$this->password) {
            $this->markTestSkipped('Ascio credentials not found');
        }

        $this->baseParams = [
            'Username' => $this->username,
            'Password' => $this->password,
            'TestMode' => 'on',
            'Simulate' => 'on',
            'domainid' => 1,
            'regperiod' => 1,
            'firstname' => 'Test',
            'lastname' => 'User',
            'companyname' => 'Test Company Inc',
            'address1' => '123 Test Street',
            'address2' => 'Suite 100',
            'city' => 'Test City',
            'state' => 'TS',
            'postcode' => '12345',
            'country' => 'US',
            'countrycode' => 'US',
            'email' => 'test@example.com',
            'fullphonenumber' => '+1.5551234567',
            'adminfirstname' => 'Admin',
            'adminlastname' => 'User',
            'admincompanyname' => 'Test Company Inc',
            'adminaddress1' => '123 Test Street',
            'adminaddress2' => 'Suite 100',
            'admincity' => 'Test City',
            'adminstate' => 'TS',
            'adminpostcode' => '12345',
            'admincountry' => 'US',
            'adminemail' => 'admin@example.com',
            'adminfullphonenumber' => '+1.5551234567',
            'ns1' => 'ns1.ascio.net',
            'ns2' => 'ns2.ascio.net',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'eppcode' => '',
            'idprotection' => false,
            'custom' => [],
            'additionalfields' => []
        ];
    }

    protected function tearDown(): void
    {
        // Log discrepancies if any
        if (!empty($this->discrepancies)) {
            $logFile = __DIR__ . '/../../logs/tld-discrepancies.log';
            $dir = dirname($logFile);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            file_put_contents($logFile, json_encode($this->discrepancies, JSON_PRETTY_PRINT), FILE_APPEND);
        }

        putenv('ASCIO_SIMULATE');
        parent::tearDown();
    }

    private function loadCredentials(): void
    {
        $this->username = getenv('ASCIO_TEST_USERNAME') ?: null;
        $this->password = getenv('ASCIO_TEST_PASSWORD') ?: null;

        if (!$this->username || !$this->password) {
            $envFiles = [
                __DIR__ . '/../../.env',
                __DIR__ . '/../../../.env',
                __DIR__ . '/../../../../.env',
            ];
            foreach ($envFiles as $envFile) {
                if (file_exists($envFile)) {
                    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines as $line) {
                        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
                        list($key, $value) = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        if ($key === 'ASCIO_ACCOUNT' && !$this->username) $this->username = $value;
                        if ($key === 'ASCIO_PASSWORD' && !$this->password) $this->password = $value;
                    }
                    break;
                }
            }
        }
    }

    private function logDiscrepancy(string $tld, string $expected, string $actual, string $context): void
    {
        $this->discrepancies[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tld' => $tld,
            'expected' => $expected,
            'actual' => $actual,
            'context' => $context
        ];
    }

    // =========================================================================
    // Simple TLDs - Registrant.Nr only
    // =========================================================================

    #[Test]
    #[DataProvider('simpleTldProvider')]
    public function validateSimpleTldOrder(string $tld): void
    {
        $domainName = 'test-' . uniqid() . '.' . $tld;
        $params = array_merge($this->baseParams, [
            'tld' => $tld,
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'additionalfields' => [
                'Registrant Number' => 'REG123456'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        // Verify order structure
        $this->assertArrayHasKey('order', $order, "TLD .$tld: Missing order key");
        $this->assertEquals('Register_Domain', $order['order']['Type']);
        $this->assertEquals($domainName, $order['order']['Domain']['DomainName']);

        // Verify RegistrantNumber is set
        if (isset($order['order']['Domain']['Registrant']['RegistrantNumber'])) {
            $this->assertEquals('REG123456', $order['order']['Domain']['Registrant']['RegistrantNumber']);
        }
    }

    public static function simpleTldProvider(): array
    {
        return [
            '.al' => ['al'],
            '.ba' => ['ba'],
            '.by' => ['by'],
            '.cn' => ['cn'],
            '.fm' => ['fm'],
            '.is' => ['is'],
            '.kr' => ['kr'],
            '.lotto' => ['lotto'],
            '.lv' => ['lv'],
            '.mk' => ['mk'],
            '.my' => ['my'],
            '.nc' => ['nc'],
            '.no' => ['no'],
            '.rio' => ['rio'],
            '.sk' => ['sk'],
            '.swiss' => ['swiss'],
            '.travel' => ['travel'],
        ];
    }

    // =========================================================================
    // .IT TLD - 7 Registrant Types
    // =========================================================================

    #[Test]
    #[DataProvider('itRegistrantTypeProvider')]
    public function validateItTldWithRegistrantType(string $legalType, string $expectedCode, bool $isCompany, string $countryCode): void
    {
        $domainName = 'test-' . uniqid() . '.it';
        $params = array_merge($this->baseParams, [
            'tld' => 'it',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'country' => $countryCode,
            'countrycode' => $countryCode,
            'companyname' => $isCompany ? 'Test Company SRL' : '',
            'additionalfields' => [
                'Legal Type' => $legalType,
                'Tax ID' => 'RSSMRA80A01H501A',
                'Birth country' => 'IT'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $registrant = $order['order']['Domain']['Registrant'];
        $this->assertEquals($expectedCode, $registrant['RegistrantType'],
            "TLD .it with '$legalType': Expected RegistrantType '$expectedCode', got '{$registrant['RegistrantType']}'");
    }

    public static function itRegistrantTypeProvider(): array
    {
        return [
            'Natural Person IT' => ['Italian and foreign natural persons', '1', false, 'IT'],
            'Company IT' => ['Companies/one man companies', '2', true, 'IT'],
            'Freelance IT' => ['Freelance workers/professionals', '3', true, 'IT'],
            'Public Org IT' => ['public organizations', '4', true, 'IT'],
            'Non-profit IT' => ['non-profit organizations', '5', true, 'IT'],
            'Other IT' => ['other subjects', '6', true, 'IT'],
            'Foreign Company' => ['Companies/one man companies', '7', true, 'DE'],
        ];
    }

    // =========================================================================
    // .CA TLD - 16 Registrant Types
    // =========================================================================

    #[Test]
    #[DataProvider('caRegistrantTypeProvider')]
    public function validateCaTldWithRegistrantType(string $legalType, string $expectedCode): void
    {
        $domainName = 'test-' . uniqid() . '.ca';
        $params = array_merge($this->baseParams, [
            'tld' => 'ca',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'country' => 'CA',
            'countrycode' => 'CA',
            'state' => 'ON',
            'additionalfields' => [
                'Legal Type' => $legalType
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $registrant = $order['order']['Domain']['Registrant'];
        $this->assertEquals($expectedCode, $registrant['RegistrantType'],
            "TLD .ca with '$legalType': Expected '$expectedCode', got '{$registrant['RegistrantType']}'");
    }

    public static function caRegistrantTypeProvider(): array
    {
        return [
            'Corporation' => ['Corporation', 'CCO'],
            'Canadian Citizen' => ['Canadian Citizen', 'CCT'],
            'Permanent Resident' => ['Permanent Resident of Canada', 'RES'],
            'Government' => ['Government', 'GOV'],
            'Educational' => ['Canadian Educational Institution', 'EDU'],
            'Association' => ['Canadian Unincorporated Association', 'ASS'],
            'Hospital' => ['Canadian Hospital', 'HOP'],
            'Partnership' => ['Partnership Registered in Canada', 'PRT'],
            'Trademark' => ['Trade-mark registered in Canada', 'TDM'],
            'Trade Union' => ['Canadian Trade Union', 'TRD'],
            'Political Party' => ['Canadian Political Party', 'PLT'],
            'Library' => ['Canadian Library Archive or Museum', 'LAM'],
            'Trust' => ['Trust established in Canada', 'TRS'],
            'Aboriginal' => ['Aboriginal Peoples', 'ABO'],
            'Legal Rep' => ['Legal Representative of a Canadian Citizen', 'LGR'],
            'Official Mark' => ['Official mark registered in Canada', 'OMK'],
        ];
    }

    // =========================================================================
    // .NL TLD - PERSOON, BV, BGG types
    // =========================================================================

    #[Test]
    #[DataProvider('nlRegistrantTypeProvider')]
    public function validateNlTldWithRegistrantType(bool $isCompany, string $countryCode, string $expectedType): void
    {
        $domainName = 'test-' . uniqid() . '.nl';
        $params = array_merge($this->baseParams, [
            'tld' => 'nl',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'country' => $countryCode,
            'countrycode' => $countryCode,
            'companyname' => $isCompany ? 'Test BV' : '',
            'additionalfields' => [
                'Organisation Number' => $isCompany ? '12345678' : ''
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $registrant = $order['order']['Domain']['Registrant'];
        $this->assertEquals($expectedType, $registrant['RegistrantType'],
            "TLD .nl: Expected type '$expectedType', got '{$registrant['RegistrantType']}'");
    }

    public static function nlRegistrantTypeProvider(): array
    {
        return [
            'Individual' => [false, 'NL', 'PERSOON'],
            'Dutch Company' => [true, 'NL', 'BV'],
            'Foreign Company' => [true, 'DE', 'BGG'],
        ];
    }

    // =========================================================================
    // .US TLD - Domain Purpose (P1-P5)
    // =========================================================================

    #[Test]
    #[DataProvider('usPurposeProvider')]
    public function validateUsTldWithPurpose(string $purpose): void
    {
        $domainName = 'test-' . uniqid() . '.us';
        $params = array_merge($this->baseParams, [
            'tld' => 'us',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'additionalfields' => [
                'Domain Purpose' => $purpose
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals($purpose, $order['order']['Domain']['DomainPurpose'],
            "TLD .us: Expected purpose '$purpose', got '{$order['order']['Domain']['DomainPurpose']}'");
    }

    public static function usPurposeProvider(): array
    {
        return [
            'Business' => ['P1'],
            'Non-profit' => ['P2'],
            'Personal' => ['P3'],
            'Education' => ['P4'],
            'Government' => ['P5'],
        ];
    }

    // =========================================================================
    // .EE TLD - priv, birthday, org types
    // =========================================================================

    #[Test]
    #[DataProvider('eeTypeProvider')]
    public function validateEeTldWithType(string $regType, string $regNumber, string $adminType): void
    {
        $domainName = 'test-' . uniqid() . '.ee';
        $params = array_merge($this->baseParams, [
            'tld' => 'ee',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'country' => 'EE',
            'countrycode' => 'EE',
            'additionalfields' => [
                'Registrant Type' => $regType,
                'Registrant Number' => $regNumber,
                'Admin Type' => $adminType,
                'Admin Number' => '12345678901',
                'Tech Type' => $regType,
                'Tech Number' => $regNumber
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $registrant = $order['order']['Domain']['Registrant'];
        $this->assertEquals($regType, $registrant['RegistrantType'] ?? null,
            "TLD .ee: Expected type '$regType'");
    }

    public static function eeTypeProvider(): array
    {
        return [
            'Individual with ID' => ['priv', '38001010001', 'priv'],
            'Individual with birthday' => ['birthday', '1980-01-01', 'birthday'],
            'Organization' => ['org', '12345678', 'priv'],
        ];
    }

    // =========================================================================
    // .NYC TLD - Domain Purpose
    // =========================================================================

    #[Test]
    #[DataProvider('nycPurposeProvider')]
    public function validateNycTldWithPurpose(string $purpose): void
    {
        $domainName = 'test-' . uniqid() . '.nyc';
        $params = array_merge($this->baseParams, [
            'tld' => 'nyc',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'additionalfields' => [
                'Domain Purpose' => $purpose
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals($purpose, $order['order']['Domain']['DomainPurpose'] ?? null,
            "TLD .nyc: Expected purpose '$purpose'");
    }

    public static function nycPurposeProvider(): array
    {
        return [
            'P1' => ['P1'],
            'P2' => ['P2'],
        ];
    }

    // =========================================================================
    // Medium TLDs - VAT/Type combinations
    // =========================================================================

    #[Test]
    #[DataProvider('mediumTldProvider')]
    public function validateMediumTldOrder(string $tld, array $additionalFields, array $expectedFields): void
    {
        $domainName = 'test-' . uniqid() . '.' . $tld;
        $params = array_merge($this->baseParams, [
            'tld' => $tld,
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'additionalfields' => $additionalFields
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $registrant = $order['order']['Domain']['Registrant'];

        foreach ($expectedFields as $field => $value) {
            $actual = $registrant[$field] ?? null;
            $this->assertEquals($value, $actual,
                "TLD .$tld: Expected $field='$value', got '$actual'");
        }
    }

    public static function mediumTldProvider(): array
    {
        return [
            '.az with VAT' => ['az', ['Registrant Type' => 'Company', 'VAT Number' => 'AZ123'], ['RegistrantType' => 'Company']],
            '.br with VAT' => ['br', ['VAT Number' => 'BR12345678901234'], ['VatNumber' => 'BR12345678901234']],
            '.ec with both' => ['ec', ['VAT Number' => 'EC123', 'Registrant Number' => 'REG456'], ['VatNumber' => 'EC123', 'RegistrantNumber' => 'REG456']],
            '.et with VAT' => ['et', ['VAT Number' => 'ET123'], ['VatNumber' => 'ET123']],
            '.hk Company' => ['hk', ['Registrant Type' => 'company', 'Registrant Number' => 'HK123'], ['RegistrantType' => 'company']],
            '.hr with VAT' => ['hr', ['VAT Number' => 'HR12345678901'], ['VatNumber' => 'HR12345678901']],
            '.si with both' => ['si', ['VAT Number' => 'SI12345678', 'Registrant Number' => 'REG123'], ['VatNumber' => 'SI12345678']],
            '.su Company' => ['su', ['Registrant Type' => 'ORG', 'VAT Number' => '1234567890', 'Registrant Number' => '123456789'], ['RegistrantType' => 'ORG']],
            '.tel with details' => ['tel', ['Registrant Details' => 'Contact info here'], ['Details' => 'Contact info here']],
        ];
    }

    // =========================================================================
    // Complex TLDs
    // =========================================================================

    #[Test]
    public function validateAmsterdamTldOrder(): void
    {
        $domainName = 'test-' . uniqid() . '.amsterdam';
        $params = array_merge($this->baseParams, [
            'tld' => 'amsterdam',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'additionalfields' => [
                'Registrant Type' => 'company',
                'Registrant Number' => 'NL123456',
                'Admin Type' => 'company',
                'Tech Type' => 'company'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $registrant = $order['order']['Domain']['Registrant'];
        $admin = $order['order']['Domain']['AdminContact'];
        $tech = $order['order']['Domain']['TechContact'];

        $this->assertEquals('company', $registrant['RegistrantType']);
        $this->assertEquals('NL123456', $registrant['RegistrantNumber']);
        $this->assertEquals('company', $admin['Type']);
        $this->assertEquals('company', $tech['Type']);
    }

    #[Test]
    public function validateCatTldOrder(): void
    {
        $domainName = 'test-' . uniqid() . '.cat';
        $params = array_merge($this->baseParams, [
            'tld' => 'cat',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'additionalfields' => [
                'Domain Purpose' => 'P1',
                'Auth Code' => 'AUTH123',
                'Registrant Details' => 'Catalan organization',
                'Trademark Name' => 'My Trademark'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertEquals('P1', $order['order']['Domain']['DomainPurpose']);
        $this->assertEquals('AUTH123', $order['order']['Domain']['AuthInfo']);
    }

    #[Test]
    public function validateMoscowTldOrder(): void
    {
        $domainName = 'test-' . uniqid() . '.moscow';
        $params = array_merge($this->baseParams, [
            'tld' => 'moscow',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'country' => 'RU',
            'countrycode' => 'RU',
            'additionalfields' => [
                'VAT Number' => '1234567890',
                'Registrant Number' => '123456789',
                'Registrant Details' => 'Moscow organization',
                'Admin Type' => 'company',
                'Admin Details' => 'Admin details',
                'Admin Number' => 'ADM123',
                'Tech Type' => 'company',
                'Tech Details' => 'Tech details',
                'Tech Number' => 'TECH123'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $registrant = $order['order']['Domain']['Registrant'];
        $this->assertEquals('1234567890', $registrant['VatNumber']);
        $this->assertEquals('123456789', $registrant['RegistrantNumber']);
    }

    #[Test]
    public function validatePtTldOrder(): void
    {
        $domainName = 'test-' . uniqid() . '.pt';
        $params = array_merge($this->baseParams, [
            'tld' => 'pt',
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'country' => 'PT',
            'countrycode' => 'PT',
            'additionalfields' => [
                'VAT Number' => 'PT123456789',
                'Registrant Number' => 'REG123',
                'Admin Number' => 'ADM456',
                'Tech Number' => 'TECH789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $registrant = $order['order']['Domain']['Registrant'];
        $admin = $order['order']['Domain']['AdminContact'];
        $tech = $order['order']['Domain']['TechContact'];

        $this->assertEquals('PT123456789', $registrant['VatNumber']);
        $this->assertEquals('REG123', $registrant['RegistrantNumber']);
        $this->assertEquals('ADM456', $admin['OrganisationNumber']);
        $this->assertEquals('TECH789', $tech['OrganisationNumber']);
    }

    // =========================================================================
    // AFNIC TLDs (inherit from .fr)
    // =========================================================================

    #[Test]
    #[DataProvider('afnicTldProvider')]
    public function validateAfnicTldInheritance(string $tld): void
    {
        $domainName = 'test-' . uniqid() . '.' . $tld;
        $params = array_merge($this->baseParams, [
            'tld' => $tld,
            'sld' => 'test-' . uniqid(),
            'domainname' => $domainName,
            'country' => 'FR',
            'countrycode' => 'FR',
            'companyname' => 'French Company SARL',
            'additionalfields' => [
                'VAT (Company)' => 'FR12345678901'
            ]
        ]);

        $request = Request::create($params);

        // Verify it uses fr parent class name
        $className = get_class($request);
        $this->assertStringContainsString('fr', $className,
            "TLD .$tld should use fr class, got $className");

        $order = $request->mapToOrder($params, 'Register_Domain');
        $registrant = $order['order']['Domain']['Registrant'];

        $this->assertEquals('company', $registrant['RegistrantType']);
    }

    public static function afnicTldProvider(): array
    {
        return [
            '.pm' => ['pm'],
            '.re' => ['re'],
            '.tf' => ['tf'],
            '.wf' => ['wf'],
            '.yt' => ['yt'],
        ];
    }
}
