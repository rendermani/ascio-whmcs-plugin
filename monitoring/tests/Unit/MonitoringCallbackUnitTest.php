<?php

/**
 * Unit Tests for MonitoringCallback
 *
 * Tests the callback handler for processing async order status updates.
 *
 * @copyright Copyright (c) Tucows Inc.
 */

declare(strict_types=1);

namespace Ascio\Monitoring\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Ascio\Monitoring\MonitoringCallback;
use Ascio\Core\ObjectType;
use Ascio\Core\Tests\MockAscioClient;
use Ascio\Core\Tests\MockDatabase;
use Ascio\Core\Tests\MockParams;

require_once __DIR__ . '/bootstrap.php';

#[Group('unit')]
#[Group('monitoring')]
#[Group('callback')]
class MonitoringCallbackUnitTest extends TestCase
{
    protected MockAscioClient $client;
    protected MockDatabase $db;
    protected MockParams $params;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new MockAscioClient();
        $this->db = new MockDatabase();
        $this->params = (new MockParams())->setServiceId(100)->setUserId(50);

        // Seed monitoring data
        $this->db->insert('mod_ascio_monitoring', [
            'whmcs_service_id' => 100,
            'user_id' => 50,
            'name' => 'testbrand',
            'tier' => 3,
            'notification_frequency' => 'Daily',
            'status' => 'Pending',
            'order_id' => 'TEST12345',
            'handle' => null,
        ]);
    }

    protected function tearDown(): void
    {
        $this->client->reset();
        $this->db->clear();
        parent::tearDown();
    }

    // =========================================================================
    // Basic Configuration Tests
    // =========================================================================

    #[Test]
    public function callbackReturnsCorrectTableName(): void
    {
        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);

        $this->assertEquals('mod_ascio_monitoring', $callback->getTableName());
    }

    #[Test]
    public function callbackReturnsCorrectObjectType(): void
    {
        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);

        $this->assertEquals(ObjectType::NAME_WATCH, $callback->getObjectType());
    }

    #[Test]
    public function callbackLoadsServiceIdFromDatabase(): void
    {
        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);

        $this->assertEquals(100, $callback->getServiceId());
    }

    #[Test]
    public function callbackLoadsUserIdFromDatabase(): void
    {
        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);

        $this->assertEquals(50, $callback->getUserId());
    }

    #[Test]
    public function callbackHandlesUnknownOrderId(): void
    {
        $callback = new MonitoringCallback($this->params, 'TESTUNKNOWN', $this->client, $this->db);

        $this->assertNull($callback->getServiceId());
        $this->assertNull($callback->getUserId());
    }

    // =========================================================================
    // Order ID Normalization Tests
    // =========================================================================

    #[Test]
    public function callbackPreservesTESTPrefix(): void
    {
        // Update database with non-prefixed order ID to test normalization
        $this->db->update('mod_ascio_monitoring', ['order_id' => 'TEST54321'], ['whmcs_service_id' => 100]);

        $callback = new MonitoringCallback($this->params, 'TEST54321', $this->client, $this->db);

        $this->assertEquals(100, $callback->getServiceId());
    }

    #[Test]
    public function callbackNormalizesNumericOrderId(): void
    {
        // Store with TEST prefix but pass numeric
        $this->db->update('mod_ascio_monitoring', ['order_id' => 'TEST999'], ['whmcs_service_id' => 100]);

        // In test mode, numeric 999 should become TEST999
        $callback = new MonitoringCallback($this->params, '999', $this->client, $this->db);

        $this->assertEquals(100, $callback->getServiceId());
    }

    // =========================================================================
    // Process Status Tests
    // =========================================================================

    #[Test]
    public function processCompletedStatusUpdatesHandle(): void
    {
        // Queue responses
        $this->client->queueResponse('getQueueMessage', $this->createQueueMessageResponse('MSG123'));
        $this->client->queueResponse('getOrder', $this->createGetOrderResponse('TEST12345', 'Completed', 'NW789'));
        $this->client->queueResponse('getNameWatch', $this->createNameWatchInfoResponse('NW789', '2025-12-31'));

        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Completed', 'MSG123', null);

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertEquals('Completed', $data->status);
        $this->assertEquals('NW789', $data->handle);
    }

    #[Test]
    public function processCompletedStatusUpdatesExpireDate(): void
    {
        $this->client->queueResponse('getQueueMessage', $this->createQueueMessageResponse('MSG123'));
        $this->client->queueResponse('getOrder', $this->createGetOrderResponse('TEST12345', 'Completed', 'NW789'));
        $this->client->queueResponse('getNameWatch', $this->createNameWatchInfoResponse('NW789', '2025-12-31'));

        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Completed', 'MSG123', null);

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertEquals('2025-12-31', $data->expire_date);
    }

    #[Test]
    public function processFailedStatusRecordsMessage(): void
    {
        $this->client->queueResponse('getQueueMessage', $this->createQueueMessageResponse('MSG123', 'Validation failed'));

        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Failed', 'MSG123', 'Validation failed');

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertEquals('Failed', $data->status);
        $this->assertEquals('Validation failed', $data->message);
    }

    #[Test]
    public function processPendingUserActionRecordsStatus(): void
    {
        $this->client->queueResponse('getQueueMessage', $this->createQueueMessageResponse('MSG123', 'Action required'));

        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Pending_End_User_Action', 'MSG123', 'Action required');

        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertEquals('Pending_End_User_Action', $data->status);
    }

    #[Test]
    public function processAcknowledgesMessage(): void
    {
        $this->client->queueResponse('getQueueMessage', $this->createQueueMessageResponse('MSG123'));

        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Pending', 'MSG123', null);

        $calls = $this->client->getCalls('ackQueueMessage');
        $this->assertCount(1, $calls);
        $this->assertEquals('MSG123', $calls[0]['messageId']);
    }

    #[Test]
    public function processWithProvidedMessageSkipsApiFetch(): void
    {
        // Pass message directly
        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Pending', 'MSG123', 'Direct message');

        $calls = $this->client->getCalls('getQueueMessage');
        $this->assertCount(0, $calls);
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function processHandlesGetNameWatchException(): void
    {
        $this->client->queueResponse('getQueueMessage', $this->createQueueMessageResponse('MSG123'));
        $this->client->queueResponse('getOrder', $this->createGetOrderResponse('TEST12345', 'Completed', 'NW789'));
        $this->client->queueException('getNameWatch', new \Exception('API timeout'));

        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Completed', 'MSG123', null);

        // Status should still update even if GetNameWatch fails
        $data = $this->db->first('mod_ascio_monitoring', ['*'], ['whmcs_service_id' => 100]);
        $this->assertEquals('Completed', $data->status);
        $this->assertEquals('NW789', $data->handle);
    }

    // =========================================================================
    // Data Access Tests
    // =========================================================================

    #[Test]
    public function getStatusReturnsCurrentStatus(): void
    {
        $this->client->queueResponse('getQueueMessage', $this->createQueueMessageResponse('MSG123'));

        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Processing', 'MSG123', null);

        $this->assertEquals('Processing', $callback->getStatus());
    }

    #[Test]
    public function getMessageReturnsMessageContent(): void
    {
        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Processing', 'MSG123', 'Test message content');

        $this->assertEquals('Test message content', $callback->getMessage());
    }

    #[Test]
    public function getDataReturnsUpdatedData(): void
    {
        $this->client->queueResponse('getQueueMessage', $this->createQueueMessageResponse('MSG123'));

        $callback = new MonitoringCallback($this->params, 'TEST12345', $this->client, $this->db);
        $callback->process('TEST12345', 'Processing', 'MSG123', null);

        $data = $callback->getData();
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('Processing', $data['status']);
    }

    // =========================================================================
    // Helper Methods for Creating Mock Responses
    // =========================================================================

    protected function createQueueMessageResponse(string $messageId, ?string $messageContent = null): object
    {
        $message = new class($messageContent) {
            private $content;
            public function __construct($content) { $this->content = $content; }
            public function getMessage() { return $this->content ?? 'Queue message'; }
        };

        return (object)[
            'GetQueueMessageResult' => new class($message) {
                private $msg;
                public function __construct($msg) { $this->msg = $msg; }
                public function getResultCode() { return 200; }
                public function getMessage() { return $this->msg; }
            }
        ];
    }

    protected function createGetOrderResponse(string $orderId, string $status, ?string $handle = null): object
    {
        $nameWatch = $handle ? new class($handle) {
            private $h;
            public function __construct($h) { $this->h = $h; }
            public function getHandle() { return $this->h; }
        } : null;

        $orderRequest = new class($nameWatch) {
            private $nw;
            public function __construct($nw) { $this->nw = $nw; }
            public function getNameWatch() { return $this->nw; }
        };

        $orderInfo = new class($orderId, $status, $orderRequest) {
            private $oid;
            private $st;
            private $req;
            public function __construct($oid, $st, $req) {
                $this->oid = $oid;
                $this->st = $st;
                $this->req = $req;
            }
            public function getOrderId() { return $this->oid; }
            public function getStatus() { return $this->st; }
            public function getOrderRequest() { return $this->req; }
        };

        return (object)[
            'GetOrderResult' => new class($orderInfo) {
                private $oi;
                public function __construct($oi) { $this->oi = $oi; }
                public function getResultCode() { return 200; }
                public function getResultMessage() { return 'Success'; }
                public function getOrderInfo() { return $this->oi; }
            }
        ];
    }

    protected function createNameWatchInfoResponse(string $handle, string $expDate): object
    {
        $info = new class($handle, $expDate) {
            private $h;
            private $ed;
            public function __construct($h, $ed) {
                $this->h = $h;
                $this->ed = $ed;
            }
            public function getHandle() { return $this->h; }
            public function getExpDate() { return $this->ed; }
            public function getName() { return 'testbrand'; }
            public function getTier() { return 3; }
            public function getStatus() { return 'Active'; }
        };

        return (object)[
            'GetNameWatchResult' => new class($info) {
                private $i;
                public function __construct($i) { $this->i = $i; }
                public function getResultCode() { return 200; }
                public function getNameWatchInfo() { return $this->i; }
            }
        ];
    }
}
