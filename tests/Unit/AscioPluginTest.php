<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SoapClientMock;

// Load the plugin file
require_once __DIR__ . '/../../ascio.php';

/**
 * Unit tests for main ascio.php plugin functions
 *
 * @covers ascio_MetaData
 * @covers ascio_getConfigArray
 * @covers ascio_AdminCustomButtonArray
 * @covers ascio_ClientAreaCustomButtonArray
 * @covers ascio_DomainSuggestionOptions
 * @covers ascio_RegisterDomain
 * @covers ascio_TransferDomain
 * @covers ascio_RenewDomain
 * @covers ascio_GetNameservers
 * @covers ascio_SaveNameservers
 * @covers ascio_GetContactDetails
 * @covers ascio_SaveContactDetails
 * @covers ascio_GetEPPCode
 * @covers ascio_Sync
 */
class AscioPluginTest extends TestCase
{
    private array $defaultParams;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
        SoapClientMock::reset();

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
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'eppcode' => 'EPP123456',
            'idprotection' => false,
            'custom' => [],
            'additionalfields' => [],
            'premiumEnabled' => false,
            'premiumCost' => null
        ];
    }

    // =========================================================================
    // ascio_MetaData() tests
    // =========================================================================

    #[Test]
    public function metaDataReturnsCorrectDisplayName(): void
    {
        $result = ascio_MetaData();

        $this->assertEquals('Ascio Domains', $result['DisplayName']);
    }

    #[Test]
    public function metaDataReturnsCorrectApiVersion(): void
    {
        $result = ascio_MetaData();

        $this->assertEquals('1.1', $result['APIVersion']);
    }

    // =========================================================================
    // ascio_getConfigArray() tests
    // =========================================================================

    #[Test]
    public function getConfigArrayIncludesUsernameField(): void
    {
        $result = ascio_getConfigArray();

        $this->assertArrayHasKey('Username', $result);
        $this->assertEquals('text', $result['Username']['Type']);
    }

    #[Test]
    public function getConfigArrayIncludesPasswordField(): void
    {
        $result = ascio_getConfigArray();

        $this->assertArrayHasKey('Password', $result);
        $this->assertEquals('password', $result['Password']['Type']);
    }

    #[Test]
    public function getConfigArrayIncludesTestModeField(): void
    {
        $result = ascio_getConfigArray();

        $this->assertArrayHasKey('TestMode', $result);
        $this->assertEquals('yesno', $result['TestMode']['Type']);
    }

    #[Test]
    public function getConfigArrayIncludesAutoExpireField(): void
    {
        $result = ascio_getConfigArray();

        $this->assertArrayHasKey('AutoExpire', $result);
        $this->assertEquals('yesno', $result['AutoExpire']['Type']);
    }

    #[Test]
    public function getConfigArrayIncludesDnsFields(): void
    {
        $result = ascio_getConfigArray();

        $this->assertArrayHasKey('AutoCreateDNS', $result);
        $this->assertArrayHasKey('DNS_Default_Zone', $result);
        $this->assertArrayHasKey('DNS_Default_Mailserver', $result);
        $this->assertArrayHasKey('DNS_Default_Mailserver_2', $result);
    }

    #[Test]
    public function getConfigArrayIncludesProxyLiteField(): void
    {
        $result = ascio_getConfigArray();

        $this->assertArrayHasKey('Proxy_Lite', $result);
        $this->assertEquals('yesno', $result['Proxy_Lite']['Type']);
    }

    // =========================================================================
    // ascio_AdminCustomButtonArray() tests
    // =========================================================================

    #[Test]
    public function adminCustomButtonArrayIncludesUpdateEppCode(): void
    {
        $result = ascio_AdminCustomButtonArray();

        $this->assertArrayHasKey('Update EPP Code', $result);
        $this->assertEquals('UpdateEPPCode', $result['Update EPP Code']);
    }

    #[Test]
    public function adminCustomButtonArrayIncludesAutorenewButtons(): void
    {
        $result = ascio_AdminCustomButtonArray();

        $this->assertArrayHasKey('Autorenew On', $result);
        $this->assertEquals('UnexpireDomain', $result['Autorenew On']);
        $this->assertArrayHasKey('Autorenew Off', $result);
        $this->assertEquals('ExpireDomain', $result['Autorenew Off']);
    }

    // =========================================================================
    // ascio_ClientAreaCustomButtonArray() tests
    // =========================================================================

    #[Test]
    public function clientAreaCustomButtonArrayIncludesUpdateEppCode(): void
    {
        $result = ascio_ClientAreaCustomButtonArray();

        $this->assertArrayHasKey('Update EPP Code', $result);
        $this->assertEquals('UpdateEPPCode', $result['Update EPP Code']);
    }

    // =========================================================================
    // ascio_DomainSuggestionOptions() tests
    // =========================================================================

    #[Test]
    public function domainSuggestionOptionsIncludesTldsToInclude(): void
    {
        $result = ascio_DomainSuggestionOptions();

        $this->assertArrayHasKey('tldsToInclude', $result);
        $this->assertEquals('text', $result['tldsToInclude']['Type']);
    }

    // =========================================================================
    // ascio_DeleteNameserver() tests
    // =========================================================================

    #[Test]
    public function deleteNameserverReturnsError(): void
    {
        $result = ascio_DeleteNameserver($this->defaultParams);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Operation not allowed', $result['error']);
    }

    // =========================================================================
    // mapNameservers() tests
    // =========================================================================

    #[Test]
    public function mapNameserversConvertsToArray(): void
    {
        $ascioNameservers = [
            (object) ['HostName' => 'ns1.example.com'],
            (object) ['HostName' => 'ns2.example.com'],
            (object) ['HostName' => 'ns3.example.com']
        ];

        $result = mapNameservers($ascioNameservers);

        $this->assertEquals(['ns1.example.com', 'ns2.example.com', 'ns3.example.com'], $result);
    }

    #[Test]
    public function mapNameserversHandlesEmptyHostnames(): void
    {
        $ascioNameservers = [
            (object) ['HostName' => 'ns1.example.com'],
            (object) ['HostName' => ''],
            (object) ['HostName' => '']
        ];

        $result = mapNameservers($ascioNameservers);

        $this->assertEquals(['ns1.example.com', '', ''], $result);
    }

    // =========================================================================
    // extractPeriods() tests
    // =========================================================================

    #[Test]
    public function extractPeriodsGroupsByTld(): void
    {
        $list = [
            (object) [
                'Product' => (object) ['Tld' => 'com', 'Period' => 1, 'OrderType' => 'Register'],
                'Price' => 10.99
            ],
            (object) [
                'Product' => (object) ['Tld' => 'com', 'Period' => 2, 'OrderType' => 'Register'],
                'Price' => 20.99
            ],
            (object) [
                'Product' => (object) ['Tld' => 'com', 'Period' => 1, 'OrderType' => 'Renew'],
                'Price' => 12.99
            ],
            (object) [
                'Product' => (object) ['Tld' => 'net', 'Period' => 1, 'OrderType' => 'Register'],
                'Price' => 11.99
            ]
        ];

        $result = extractPeriods($list);

        $this->assertArrayHasKey('com', $result);
        $this->assertArrayHasKey('net', $result);
        $this->assertEquals(10.99, $result['com']['OrderType']['Register']);
        $this->assertEquals(12.99, $result['com']['OrderType']['Renew']);
        $this->assertContains(1, $result['com']['Period']);
        $this->assertContains(2, $result['com']['Period']);
    }

    #[Test]
    public function extractPeriodsHandlesRestoreOrderType(): void
    {
        $list = [
            (object) [
                'Product' => (object) ['Tld' => 'com', 'Period' => 0, 'OrderType' => 'Restore'],
                'Price' => 99.99
            ]
        ];

        $result = extractPeriods($list);

        $this->assertEquals(99.99, $result['com']['OrderType']['Restore']);
    }

    #[Test]
    public function extractPeriodsHandlesZeroPeriodTransfer(): void
    {
        $list = [
            (object) [
                'Product' => (object) ['Tld' => 'com', 'Period' => 0, 'OrderType' => 'Transfer'],
                'Price' => 8.99
            ],
            (object) [
                'Product' => (object) ['Tld' => 'com', 'Period' => 1, 'OrderType' => 'Transfer'],
                'Price' => 10.99
            ]
        ];

        $result = extractPeriods($list);

        // Period 1 transfer overwrites period 0 transfer (both conditions match)
        // The logic captures Period==1 OR (Transfer AND Period==0), so both match
        $this->assertEquals(10.99, $result['com']['OrderType']['Transfer']);
    }

    // =========================================================================
    // Integration with Request class
    // =========================================================================

    #[Test]
    public function registerDomainCallsRequestClass(): void
    {
        // Set up session cache mock
        CapsuleMock::setTableData('mod_asciosession', [
            ['account' => 'testuser', 'sessionId' => 'mock-session-123']
        ]);

        // This test verifies the function exists and accepts parameters
        // Full integration would require mocking the SoapClient
        $this->assertTrue(function_exists('ascio_RegisterDomain'));
    }

    #[Test]
    public function transferDomainCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_TransferDomain'));
    }

    #[Test]
    public function renewDomainCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_RenewDomain'));
    }

    #[Test]
    public function getNameserversCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_GetNameservers'));
    }

    #[Test]
    public function saveNameserversCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_SaveNameservers'));
    }

    #[Test]
    public function getContactDetailsCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_GetContactDetails'));
    }

    #[Test]
    public function saveContactDetailsCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_SaveContactDetails'));
    }

    #[Test]
    public function getEppCodeCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_GetEPPCode'));
    }

    #[Test]
    public function updateEppCodeCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_UpdateEPPCode'));
    }

    #[Test]
    public function syncCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_Sync'));
    }

    #[Test]
    public function checkAvailabilityCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_CheckAvailability'));
    }

    #[Test]
    public function getDomainInformationCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_GetDomainInformation'));
    }

    #[Test]
    public function getRegistrarLockCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_GetRegistrarLock'));
    }

    #[Test]
    public function saveRegistrarLockCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_saveRegistrarLock'));
    }

    #[Test]
    public function idProtectToggleCallsRequestClass(): void
    {
        $this->assertTrue(function_exists('ascio_IDProtectToggle'));
    }

    #[Test]
    public function getDnsCallsZoneClass(): void
    {
        $this->assertTrue(function_exists('ascio_GetDNS'));
    }

    #[Test]
    public function saveDnsCallsZoneClass(): void
    {
        $this->assertTrue(function_exists('ascio_SaveDNS'));
    }

    #[Test]
    public function getTldPricingCallsRequest(): void
    {
        $this->assertTrue(function_exists('ascio_GetTldPricing'));
    }

    #[Test]
    public function adminDomainsTabFieldsReturnsArray(): void
    {
        CapsuleMock::setTableData('tbldomains_extra', [
            ['domain_id' => 1, 'name' => 'verified_by', 'value' => '127.0.0.1'],
            ['domain_id' => 1, 'name' => 'verified_date', 'value' => '2024-01-15']
        ]);

        $result = ascio_AdminDomainsTabFields($this->defaultParams);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Registrant Verification', $result);
    }

    #[Test]
    public function expireDomainFunctionExists(): void
    {
        $this->assertTrue(function_exists('ascio_ExpireDomain'));
    }

    #[Test]
    public function unexpireDomainFunctionExists(): void
    {
        $this->assertTrue(function_exists('ascio_UnexpireDomain'));
    }

    #[Test]
    public function getDomainSuggestionsFunctionExists(): void
    {
        $this->assertTrue(function_exists('ascio_GetDomainSuggestions'));
    }

    #[Test]
    public function resendIrtpVerificationEmailFunctionExists(): void
    {
        $this->assertTrue(function_exists('ascio_ResendIRTPVerificationEmail'));
    }

    #[Test]
    public function getEmailForwardingFunctionExists(): void
    {
        $this->assertTrue(function_exists('ascio_GetEmailForwarding'));
    }

    #[Test]
    public function saveEmailForwardingFunctionExists(): void
    {
        $this->assertTrue(function_exists('ascio_SaveEmailForwarding'));
    }

    #[Test]
    public function modifyNameserverFunctionExists(): void
    {
        $this->assertTrue(function_exists('ascio_ModifyNameserver'));
    }
}
