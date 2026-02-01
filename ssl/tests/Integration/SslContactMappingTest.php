<?php
/**
 * SSL Contact Mapping Tests
 *
 * Tests contact mapping from WHMCS parameters to Ascio v3 objects.
 * Verifies owner, admin, and tech contact creation and field formatting.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Integration;

use ascio\v3 as v3;

require_once __DIR__ . '/SslIntegrationTestBase.php';

class SslContactMappingTest extends SslIntegrationTestBase
{
    /**
     * Test owner contact mapping to Registrant object
     *
     * @test
     */
    public function testOwnerContactMapping(): void
    {
        $params = [
            'ownerFirstName' => 'John',
            'ownerLastName' => 'Doe',
            'ownerCompanyName' => 'ACME Corporation',
            'ownerEmail' => 'john.doe@acme.com',
            'ownerPhonePrefix' => '+49',
            'phonenumberowner' => '89 1234567',
            'ownerAddress1' => 'Main Street 123',
            'ownerAddress2' => 'Building A',
            'ownerCity' => 'Munich',
            'ownerState' => 'Bavaria',
            'ownerPostcode' => '80331',
            'ownerCountry' => 'DE',
        ];

        $contacts = $this->buildContacts($params);
        $owner = $contacts['owner'];

        $this->assertInstanceOf(v3\Registrant::class, $owner);
        $this->assertEquals('John', $owner->getFirstName());
        $this->assertEquals('Doe', $owner->getLastName());
        $this->assertEquals('ACME Corporation', $owner->getOrgName());
        $this->assertEquals('john.doe@acme.com', $owner->getEmail());
        $this->assertEquals('Main Street 123', $owner->getAddress1());
        $this->assertEquals('Building A', $owner->getAddress2());
        $this->assertEquals('Munich', $owner->getCity());
        $this->assertEquals('Bavaria', $owner->getState());
        $this->assertEquals('80331', $owner->getPostalCode());
        $this->assertEquals('DE', $owner->getCountryCode());
    }

    /**
     * Test admin contact mapping to Contact object
     *
     * @test
     */
    public function testAdminContactMapping(): void
    {
        $params = [
            'adminFirstName' => 'Jane',
            'adminLastName' => 'Smith',
            'adminCompanyName' => 'ACME IT Dept',
            'adminEmail' => 'jane.smith@acme.com',
            'adminPhonePrefix' => '+1',
            'phonenumberadmin' => '555 1234567',
            'adminAddress1' => '456 Tech Avenue',
            'adminAddress2' => 'Suite 100',
            'adminCity' => 'San Francisco',
            'adminState' => 'California',
            'adminPostcode' => '94102',
            'adminCountry' => 'US',
        ];

        $contacts = $this->buildContacts($params);
        $admin = $contacts['admin'];

        $this->assertInstanceOf(v3\Contact::class, $admin);
        $this->assertEquals('Jane', $admin->getFirstName());
        $this->assertEquals('Smith', $admin->getLastName());
        $this->assertEquals('ACME IT Dept', $admin->getOrgName());
        $this->assertEquals('jane.smith@acme.com', $admin->getEmail());
        $this->assertEquals('456 Tech Avenue', $admin->getAddress1());
        $this->assertEquals('Suite 100', $admin->getAddress2());
        $this->assertEquals('San Francisco', $admin->getCity());
        $this->assertEquals('California', $admin->getState());
        $this->assertEquals('94102', $admin->getPostalCode());
        $this->assertEquals('US', $admin->getCountryCode());
    }

    /**
     * Test tech contact mapping to Contact object
     *
     * @test
     */
    public function testTechContactMapping(): void
    {
        $params = [
            'techFirstName' => 'Bob',
            'techLastName' => 'Tech',
            'techCompanyName' => 'TechSupport Inc',
            'techEmail' => 'bob@techsupport.com',
            'techPhonePrefix' => '+44',
            'phonenumbertech' => '20 7123456',
            'techAddress1' => '789 Server Lane',
            'techAddress2' => '',
            'techCity' => 'London',
            'techState' => 'England',
            'techPostcode' => 'EC1A 1BB',
            'techCountry' => 'GB',
        ];

        $contacts = $this->buildContacts($params);
        $tech = $contacts['tech'];

        $this->assertInstanceOf(v3\Contact::class, $tech);
        $this->assertEquals('Bob', $tech->getFirstName());
        $this->assertEquals('Tech', $tech->getLastName());
        $this->assertEquals('TechSupport Inc', $tech->getOrgName());
        $this->assertEquals('bob@techsupport.com', $tech->getEmail());
        $this->assertEquals('789 Server Lane', $tech->getAddress1());
        $this->assertEquals('', $tech->getAddress2());
        $this->assertEquals('London', $tech->getCity());
        $this->assertEquals('England', $tech->getState());
        $this->assertEquals('EC1A 1BB', $tech->getPostalCode());
        $this->assertEquals('GB', $tech->getCountryCode());
    }

    /**
     * Test phone number formatting with various inputs
     *
     * @test
     * @dataProvider phoneNumberProvider
     */
    public function testPhoneFormatting(string $prefix, string $number, string $expected): void
    {
        $formatted = $this->formatPhone($prefix, $number);
        $this->assertEquals($expected, $formatted);
    }

    /**
     * Data provider for phone number formatting
     */
    public static function phoneNumberProvider(): array
    {
        return [
            'german_standard' => ['+49', '891234567', '+49.891234567'],
            'german_with_spaces' => ['+49', '89 123 4567', '+49.891234567'],
            'us_standard' => ['+1', '5551234567', '+1.5551234567'],
            'us_with_spaces' => ['+1', '555 123 4567', '+1.5551234567'],
            'uk_standard' => ['+44', '2071234567', '+44.2071234567'],
            'prefix_without_plus' => ['49', '891234567', '+49.891234567'],
            'prefix_with_plus' => ['+49', '891234567', '+49.891234567'],
            'multiple_spaces' => ['+33', '1  23  45  67', '+33.1234567'],
            'tabs_and_spaces' => ['+31', "20\t1234567", '+31.201234567'],
        ];
    }

    /**
     * Test address mapping with special characters
     *
     * @test
     */
    public function testAddressMapping(): void
    {
        $params = [
            'ownerAddress1' => 'Musterstra\u00dfe 123', // German eszett
            'ownerAddress2' => 'Geb\u00e4ude B', // German umlaut
            'ownerCity' => 'K\u00f6ln', // Cologne with umlaut
            'ownerState' => 'Nordrhein-Westfalen',
            'ownerPostcode' => '50667',
            'ownerCountry' => 'DE',
            'ownerFirstName' => 'Hans',
            'ownerLastName' => 'M\u00fcller', // Mueller with umlaut
            'ownerEmail' => 'hans@example.com',
            'ownerPhonePrefix' => '+49',
            'phonenumberowner' => '2211234567',
        ];

        $contacts = $this->buildContacts($params);
        $owner = $contacts['owner'];

        // Verify address fields are preserved
        $this->assertNotEmpty($owner->getAddress1());
        $this->assertNotEmpty($owner->getCity());
        $this->assertEquals('DE', $owner->getCountryCode());
    }

    /**
     * Test contact mapping with minimal data (only required fields)
     *
     * @test
     */
    public function testMinimalContactMapping(): void
    {
        $params = [
            'ownerFirstName' => 'Test',
            'ownerLastName' => 'User',
            'ownerEmail' => 'test@example.com',
            'ownerPhonePrefix' => '+1',
            'phonenumberowner' => '5551234567',
            'ownerAddress1' => '123 Test St',
            'ownerCity' => 'Test City',
            'ownerPostcode' => '12345',
            'ownerCountry' => 'US',
        ];

        $contacts = $this->buildContacts($params);
        $owner = $contacts['owner'];

        $this->assertEquals('Test', $owner->getFirstName());
        $this->assertEquals('User', $owner->getLastName());
        $this->assertEquals('test@example.com', $owner->getEmail());
        $this->assertEquals('123 Test St', $owner->getAddress1());
        $this->assertEquals('Test City', $owner->getCity());
        $this->assertEquals('12345', $owner->getPostalCode());
        $this->assertEquals('US', $owner->getCountryCode());
    }

    /**
     * Test that all three contacts are built together
     *
     * @test
     */
    public function testAllContactsBuilt(): void
    {
        $params = [
            'ownerFirstName' => 'Owner',
            'ownerLastName' => 'Test',
            'ownerEmail' => 'owner@test.com',
            'ownerPhonePrefix' => '+49',
            'phonenumberowner' => '891111111',
            'ownerAddress1' => 'Owner St 1',
            'ownerCity' => 'Munich',
            'ownerPostcode' => '80331',
            'ownerCountry' => 'DE',
            'adminFirstName' => 'Admin',
            'adminLastName' => 'Test',
            'adminEmail' => 'admin@test.com',
            'adminPhonePrefix' => '+49',
            'phonenumberadmin' => '892222222',
            'adminAddress1' => 'Admin St 2',
            'adminCity' => 'Munich',
            'adminPostcode' => '80332',
            'adminCountry' => 'DE',
            'techFirstName' => 'Tech',
            'techLastName' => 'Test',
            'techEmail' => 'tech@test.com',
            'techPhonePrefix' => '+49',
            'phonenumbertech' => '893333333',
            'techAddress1' => 'Tech St 3',
            'techCity' => 'Munich',
            'techPostcode' => '80333',
            'techCountry' => 'DE',
        ];

        $contacts = $this->buildContacts($params);

        $this->assertArrayHasKey('owner', $contacts);
        $this->assertArrayHasKey('admin', $contacts);
        $this->assertArrayHasKey('tech', $contacts);

        $this->assertInstanceOf(v3\Registrant::class, $contacts['owner']);
        $this->assertInstanceOf(v3\Contact::class, $contacts['admin']);
        $this->assertInstanceOf(v3\Contact::class, $contacts['tech']);

        // Verify each contact has distinct data
        $this->assertEquals('Owner', $contacts['owner']->getFirstName());
        $this->assertEquals('Admin', $contacts['admin']->getFirstName());
        $this->assertEquals('Tech', $contacts['tech']->getFirstName());
    }

    /**
     * Test organization type contact (company)
     *
     * @test
     */
    public function testOrganizationTypeContact(): void
    {
        $contactData = TestDataFactory::createContactData('owner');
        $contactData['orgName'] = 'Test Corporation GmbH';
        $contactData['type'] = 'Organization';

        $registrant = TestDataFactory::buildRegistrant($contactData);

        $this->assertInstanceOf(v3\Registrant::class, $registrant);
        $this->assertEquals('Test Corporation GmbH', $registrant->getOrgName());
        $this->assertEquals('Organization', $registrant->getType());
    }

    /**
     * Test individual type contact (person)
     *
     * @test
     */
    public function testIndividualTypeContact(): void
    {
        $contactData = TestDataFactory::createContactData('owner');
        $contactData['orgName'] = '';
        $contactData['type'] = 'Person';

        $registrant = TestDataFactory::buildRegistrant($contactData);

        $this->assertInstanceOf(v3\Registrant::class, $registrant);
        $this->assertEquals('', $registrant->getOrgName());
        $this->assertEquals('Person', $registrant->getType());
    }

    /**
     * Test country code validation
     *
     * @test
     * @dataProvider countryCodeProvider
     */
    public function testCountryCodeMapping(string $countryCode): void
    {
        $params = [
            'ownerCountry' => $countryCode,
            'ownerFirstName' => 'Test',
            'ownerLastName' => 'User',
            'ownerEmail' => 'test@example.com',
            'ownerPhonePrefix' => '+1',
            'phonenumberowner' => '5551234567',
        ];

        $contacts = $this->buildContacts($params);
        $owner = $contacts['owner'];

        // Country code should be exactly 2 characters (ISO 3166-1 alpha-2)
        $this->assertEquals($countryCode, $owner->getCountryCode());
        $this->assertEquals(2, strlen($owner->getCountryCode()));
    }

    /**
     * Data provider for country codes
     */
    public static function countryCodeProvider(): array
    {
        return [
            'germany' => ['DE'],
            'united_states' => ['US'],
            'united_kingdom' => ['GB'],
            'france' => ['FR'],
            'netherlands' => ['NL'],
            'austria' => ['AT'],
            'switzerland' => ['CH'],
            'canada' => ['CA'],
            'australia' => ['AU'],
            'japan' => ['JP'],
        ];
    }

    /**
     * Test email format in contact mapping
     *
     * @test
     */
    public function testEmailFormatMapping(): void
    {
        $emails = [
            'simple@example.com',
            'with.dots@example.com',
            'with+plus@example.com',
            'user@subdomain.example.com',
        ];

        foreach ($emails as $email) {
            $params = [
                'ownerEmail' => $email,
                'ownerFirstName' => 'Test',
                'ownerLastName' => 'User',
                'ownerPhonePrefix' => '+1',
                'phonenumberowner' => '5551234567',
            ];

            $contacts = $this->buildContacts($params);
            $owner = $contacts['owner'];

            $this->assertEquals($email, $owner->getEmail());
            $this->assertStringContainsString('@', $owner->getEmail());
        }
    }

    /**
     * Test default values are applied when params missing
     *
     * @test
     */
    public function testDefaultValuesApplied(): void
    {
        // Empty params should use defaults
        $contacts = $this->buildContacts([]);

        // Check defaults are applied
        $owner = $contacts['owner'];
        $this->assertNotEmpty($owner->getFirstName());
        $this->assertNotEmpty($owner->getLastName());
        $this->assertNotEmpty($owner->getEmail());
        $this->assertNotEmpty($owner->getPhone());
    }
}
