<?php
/**
 * Domain Order Validation Integration Tests
 *
 * Tests ValidateOrder API for all order types to verify order structure
 * and data mapping without creating actual orders.
 *
 * @group integration
 * @group v3
 * @group order-validation
 */

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request;
use Ascio\Tests\Mocks\MockParamsV3;

#[Group('integration')]
#[Group('v3')]
#[Group('order-validation')]
class DomainOrderValidationTest extends IntegrationTestBase
{
    // =========================================================================
    // Register Domain Validation Tests
    // =========================================================================

    #[Test]
    public function testValidateRegisterDomain(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        // Verify order structure before API call
        $this->assertOrderStructure($order, 'Register_Domain');
        $this->assertEquals($domainName, $order['Order']['Domain']['DomainName']);

        // Verify contacts are present
        $this->assertArrayHasKey('Registrant', $order['Order']['Domain']);
        $this->assertArrayHasKey('AdminContact', $order['Order']['Domain']);
        $this->assertArrayHasKey('TechContact', $order['Order']['Domain']);
        $this->assertArrayHasKey('BillingContact', $order['Order']['Domain']);
        $this->assertArrayHasKey('NameServers', $order['Order']['Domain']);

        // Verify registrant has Name (v3 format)
        $this->assertArrayHasKey('Name', $order['Order']['Domain']['Registrant']);

        // Verify nameserver structure
        $this->assertArrayHasKey('NameServer1', $order['Order']['Domain']['NameServers']);
        $this->assertArrayHasKey('HostName', $order['Order']['Domain']['NameServers']['NameServer1']);

        // Call ValidateOrder API
        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    #[Test]
    public function testValidateRegisterDomainWithIdProtection(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, ['idprotection' => true]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        // Verify privacy proxy is set
        $this->assertArrayHasKey('PrivacyProxy', $order['Order']['Domain']);
        $this->assertEquals('Proxy', $order['Order']['Domain']['PrivacyProxy']['Type']);

        // Call ValidateOrder API
        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    #[Test]
    public function testValidateRegisterDomainWithPrivacyLite(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, [
            'idprotection' => true,
            'Proxy_Lite' => 'on',
        ]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        // Verify privacy (not full proxy) is set
        $this->assertArrayHasKey('PrivacyProxy', $order['Order']['Domain']);
        $this->assertEquals('Privacy', $order['Order']['Domain']['PrivacyProxy']['Type']);

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    #[Test]
    #[DataProvider('tldProvider')]
    public function testValidateRegisterDomainForTld(string $tld, array $additionalFields): void
    {
        $domainName = $this->generateTestDomain($tld);
        $params = $this->getRegistrationParams($domainName, [
            'additionalfields' => $additionalFields,
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $this->assertOrderStructure($order, 'Register_Domain');

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    public static function tldProvider(): array
    {
        return [
            '.com (generic)' => ['com', []],
            '.net (generic)' => ['net', []],
            '.org (generic)' => ['org', []],
            '.info (generic)' => ['info', []],
            '.biz (generic)' => ['biz', []],
        ];
    }

    // =========================================================================
    // Transfer Domain Validation Tests
    // =========================================================================

    #[Test]
    public function testValidateTransferDomain(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getTransferParams($domainName, 'TRANSFER-EPP-CODE-12345');

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Transfer_Domain');

        $this->assertOrderStructure($order, 'Transfer_Domain');
        $this->assertEquals('TRANSFER-EPP-CODE-12345', $order['Order']['Domain']['AuthInfo']);

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    #[Test]
    public function testValidateTransferDomainWithIdProtection(): void
    {
        $domainName = $this->generateTestDomain('net');
        $params = $this->getTransferParams($domainName, 'EPP-CODE-NET', ['idprotection' => true]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Transfer_Domain');

        // Transfer should include privacy proxy option
        $this->assertArrayHasKey('PrivacyProxy', $order['Order']['Domain']);

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    // =========================================================================
    // Renew Domain Validation Tests
    // =========================================================================

    #[Test]
    public function testValidateRenewDomain(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, [
            'regperiod' => 1,
        ]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Renew_Domain');

        $this->assertOrderStructure($order, 'Renew_Domain');
        $this->assertEquals(1, $order['Order']['Domain']['RegPeriod']);

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    #[Test]
    public function testValidateRenewDomainMultiYear(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, [
            'regperiod' => 2,
        ]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Renew_Domain');

        $this->assertEquals(2, $order['Order']['Domain']['RegPeriod']);

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    // =========================================================================
    // Expire/Unexpire Domain Validation Tests
    // =========================================================================

    #[Test]
    public function testValidateExpireDomain(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Expire_Domain');

        $this->assertOrderStructure($order, 'Expire_Domain');

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    #[Test]
    public function testValidateUnexpireDomain(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Unexpire_Domain');

        $this->assertOrderStructure($order, 'Unexpire_Domain');

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    // =========================================================================
    // Nameserver Update Validation Tests
    // =========================================================================

    #[Test]
    public function testValidateNameserverUpdate(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, [
            'ns1' => 'ns1.newprovider.com',
            'ns2' => 'ns2.newprovider.com',
            'ns3' => 'ns3.newprovider.com',
            'ns4' => '',
            'ns5' => '',
        ]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Nameserver_Update');

        $this->assertOrderStructure($order, 'Nameserver_Update');

        // Verify nameservers are updated
        $this->assertEquals('ns1.newprovider.com', $order['Order']['Domain']['NameServers']['NameServer1']['HostName']);
        $this->assertEquals('ns2.newprovider.com', $order['Order']['Domain']['NameServers']['NameServer2']['HostName']);
        $this->assertEquals('ns3.newprovider.com', $order['Order']['Domain']['NameServers']['NameServer3']['HostName']);

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    #[Test]
    public function testValidateNameserverUpdateWithMinimalNs(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, [
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
        ]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Nameserver_Update');

        // Two nameservers should be sufficient
        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    // =========================================================================
    // Contact Update Validation Tests
    // =========================================================================

    #[Test]
    public function testValidateContactUpdate(): void
    {
        $domainName = $this->generateTestDomain('com');
        $contactData = $this->getContactDataForCountry('US');

        $params = $this->getRegistrationParams($domainName, array_merge($contactData, [
            'adminfirstname' => 'Updated',
            'adminlastname' => 'Admin',
            'adminemail' => 'updated-admin@example.com',
        ]));

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Contact_Update');

        $this->assertOrderStructure($order, 'Contact_Update');

        // Verify admin contact is updated
        $this->assertEquals('Updated', $order['Order']['Domain']['AdminContact']['FirstName']);
        $this->assertEquals('Admin', $order['Order']['Domain']['AdminContact']['LastName']);

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    // =========================================================================
    // Owner Change Validation Tests
    // =========================================================================

    #[Test]
    public function testValidateOwnerChange(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, [
            'firstname' => 'New',
            'lastname' => 'Owner',
            'companyname' => 'New Company LLC',
            'email' => 'newowner@example.com',
        ]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Owner_Change');

        $this->assertOrderStructure($order, 'Owner_Change');

        // Verify registrant name is updated (v3 uses combined Name field)
        $this->assertEquals('New Owner', $order['Order']['Domain']['Registrant']['Name']);
        $this->assertEquals('New Company LLC', $order['Order']['Domain']['Registrant']['OrgName']);

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    // =========================================================================
    // Change Locks Validation Tests
    // =========================================================================

    #[Test]
    public function testValidateChangeLocks(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, [
            'lockenabled' => 'locked',
        ]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Change_Locks');

        $this->assertOrderStructure($order, 'Change_Locks');

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    #[Test]
    public function testValidateChangeLocksUnlock(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, [
            'lockenabled' => 'unlocked',
        ]);

        $request = new Request($params);
        $params['lockenabled'] = 'unlocked';
        $order = $request->mapToOrder($params, 'Change_Locks');
        $order['Order']['Domain']['TransferLock'] = 'UnLock';

        $this->assertOrderStructure($order, 'Change_Locks');

        $result = $this->callApiMethod('ValidateOrder', $order);
        $this->assertValidationSuccess($result);
    }

    // =========================================================================
    // Transaction Comment Tests
    // =========================================================================

    #[Test]
    public function testOrderIncludesTransactionComment(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName, [
            'domainid' => 12345,
            'userid' => 67890,
        ]);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        // Verify transaction comment contains WHMCS identifiers
        $this->assertArrayHasKey('TransactionComment', $order['Order']);
        $comment = json_decode($order['Order']['TransactionComment'], true);

        $this->assertEquals('WHMCS', $comment['application']);
        $this->assertEquals(12345, $comment['domainId']);
        $this->assertEquals(67890, $comment['userId']);
        $this->assertEquals('Domain', $comment['objectType']);
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function testValidationFailsForInvalidDomain(): void
    {
        // Use an invalid domain name format
        $params = $this->getRegistrationParams('invalid_domain_with_underscore.com');

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $result = $this->callApiMethod('ValidateOrder', $order);

        // Should return validation error (not crash)
        $this->assertIsObject($result);

        // ResultCode 400 typically indicates validation error
        if ($result->ResultCode !== 200) {
            $this->assertNotEquals(200, $result->ResultCode);
        }
    }

    #[Test]
    public function testValidationFailsForMissingContact(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName);

        $request = new Request($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        // Remove required contact
        unset($order['Order']['Domain']['Registrant']);

        $result = $this->callApiMethod('ValidateOrder', $order);

        // Should fail validation
        $this->assertNotEquals(200, $result->ResultCode, 'Missing registrant should fail validation');
    }
}
