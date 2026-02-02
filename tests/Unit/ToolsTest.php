<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Tools;
use ascio\AscioException;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Unit tests for ascio\Tools class
 *
 * @covers \ascio\Tools
 */
class ToolsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
    }

    // =========================================================================
    // splitName() tests
    // =========================================================================

    #[Test]
    public function splitNameSplitsFirstAndLastName(): void
    {
        $result = Tools::splitName('John Doe');

        $this->assertEquals('John', $result['first']);
        $this->assertEquals('Doe', $result['last']);
    }

    #[Test]
    public function splitNameHandlesMultipleLastNames(): void
    {
        $result = Tools::splitName('John Van Der Berg');

        $this->assertEquals('John', $result['first']);
        $this->assertEquals('Van Der Berg', $result['last']);
    }

    #[Test]
    public function splitNameHandlesSingleName(): void
    {
        $result = Tools::splitName('Madonna');

        // Single name is used as last name, first name is empty
        $this->assertEquals('', $result['first']);
        $this->assertEquals('Madonna', $result['last']);
    }

    #[Test]
    public function splitNameHandlesEmptyString(): void
    {
        $result = Tools::splitName('');

        $this->assertEquals('', $result['first']);
        $this->assertEquals('', $result['last']);
    }

    // =========================================================================
    // cleanString() tests
    // =========================================================================

    #[Test]
    public function cleanStringReplacesSpecialCodes(): void
    {
        $result = Tools::cleanString('Error$240ADetails$240DEnd');

        $this->assertEquals('Error:Details.End', $result);
    }

    #[Test]
    public function cleanStringReplacesShortCodes(): void
    {
        $result = Tools::cleanString('Error$0ADetails$0DEnd');

        $this->assertEquals('Error:Details.End', $result);
    }

    #[Test]
    public function cleanStringReturnsUnchangedStringWithoutCodes(): void
    {
        $input = 'Normal string without codes';
        $result = Tools::cleanString($input);

        $this->assertEquals($input, $result);
    }

    // =========================================================================
    // replaceSpecialCharacters() tests
    // =========================================================================

    #[Test]
    public function replaceSpecialCharactersReplacesGermanUmlauts(): void
    {
        $result = Tools::replaceSpecialCharacters('Müller Schäfer Böhm Größe');

        // Note: ß is replaced with single 's', not 'ss'
        $this->assertEquals('Muller Schafer Bohm Grose', $result);
    }

    #[Test]
    public function replaceSpecialCharactersReplacesUppercaseUmlauts(): void
    {
        $result = Tools::replaceSpecialCharacters('ÜBER ÄRGER ÖL');

        $this->assertEquals('UBER ARGER OL', $result);
    }

    // =========================================================================
    // isIcannTld() tests
    // =========================================================================

    #[Test]
    #[DataProvider('icannTldProvider')]
    public function isIcannTldIdentifiesCorrectly(string $domain, bool $expected): void
    {
        $result = Tools::isIcannTld($domain);

        $this->assertEquals($expected, $result);
    }

    public static function icannTldProvider(): array
    {
        return [
            // ICANN TLDs (>2 characters)
            'com domain' => ['example.com', true],
            'net domain' => ['example.net', true],
            'org domain' => ['example.org', true],
            'info domain' => ['example.info', true],
            'biz domain' => ['example.biz', true],
            'xyz domain' => ['example.xyz', true],

            // Country code TLDs (2 characters) - NOT ICANN
            'de domain' => ['example.de', false],
            'uk domain' => ['example.uk', false],
            'fr domain' => ['example.fr', false],
            'it domain' => ['example.it', false],
            'nl domain' => ['example.nl', false],
            'ca domain' => ['example.ca', false],

            // Second level domains
            'co.uk domain' => ['example.co.uk', false],
            'com.au domain' => ['example.com.au', false],
        ];
    }

    // =========================================================================
    // generateEppCode() tests
    // =========================================================================

    #[Test]
    public function generateEppCodeReturnsCorrectLength(): void
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = Tools::generateEppCode(12, $chars);

        $this->assertEquals(12, strlen($code));
    }

    #[Test]
    public function generateEppCodeUsesOnlyProvidedCharacters(): void
    {
        $chars = 'ABC123';
        $code = Tools::generateEppCode(20, $chars);

        for ($i = 0; $i < strlen($code); $i++) {
            $this->assertStringContainsString($code[$i], $chars);
        }
    }

    #[Test]
    public function generateEppCodeReturnsEmptyStringForZeroLength(): void
    {
        $chars = 'abcdef';
        $code = Tools::generateEppCode(0, $chars);

        $this->assertEquals('', $code);
    }

    // =========================================================================
    // fixPhone() tests
    // =========================================================================

    #[Test]
    public function fixPhoneReturnsEmptyStringForEmptyInput(): void
    {
        $result = Tools::fixPhone('', 'US');

        $this->assertEquals('', $result);
    }

    #[Test]
    public function fixPhonePassesThroughAlreadyFormattedNumber(): void
    {
        // The regex /^[\+][1-9]{2}\.[0-9]*/ requires exactly 2 digits before the dot
        // +49.30123456 matches (49 = 2 digits), +1.555 doesn't match (1 = 1 digit)
        $result = Tools::fixPhone('+49.30123456', 'DE');

        $this->assertEquals('+49.30123456', $result);
    }

    #[Test]
    public function fixPhoneFormatsUSNumber(): void
    {
        // Use a valid US number (555 prefix is reserved/invalid)
        // Washington DC area code (202) is valid
        $result = Tools::fixPhone('+12025551234', 'US');

        // libphonenumber formats to +countrycode.nationalnumber
        $this->assertEquals('+1.2025551234', $result);
    }

    #[Test]
    public function fixPhoneFormatsGermanNumber(): void
    {
        $result = Tools::fixPhone('+4930123456', 'DE');

        $this->assertEquals('+49.30123456', $result);
    }

    #[Test]
    public function fixPhoneFormatsNumberWithLeadingZero(): void
    {
        $result = Tools::fixPhone('030123456', 'DE');

        $this->assertEquals('+49.30123456', $result);
    }

    #[Test]
    public function fixPhoneThrowsExceptionForTooShortNumber(): void
    {
        $this->expectException(AscioException::class);
        $this->expectExceptionMessage('Phone number too short');

        Tools::fixPhone('123', 'US');
    }

    #[Test]
    public function fixPhoneThrowsExceptionForInvalidStart(): void
    {
        $this->expectException(AscioException::class);
        $this->expectExceptionMessage('Phone numbers should start with 0 or +');

        Tools::fixPhone('5551234567', 'US');
    }

    #[Test]
    public function fixPhoneHandlesCountryCaseInsensitivity(): void
    {
        // Use valid US number (DC area code)
        $result1 = Tools::fixPhone('+12025551234', 'us');
        $result2 = Tools::fixPhone('+12025551234', 'US');

        $this->assertEquals($result1, $result2);
    }

    // =========================================================================
    // compareRegistrant() tests
    // =========================================================================

    #[Test]
    public function compareRegistrantReturnsOwnerChangeForNameChange(): void
    {
        $oldContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'OrgName' => 'Test Company',
            'RegistrantNumber' => '12345',
            'City' => 'Test City'
        ];

        $newContact = (object) [
            'FirstName' => 'Jane',
            'LastName' => 'Doe',
            'OrgName' => 'Test Company',
            'RegistrantNumber' => '12345',
            'City' => 'Test City'
        ];

        $result = Tools::compareRegistrant($newContact, $oldContact);

        $this->assertEquals('OwnerChange', $result);
    }

    #[Test]
    public function compareRegistrantReturnsOwnerChangeForOrgNameChange(): void
    {
        $oldContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'OrgName' => 'Old Company',
            'RegistrantNumber' => '12345',
            'City' => 'Test City'
        ];

        $newContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'OrgName' => 'New Company',
            'RegistrantNumber' => '12345',
            'City' => 'Test City'
        ];

        $result = Tools::compareRegistrant($newContact, $oldContact);

        $this->assertEquals('OwnerChange', $result);
    }

    #[Test]
    public function compareRegistrantReturnsDetailsUpdateForAddressChange(): void
    {
        $oldContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'OrgName' => 'Test Company',
            'RegistrantNumber' => '12345',
            'City' => 'Old City'
        ];

        $newContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'OrgName' => 'Test Company',
            'RegistrantNumber' => '12345',
            'City' => 'New City'
        ];

        $result = Tools::compareRegistrant($newContact, $oldContact);

        $this->assertEquals('RegistrantDetailsUpdate', $result);
    }

    #[Test]
    public function compareRegistrantReturnsFalseForNoChanges(): void
    {
        $oldContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'OrgName' => 'Test Company',
            'RegistrantNumber' => '12345',
            'City' => 'Test City'
        ];

        $newContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'OrgName' => 'Test Company',
            'RegistrantNumber' => '12345',
            'City' => 'Test City'
        ];

        $result = Tools::compareRegistrant($newContact, $oldContact);

        $this->assertFalse($result);
    }

    #[Test]
    public function compareRegistrantHandlesUmlautReplacement(): void
    {
        $oldContact = (object) [
            'FirstName' => 'Müller',
            'LastName' => 'Test',
            'OrgName' => 'Test Company',
            'RegistrantNumber' => '12345',
            'City' => 'Test City'
        ];

        $newContact = (object) [
            'FirstName' => 'Muller',
            'LastName' => 'Test',
            'OrgName' => 'Test Company',
            'RegistrantNumber' => '12345',
            'City' => 'Test City'
        ];

        $result = Tools::compareRegistrant($newContact, $oldContact);

        // Should be no change since umlauts are replaced
        $this->assertFalse($result);
    }

    // =========================================================================
    // compareContact() tests
    // =========================================================================

    #[Test]
    public function compareContactReturnsContactUpdateForChanges(): void
    {
        $oldContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'Email' => 'old@example.com',
            'City' => 'Test City'
        ];

        $newContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'Email' => 'new@example.com',
            'City' => 'Test City'
        ];

        $result = Tools::compareContact($newContact, $oldContact);

        $this->assertEquals('ContactUpdate', $result);
    }

    #[Test]
    public function compareContactReturnsFalseForNoChanges(): void
    {
        $oldContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'Email' => 'test@example.com',
            'City' => 'Test City'
        ];

        $newContact = (object) [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'Email' => 'test@example.com',
            'City' => 'Test City'
        ];

        $result = Tools::compareContact($newContact, $oldContact);

        $this->assertFalse($result);
    }

    // =========================================================================
    // diffContact() tests
    // =========================================================================

    #[Test]
    public function diffContactReturnsEmptyArrayWhenCityIsNull(): void
    {
        $newContact = (object) ['City' => null, 'Name' => 'John'];
        $oldContact = (object) ['City' => 'Old City', 'Name' => 'John'];

        $result = Tools::diffContact($newContact, $oldContact);

        $this->assertEquals([], $result);
    }

    #[Test]
    public function diffContactReturnsChangedFields(): void
    {
        $oldContact = (object) [
            'City' => 'Old City',
            'Name' => 'John Doe',
            'Email' => 'old@example.com'
        ];

        $newContact = (object) [
            'City' => 'New City',
            'Name' => 'John Doe',
            'Email' => 'new@example.com'
        ];

        $result = Tools::diffContact($newContact, $oldContact);

        $this->assertArrayHasKey('City', $result);
        $this->assertEquals('New City', $result['City']);
        $this->assertArrayHasKey('Email', $result);
        $this->assertEquals('new@example.com', $result['Email']);
        $this->assertArrayNotHasKey('Name', $result);
    }

    // =========================================================================
    // formatError() tests
    // =========================================================================

    #[Test]
    public function formatErrorReturnsEmptyStringForNullItems(): void
    {
        $result = Tools::formatError(null, 'Error message');

        $this->assertEquals('', $result);
    }

    #[Test]
    public function formatErrorReturnEmptyStringForEmptyArray(): void
    {
        $result = Tools::formatError([], 'Error message');

        $this->assertEquals('', $result);
    }

    #[Test]
    public function formatErrorFormatsMultipleItems(): void
    {
        $items = [
            (object) ['Message' => 'First error'],
            (object) ['Message' => 'Second error']
        ];

        $result = Tools::formatError($items, 'Test');

        $this->assertStringContainsString('First error', $result);
        $this->assertStringContainsString('Second error', $result);
    }

    #[Test]
    public function formatErrorHandlesSingleItem(): void
    {
        $item = (object) ['Message' => 'Single error'];

        $result = Tools::formatError($item, 'Test');

        $this->assertStringContainsString('Single error', $result);
    }

    // =========================================================================
    // dateFromXsDateTime() tests
    // =========================================================================

    #[Test]
    public function dateFromXsDateTimeExtractsDatePart(): void
    {
        $result = Tools::dateFromXsDateTime('2024-01-15T10:30:00');

        $this->assertEquals('2024-01-15', $result);
    }

    #[Test]
    public function dateFromXsDateTimeHandlesDateWithoutTime(): void
    {
        $result = Tools::dateFromXsDateTime('2024-01-15');

        $this->assertEquals('2024-01-15', $result);
    }

    // =========================================================================
    // cleanAscioParams() tests
    // =========================================================================

    #[Test]
    public function cleanAscioParamsPreservesNonEmptyValues(): void
    {
        $params = [
            'name' => 'John',
            'email' => 'test@example.com',
            'age' => '25'
        ];

        $result = Tools::cleanAscioParams($params);

        $this->assertEquals('John', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('25', $result['age']);
    }

    #[Test]
    public function cleanAscioParamsHandlesNestedArrays(): void
    {
        $params = [
            'contact' => [
                'name' => 'John',
                'email' => 'test@example.com'
            ],
            'domain' => 'example.com'
        ];

        $result = Tools::cleanAscioParams($params);

        $this->assertEquals('John', $result['contact']['name']);
        $this->assertEquals('test@example.com', $result['contact']['email']);
        $this->assertEquals('example.com', $result['domain']);
    }

    // =========================================================================
    // reformatPrices() tests
    // =========================================================================

    #[Test]
    public function reformatPricesExtractsPricesCorrectly(): void
    {
        $result = (object) [
            'PriceInfo' => (object) [
                'Currency' => 'USD',
                'Prices' => (object) [
                    'Price' => [
                        (object) ['OrderType' => 'Register', 'Period' => 1, 'Price' => 10.99],
                        (object) ['OrderType' => 'Register', 'Period' => 2, 'Price' => 20.99],
                        (object) ['OrderType' => 'Renew', 'Period' => 1, 'Price' => 12.99],
                        (object) ['OrderType' => 'Renew', 'Period' => 2, 'Price' => 24.99],
                    ]
                ]
            ]
        ];

        $prices = Tools::reformatPrices($result);

        $this->assertEquals(10.99, $prices['register']);
        $this->assertEquals(12.99, $prices['renew']);
        $this->assertEquals('USD', $prices['CurrencyCode']);
    }

    // =========================================================================
    // getApiUser() tests
    // =========================================================================

    #[Test]
    public function getApiUserReturnsApiUserFromDatabase(): void
    {
        CapsuleMock::setTableData('tbladmins', [
            ['username' => 'admin', 'notes' => 'regular admin'],
            ['username' => 'apiuser', 'notes' => 'apiuser']
        ]);

        $result = Tools::getApiUser();

        $this->assertEquals('apiuser', $result);
    }

    #[Test]
    public function getApiUserReturnsFallbackAdminIfNoApiUser(): void
    {
        CapsuleMock::setTableData('tbladmins', [
            ['username' => 'admin1', 'notes' => 'regular admin'],
            ['username' => 'admin2', 'notes' => 'another admin']
        ]);

        // Reset global cache
        global $cachedAdminUser;
        $cachedAdminUser = null;

        $result = Tools::getApiUser();

        // Should return the last admin user
        $this->assertEquals('admin2', $result);
    }
}
