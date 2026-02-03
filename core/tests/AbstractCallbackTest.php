<?php

namespace Ascio\Core\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Ascio\Core\AbstractCallback;
use Ascio\Core\OrderStatus;
use Ascio\Core\AscioApiException;

/**
 * Concrete implementation of AbstractCallback for testing.
 */
class TestCallback extends AbstractCallback
{
    private string $tableName = 'mod_test_orders';
    private string $objectType = 'TestObjectType';
    private string $moduleName = 'test_module';
    private bool $processStatusCalled = false;
    private $objectFromOrder = null;

    /**
     * Set the table name for testing.
     */
    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * {@inheritdoc}
     */
    protected function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * {@inheritdoc}
     */
    protected function processStatus(): void
    {
        $this->processStatusCalled = true;

        // Simulate module-specific processing based on status
        if ($this->isCompleted()) {
            $this->setData('completed_at', date('Y-m-d H:i:s'));
        }

        if ($this->isFailed()) {
            $this->setData('failed_at', date('Y-m-d H:i:s'));
        }

        if ($this->isPendingUserAction()) {
            $this->setData('pending_user_action', true);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjectFromOrder()
    {
        if ($this->order) {
            return $this->order->getOrderRequest()->getSslCertificate();
        }
        return $this->objectFromOrder;
    }

    /**
     * Set custom object to return from getObjectFromOrder().
     */
    public function setObjectFromOrder($object): self
    {
        $this->objectFromOrder = $object;
        return $this;
    }

    /**
     * Check if processStatus() was called.
     */
    public function wasProcessStatusCalled(): bool
    {
        return $this->processStatusCalled;
    }

    /**
     * Public accessors for protected methods (for testing).
     */
    public function testIsFailed(): bool
    {
        return $this->isFailed();
    }

    public function testIsCompleted(): bool
    {
        return $this->isCompleted();
    }

    public function testIsPendingUserAction(): bool
    {
        return $this->isPendingUserAction();
    }

    public function testSetData(string $key, $value): self
    {
        return $this->setData($key, $value);
    }

    public function testNormalizeOrderId($orderId): string
    {
        return $this->normalizeOrderId($orderId);
    }
}

/**
 * Unit tests for AbstractCallback.
 */
class AbstractCallbackTest extends TestCase
{
    protected MockAscioClient $client;
    protected MockParams $params;
    protected MockDatabase $db;

    protected function setUp(): void
    {
        $this->client = new MockAscioClient();
        $this->params = new MockParams();
        $this->db = new MockDatabase();
    }

    // ==========================================
    // Constructor and Initialization Tests
    // ==========================================

    #[Test]
    public function constructorLoadsServiceDataFromDatabase(): void
    {
        $this->db->seed('mod_test_orders', [
            [
                'order_id' => 'TEST123',
                'whmcs_service_id' => 42,
                'user_id' => 100,
            ]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);

        $this->assertEquals(42, $callback->getServiceId());
        $this->assertEquals(100, $callback->getUserId());
    }

    #[Test]
    public function constructorHandlesMissingServiceData(): void
    {
        $callback = new TestCallback($this->params, 'TEST999', $this->client, $this->db);

        $this->assertNull($callback->getServiceId());
        $this->assertNull($callback->getUserId());
    }

    // ==========================================
    // Order ID Normalization Tests
    // ==========================================

    #[Test]
    public function normalizeOrderIdAddsTestPrefixInTestMode(): void
    {
        $this->params->setTestMode(true);

        $callback = new TestCallback($this->params, '12345', $this->client, $this->db);

        $this->assertEquals('TEST12345', $callback->testNormalizeOrderId('12345'));
    }

    #[Test]
    public function normalizeOrderIdAddsAPrefixInLiveMode(): void
    {
        $this->params->setTestMode(false);

        $callback = new TestCallback($this->params, '12345', $this->client, $this->db);

        $this->assertEquals('A12345', $callback->testNormalizeOrderId('12345'));
    }

    #[Test]
    public function normalizeOrderIdPreservesExistingTestPrefix(): void
    {
        $this->params->setTestMode(true);

        $callback = new TestCallback($this->params, 'TEST12345', $this->client, $this->db);

        $this->assertEquals('TEST12345', $callback->testNormalizeOrderId('TEST12345'));
    }

    #[Test]
    public function normalizeOrderIdPreservesExistingAPrefix(): void
    {
        $this->params->setTestMode(false);

        $callback = new TestCallback($this->params, 'A12345', $this->client, $this->db);

        $this->assertEquals('A12345', $callback->testNormalizeOrderId('A12345'));
    }

    #[Test]
    public function normalizeOrderIdHandlesZero(): void
    {
        $callback = new TestCallback($this->params, '0', $this->client, $this->db);

        // Zero is not > 0, so it should remain as-is
        $this->assertEquals('0', $callback->testNormalizeOrderId('0'));
    }

    // ==========================================
    // Process Method Tests
    // ==========================================

    #[Test]
    public function processCallsProcessStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', 'test message');

        $this->assertTrue($callback->wasProcessStatusCalled());
    }

    #[Test]
    public function processUpdatesStatusInData(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::COMPLETED, 'MSG001', 'test message');

        $data = $callback->getData();
        $this->assertEquals(OrderStatus::COMPLETED, $data['status']);
    }

    #[Test]
    public function processFetchesOrderForCompletedStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::COMPLETED, 'MSG001', 'test message');

        // Check that getOrder was called
        $calls = $this->client->getCalls('getOrder');
        $this->assertNotEmpty($calls);
        $this->assertEquals('TEST123', $calls[0]['orderId']);
    }

    #[Test]
    public function processFetchesOrderForFailedStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::FAILED, 'MSG001', 'test message');

        $calls = $this->client->getCalls('getOrder');
        $this->assertNotEmpty($calls);
    }

    #[Test]
    public function processDoesNotFetchOrderForPendingStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', 'test message');

        $calls = $this->client->getCalls('getOrder');
        $this->assertEmpty($calls);
    }

    #[Test]
    public function processAcknowledgesMessage(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', 'test message');

        $calls = $this->client->getCalls('ackQueueMessage');
        $this->assertNotEmpty($calls);
        $this->assertEquals('MSG001', $calls[0]['messageId']);
    }

    #[Test]
    public function processWritesStatusToDatabase(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1, 'status' => 'Pending']
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::COMPLETED, 'MSG001', 'test message');

        $row = $this->db->first('mod_test_orders', ['*'], ['order_id' => 'TEST123']);
        $this->assertEquals(OrderStatus::COMPLETED, $row->status);
    }

    #[Test]
    public function processFetchesMessageWhenNotProvided(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', null);

        $calls = $this->client->getCalls('getQueueMessage');
        $this->assertNotEmpty($calls);
        $this->assertEquals('MSG001', $calls[0]['messageId']);
    }

    #[Test]
    public function processUsesProvidedMessage(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', 'provided message');

        // Should not call getQueueMessage since message was provided
        $calls = $this->client->getCalls('getQueueMessage');
        $this->assertEmpty($calls);

        $this->assertEquals('provided message', $callback->getMessage());
    }

    // ==========================================
    // Status Mapping Tests
    // ==========================================

    #[Test]
    public function isFailedReturnsTrueForFailedStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::FAILED, 'MSG001', 'test message');

        $this->assertTrue($callback->testIsFailed());
    }

    #[Test]
    public function isFailedReturnsTrueForInvalidStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::INVALID, 'MSG001', 'test message');

        $this->assertTrue($callback->testIsFailed());
    }

    #[Test]
    public function isFailedReturnsFalseForCompletedStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::COMPLETED, 'MSG001', 'test message');

        $this->assertFalse($callback->testIsFailed());
    }

    #[Test]
    public function isCompletedReturnsTrueForCompletedStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::COMPLETED, 'MSG001', 'test message');

        $this->assertTrue($callback->testIsCompleted());
    }

    #[Test]
    public function isCompletedReturnsFalseForPendingStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', 'test message');

        $this->assertFalse($callback->testIsCompleted());
    }

    #[Test]
    public function isPendingUserActionReturnsTrueForPendingEndUserAction(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING_END_USER_ACTION, 'MSG001', 'test message');

        $this->assertTrue($callback->testIsPendingUserAction());
    }

    #[Test]
    public function isPendingUserActionReturnsFalseForPending(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', 'test message');

        $this->assertFalse($callback->testIsPendingUserAction());
    }

    // ==========================================
    // Order Handling Tests
    // ==========================================

    #[Test]
    public function fetchOrderStoresOrderInformation(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::COMPLETED, 'MSG001', 'test message');

        $order = $callback->getOrder();
        $this->assertNotNull($order);
        $this->assertEquals('TEST123', $order->getOrderId());
    }

    #[Test]
    public function getOrderReturnsNullWhenNotFetched(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', 'test message');

        // Order not fetched for Pending status
        $this->assertNull($callback->getOrder());
    }

    // ==========================================
    // Error Condition Tests
    // ==========================================

    #[Test]
    public function fetchOrderThrowsExceptionOnApiError(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        // Queue an error response for getOrder
        $errorResult = new class {
            public function getResultCode() { return 400; }
            public function getResultMessage() { return 'Order not found'; }
            public function getErrors() { return null; }
        };
        $this->client->queueResponse('getOrder', (object)['GetOrderResult' => $errorResult]);

        $this->expectException(AscioApiException::class);
        $this->expectExceptionMessage('Order not found');

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::COMPLETED, 'MSG001', 'test message');
    }

    #[Test]
    public function fetchMessageThrowsExceptionOnApiError(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        // Queue an exception for getQueueMessage
        $this->client->queueException(
            'getQueueMessage',
            new \Exception('Message not found')
        );

        $this->expectException(AscioApiException::class);
        $this->expectExceptionMessage('Message not found');

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', null); // null message triggers fetch
    }

    #[Test]
    public function ackFailureIsHandled(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        // Queue an exception for ack
        $this->client->queueException(
            'ackQueueMessage',
            new \Exception('Ack failed')
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ack failed');

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', 'test message');
    }

    // ==========================================
    // Data Management Tests
    // ==========================================

    #[Test]
    public function setDataStoresValues(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->testSetData('custom_field', 'custom_value');

        $data = $callback->getData();
        $this->assertEquals('custom_value', $data['custom_field']);
    }

    #[Test]
    public function setDataReturnsSelfForChaining(): void
    {
        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);

        $result = $callback->testSetData('key1', 'value1')
            ->testSetData('key2', 'value2');

        $this->assertInstanceOf(TestCallback::class, $result);

        $data = $callback->getData();
        $this->assertEquals('value1', $data['key1']);
        $this->assertEquals('value2', $data['key2']);
    }

    #[Test]
    public function processStatusSetsCompletedAtOnSuccess(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::COMPLETED, 'MSG001', 'test message');

        $data = $callback->getData();
        $this->assertArrayHasKey('completed_at', $data);
    }

    #[Test]
    public function processStatusSetsFailedAtOnFailure(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::FAILED, 'MSG001', 'test message');

        $data = $callback->getData();
        $this->assertArrayHasKey('failed_at', $data);
    }

    #[Test]
    public function processStatusSetsPendingUserActionFlag(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING_END_USER_ACTION, 'MSG001', 'test message');

        $data = $callback->getData();
        $this->assertTrue($data['pending_user_action']);
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    #[Test]
    public function getStatusReturnsCurrentStatus(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::COMPLETED, 'MSG001', 'test message');

        $this->assertEquals(OrderStatus::COMPLETED, $callback->getStatus());
    }

    #[Test]
    public function getMessageReturnsMessageContent(): void
    {
        $this->db->seed('mod_test_orders', [
            ['order_id' => 'TEST123', 'whmcs_service_id' => 1, 'user_id' => 1]
        ]);

        $callback = new TestCallback($this->params, 'TEST123', $this->client, $this->db);
        $callback->process('TEST123', OrderStatus::PENDING, 'MSG001', 'my test message');

        $this->assertEquals('my test message', $callback->getMessage());
    }
}
