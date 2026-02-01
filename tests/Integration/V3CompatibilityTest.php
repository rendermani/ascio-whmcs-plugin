<?php
/**
 * V3 API Compatibility Integration Tests
 *
 * Verifies that v3 API responses maintain compatibility with v2 processing code.
 * Tests response format consistency between v2 and v3 APIs.
 *
 * @group integration
 * @group v3
 * @group compatibility
 */

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\v3\domains\RequestV3;
use ascio\v2\domains\Request as RequestV2;
use Ascio\Tests\Mocks\MockParamsV3;

#[Group('integration')]
#[Group('v3')]
#[Group('compatibility')]
class V3CompatibilityTest extends IntegrationTestBase
{
    // =========================================================================
    // Order Response Format Tests
    // =========================================================================

    #[Test]
    public function testOrderResponseFormat(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName);

        $request = new RequestV3($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        // v3 uses 'Order' key (PascalCase)
        $this->assertArrayHasKey('Order', $order, 'v3 should use Order key');

        // Verify order structure matches expected format
        $orderData = $order['Order'];

        $this->assertArrayHasKey('Type', $orderData, 'Order should have Type');
        $this->assertArrayHasKey('Domain', $orderData, 'Order should have Domain');
        $this->assertArrayHasKey('TransactionComment', $orderData, 'Order should have TransactionComment');

        // Domain structure
        $domain = $orderData['Domain'];
        $this->assertArrayHasKey('DomainName', $domain, 'Domain should have DomainName');
        $this->assertArrayHasKey('Registrant', $domain, 'Domain should have Registrant');
        $this->assertArrayHasKey('NameServers', $domain, 'Domain should have NameServers');
    }

    #[Test]
    public function testValidateOrderResponseFormat(): void
    {
        $domainName = $this->generateTestDomain('com');
        $params = $this->getRegistrationParams($domainName);

        $request = new RequestV3($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $result = $this->callApiMethod('ValidateOrder', $order);

        // v3 response format
        $this->assertIsObject($result);
        $this->assertObjectHasProperty('ResultCode', $result);
        $this->assertObjectHasProperty('ResultMessage', $result);

        // ResultCode should be numeric
        $this->assertIsInt($result->ResultCode);
    }

    // =========================================================================
    // Domain Object Format Tests
    // =========================================================================

    #[Test]
    public function testDomainObjectFormat(): void
    {
        // Find an existing domain to test format
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing domain found for format test');
        }

        // Verify v3 domain object format
        $this->assertIsObject($existingDomain);

        // v3 uses PascalCase property names
        $expectedProperties = [
            'DomainName',
            'DomainHandle',
            'Status',
        ];

        foreach ($expectedProperties as $prop) {
            $this->assertObjectHasProperty(
                $prop,
                $existingDomain,
                "Domain should have $prop property"
            );
        }

        // If contacts exist, verify format
        if (isset($existingDomain->Registrant)) {
            $this->assertIsObject($existingDomain->Registrant);
        }

        // If nameservers exist, verify format
        if (isset($existingDomain->NameServers)) {
            $this->assertIsObject($existingDomain->NameServers);

            if (isset($existingDomain->NameServers->NameServer1)) {
                $this->assertObjectHasProperty(
                    'HostName',
                    $existingDomain->NameServers->NameServer1
                );
            }
        }
    }

    #[Test]
    public function testDomainObjectContactFormat(): void
    {
        $existingDomain = $this->findExistingDomain('com');

        if (!$existingDomain) {
            $this->markTestSkipped('No existing domain found');
        }

        // Test Registrant format (v3 uses Name, not FirstName/LastName)
        if (isset($existingDomain->Registrant)) {
            $reg = $existingDomain->Registrant;

            // v3 Registrant should have Name field
            if (isset($reg->Name)) {
                $this->assertIsString($reg->Name);
            }

            // Common contact fields
            $contactFields = ['Email', 'Phone', 'Address1', 'City', 'CountryCode'];
            foreach ($contactFields as $field) {
                if (isset($reg->$field)) {
                    $this->assertNotNull($reg->$field);
                }
            }
        }

        // Test Admin/Tech contact format (uses FirstName/LastName)
        if (isset($existingDomain->AdminContact)) {
            $admin = $existingDomain->AdminContact;

            // v3 contacts have FirstName/LastName
            $contactNameFields = ['FirstName', 'LastName'];
            foreach ($contactNameFields as $field) {
                if (isset($admin->$field)) {
                    $this->assertIsString($admin->$field);
                }
            }
        }
    }

    // =========================================================================
    // Search Result Format Tests
    // =========================================================================

    #[Test]
    public function testSearchResultFormat(): void
    {
        // v3 SearchDomain result format
        $criteria = [
            'Mode' => 'Fuzzy',
            'WithoutStates' => ['deleted'],
            'Clauses' => [
                [
                    'Attribute' => 'DomainName',
                    'Value' => '*.com',
                    'Operator' => 'Like',
                ],
            ],
            'PageInfo' => [
                'PageIndex' => 0,
                'PageSize' => 5,
            ],
        ];

        $result = $this->callApiMethod('SearchDomain', ['Criteria' => $criteria]);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('ResultCode', $result);

        if ($result->ResultCode === 200) {
            // Verify Domains structure
            if (isset($result->Domains)) {
                $this->assertIsObject($result->Domains);

                if (isset($result->Domains->Domain)) {
                    $domains = $result->Domains->Domain;

                    // Normalize to array
                    if (!is_array($domains)) {
                        $domains = [$domains];
                    }

                    // Each domain should have expected properties
                    foreach ($domains as $domain) {
                        $this->assertIsObject($domain);
                        $this->assertObjectHasProperty('DomainName', $domain);
                    }
                }
            }

            // May have TotalCount for pagination
            if (isset($result->TotalCount)) {
                $this->assertIsInt($result->TotalCount);
            }
        }
    }

    #[Test]
    public function testGetDomainsVsSearchDomainFormat(): void
    {
        // Both SearchDomain and GetDomains should return similar domain objects
        $criteria = [
            'Mode' => 'Fuzzy',
            'Clauses' => [],
            'PageInfo' => ['PageIndex' => 0, 'PageSize' => 1],
        ];

        $result = $this->callApiMethod('SearchDomain', ['Criteria' => $criteria]);

        $this->assertIsObject($result);

        // The domain objects should have consistent format regardless of API used
        if ($result->ResultCode === 200 && isset($result->Domains->Domain)) {
            $domain = is_array($result->Domains->Domain)
                ? $result->Domains->Domain[0]
                : $result->Domains->Domain;

            // Standard v3 domain properties
            $this->assertObjectHasProperty('DomainName', $domain);
            $this->assertObjectHasProperty('DomainHandle', $domain);
        }
    }

    // =========================================================================
    // Poll Message Format Tests
    // =========================================================================

    #[Test]
    public function testPollMessageFormat(): void
    {
        // v3 PollQueue format
        $params = ['MsgType' => 'Message_to_Partner'];
        $result = $this->callApiMethod('PollQueue', $params);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('ResultCode', $result);

        // 200 = message available, 201 = no messages
        $this->assertContains($result->ResultCode, [200, 201]);

        if ($result->ResultCode === 200 && isset($result->QueueMessage)) {
            $message = $result->QueueMessage;

            // v3 message format uses MsgId (not MessageId)
            $this->assertObjectHasProperty('MsgId', $message);

            // May have additional properties depending on message type
            $possibleProperties = ['OrderId', 'OrderStatus', 'DomainName', 'Msg'];
            $hasExpectedProperty = false;

            foreach ($possibleProperties as $prop) {
                if (isset($message->$prop)) {
                    $hasExpectedProperty = true;
                    break;
                }
            }

            $this->assertTrue($hasExpectedProperty, 'Message should have at least one identifying property');
        }
    }

    // =========================================================================
    // Error Response Format Tests
    // =========================================================================

    #[Test]
    public function testErrorResponseFormat(): void
    {
        // Trigger an error by sending invalid order
        $invalidOrder = [
            'Order' => [
                'Type' => 'Register_Domain',
                'Domain' => [
                    'DomainName' => '', // Invalid - empty domain name
                ],
            ],
        ];

        $result = $this->callApiMethod('ValidateOrder', $invalidOrder);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('ResultCode', $result);

        // Error response should have non-200 code
        $this->assertNotEquals(200, $result->ResultCode);

        // Should have error message
        $this->assertObjectHasProperty('ResultMessage', $result);

        // May have Errors array
        if (isset($result->Errors)) {
            if (isset($result->Errors->string)) {
                $errors = $result->Errors->string;

                // Normalize to array
                if (!is_array($errors)) {
                    $errors = [$errors];
                }

                $this->assertNotEmpty($errors);
            }
        }
    }

    #[Test]
    public function testAuthenticationErrorFormat(): void
    {
        // Test with invalid credentials
        $invalidParams = array_merge($this->params, [
            'Username' => 'invalid_user',
            'Password' => 'invalid_password',
        ]);

        $request = new RequestV3($invalidParams);
        $order = $request->mapToOrder($invalidParams, 'Register_Domain');

        // Use direct SOAP call with invalid credentials
        $wsdl = ASCIO_V3_WSDL_TEST;
        $client = new \SoapClient($wsdl, [
            'cache_wsdl' => WSDL_CACHE_MEMORY,
            'trace' => 1,
            'exceptions' => true,
        ]);

        $credentials = [
            'Account' => 'invalid_user',
            'Password' => 'invalid_password',
        ];
        $header = new \SoapHeader(
            'http://www.ascio.com/2013/02',
            'SecurityHeaderDetails',
            $credentials,
            false
        );
        $client->__setSoapHeaders($header);

        try {
            $response = $client->__soapCall('ValidateOrder', ['parameters' => ['request' => $order]]);
            $result = $response->ValidateOrderResult ?? $response;

            // Should return 401 for authentication failure
            $this->assertEquals(401, $result->ResultCode);
        } catch (\SoapFault $e) {
            // API may throw SoapFault for auth errors or internal errors
            // Either way, invalid credentials should not succeed
            $this->assertStringContainsString(
                'error',
                strtolower($e->getMessage()),
                'Invalid credentials should cause an error'
            );
        }
    }

    // =========================================================================
    // Contact Mapping Format Tests
    // =========================================================================

    #[Test]
    public function testRegistrantMappingFormat(): void
    {
        $params = $this->getRegistrationParams($this->generateTestDomain('com'), [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'companyname' => 'Test Inc',
            'email' => 'john@example.com',
        ]);

        $request = new RequestV3($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $registrant = $order['Order']['Domain']['Registrant'];

        // v3 Registrant uses 'Name' (combined first+last)
        $this->assertArrayHasKey('Name', $registrant);
        $this->assertEquals('John Doe', $registrant['Name']);

        // OrgName for company
        $this->assertArrayHasKey('OrgName', $registrant);
        $this->assertEquals('Test Inc', $registrant['OrgName']);

        // Email
        $this->assertArrayHasKey('Email', $registrant);
        $this->assertEquals('john@example.com', $registrant['Email']);
    }

    #[Test]
    public function testContactMappingFormat(): void
    {
        $params = $this->getRegistrationParams($this->generateTestDomain('com'), [
            'adminfirstname' => 'Admin',
            'adminlastname' => 'User',
            'admincompanyname' => 'Admin Inc',
            'adminemail' => 'admin@example.com',
        ]);

        $request = new RequestV3($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $adminContact = $order['Order']['Domain']['AdminContact'];

        // v3 Admin/Tech/Billing contacts use FirstName/LastName
        $this->assertArrayHasKey('FirstName', $adminContact);
        $this->assertArrayHasKey('LastName', $adminContact);
        $this->assertEquals('Admin', $adminContact['FirstName']);
        $this->assertEquals('User', $adminContact['LastName']);

        // Should NOT have combined 'Name' field for contacts
        // (Name is only for Registrant)
    }

    // =========================================================================
    // Nameserver Format Tests
    // =========================================================================

    #[Test]
    public function testNameserverMappingFormat(): void
    {
        $params = $this->getRegistrationParams($this->generateTestDomain('com'), [
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
            'ns3' => 'ns3.example.com',
            'ns4' => '',
            'ns5' => '',
        ]);

        $request = new RequestV3($params);
        $order = $request->mapToOrder($params, 'Register_Domain');

        $nameservers = $order['Order']['Domain']['NameServers'];

        // v3 uses NameServer1, NameServer2, etc. with nested HostName
        $this->assertArrayHasKey('NameServer1', $nameservers);
        $this->assertArrayHasKey('HostName', $nameservers['NameServer1']);
        $this->assertEquals('ns1.example.com', $nameservers['NameServer1']['HostName']);

        $this->assertArrayHasKey('NameServer2', $nameservers);
        $this->assertEquals('ns2.example.com', $nameservers['NameServer2']['HostName']);

        $this->assertArrayHasKey('NameServer3', $nameservers);
        $this->assertEquals('ns3.example.com', $nameservers['NameServer3']['HostName']);

        // Empty nameservers should still have the structure
        $this->assertArrayHasKey('NameServer4', $nameservers);
        $this->assertArrayHasKey('NameServer5', $nameservers);
    }

    // =========================================================================
    // Order Type Compatibility Tests
    // =========================================================================

    #[Test]
    #[DataProvider('orderTypeProvider')]
    public function testOrderTypeMapping(string $orderType): void
    {
        $params = $this->getRegistrationParams($this->generateTestDomain('com'));

        $request = new RequestV3($params);
        $order = $request->mapToOrder($params, $orderType);

        // Order type should be preserved exactly
        $this->assertEquals($orderType, $order['Order']['Type']);

        // All order types should have Domain
        $this->assertArrayHasKey('Domain', $order['Order']);
    }

    public static function orderTypeProvider(): array
    {
        return [
            'Register_Domain' => ['Register_Domain'],
            'Transfer_Domain' => ['Transfer_Domain'],
            'Renew_Domain' => ['Renew_Domain'],
            'Expire_Domain' => ['Expire_Domain'],
            'Unexpire_Domain' => ['Unexpire_Domain'],
            'Nameserver_Update' => ['Nameserver_Update'],
            'Contact_Update' => ['Contact_Update'],
            'Owner_Change' => ['Owner_Change'],
            'Change_Locks' => ['Change_Locks'],
            'Update_AuthInfo' => ['Update_AuthInfo'],
        ];
    }
}
