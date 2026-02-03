<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use AscioQueue;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;

/**
 * Unit tests for AscioQueue class
 *
 * Tests the job queue functionality for Ascio domain operations.
 * The queue stores API method calls and their serialized requests
 * for asynchronous processing.
 */
#[CoversClass(AscioQueue::class)]
class QueueTest extends TestCase
{
    private AscioQueue $queue;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        $this->queue = new AscioQueue();
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesEmptyQueue(): void
    {
        $queue = new AscioQueue();

        $this->assertInstanceOf(AscioQueue::class, $queue);
    }

    // =========================================================================
    // add() method tests
    // =========================================================================

    #[Test]
    public function addInsertsJobToDatabase(): void
    {
        $method = 'RegisterDomain';
        $request = ['domain' => 'example.com', 'period' => 1];
        $result = $this->createMockResult('ORD-12345');

        $lastId = $this->queue->add($method, $request, $result);

        $insertCalls = WhmcsFunctionsMock::getInsertQueryCalls();
        $this->assertCount(1, $insertCalls);
        $this->assertEquals('tblasciojobs', $insertCalls[0]['table']);
        $this->assertEquals('ORD-12345', $insertCalls[0]['data']['order_id']);
        $this->assertEquals($method, $insertCalls[0]['data']['method']);
        $this->assertEquals(serialize($request), $insertCalls[0]['data']['request']);
    }

    #[Test]
    public function addReturnsInsertedId(): void
    {
        WhmcsFunctionsMock::setInsertQueryLastId(99);

        $result = $this->createMockResult('ORD-001');
        $lastId = $this->queue->add('TestMethod', [], $result);

        $this->assertEquals(100, $lastId);
    }

    #[Test]
    public function addStoresLastIdForChaining(): void
    {
        WhmcsFunctionsMock::setInsertQueryLastId(0);

        $result1 = $this->createMockResult('ORD-001');
        $result2 = $this->createMockResult('ORD-002');

        // First job - no last_id
        $this->queue->add('FirstMethod', ['data' => 1], $result1);

        // Second job - should have last_id pointing to first job
        $this->queue->add('SecondMethod', ['data' => 2], $result2);

        $insertCalls = WhmcsFunctionsMock::getInsertQueryCalls();

        $this->assertEquals(null, $insertCalls[0]['data']['last_id']);
        $this->assertEquals(1, $insertCalls[1]['data']['last_id']);
    }

    #[Test]
    public function addSerializesRequestData(): void
    {
        $complexRequest = [
            'domain' => 'example.com',
            'contacts' => [
                'registrant' => ['name' => 'John Doe', 'email' => 'john@example.com'],
                'admin' => ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
            ],
            'nameservers' => ['ns1.example.com', 'ns2.example.com'],
        ];

        $result = $this->createMockResult('ORD-123');
        $this->queue->add('RegisterDomain', $complexRequest, $result);

        $insertCalls = WhmcsFunctionsMock::getInsertQueryCalls();
        $storedRequest = $insertCalls[0]['data']['request'];

        $this->assertEquals(serialize($complexRequest), $storedRequest);
        $this->assertEquals($complexRequest, unserialize($storedRequest));
    }

    #[Test]
    public function addHandlesEmptyRequest(): void
    {
        $result = $this->createMockResult('ORD-EMPTY');
        $lastId = $this->queue->add('CheckDomain', [], $result);

        $insertCalls = WhmcsFunctionsMock::getInsertQueryCalls();
        $this->assertEquals(serialize([]), $insertCalls[0]['data']['request']);
    }

    // =========================================================================
    // updateOrderId() method tests
    // =========================================================================

    #[Test]
    public function updateOrderIdUpdatesJobRecord(): void
    {
        $lastId = 42;
        $orderId = 'ORD-NEW-123';

        $result = $this->queue->updateOrderId($lastId, $orderId);

        $updateCalls = WhmcsFunctionsMock::getUpdateQueryCalls();
        $this->assertCount(1, $updateCalls);
        $this->assertEquals('tblasciojobs', $updateCalls[0]['table']);
        $this->assertEquals(['order_id' => $orderId], $updateCalls[0]['data']);
        $this->assertEquals(['last_id' => $lastId], $updateCalls[0]['where']);
    }

    #[Test]
    public function updateOrderIdReturnsLastId(): void
    {
        $lastId = 55;
        $orderId = 'ORD-XYZ';

        $result = $this->queue->updateOrderId($lastId, $orderId);

        $this->assertEquals($lastId, $result);
    }

    #[Test]
    public function updateOrderIdHandlesNullOrderId(): void
    {
        $lastId = 10;
        $orderId = null;

        $result = $this->queue->updateOrderId($lastId, $orderId);

        $updateCalls = WhmcsFunctionsMock::getUpdateQueryCalls();
        $this->assertEquals(['order_id' => null], $updateCalls[0]['data']);
        $this->assertEquals($lastId, $result);
    }

    // =========================================================================
    // getNextRequest() method tests
    // =========================================================================

    #[Test]
    public function getNextRequestReturnsJobData(): void
    {
        $serializedRequest = serialize(['domain' => 'test.com']);
        WhmcsFunctionsMock::setSelectQueryResults([
            [
                ['id' => 5, 'method' => 'RenewDomain', 'request' => $serializedRequest]
            ]
        ]);

        $result = $this->queue->getNextRequest(4);

        $this->assertIsArray($result);
        $this->assertEquals(5, $result['id']);
        $this->assertEquals('RenewDomain', $result['method']);
        $this->assertEquals(['domain' => 'test.com'], $result['request']);
    }

    #[Test]
    public function getNextRequestQueriesCorrectTable(): void
    {
        WhmcsFunctionsMock::setSelectQueryResults([[[]]]);

        $this->queue->getNextRequest(10);

        $selectCalls = WhmcsFunctionsMock::getSelectQueryCalls();
        $this->assertCount(1, $selectCalls);
        $this->assertEquals('tblasciojobs', $selectCalls[0]['table']);
        $this->assertEquals('id,method,request', $selectCalls[0]['fields']);
        $this->assertEquals(['last_id' => 10], $selectCalls[0]['where']);
    }

    #[Test]
    public function getNextRequestReturnsFalseWhenNoJobFound(): void
    {
        // Empty result set - no rows
        WhmcsFunctionsMock::setSelectQueryResults([[]]);

        $result = $this->queue->getNextRequest(999);

        $this->assertFalse($result);
    }

    #[Test]
    public function getNextRequestUnserializesRequestData(): void
    {
        $originalRequest = [
            'domain' => 'complex.com',
            'nested' => ['key' => 'value', 'number' => 42],
        ];
        $serializedRequest = serialize($originalRequest);

        WhmcsFunctionsMock::setSelectQueryResults([
            [
                ['id' => 1, 'method' => 'TransferDomain', 'request' => $serializedRequest]
            ]
        ]);

        $result = $this->queue->getNextRequest(0);

        $this->assertEquals($originalRequest, $result['request']);
    }

    #[Test]
    public function getNextRequestHandlesEmptySerializedRequest(): void
    {
        $serializedRequest = serialize([]);

        WhmcsFunctionsMock::setSelectQueryResults([
            [
                ['id' => 2, 'method' => 'GetDomainInfo', 'request' => $serializedRequest]
            ]
        ]);

        $result = $this->queue->getNextRequest(1);

        $this->assertEquals([], $result['request']);
    }

    // =========================================================================
    // getLastId() method tests
    // =========================================================================

    #[Test]
    public function getLastIdReturnsIdForOrderId(): void
    {
        WhmcsFunctionsMock::setGetQueryValResult('tblasciojobs', 'id', 'order_id', 123);

        $result = $this->queue->getLastId('ORD-FIND-ME');

        $this->assertEquals(123, $result);
    }

    #[Test]
    public function getLastIdReturnsNullWhenNotFound(): void
    {
        // No specific result set - will return null by default
        WhmcsFunctionsMock::reset();

        $result = $this->queue->getLastId('ORD-NONEXISTENT');

        $this->assertNull($result);
    }

    #[Test]
    public function getLastIdQueriesCorrectTableAndField(): void
    {
        // The get_query_val function doesn't track calls directly,
        // but we can verify the result behavior
        WhmcsFunctionsMock::setGetQueryValResult('tblasciojobs', 'id', 'order_id', 456);

        $result = $this->queue->getLastId('ORD-TEST');

        $this->assertEquals(456, $result);
    }

    // =========================================================================
    // Integration-style tests (multiple operations)
    // =========================================================================

    #[Test]
    public function chainedJobsHaveCorrectLastIdReferences(): void
    {
        WhmcsFunctionsMock::setInsertQueryLastId(100);

        $result1 = $this->createMockResult('ORD-CHAIN-1');
        $result2 = $this->createMockResult('ORD-CHAIN-2');
        $result3 = $this->createMockResult('ORD-CHAIN-3');

        $id1 = $this->queue->add('Step1', ['step' => 1], $result1);
        $id2 = $this->queue->add('Step2', ['step' => 2], $result2);
        $id3 = $this->queue->add('Step3', ['step' => 3], $result3);

        $insertCalls = WhmcsFunctionsMock::getInsertQueryCalls();

        $this->assertEquals(101, $id1);
        $this->assertEquals(102, $id2);
        $this->assertEquals(103, $id3);

        // First job has no last_id (was null before)
        $this->assertNull($insertCalls[0]['data']['last_id']);
        // Second job references first
        $this->assertEquals(101, $insertCalls[1]['data']['last_id']);
        // Third job references second
        $this->assertEquals(102, $insertCalls[2]['data']['last_id']);
    }

    #[Test]
    public function multipleQueuesAreIndependent(): void
    {
        WhmcsFunctionsMock::setInsertQueryLastId(0);

        $queue1 = new AscioQueue();
        $queue2 = new AscioQueue();

        $result1 = $this->createMockResult('ORD-Q1-1');
        $result2 = $this->createMockResult('ORD-Q2-1');

        $id1 = $queue1->add('Queue1Method', [], $result1);
        $id2 = $queue2->add('Queue2Method', [], $result2);

        $insertCalls = WhmcsFunctionsMock::getInsertQueryCalls();

        // First queue's job has no last_id
        $this->assertNull($insertCalls[0]['data']['last_id']);
        // Second queue's job also has no last_id (separate instance)
        $this->assertNull($insertCalls[1]['data']['last_id']);
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Create a mock result object with an order ID
     */
    private function createMockResult(string $orderId): object
    {
        return (object) [
            'order' => (object) [
                'OrderId' => $orderId,
            ],
        ];
    }
}
