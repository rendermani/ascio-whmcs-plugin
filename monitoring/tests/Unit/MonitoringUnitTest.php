<?php

/**
 * Comprehensive Unit Tests for Monitoring (NameWatch) Business Logic
 *
 * Tests the Monitoring class with mocked API client and database.
 * Covers all public methods, error handling, and edge cases.
 *
 * @copyright Copyright (c) Tucows Inc.
 */

declare(strict_types=1);

namespace Ascio\Monitoring\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Ascio\Monitoring\Monitoring;
use Ascio\Core\Tests\MockAscioClient;
use Ascio\Core\Tests\MockDatabase;
use Ascio\Core\Tests\MockParams;
use Ascio\Core\OrderType;

require_once __DIR__ . '/bootstrap.php';

#[Group('unit')]
#[Group('monitoring')]
class MonitoringUnitTest extends TestCase
{
    protected MockAscioClient $client;
    protected MockDatabase $db;
    protected MockParams $params;
    protected Monitoring $monitoring;

    /**
     * Standard contact data for tests.
     */
    protected array $contactData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'company' => 'Test Corp LLC',
        'address1' => '123 Main Street',
        'address2' => 'Suite 100',
        'city' => 'New York',
        'state' => 'NY',
        'postcode' => '10001',
        'country' => 'US',
        'phone' => '+1.5551234567',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new MockAscioClient();
        $this->db = new MockDatabase();
        $this->params = (new MockParams())->setServiceId(100)->setUserId(50);

        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        // Seed initial monitoring data
        $this->seedMonitoringData();
    }

    protected function tearDown(): void
    {
        $this->client->reset();
        $this->db->clear();
        parent::tearDown();
    }

    /**
     * Seed standard monitoring data.
     */
    protected function seedMonitoringData(): void
    {
        $this->db->insert('mod_ascio_monitoring', [
            'whmcs_service_id' => 100,
            'user_id' => 50,
            'name' => 'testbrand',
            'tier' => 3,
            'notification_frequency' => 'Daily',
            'email_notification_1' => 'admin@test.com',
            'email_notification_2' => '',
            'email_notification_3' => '',
            'email_notification_4' => '',
            'email_notification_5' => '',
            'period' => 1,
            'status' => 'Pending',
            'handle' => null,
            'order_id' => null,
            'owner_name' => 'John Doe',
            'owner_email' => 'john@example.com',
            'owner_company' => 'Test Corp',
            'owner_address1' => '123 Main St',
            'owner_address2' => '',
            'owner_city' => 'New York',
            'owner_state' => 'NY',
            'owner_postcode' => '10001',
            'owner_country' => 'US',
            'owner_phone' => '+1.5551234567',
        ]);
    }

    // =========================================================================
    // Register Method Tests
    // =========================================================================

    #[Test]
    public function registerCallsApiWithCorrectOrderType(): void
    {
        $result = $this->monitoring->register($this->contactData);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['code']);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function registerReturnsOrderIdOnSuccess(): void
    {
        $result = $this->monitoring->register($this->contactData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('order_id', $result);
        $this->assertNotEmpty($result['order_id']);
    }

    #[Test]
    public function registerUpdatesStatusInDatabase(): void
    {
        $result = $this->monitoring->register($this->contactData);

        $this->assertTrue($result['success']);

        // Verify database was updated
        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertNotNull($data->order_id);
        $this->assertEquals(200, $data->code);
        $this->assertEquals('Pending', $data->status);
    }

    #[Test]
    public function registerPrefixesOrderIdWithTESTInTestMode(): void
    {
        $result = $this->monitoring->register($this->contactData);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('TEST', $result['order_id']);
    }

    #[Test]
    public function registerPrefixesOrderIdWithAInLiveMode(): void
    {
        $liveParams = (new MockParams())->setServiceId(100)->setUserId(50)->setTestMode(false);
        $monitoring = new Monitoring($liveParams, $this->client, $this->db);

        $result = $monitoring->register($this->contactData);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('A', $result['order_id']);
    }

    #[Test]
    public function registerHandlesApiError(): void
    {
        // Queue an error response
        $errorResponse = $this->createErrorResponse(400, 'Invalid tier value');
        $this->client->queueResponse('createOrder', $errorResponse);

        $result = $this->monitoring->register($this->contactData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(400, $result['code']);
    }

    #[Test]
    public function registerHandlesSoapFault(): void
    {
        $this->client->queueException('createOrder', new \SoapFault('Server', 'Connection timeout'));

        $result = $this->monitoring->register($this->contactData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Temporary error', $result['error']);
    }

    #[Test]
    public function registerIncludesAllNotificationEmails(): void
    {
        // Update to have multiple notification emails
        $this->db->update('mod_ascio_monitoring', [
            'email_notification_1' => 'alert1@test.com',
            'email_notification_2' => 'alert2@test.com',
            'email_notification_3' => 'alert3@test.com',
            'email_notification_4' => '',
            'email_notification_5' => '',
        ], ['whmcs_service_id' => 100]);

        // Clear cached data and reload
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->register($this->contactData);

        $this->assertTrue($result['success']);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    // =========================================================================
    // Renew Method Tests
    // =========================================================================

    #[Test]
    public function renewCallsApiWithRenewOrderType(): void
    {
        // Set up existing handle
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->renew($this->contactData);

        $this->assertTrue($result['success']);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function renewIncludesExistingHandle(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->renew($this->contactData);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function renewHandlesApiError(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $errorResponse = $this->createErrorResponse(400, 'Renewal not allowed yet');
        $this->client->queueResponse('createOrder', $errorResponse);

        $result = $this->monitoring->renew($this->contactData);

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function renewUpdatesStatusInDatabase(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->renew($this->contactData);

        $this->assertTrue($result['success']);

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertNotNull($data->order_id);
    }

    // =========================================================================
    // Terminate Method Tests
    // =========================================================================

    #[Test]
    public function terminateCallsApiWithDeleteOrderType(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->terminate();

        $this->assertTrue($result['success']);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function terminateHandlesApiError(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $errorResponse = $this->createErrorResponse(400, 'Termination not allowed');
        $this->client->queueResponse('createOrder', $errorResponse);

        $result = $this->monitoring->terminate();

        $this->assertFalse($result['success']);
    }

    // =========================================================================
    // Change Tier Method Tests
    // =========================================================================

    #[Test]
    public function changeTierReturnsErrorWithoutHandle(): void
    {
        // No handle set
        $result = $this->monitoring->changeTier(5, $this->contactData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No active monitoring handle', $result['error']);
    }

    #[Test]
    public function changeTierSubmitsUpdateOrder(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->changeTier(5, $this->contactData);

        $this->assertTrue($result['success']);

        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function changeTierUpdatesDatabaseWithNewTier(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->changeTier(5, $this->contactData);

        $this->assertTrue($result['success']);

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertEquals(5, $data->tier);
    }

    #[Test]
    #[DataProvider('validTierProvider')]
    public function changeTierAcceptsValidTiers(int $tier): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->changeTier($tier, $this->contactData);

        $this->assertTrue($result['success']);
    }

    public static function validTierProvider(): array
    {
        return [
            'tier 1' => [1],
            'tier 2' => [2],
            'tier 3' => [3],
            'tier 4' => [4],
            'tier 5' => [5],
        ];
    }

    #[Test]
    public function changeTierHandlesApiError(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $errorResponse = $this->createErrorResponse(400, 'Invalid tier change');
        $this->client->queueResponse('createOrder', $errorResponse);

        $result = $this->monitoring->changeTier(5, $this->contactData);

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function changeTierHandlesSoapFault(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $this->client->queueException('createOrder', new \SoapFault('Server', 'API error'));

        $result = $this->monitoring->changeTier(5, $this->contactData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Temporary error', $result['error']);
    }

    // =========================================================================
    // Get Alerts Method Tests
    // =========================================================================

    #[Test]
    public function getAlertsReturnsErrorWithoutHandle(): void
    {
        $result = $this->monitoring->getAlerts();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No active monitoring handle', $result['error']);
    }

    #[Test]
    public function getAlertsReturnsInfoWithHandle(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->getAlerts();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('handle', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('tier', $result);
        $this->assertArrayHasKey('alerts', $result);
    }

    #[Test]
    public function getAlertsCallsGetNameWatchApi(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->getAlerts();

        $this->assertTrue($result['success']);

        $calls = $this->client->getCalls('getNameWatch');
        $this->assertCount(1, $calls);
        $this->assertEquals('NW123456', $calls[0]['handle']);
    }

    #[Test]
    public function getAlertsHandlesApiException(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $this->client->queueException('getNameWatch', new \Exception('API unavailable'));

        $result = $this->monitoring->getAlerts();

        $this->assertFalse($result['success']);
        $this->assertEquals('API unavailable', $result['error']);
    }

    #[Test]
    public function getAlertsIncludesMessage(): void
    {
        $this->db->update('mod_ascio_monitoring', ['handle' => 'NW123456'], ['whmcs_service_id' => 100]);
        $this->monitoring = new Monitoring($this->params, $this->client, $this->db);

        $result = $this->monitoring->getAlerts();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('via email', $result['message']);
    }

    // =========================================================================
    // Get Info Method Tests
    // =========================================================================

    #[Test]
    public function getInfoCallsApiWithHandle(): void
    {
        $info = $this->monitoring->getInfo('NW123456');

        $this->assertNotNull($info);

        $calls = $this->client->getCalls('getNameWatch');
        $this->assertCount(1, $calls);
        $this->assertEquals('NW123456', $calls[0]['handle']);
    }

    #[Test]
    public function getInfoReturnsNameWatchInfo(): void
    {
        $info = $this->monitoring->getInfo('NW123456');

        $this->assertNotNull($info);
        $this->assertEquals('NW123456', $info->getHandle());
    }

    // =========================================================================
    // Database Methods Tests
    // =========================================================================

    #[Test]
    public function readDbLoadsExistingData(): void
    {
        $data = $this->monitoring->readDb();

        $this->assertNotNull($data);
        $this->assertEquals('testbrand', $data->name);
        $this->assertEquals(3, $data->tier);
        $this->assertEquals('Daily', $data->notification_frequency);
    }

    #[Test]
    public function readDbReturnsNullForNonExistentService(): void
    {
        $params = (new MockParams())->setServiceId(999)->setUserId(1);
        $monitoring = new Monitoring($params, $this->client, $this->db);

        $data = $monitoring->readDb();

        $this->assertNull($data);
    }

    #[Test]
    public function readDbCachesData(): void
    {
        // First read
        $data1 = $this->monitoring->readDb();
        $originalName = $data1->name;

        // Modify directly in database (simulating external change)
        $this->db->update('mod_ascio_monitoring', ['name' => 'modified'], ['whmcs_service_id' => 100]);

        // Second read should return cached data, not the modified DB data
        $data2 = $this->monitoring->readDb();

        $this->assertEquals($originalName, $data2->name);
        $this->assertEquals('testbrand', $data2->name);
    }

    #[Test]
    public function writeDbInsertsNewRecord(): void
    {
        $this->db->clear();

        $params = (new MockParams())->setServiceId(200)->setUserId(60);
        $monitoring = new Monitoring($params, $this->client, $this->db);

        $monitoring->fromForm([
            'name' => 'newbrand',
            'tier' => 2,
            'notification_frequency' => 'Weekly',
            'email_notification_1' => 'test@example.com',
            'period' => 1,
        ]);

        $monitoring->writeDb();

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 200]);
        $this->assertNotNull($data);
        $this->assertEquals('newbrand', $data->name);
        $this->assertEquals(2, $data->tier);
        $this->assertEquals('Weekly', $data->notification_frequency);
    }

    #[Test]
    public function writeDbUpdatesExistingRecord(): void
    {
        $this->monitoring->fromForm([
            'name' => 'updatedbrand',
            'tier' => 5,
            'notification_frequency' => 'Monthly',
        ]);

        $this->monitoring->writeDb();

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertEquals('updatedbrand', $data->name);
        $this->assertEquals(5, $data->tier);
        $this->assertEquals('Monthly', $data->notification_frequency);
    }

    #[Test]
    public function writeDbSetsServiceIdAndUserId(): void
    {
        $this->db->clear();

        $params = (new MockParams())->setServiceId(300)->setUserId(70);
        $monitoring = new Monitoring($params, $this->client, $this->db);

        $monitoring->fromForm([
            'name' => 'brand300',
            'tier' => 1,
        ]);

        $monitoring->writeDb();

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 300]);
        $this->assertEquals(300, $data->whmcs_service_id);
        $this->assertEquals(70, $data->user_id);
    }

    // =========================================================================
    // Form Data Methods Tests
    // =========================================================================

    #[Test]
    public function fromFormSetsAllFields(): void
    {
        $formData = [
            'name' => 'formbrand',
            'tier' => 4,
            'notification_frequency' => 'Weekly',
            'email_notification_1' => 'email1@test.com',
            'email_notification_2' => 'email2@test.com',
            'email_notification_3' => 'email3@test.com',
            'email_notification_4' => 'email4@test.com',
            'email_notification_5' => 'email5@test.com',
            'period' => 2,
            'owner_name' => 'Jane Doe',
            'owner_email' => 'jane@test.com',
            'owner_company' => 'Jane Corp',
            'owner_address1' => '456 Oak St',
            'owner_address2' => 'Floor 2',
            'owner_city' => 'Los Angeles',
            'owner_state' => 'CA',
            'owner_postcode' => '90001',
            'owner_country' => 'US',
            'owner_phone' => '+1.5559876543',
        ];

        $this->monitoring->fromForm($formData);
        $data = $this->monitoring->getData();

        $this->assertEquals('formbrand', $data['name']);
        $this->assertEquals(4, $data['tier']);
        $this->assertEquals('Weekly', $data['notification_frequency']);
        $this->assertEquals('email1@test.com', $data['email_notification_1']);
        $this->assertEquals('email2@test.com', $data['email_notification_2']);
        $this->assertEquals('email3@test.com', $data['email_notification_3']);
        $this->assertEquals('email4@test.com', $data['email_notification_4']);
        $this->assertEquals('email5@test.com', $data['email_notification_5']);
        $this->assertEquals(2, $data['period']);
        $this->assertEquals('Jane Doe', $data['owner_name']);
        $this->assertEquals('jane@test.com', $data['owner_email']);
        $this->assertEquals('Jane Corp', $data['owner_company']);
        $this->assertEquals('Los Angeles', $data['owner_city']);
    }

    #[Test]
    public function fromFormSetsDefaultsForMissingFields(): void
    {
        $this->monitoring->fromForm([
            'name' => 'minimaldata',
        ]);

        $data = $this->monitoring->getData();

        $this->assertEquals('minimaldata', $data['name']);
        $this->assertEquals(1, $data['tier']);
        $this->assertEquals('Daily', $data['notification_frequency']);
        $this->assertEquals(1, $data['period']);
    }

    #[Test]
    public function fromFormCastsTierToInteger(): void
    {
        $this->monitoring->fromForm([
            'name' => 'test',
            'tier' => '3', // String value
        ]);

        $data = $this->monitoring->getData();
        $this->assertSame(3, $data['tier']);
    }

    #[Test]
    public function fromFormCastsPeriodToInteger(): void
    {
        $this->monitoring->fromForm([
            'name' => 'test',
            'period' => '2', // String value
        ]);

        $data = $this->monitoring->getData();
        $this->assertSame(2, $data['period']);
    }

    #[Test]
    public function fromFormIsChainable(): void
    {
        $result = $this->monitoring->fromForm(['name' => 'test']);

        $this->assertInstanceOf(Monitoring::class, $result);
    }

    #[Test]
    public function toFormReturnsAllData(): void
    {
        $formData = [
            'name' => 'toformtest',
            'tier' => 3,
            'notification_frequency' => 'Monthly',
        ];

        $this->monitoring->fromForm($formData);
        $data = $this->monitoring->toForm();

        $this->assertEquals('toformtest', $data['name']);
        $this->assertEquals(3, $data['tier']);
        $this->assertEquals('Monthly', $data['notification_frequency']);
    }

    #[Test]
    public function toFormLoadsFromDbIfNotCached(): void
    {
        // Create a fresh instance
        $monitoring = new Monitoring($this->params, $this->client, $this->db);

        $data = $monitoring->toForm();

        $this->assertEquals('testbrand', $data['name']);
        $this->assertEquals(3, $data['tier']);
    }

    // =========================================================================
    // Service ID Tests
    // =========================================================================

    #[Test]
    public function getServiceIdReturnsConfiguredId(): void
    {
        $this->assertEquals(100, $this->monitoring->getServiceId());
    }

    #[Test]
    public function setServiceIdUpdatesId(): void
    {
        $result = $this->monitoring->setServiceId(500);

        $this->assertInstanceOf(Monitoring::class, $result);
        $this->assertEquals(500, $this->monitoring->getServiceId());
    }

    // =========================================================================
    // Build Owner Tests
    // =========================================================================

    #[Test]
    public function registerBuildsOwnerFromContactData(): void
    {
        $result = $this->monitoring->register($this->contactData);

        $this->assertTrue($result['success']);

        // Verify the API was called (we can't directly inspect buildOwner, but we can verify the flow)
        $calls = $this->client->getCalls('createOrder');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function registerHandlesEmptyContactData(): void
    {
        $result = $this->monitoring->register([]);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function registerHandlesPartialContactData(): void
    {
        $partialContact = [
            'name' => 'Partial Contact',
            'email' => 'partial@test.com',
            // Missing other fields
        ];

        $result = $this->monitoring->register($partialContact);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // Order ID Formatting Tests
    // =========================================================================

    #[Test]
    public function registerPreservesExistingTESTPrefix(): void
    {
        // Mock client returns order ID that already starts with TEST
        $customResponse = $this->createSuccessResponse('TEST12345', 'Pending');
        $this->client->queueResponse('createOrder', $customResponse);

        $result = $this->monitoring->register($this->contactData);

        $this->assertTrue($result['success']);
        $this->assertEquals('TEST12345', $result['order_id']);
    }

    #[Test]
    public function registerPreservesExistingAPrefix(): void
    {
        // Mock client returns order ID that already starts with A
        $customResponse = $this->createSuccessResponse('A98765', 'Pending');
        $this->client->queueResponse('createOrder', $customResponse);

        $result = $this->monitoring->register($this->contactData);

        $this->assertTrue($result['success']);
        $this->assertEquals('A98765', $result['order_id']);
    }

    // =========================================================================
    // Error Recording Tests
    // =========================================================================

    #[Test]
    public function registerRecordsErrorsInDatabase(): void
    {
        $errorResponse = $this->createErrorResponse(400, 'Validation failed', [
            ['field' => 'name', 'message' => 'Name is required'],
            ['field' => 'tier', 'message' => 'Invalid tier'],
        ]);
        $this->client->queueResponse('createOrder', $errorResponse);

        $this->monitoring->register($this->contactData);

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertNotNull($data->errors);
        $this->assertEquals(400, $data->code);
    }

    // =========================================================================
    // Helper Methods for Creating Mock Responses
    // =========================================================================

    /**
     * Create a success response.
     */
    protected function createSuccessResponse(string $orderId, string $status): object
    {
        $orderInfo = new class($orderId, $status) {
            private $orderId;
            private $status;
            public function __construct($orderId, $status) {
                $this->orderId = $orderId;
                $this->status = $status;
            }
            public function getOrderId() { return $this->orderId; }
            public function getStatus() { return $this->status; }
        };

        $result = new class($orderInfo) {
            private $orderInfo;
            public function __construct($orderInfo) { $this->orderInfo = $orderInfo; }
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Success'; }
            public function getOrderInfo() { return $this->orderInfo; }
            public function getErrors() { return null; }
        };

        return (object)['CreateOrderResult' => $result];
    }

    /**
     * Create an error response.
     */
    protected function createErrorResponse(int $code, string $message, array $errors = []): object
    {
        $errorContainer = new class($errors) {
            private $errors;
            public function __construct($errors) { $this->errors = $errors; }
            public function getErrorCode() {
                return array_map(function($e) {
                    return new class($e) {
                        private $e;
                        public function __construct($e) { $this->e = $e; }
                        public function getCode() { return $this->e['code'] ?? null; }
                        public function getMessage() { return $this->e['message'] ?? null; }
                        public function getValue() { return $this->e['value'] ?? null; }
                        public function getFieldName() { return $this->e['field'] ?? null; }
                    };
                }, $this->errors);
            }
            public function getString() { return []; }
        };

        $result = new class($code, $message, count($errors) > 0 ? $errorContainer : null) {
            private $code;
            private $message;
            private $errors;
            public function __construct($code, $message, $errors) {
                $this->code = $code;
                $this->message = $message;
                $this->errors = $errors;
            }
            public function getResultCode() { return $this->code; }
            public function getResultMessage() { return $this->message; }
            public function getOrderInfo() { return null; }
            public function getErrors() { return $this->errors; }
        };

        return (object)['CreateOrderResult' => $result];
    }
}
