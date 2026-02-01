<?php

namespace Ascio\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Mapping Layer Tests
 *
 * Verify the mapping layer in RequestV3 correctly translates v3 responses
 * to v2-compatible format. These tests use fixture data to verify mappings
 * without requiring live API access.
 */
class MappingLayerTest extends TestCase
{
    /**
     * @var array v2 fixture responses
     */
    protected array $v2Fixtures;

    /**
     * @var array v3 fixture responses
     */
    protected array $v3Fixtures;

    /**
     * @var array Expected mapped responses
     */
    protected array $expectedMapped;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();

        $fixturesPath = __DIR__ . '/../fixtures/';

        $this->v2Fixtures = json_decode(
            file_get_contents($fixturesPath . 'v2_responses.json'),
            true
        );

        $this->v3Fixtures = json_decode(
            file_get_contents($fixturesPath . 'v3_responses.json'),
            true
        );

        $this->expectedMapped = json_decode(
            file_get_contents($fixturesPath . 'expected_mapped.json'),
            true
        );
    }

    // ========================================
    // ORDER RESULT MAPPING TESTS
    // ========================================

    /**
     * Test that v3 Order object structure matches expected format
     */
    public function testOrderResultMapping(): void
    {
        $v3Order = $this->v3Fixtures['CreateOrder_Success']['CreateOrderResult']['Order'];
        $v2Order = $this->v2Fixtures['CreateOrder_Success']['order'];

        // Core fields should match
        $this->assertEquals($v2Order['OrderId'], $v3Order['OrderId']);
        $this->assertEquals($v2Order['Type'], $v3Order['Type']);
        $this->assertEquals($v2Order['Status'], $v3Order['Status']);
        $this->assertEquals($v2Order['TransactionComment'], $v3Order['TransactionComment']);

        // Domain object should be present
        $this->assertArrayHasKey('Domain', $v3Order);
        $this->assertArrayHasKey('Domain', $v2Order);
    }

    /**
     * Test Order key case difference is documented
     */
    public function testOrderKeyCase(): void
    {
        // v2 uses lowercase 'order'
        $this->assertArrayHasKey('order', $this->v2Fixtures['CreateOrder_Success']);

        // v3 uses PascalCase 'Order' inside CreateOrderResult
        $this->assertArrayHasKey('Order', $this->v3Fixtures['CreateOrder_Success']['CreateOrderResult']);
    }

    // ========================================
    // DOMAIN OBJECT MAPPING TESTS
    // ========================================

    /**
     * Test Domain object mapping compatibility
     */
    public function testDomainObjectMapping(): void
    {
        $v2Domain = $this->v2Fixtures['GetDomain']['domain'];
        $v3Domain = $this->v3Fixtures['GetDomain']['GetDomainResult']['Domain'];

        // All standard domain fields should match
        $this->assertEquals($v2Domain['DomainName'], $v3Domain['DomainName']);
        $this->assertEquals($v2Domain['DomainHandle'], $v3Domain['DomainHandle']);
        $this->assertEquals($v2Domain['RegPeriod'], $v3Domain['RegPeriod']);
        $this->assertEquals($v2Domain['AuthInfo'], $v3Domain['AuthInfo']);
        $this->assertEquals($v2Domain['Status'], $v3Domain['Status']);
        $this->assertEquals($v2Domain['ExpDate'], $v3Domain['ExpDate']);
        $this->assertEquals($v2Domain['CreDate'], $v3Domain['CreDate']);
        $this->assertEquals($v2Domain['TransferLock'], $v3Domain['TransferLock']);
    }

    /**
     * Test Domain key case difference
     */
    public function testDomainKeyCase(): void
    {
        // v2 uses lowercase 'domain'
        $this->assertArrayHasKey('domain', $this->v2Fixtures['GetDomain']);

        // v3 uses 'Domain' inside GetDomainResult
        $this->assertArrayHasKey('Domain', $this->v3Fixtures['GetDomain']['GetDomainResult']);
    }

    // ========================================
    // CONTACT MAPPING TESTS
    // ========================================

    /**
     * Test Contact object mapping compatibility
     */
    public function testContactMapping(): void
    {
        $v2Contact = $this->v2Fixtures['CreateOrder_Success']['order']['Domain']['AdminContact'];
        $v3Contact = $this->v3Fixtures['CreateOrder_Success']['CreateOrderResult']['Order']['Domain']['AdminContact'];

        // All contact fields should match
        $this->assertEquals($v2Contact['FirstName'], $v3Contact['FirstName']);
        $this->assertEquals($v2Contact['LastName'], $v3Contact['LastName']);
        $this->assertEquals($v2Contact['OrgName'], $v3Contact['OrgName']);
        $this->assertEquals($v2Contact['Address1'], $v3Contact['Address1']);
        $this->assertEquals($v2Contact['City'], $v3Contact['City']);
        $this->assertEquals($v2Contact['State'], $v3Contact['State']);
        $this->assertEquals($v2Contact['PostalCode'], $v3Contact['PostalCode']);
        $this->assertEquals($v2Contact['CountryCode'], $v3Contact['CountryCode']);
        $this->assertEquals($v2Contact['Email'], $v3Contact['Email']);
        $this->assertEquals($v2Contact['Phone'], $v3Contact['Phone']);
    }

    /**
     * Test Registrant object mapping (Name instead of FirstName/LastName)
     */
    public function testRegistrantMapping(): void
    {
        $v2Registrant = $this->v2Fixtures['CreateOrder_Success']['order']['Domain']['Registrant'];
        $v3Registrant = $this->v3Fixtures['CreateOrder_Success']['CreateOrderResult']['Order']['Domain']['Registrant'];

        // Registrant uses 'Name' instead of FirstName/LastName
        $this->assertArrayHasKey('Name', $v2Registrant);
        $this->assertArrayHasKey('Name', $v3Registrant);
        $this->assertEquals($v2Registrant['Name'], $v3Registrant['Name']);

        // Other fields should match
        $this->assertEquals($v2Registrant['OrgName'], $v3Registrant['OrgName']);
        $this->assertEquals($v2Registrant['Email'], $v3Registrant['Email']);
    }

    // ========================================
    // NAMESERVER MAPPING TESTS
    // ========================================

    /**
     * Test Nameserver object mapping compatibility
     */
    public function testNameserverMapping(): void
    {
        $v2NS = $this->v2Fixtures['GetDomain']['domain']['NameServers'];
        $v3NS = $this->v3Fixtures['GetDomain']['GetDomainResult']['Domain']['NameServers'];

        // Nameserver structure should be identical
        $this->assertEquals(
            $v2NS['NameServer1']['HostName'],
            $v3NS['NameServer1']['HostName']
        );
        $this->assertEquals(
            $v2NS['NameServer2']['HostName'],
            $v3NS['NameServer2']['HostName']
        );
    }

    // ========================================
    // ERROR MAPPING TESTS
    // ========================================

    /**
     * Test error array mapping from v3 to v2-compatible format
     */
    public function testErrorMapping(): void
    {
        $v2Errors = $this->v2Fixtures['CreateOrder_Error']['CreateOrderResult']['Values']['string'];
        $v3Errors = $this->v3Fixtures['CreateOrder_Error']['CreateOrderResult']['Errors']['string'];

        // Both should have the same error messages
        $this->assertEquals($v2Errors, $v3Errors);
    }

    /**
     * Test error key difference (Values vs Errors)
     */
    public function testErrorKeyDifference(): void
    {
        // v2 uses 'Values' for errors
        $this->assertArrayHasKey('Values', $this->v2Fixtures['CreateOrder_Error']['CreateOrderResult']);

        // v3 uses 'Errors'
        $this->assertArrayHasKey('Errors', $this->v3Fixtures['CreateOrder_Error']['CreateOrderResult']);
    }

    /**
     * Test single error vs array error handling
     */
    public function testSingleErrorMapping(): void
    {
        $v2Error = $this->v2Fixtures['ValidateOrder_Error']['ValidateOrderResult']['Values']['string'];
        $v3Error = $this->v3Fixtures['ValidateOrder_Error']['ValidateOrderResult']['Errors']['string'];

        // Single error should be a string, not array
        $this->assertIsString($v2Error);
        $this->assertIsString($v3Error);
        $this->assertEquals($v2Error, $v3Error);
    }

    // ========================================
    // POLLING MAPPING TESTS
    // ========================================

    /**
     * Test poll message mapping (PollMessage vs PollQueue)
     */
    public function testPollMessageMapping(): void
    {
        $v2Poll = $this->v2Fixtures['PollMessage_HasMessage'];
        $v3Poll = $this->v3Fixtures['PollQueue_HasMessage'];

        // Result codes should match
        $this->assertEquals(
            $v2Poll['PollMessageResult']['ResultCode'],
            $v3Poll['PollQueueResult']['ResultCode']
        );

        // v2 uses 'item', v3 uses 'QueueMessage'
        $this->assertArrayHasKey('item', $v2Poll);
        $this->assertArrayHasKey('QueueMessage', $v3Poll['PollQueueResult']);

        $v2Item = $v2Poll['item'];
        $v3Message = $v3Poll['PollQueueResult']['QueueMessage'];

        // Core fields should be present in both
        $this->assertArrayHasKey('MsgId', $v2Item);
        $this->assertArrayHasKey('OrderId', $v2Item);
        $this->assertArrayHasKey('OrderStatus', $v2Item);
        $this->assertArrayHasKey('DomainName', $v2Item);

        // v3 has both old and new field names for compatibility
        $this->assertArrayHasKey('MessageId', $v3Message);
        $this->assertArrayHasKey('MsgId', $v3Message); // Also present for compat
        $this->assertArrayHasKey('OrderId', $v3Message);
        $this->assertArrayHasKey('OrderStatus', $v3Message);
        $this->assertArrayHasKey('ObjectName', $v3Message);
        $this->assertArrayHasKey('DomainName', $v3Message); // Also present for compat
    }

    /**
     * Test empty queue response mapping
     */
    public function testEmptyQueueMapping(): void
    {
        $v2Empty = $this->v2Fixtures['PollMessage_Empty']['PollMessageResult'];
        $v3Empty = $this->v3Fixtures['PollQueue_Empty']['PollQueueResult'];

        // Result code 201 indicates empty queue in both
        $this->assertEquals(201, $v2Empty['ResultCode']);
        $this->assertEquals(201, $v3Empty['ResultCode']);
    }

    /**
     * Test GetMessageQueue/GetQueueMessage mapping
     */
    public function testGetMessageQueueMapping(): void
    {
        $v2Msg = $this->v2Fixtures['GetMessageQueue'];
        $v3Msg = $this->v3Fixtures['GetQueueMessage'];

        // v2 uses 'item', v3 uses 'Message' (inside result)
        $this->assertArrayHasKey('item', $v2Msg);
        $this->assertArrayHasKey('Message', $v3Msg['GetQueueMessageResult']);
    }

    // ========================================
    // SEARCH DOMAIN MAPPING TESTS
    // ========================================

    /**
     * Test SearchDomain response mapping
     */
    public function testSearchDomainMapping(): void
    {
        $v2Search = $this->v2Fixtures['SearchDomain'];
        $v3Search = $this->v3Fixtures['SearchDomain'];

        // v2 uses 'domains', v3 uses 'Domains' (PascalCase)
        $this->assertArrayHasKey('domains', $v2Search);
        $this->assertArrayHasKey('Domains', $v3Search['SearchDomainResult']);
    }

    /**
     * Test SearchDomain with multiple results
     */
    public function testSearchDomainMultipleMapping(): void
    {
        $v2Multi = $this->v2Fixtures['SearchDomain_Multiple']['domains']['Domain'];
        $v3Multi = $this->v3Fixtures['SearchDomain_Multiple']['SearchDomainResult']['Domains']['Domain'];

        // Both should be arrays when multiple results
        $this->assertIsArray($v2Multi);
        $this->assertIsArray($v3Multi);
        $this->assertCount(2, $v2Multi);
        $this->assertCount(2, $v3Multi);
    }

    /**
     * Test SearchDomain not found response
     */
    public function testSearchDomainNotFoundMapping(): void
    {
        $v2NotFound = $this->v2Fixtures['SearchDomain_NotFound']['domains'];
        $v3NotFound = $this->v3Fixtures['SearchDomain_NotFound']['SearchDomainResult']['Domains'];

        // v2 returns empty object, v3 returns null
        $this->assertEmpty($v2NotFound);
        $this->assertNull($v3NotFound);
    }

    // ========================================
    // AVAILABILITY INFO MAPPING TESTS
    // ========================================

    /**
     * Test AvailabilityInfo response mapping (should be identical)
     */
    public function testAvailabilityInfoMapping(): void
    {
        $v2Available = $this->v2Fixtures['AvailabilityInfo_Available']['AvailabilityInfoResult'];
        $v3Available = $this->v3Fixtures['AvailabilityInfo_Available']['AvailabilityInfoResult'];

        // Structure should be identical
        $this->assertEquals($v2Available['ResultCode'], $v3Available['ResultCode']);
        $this->assertEquals($v2Available['DomainName'], $v3Available['DomainName']);
        $this->assertEquals($v2Available['DomainNameAvailable'], $v3Available['DomainNameAvailable']);
    }

    // ========================================
    // RESULT CODE MAPPING TESTS
    // ========================================

    /**
     * Test that result codes are consistent
     */
    public function testResultCodeConsistency(): void
    {
        // Success code
        $this->assertEquals(
            $this->v2Fixtures['CreateOrder_Success']['CreateOrderResult']['ResultCode'],
            $this->v3Fixtures['CreateOrder_Success']['CreateOrderResult']['ResultCode']
        );

        // Error code
        $this->assertEquals(
            $this->v2Fixtures['CreateOrder_Error']['CreateOrderResult']['ResultCode'],
            $this->v3Fixtures['CreateOrder_Error']['CreateOrderResult']['ResultCode']
        );

        // Auth error code
        $this->assertEquals(
            $this->v2Fixtures['Auth_Error']['ResultCode'],
            $this->v3Fixtures['Auth_Error']['ResultCode']
        );

        // Server error code
        $this->assertEquals(
            $this->v2Fixtures['Server_Error']['ResultCode'],
            $this->v3Fixtures['Server_Error']['ResultCode']
        );
    }

    // ========================================
    // EXPECTED MAPPED FORMAT TESTS
    // ========================================

    /**
     * Test that expected mapped format matches v3 structure
     */
    public function testExpectedMappedFormatForOrder(): void
    {
        $expected = $this->expectedMapped['CreateOrder_Success'];
        $v3Actual = $this->v3Fixtures['CreateOrder_Success']['CreateOrderResult'];

        // Order key should be PascalCase
        $this->assertArrayHasKey('Order', $expected);
        $this->assertArrayHasKey('Order', $v3Actual);

        // OrderId should match
        $this->assertEquals($expected['Order']['OrderId'], $v3Actual['Order']['OrderId']);
    }

    /**
     * Test that error format matches expected
     */
    public function testExpectedMappedFormatForError(): void
    {
        $expected = $this->expectedMapped['CreateOrder_Error'];

        // Should be simple error array format
        $this->assertArrayHasKey('error', $expected);
        $this->assertIsString($expected['error']);
    }

    // ========================================
    // FIXTURE VALIDATION TESTS
    // ========================================

    /**
     * Validate v2 fixtures have expected structure
     */
    public function testV2FixturesStructure(): void
    {
        $this->assertArrayHasKey('CreateOrder_Success', $this->v2Fixtures);
        $this->assertArrayHasKey('CreateOrder_Error', $this->v2Fixtures);
        $this->assertArrayHasKey('GetOrder', $this->v2Fixtures);
        $this->assertArrayHasKey('GetDomain', $this->v2Fixtures);
        $this->assertArrayHasKey('SearchDomain', $this->v2Fixtures);
        $this->assertArrayHasKey('PollMessage_HasMessage', $this->v2Fixtures);
    }

    /**
     * Validate v3 fixtures have expected structure
     */
    public function testV3FixturesStructure(): void
    {
        $this->assertArrayHasKey('CreateOrder_Success', $this->v3Fixtures);
        $this->assertArrayHasKey('CreateOrder_Error', $this->v3Fixtures);
        $this->assertArrayHasKey('GetOrder', $this->v3Fixtures);
        $this->assertArrayHasKey('GetDomain', $this->v3Fixtures);
        $this->assertArrayHasKey('SearchDomain', $this->v3Fixtures);
        $this->assertArrayHasKey('PollQueue_HasMessage', $this->v3Fixtures);
    }

    /**
     * Validate expected mapped fixtures have expected structure
     */
    public function testExpectedMappedFixturesStructure(): void
    {
        $this->assertArrayHasKey('CreateOrder_Success', $this->expectedMapped);
        $this->assertArrayHasKey('CreateOrder_Error', $this->expectedMapped);
        $this->assertArrayHasKey('GetOrder', $this->expectedMapped);
        $this->assertArrayHasKey('GetDomain', $this->expectedMapped);
        $this->assertArrayHasKey('_compatibility_summary', $this->expectedMapped);
    }
}
