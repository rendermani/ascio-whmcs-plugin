<?php

/**
 * Unit Tests for Defensive Registration (DPML) Class
 *
 * Tests the Defensive class methods with mocked API client and database.
 * These tests do NOT require real API credentials.
 *
 * @copyright Copyright (c) Tucows Inc.
 */

declare(strict_types=1);

namespace Ascio\Defensive\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Ascio\Defensive\Defensive;
use Ascio\Core\Tests\MockAscioClient;
use Ascio\Core\Tests\MockDatabase;
use Ascio\Core\Tests\MockParams;

require_once __DIR__ . '/bootstrap.php';

#[Group('unit')]
#[Group('defensive')]
#[CoversClass(Defensive::class)]
class DefensiveTest extends TestCase
{
    private MockAscioClient $mockClient;
    private MockDatabase $mockDb;
    private MockParams $mockParams;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockAscioClient();
        $this->mockDb = new MockDatabase();
        $this->mockParams = new MockParams();
        $this->mockParams->setServiceId(1001);
        $this->mockParams->setUserId(500);
    }

    protected function tearDown(): void
    {
        $this->mockClient->reset();
        $this->mockDb->clear();
        parent::tearDown();
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    #[Test]
    public function constructorSetsServiceIdFromParams(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $this->assertEquals(1001, $defensive->getServiceId());
    }

    #[Test]
    public function constructorAcceptsDependencyInjection(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        // If no exceptions thrown and we can get service ID, DI worked
        $this->assertInstanceOf(Defensive::class, $defensive);
    }

    // =========================================================================
    // readDb() Tests
    // =========================================================================

    #[Test]
    public function readDbReturnsNullWhenNoData(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $result = $defensive->readDb();

        $this->assertNull($result);
    }

    #[Test]
    public function readDbReturnsExistingData(): void
    {
        // Seed the database with existing record
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'user_id' => 500,
                'name' => 'test-brand.dpml',
                'mark_handle' => 'MK-12345',
                'status' => 'Pending',
                'period' => 1,
            ],
        ]);

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $result = $defensive->readDb();

        $this->assertNotNull($result);
        $this->assertEquals('test-brand.dpml', $result->name);
        $this->assertEquals('MK-12345', $result->mark_handle);
        $this->assertEquals('Pending', $result->status);
    }

    #[Test]
    public function readDbCachesResultOnSubsequentCalls(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'user_id' => 500,
                'name' => 'original-name.dpml',
            ],
        ]);

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        // First read
        $result1 = $defensive->readDb();
        $this->assertEquals('original-name.dpml', $result1->name);

        // Modify underlying data (simulate external change)
        $this->mockDb->update('mod_ascio_defensive', ['name' => 'modified-name.dpml'], ['whmcs_service_id' => 1001]);

        // Second read should return cached data
        $result2 = $defensive->readDb();
        $this->assertEquals('original-name.dpml', $result2->name);
    }

    // =========================================================================
    // writeDb() Tests
    // =========================================================================

    #[Test]
    public function writeDbInsertsNewRecordWhenNoExistingData(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $defensive->fromForm([
            'name' => 'new-brand.dpml',
            'mark_handle' => 'MK-NEW',
            'period' => 2,
            'owner_name' => 'John Doe',
            'owner_email' => 'john@example.com',
        ]);

        $defensive->writeDb();

        $stored = $this->mockDb->first('mod_ascio_defensive', ['*'], ['whmcs_service_id' => 1001]);
        $this->assertNotNull($stored);
        $this->assertEquals('new-brand.dpml', $stored->name);
        $this->assertEquals('MK-NEW', $stored->mark_handle);
        $this->assertEquals(2, $stored->period);
        $this->assertEquals(1001, $stored->whmcs_service_id);
        $this->assertEquals(500, $stored->user_id);
    }

    #[Test]
    public function writeDbUpdatesExistingRecord(): void
    {
        // Seed existing record
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'user_id' => 500,
                'name' => 'old-name.dpml',
                'status' => 'Pending',
            ],
        ]);

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        // Read first to load existing data
        $defensive->readDb();

        // Modify and save
        $defensive->fromForm([
            'name' => 'updated-name.dpml',
            'period' => 3,
        ]);

        $defensive->writeDb();

        $stored = $this->mockDb->first('mod_ascio_defensive', ['*'], ['whmcs_service_id' => 1001]);
        $this->assertEquals('updated-name.dpml', $stored->name);
        $this->assertEquals(3, $stored->period);
    }

    // =========================================================================
    // fromForm() Tests
    // =========================================================================

    #[Test]
    public function fromFormSetsAllFields(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $formData = [
            'name' => 'brand.dpml',
            'mark_handle' => 'MK-FORM',
            'period' => 5,
            'owner_name' => 'Jane Smith',
            'owner_email' => 'jane@example.com',
            'owner_company' => 'Smith Corp',
            'owner_address1' => '123 Main St',
            'owner_address2' => 'Suite 100',
            'owner_city' => 'Boston',
            'owner_state' => 'MA',
            'owner_postcode' => '02101',
            'owner_country' => 'US',
            'owner_phone' => '+1.5551234567',
            'admin_name' => 'Admin User',
            'admin_email' => 'admin@example.com',
            'admin_company' => 'Admin Corp',
            'admin_phone' => '+1.5557654321',
            'tech_name' => 'Tech User',
            'tech_email' => 'tech@example.com',
            'tech_company' => 'Tech Corp',
            'tech_phone' => '+1.5559998888',
        ];

        $result = $defensive->fromForm($formData);

        // Returns self for fluent interface
        $this->assertSame($defensive, $result);

        $data = $defensive->getData();
        $this->assertEquals('brand.dpml', $data['name']);
        $this->assertEquals('MK-FORM', $data['mark_handle']);
        $this->assertEquals(5, $data['period']);
        $this->assertEquals('Jane Smith', $data['owner_name']);
        $this->assertEquals('jane@example.com', $data['owner_email']);
        $this->assertEquals('Smith Corp', $data['owner_company']);
        $this->assertEquals('123 Main St', $data['owner_address1']);
        $this->assertEquals('Admin User', $data['admin_name']);
        $this->assertEquals('Tech User', $data['tech_name']);
    }

    #[Test]
    public function fromFormHandlesEmptyFields(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $formData = [
            'name' => 'minimal.dpml',
        ];

        $defensive->fromForm($formData);

        $data = $defensive->getData();
        $this->assertEquals('minimal.dpml', $data['name']);
        $this->assertEquals('', $data['mark_handle']);
        $this->assertEquals(1, $data['period']); // Default period
        $this->assertEquals('', $data['owner_name']);
    }

    // =========================================================================
    // toForm() Tests
    // =========================================================================

    #[Test]
    public function toFormReturnsDataFromDb(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'user_id' => 500,
                'name' => 'db-brand.dpml',
                'mark_handle' => 'MK-DB',
                'status' => 'Active',
            ],
        ]);

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $formData = $defensive->toForm();

        $this->assertEquals('db-brand.dpml', $formData['name']);
        $this->assertEquals('MK-DB', $formData['mark_handle']);
    }

    // =========================================================================
    // register() Tests
    // =========================================================================

    #[Test]
    public function registerCreatesSuccessfulOrder(): void
    {
        // Seed initial data
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'user_id' => 500,
                'name' => 'registration-test.dpml',
                'mark_handle' => 'MK-REG',
                'period' => 1,
            ],
        ]);

        // Mock successful API response
        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(12345, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $contactData = [
            'owner_name' => 'Test Owner',
            'owner_email' => 'owner@example.com',
            'owner_company' => 'Test Company',
            'owner_address1' => '123 Test St',
            'owner_city' => 'Test City',
            'owner_state' => 'TS',
            'owner_postcode' => '12345',
            'owner_country' => 'US',
            'owner_phone' => '+1.5551234567',
        ];

        $result = $defensive->register($contactData);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['code']);
        $this->assertEquals('Pending', $result['status']);
        $this->assertStringContainsString('12345', $result['order_id']);
    }

    #[Test]
    public function registerHandlesApiError(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'error-test.dpml',
                'period' => 1,
            ],
        ]);

        // Mock error response
        $this->mockClient->queueResponse('createOrder', $this->makeErrorOrderResponse(400, 'Invalid mark handle'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $result = $defensive->register([]);

        $this->assertFalse($result['success']);
        $this->assertEquals(400, $result['code']);
    }

    #[Test]
    public function registerHandlesSoapFault(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'soap-error.dpml',
                'period' => 1,
            ],
        ]);

        // Mock SoapFault exception
        $this->mockClient->queueException('createOrder', new \SoapFault('Server', 'Connection timeout'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $result = $defensive->register([]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Temporary error', $result['error']);
        $this->assertStringContainsString('Connection timeout', $result['error']);
    }

    #[Test]
    public function registerSetsCorrectOrderType(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'order-type-test.dpml',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(99999, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->register([]);

        $calls = $this->mockClient->getCalls('createOrder');
        $this->assertCount(1, $calls);

        $orderRequest = $calls[0]['orderRequest'];
        $this->assertEquals('Register', $orderRequest->getType());
    }

    // =========================================================================
    // renew() Tests
    // =========================================================================

    #[Test]
    public function renewCreatesSuccessfulOrder(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'renewal-test.dpml',
                'handle' => 'DEF-123456',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(54321, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $result = $defensive->renew([]);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['code']);
    }

    #[Test]
    public function renewSetsCorrectOrderType(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'renew-type-test.dpml',
                'handle' => 'DEF-RENEW',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(11111, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->renew([]);

        $calls = $this->mockClient->getCalls('createOrder');
        $this->assertCount(1, $calls);

        $orderRequest = $calls[0]['orderRequest'];
        $this->assertEquals('Renew', $orderRequest->getType());
    }

    #[Test]
    public function renewUsesExistingHandle(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'handle-test.dpml',
                'handle' => 'DEF-EXISTING',
                'period' => 2,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(22222, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->renew([]);

        $calls = $this->mockClient->getCalls('createOrder');
        $orderRequest = $calls[0]['orderRequest'];

        // The defensive object in the order should have the existing handle
        $defensiveObj = $orderRequest->getDefensive();
        $this->assertEquals('DEF-EXISTING', $defensiveObj->getHandle());
    }

    // =========================================================================
    // terminate() Tests
    // =========================================================================

    #[Test]
    public function terminateCreatesDeleteOrder(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'terminate-test.dpml',
                'handle' => 'DEF-DELETE',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(33333, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $result = $defensive->terminate();

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function terminateSetsCorrectOrderType(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'delete-type-test.dpml',
                'handle' => 'DEF-TERM',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(44444, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->terminate();

        $calls = $this->mockClient->getCalls('createOrder');
        $orderRequest = $calls[0]['orderRequest'];

        $this->assertEquals('Delete', $orderRequest->getType());
    }

    #[Test]
    public function terminateHandlesApiError(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'terminate-error.dpml',
                'handle' => 'DEF-ERR',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeErrorOrderResponse(400, 'Cannot delete active registration'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $result = $defensive->terminate();

        $this->assertFalse($result['success']);
        $this->assertEquals(400, $result['code']);
    }

    // =========================================================================
    // getInfo() Tests
    // =========================================================================

    #[Test]
    public function getInfoRetrievesDefensiveDetails(): void
    {
        $this->mockClient->queueResponse('getDefensive', $this->makeDefensiveInfoResponse('DEF-INFO', '2026-12-31'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $info = $defensive->getInfo('DEF-INFO');

        $this->assertEquals('DEF-INFO', $info->getHandle());
        $this->assertEquals('2026-12-31', $info->getExpDate());
    }

    #[Test]
    public function getInfoCallsClientWithCorrectHandle(): void
    {
        $this->mockClient->queueResponse('getDefensive', $this->makeDefensiveInfoResponse('DEF-CHECK', '2025-06-15'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->getInfo('DEF-CHECK');

        $calls = $this->mockClient->getCalls('getDefensive');
        $this->assertCount(1, $calls);
        $this->assertEquals('DEF-CHECK', $calls[0]['handle']);
    }

    // =========================================================================
    // buildRegistrant() Tests (via reflection)
    // =========================================================================

    #[Test]
    public function buildRegistrantCreatesValidObject(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $reflection = new \ReflectionMethod($defensive, 'buildRegistrant');
        $reflection->setAccessible(true);

        $contactData = [
            'owner_name' => 'Registrant Name',
            'owner_email' => 'registrant@example.com',
            'owner_company' => 'Registrant Corp',
            'owner_address1' => '456 Registrant Ave',
            'owner_address2' => 'Floor 2',
            'owner_city' => 'Chicago',
            'owner_state' => 'IL',
            'owner_postcode' => '60601',
            'owner_country' => 'US',
            'owner_phone' => '+1.3125551234',
        ];

        $registrant = $reflection->invoke($defensive, $contactData, 'owner');

        $this->assertEquals('Registrant', $registrant->getFirstName());
        $this->assertEquals('Name', $registrant->getLastName());
        $this->assertEquals('registrant@example.com', $registrant->getEmail());
        $this->assertEquals('Registrant Corp', $registrant->getOrgName());
        $this->assertEquals('456 Registrant Ave', $registrant->getAddress1());
        $this->assertEquals('Floor 2', $registrant->getAddress2());
        $this->assertEquals('Chicago', $registrant->getCity());
        $this->assertEquals('IL', $registrant->getState());
        $this->assertEquals('60601', $registrant->getPostalCode());
        $this->assertEquals('US', $registrant->getCountryCode());
        $this->assertEquals('+1.3125551234', $registrant->getPhone());
    }

    #[Test]
    public function buildRegistrantHandlesEmptyValues(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $reflection = new \ReflectionMethod($defensive, 'buildRegistrant');
        $reflection->setAccessible(true);

        $registrant = $reflection->invoke($defensive, [], 'owner');

        $this->assertEquals('', $registrant->getFirstName());
        $this->assertEquals('', $registrant->getLastName());
        $this->assertEquals('', $registrant->getEmail());
    }

    // =========================================================================
    // buildContact() Tests (via reflection)
    // =========================================================================

    #[Test]
    public function buildContactCreatesValidObject(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $reflection = new \ReflectionMethod($defensive, 'buildContact');
        $reflection->setAccessible(true);

        $contactData = [
            'admin_name' => 'Admin Contact',
            'admin_email' => 'admin@example.com',
            'admin_company' => 'Admin Corp',
            'admin_phone' => '+1.2125559999',
            'owner_address1' => '789 Owner St',
            'owner_city' => 'New York',
            'owner_state' => 'NY',
            'owner_postcode' => '10001',
            'owner_country' => 'US',
        ];

        $contact = $reflection->invoke($defensive, $contactData, 'admin');

        $this->assertEquals('Admin', $contact->getFirstName());
        $this->assertEquals('Contact', $contact->getLastName());
        $this->assertEquals('admin@example.com', $contact->getEmail());
        $this->assertEquals('Admin Corp', $contact->getOrgName());
        $this->assertEquals('+1.2125559999', $contact->getPhone());
        // Address falls back to owner data
        $this->assertEquals('789 Owner St', $contact->getAddress1());
        $this->assertEquals('New York', $contact->getCity());
    }

    #[Test]
    public function buildContactFallsBackToOwnerData(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $reflection = new \ReflectionMethod($defensive, 'buildContact');
        $reflection->setAccessible(true);

        $contactData = [
            'owner_name' => 'Owner Name',
            'owner_email' => 'owner@example.com',
            'owner_company' => 'Owner Corp',
            'owner_phone' => '+1.5551112222',
        ];

        // Build tech contact without specific tech data
        $contact = $reflection->invoke($defensive, $contactData, 'tech');

        // Should fall back to owner data
        $this->assertEquals('Owner', $contact->getFirstName());
        $this->assertEquals('Name', $contact->getLastName());
        $this->assertEquals('owner@example.com', $contact->getEmail());
    }

    // =========================================================================
    // formatOrderId() Tests (via reflection)
    // =========================================================================

    #[Test]
    #[DataProvider('orderIdFormatProvider')]
    public function formatOrderIdAddsCorrectPrefix(string $input, bool $testMode, string $expected): void
    {
        $this->mockParams->setTestMode($testMode);

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $reflection = new \ReflectionMethod($defensive, 'formatOrderId');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($defensive, $input);

        $this->assertEquals($expected, $result);
    }

    public static function orderIdFormatProvider(): array
    {
        return [
            'test mode numeric' => ['12345', true, 'TEST12345'],
            'test mode already prefixed' => ['TEST12345', true, 'TEST12345'],
            'live mode numeric' => ['12345', false, 'A12345'],
            'live mode already prefixed' => ['A12345', false, 'A12345'],
        ];
    }

    // =========================================================================
    // setServiceId() / getServiceId() Tests
    // =========================================================================

    #[Test]
    public function setServiceIdUpdatesValue(): void
    {
        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);

        $this->assertEquals(1001, $defensive->getServiceId());

        $result = $defensive->setServiceId(2002);

        $this->assertSame($defensive, $result); // Fluent interface
        $this->assertEquals(2002, $defensive->getServiceId());
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function registerUpdatesStatusOnError(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'status-update-test.dpml',
                'status' => 'Initial',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeErrorOrderResponse(400, 'Invalid data'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->register([]);

        // Check that status was updated in database
        $stored = $this->mockDb->first('mod_ascio_defensive', ['*'], ['whmcs_service_id' => 1001]);
        $this->assertEquals('Invalid data', $stored->status);
        $this->assertEquals(400, $stored->code);
    }

    #[Test]
    public function registerUpdatesStatusOnSuccess(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'success-status-test.dpml',
                'status' => 'Initial',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(55555, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->register([]);

        $stored = $this->mockDb->first('mod_ascio_defensive', ['*'], ['whmcs_service_id' => 1001]);
        $this->assertEquals('Pending', $stored->status);
        $this->assertEquals(200, $stored->code);
        $this->assertStringContainsString('55555', $stored->order_id);
    }

    // =========================================================================
    // Mark Handle Tests
    // =========================================================================

    #[Test]
    public function registerSetsMarkHandleWhenProvided(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'mark-handle-test.dpml',
                'mark_handle' => 'TMCH-12345',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(66666, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->register([]);

        $calls = $this->mockClient->getCalls('createOrder');
        $orderRequest = $calls[0]['orderRequest'];
        $defensiveObj = $orderRequest->getDefensive();

        $this->assertEquals('TMCH-12345', $defensiveObj->getMarkHandle());
    }

    #[Test]
    public function registerOmitsMarkHandleWhenEmpty(): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'no-mark-handle.dpml',
                'mark_handle' => '',
                'period' => 1,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(77777, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->register([]);

        $calls = $this->mockClient->getCalls('createOrder');
        $orderRequest = $calls[0]['orderRequest'];
        $defensiveObj = $orderRequest->getDefensive();

        // Mark handle should be null or empty when not set
        $markHandle = $defensiveObj->getMarkHandle();
        $this->assertTrue(empty($markHandle));
    }

    // =========================================================================
    // Period Tests
    // =========================================================================

    #[Test]
    #[DataProvider('periodProvider')]
    public function registerUsesPeriodFromData(int $period): void
    {
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 1001,
                'name' => 'period-test.dpml',
                'period' => $period,
            ],
        ]);

        $this->mockClient->queueResponse('createOrder', $this->makeSuccessfulOrderResponse(88888, 'Pending'));

        $defensive = new Defensive($this->mockParams, $this->mockClient, $this->mockDb);
        $defensive->register([]);

        $calls = $this->mockClient->getCalls('createOrder');
        $orderRequest = $calls[0]['orderRequest'];

        $this->assertEquals($period, $orderRequest->getPeriod());
    }

    public static function periodProvider(): array
    {
        return [
            '1 year' => [1],
            '2 years' => [2],
            '3 years' => [3],
            '5 years' => [5],
            '10 years' => [10],
        ];
    }

    // =========================================================================
    // Helper Methods for Creating Mock Responses
    // =========================================================================

    /**
     * Create a successful order response mock.
     */
    private function makeSuccessfulOrderResponse(int $orderId, string $status): object
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
     * Create an error order response mock.
     */
    private function makeErrorOrderResponse(int $code, string $message): object
    {
        $result = new class($code, $message) {
            private $code;
            private $message;
            public function __construct($code, $message) {
                $this->code = $code;
                $this->message = $message;
            }
            public function getResultCode() { return $this->code; }
            public function getResultMessage() { return $this->message; }
            public function getOrderInfo() { return null; }
            public function getErrors() {
                return new class($this->message) {
                    private $message;
                    public function __construct($message) { $this->message = $message; }
                    public function getString() { return [$this->message]; }
                    public function getErrorCode() { return []; }
                };
            }
        };

        return (object)['CreateOrderResult' => $result];
    }

    /**
     * Create a defensive info response mock.
     */
    private function makeDefensiveInfoResponse(string $handle, string $expDate): object
    {
        $info = new class($handle, $expDate) {
            private $handle;
            private $expDate;
            public function __construct($handle, $expDate) {
                $this->handle = $handle;
                $this->expDate = $expDate;
            }
            public function getHandle() { return $this->handle; }
            public function getExpDate() { return $this->expDate; }
            public function getName() { return 'test-defensive.dpml'; }
            public function getStatus() { return 'Active'; }
            public function getCreated() { return new \DateTime('2024-01-01'); }
            public function getExpires() { return new \DateTime($this->expDate); }
            public function getAuthInfo() { return 'AUTH-CODE-123'; }
        };

        $result = new class($info) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Success'; }
            public function getDefensiveInfo() { return $this->info; }
        };

        return (object)['GetDefensiveResult' => $result];
    }
}
