<?php

namespace Ascio\Tests\Mocks;

/**
 * Mock Parameters for v3 API testing
 *
 * Provides test parameters that mimic WHMCS module params
 */
class MockParamsV3
{
    /**
     * Get default test parameters
     *
     * @return array
     */
    public static function getDefault(): array
    {
        return [
            'Username' => 'test_account',
            'Password' => 'test_password',
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
            'techfirstname' => 'Tech',
            'techlastname' => 'Support',
            'techcompanyname' => 'Test Company',
            'techaddress1' => '123 Test Street',
            'techaddress2' => '',
            'techcity' => 'Test City',
            'techstate' => 'TS',
            'techpostcode' => '12345',
            'techcountry' => 'US',
            'techemail' => 'tech@example.com',
            'techfullphonenumber' => '+1.5551234567',
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'eppcode' => 'EPP123456',
            'idprotection' => false,
            'userid' => 100,
            'custom' => [],
            'additionalfields' => [
                'Application Purpose' => '',
                'Comment' => '',
                'Nexus Category' => '',
                'Nexus Country' => '',
                'Legal Type' => '',
                'Language' => '',
                'Organisation Type' => '',
                'Registrant ID Number' => '',
                'Admin ID Number' => '',
                'Registrant Type' => '',
            ],
            'customfields' => [],
            'options' => [],
            'OrganisationNumber' => '',
            'VAT Number' => '',
            'Proxy_Lite' => 'off',
            'RegistrantType' => '',
            'VatNumber' => '',
            'IdNumber' => '',
            'fax' => '',
            'adminfax' => '',
            'techfax' => '',
            'RegistrantDetails' => [],
            'AdminDetails' => [],
            'TechDetails' => []
        ];
    }

    /**
     * Get parameters for a specific TLD
     *
     * @param string $tld The TLD (e.g., 'ca', 'de', 'it')
     * @param array $overrides Additional parameter overrides
     * @return array
     */
    public static function forTld(string $tld, array $overrides = []): array
    {
        $defaults = self::getDefault();
        $tldDefaults = self::getTldDefaults($tld);

        return array_merge($defaults, $tldDefaults, $overrides);
    }

    /**
     * Get TLD-specific default values
     *
     * @param string $tld
     * @return array
     */
    private static function getTldDefaults(string $tld): array
    {
        $tldDefaults = [
            'ca' => [
                'tld' => 'ca',
                'domainname' => 'example.ca',
                'country' => 'CA',
                'admincountry' => 'CA',
                'techcountry' => 'CA',
                'city' => 'Toronto',
                'admincity' => 'Toronto',
                'techcity' => 'Toronto',
                'state' => 'ON',
                'adminstate' => 'ON',
                'techstate' => 'ON',
                'postcode' => 'M5V 1A1',
                'adminpostcode' => 'M5V 1A1',
                'techpostcode' => 'M5V 1A1',
                'fullphonenumber' => '+1.4165551234',
                'adminfullphonenumber' => '+1.4165551234',
                'techfullphonenumber' => '+1.4165551234',
                'additionalfields' => [
                    'Legal Type' => 'Corporation'
                ]
            ],
            'de' => [
                'tld' => 'de',
                'domainname' => 'example.de',
                'country' => 'DE',
                'admincountry' => 'DE',
                'techcountry' => 'DE',
                'city' => 'Berlin',
                'admincity' => 'Berlin',
                'techcity' => 'Berlin',
                'state' => 'BE',
                'adminstate' => 'BE',
                'techstate' => 'BE',
                'postcode' => '10115',
                'adminpostcode' => '10115',
                'techpostcode' => '10115',
                'fullphonenumber' => '+49.301234567',
                'adminfullphonenumber' => '+49.301234567',
                'techfullphonenumber' => '+49.301234567'
            ],
            'it' => [
                'tld' => 'it',
                'domainname' => 'example.it',
                'country' => 'IT',
                'admincountry' => 'IT',
                'techcountry' => 'IT',
                'city' => 'Rome',
                'admincity' => 'Rome',
                'techcity' => 'Rome',
                'state' => 'RM',
                'adminstate' => 'RM',
                'techstate' => 'RM',
                'postcode' => '00100',
                'adminpostcode' => '00100',
                'techpostcode' => '00100',
                'fullphonenumber' => '+39.0612345678',
                'adminfullphonenumber' => '+39.0612345678',
                'techfullphonenumber' => '+39.0612345678',
                'additionalfields' => [
                    'Registrant Type' => '1'  // Italian natural person
                ]
            ],
            'uk' => [
                'tld' => 'uk',
                'domainname' => 'example.uk',
                'country' => 'GB',
                'admincountry' => 'GB',
                'techcountry' => 'GB',
                'city' => 'London',
                'admincity' => 'London',
                'techcity' => 'London',
                'state' => 'England',
                'adminstate' => 'England',
                'techstate' => 'England',
                'postcode' => 'SW1A 1AA',
                'adminpostcode' => 'SW1A 1AA',
                'techpostcode' => 'SW1A 1AA',
                'fullphonenumber' => '+44.2012345678',
                'adminfullphonenumber' => '+44.2012345678',
                'techfullphonenumber' => '+44.2012345678'
            ],
            'nl' => [
                'tld' => 'nl',
                'domainname' => 'example.nl',
                'country' => 'NL',
                'admincountry' => 'NL',
                'techcountry' => 'NL',
                'city' => 'Amsterdam',
                'admincity' => 'Amsterdam',
                'techcity' => 'Amsterdam',
                'state' => 'NH',
                'adminstate' => 'NH',
                'techstate' => 'NH',
                'postcode' => '1012 JS',
                'adminpostcode' => '1012 JS',
                'techpostcode' => '1012 JS',
                'fullphonenumber' => '+31.201234567',
                'adminfullphonenumber' => '+31.201234567',
                'techfullphonenumber' => '+31.201234567'
            ],
            'fr' => [
                'tld' => 'fr',
                'domainname' => 'example.fr',
                'country' => 'FR',
                'admincountry' => 'FR',
                'techcountry' => 'FR',
                'city' => 'Paris',
                'admincity' => 'Paris',
                'techcity' => 'Paris',
                'state' => 'IDF',
                'adminstate' => 'IDF',
                'techstate' => 'IDF',
                'postcode' => '75001',
                'adminpostcode' => '75001',
                'techpostcode' => '75001',
                'fullphonenumber' => '+33.112345678',
                'adminfullphonenumber' => '+33.112345678',
                'techfullphonenumber' => '+33.112345678'
            ],
            'sg' => [
                'tld' => 'sg',
                'domainname' => 'example.sg',
                'country' => 'SG',
                'admincountry' => 'SG',
                'techcountry' => 'SG',
                'city' => 'Singapore',
                'admincity' => 'Singapore',
                'techcity' => 'Singapore',
                'state' => '',
                'adminstate' => '',
                'techstate' => '',
                'postcode' => '018956',
                'adminpostcode' => '018956',
                'techpostcode' => '018956',
                'fullphonenumber' => '+65.61234567',
                'adminfullphonenumber' => '+65.61234567',
                'techfullphonenumber' => '+65.61234567',
                'additionalfields' => [
                    'Registrant Type' => 'Individual',
                    'RCB/Singapore ID' => 'S1234567D'
                ]
            ],
            'au' => [
                'tld' => 'au',
                'domainname' => 'example.au',
                'country' => 'AU',
                'admincountry' => 'AU',
                'techcountry' => 'AU',
                'city' => 'Sydney',
                'admincity' => 'Sydney',
                'techcity' => 'Sydney',
                'state' => 'NSW',
                'adminstate' => 'NSW',
                'techstate' => 'NSW',
                'postcode' => '2000',
                'adminpostcode' => '2000',
                'techpostcode' => '2000',
                'fullphonenumber' => '+61.212345678',
                'adminfullphonenumber' => '+61.212345678',
                'techfullphonenumber' => '+61.212345678',
                'additionalfields' => [
                    'Eligibility ID Type' => 'ACN',
                    'Eligibility ID' => '123456789'
                ]
            ]
        ];

        return $tldDefaults[$tld] ?? ['tld' => $tld, 'domainname' => "example.{$tld}"];
    }

    /**
     * Get parameters for a domain registration
     *
     * @param string $domainName
     * @param array $overrides
     * @return array
     */
    public static function forRegistration(string $domainName, array $overrides = []): array
    {
        $parts = explode('.', $domainName, 2);
        $sld = $parts[0];
        $tld = $parts[1] ?? 'com';

        $defaults = self::getDefault();
        $tldDefaults = self::getTldDefaults($tld);

        return array_merge($defaults, $tldDefaults, [
            'domainname' => $domainName,
            'sld' => $sld,
            'tld' => $tld
        ], $overrides);
    }

    /**
     * Get parameters for a domain transfer
     *
     * @param string $domainName
     * @param string $eppCode
     * @param array $overrides
     * @return array
     */
    public static function forTransfer(string $domainName, string $eppCode, array $overrides = []): array
    {
        return self::forRegistration($domainName, array_merge([
            'eppcode' => $eppCode
        ], $overrides));
    }

    /**
     * Get parameters for a domain renewal
     *
     * @param string $domainName
     * @param int $regperiod
     * @param array $overrides
     * @return array
     */
    public static function forRenewal(string $domainName, int $regperiod = 1, array $overrides = []): array
    {
        return self::forRegistration($domainName, array_merge([
            'regperiod' => $regperiod
        ], $overrides));
    }

    /**
     * Get parameters with premium domain pricing
     *
     * @param string $domainName
     * @param float $cost
     * @param array $overrides
     * @return array
     */
    public static function forPremiumDomain(string $domainName, float $cost, array $overrides = []): array
    {
        return self::forRegistration($domainName, array_merge([
            'premiumEnabled' => true,
            'premiumCost' => $cost
        ], $overrides));
    }

    /**
     * Get parameters with ID protection enabled
     *
     * @param string $domainName
     * @param array $overrides
     * @return array
     */
    public static function withIdProtection(string $domainName, array $overrides = []): array
    {
        return self::forRegistration($domainName, array_merge([
            'idprotection' => true
        ], $overrides));
    }

    /**
     * Get contact details in WHMCS nested format
     *
     * @param array $overrides
     * @return array
     */
    public static function getContactDetails(array $overrides = []): array
    {
        $default = [
            'Registrant' => [
                'First Name' => 'John',
                'Last Name' => 'Doe',
                'Company Name' => 'Test Company',
                'Address1' => '123 Test Street',
                'Address2' => 'Suite 100',
                'City' => 'Test City',
                'State' => 'TS',
                'Postcode' => '12345',
                'Country' => 'US',
                'Country Code' => 'US',
                'Email' => 'registrant@example.com',
                'Phone Number' => '+1.5551234567',
                'Fax Number' => ''
            ],
            'Admin' => [
                'First Name' => 'Admin',
                'Last Name' => 'User',
                'Company Name' => 'Test Company',
                'Address1' => '123 Test Street',
                'Address2' => '',
                'City' => 'Test City',
                'State' => 'TS',
                'Postcode' => '12345',
                'Country' => 'US',
                'Country Code' => 'US',
                'Email' => 'admin@example.com',
                'Phone Number' => '+1.5551234567',
                'Fax Number' => ''
            ],
            'Technical' => [
                'First Name' => 'Tech',
                'Last Name' => 'Support',
                'Company Name' => 'Test Company',
                'Address1' => '123 Test Street',
                'Address2' => '',
                'City' => 'Test City',
                'State' => 'TS',
                'Postcode' => '12345',
                'Country' => 'US',
                'Country Code' => 'US',
                'Email' => 'tech@example.com',
                'Phone Number' => '+1.5551234567',
                'Fax Number' => ''
            ]
        ];

        return array_replace_recursive($default, $overrides);
    }
}
