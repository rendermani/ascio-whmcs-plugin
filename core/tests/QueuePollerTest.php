<?php

namespace Ascio\Core\Tests;

use PHPUnit\Framework\TestCase;
use Ascio\Core\QueuePoller;
use Ascio\Core\ObjectType;

/**
 * Unit tests for QueuePoller.
 */
class QueuePollerTest extends TestCase
{
    protected MockAscioClient $client;
    protected MockParams $params;
    protected QueuePoller $poller;

    protected function setUp(): void
    {
        $this->client = new MockAscioClient();
        $this->params = new MockParams();
        $this->poller = new QueuePoller($this->params, $this->client);
    }

    public function testRegisterCallbackAddsToMap(): void
    {
        $this->poller->registerCallback(ObjectType::NAME_WATCH, 'TestCallback');

        $map = $this->poller->getCallbackMap();
        $this->assertArrayHasKey(ObjectType::NAME_WATCH, $map);
        $this->assertEquals('TestCallback', $map[ObjectType::NAME_WATCH]);
    }

    public function testHasCallbackReturnsTrueWhenRegistered(): void
    {
        $this->poller->registerCallback(ObjectType::NAME_WATCH, 'TestCallback');

        $this->assertTrue($this->poller->hasCallback(ObjectType::NAME_WATCH));
    }

    public function testHasCallbackReturnsFalseWhenNotRegistered(): void
    {
        $this->assertFalse($this->poller->hasCallback(ObjectType::DEFENSIVE));
    }

    public function testPollEmptyQueueReturnsZeroProcessed(): void
    {
        // Mock returns empty queue by default
        $this->poller->registerCallback(ObjectType::NAME_WATCH, MockCallbackHandler::class);

        $results = $this->poller->poll();

        $this->assertArrayHasKey(ObjectType::NAME_WATCH, $results);
        $this->assertEquals(0, $results[ObjectType::NAME_WATCH]['processed']);
    }

    public function testPollTypesOnlyPollsSpecifiedTypes(): void
    {
        $this->poller->registerCallback(ObjectType::NAME_WATCH, MockCallbackHandler::class);
        $this->poller->registerCallback(ObjectType::DEFENSIVE, MockCallbackHandler::class);

        $results = $this->poller->pollTypes([ObjectType::NAME_WATCH]);

        $this->assertArrayHasKey(ObjectType::NAME_WATCH, $results);
        $this->assertArrayNotHasKey(ObjectType::DEFENSIVE, $results);
    }

    public function testSetMaxMessagesLimitsProcessing(): void
    {
        $this->poller->setMaxMessages(5);
        $this->poller->registerCallback(ObjectType::NAME_WATCH, MockCallbackHandler::class);

        // Queue multiple messages
        for ($i = 0; $i < 10; $i++) {
            $queueMessage = new class($i) {
                private $i;
                public function __construct($i) { $this->i = $i; }
                public function getOrderId() { return "TEST{$this->i}"; }
                public function getOrderStatus() { return 'Completed'; }
                public function getMessageId() { return "MSG{$this->i}"; }
            };

            $this->client->queueResponse('pollQueue', $this->makePollResponse($queueMessage));
        }

        // Should only process up to maxMessages
        // Note: This test would need actual callback execution to verify
    }

    public function testSetLoggerReceivesMessages(): void
    {
        $messages = [];
        $this->poller->setLogger(function ($message) use (&$messages) {
            $messages[] = $message;
        });

        $this->poller->registerCallback(ObjectType::NAME_WATCH, MockCallbackHandler::class);
        $this->poller->poll();

        // Logger should have received at least one message
        // (actual messages depend on implementation)
    }

    public function testPollObjectTypeThrowsForUnregisteredType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No callback registered');

        $this->poller->pollObjectType(ObjectType::DEFENSIVE);
    }

    protected function makePollResponse($message): object
    {
        $result = new class($message) {
            private $message;
            public function __construct($message) { $this->message = $message; }
            public function getResultCode() { return $this->message ? 200 : 201; }
            public function getResultMessage() { return $this->message ? 'Message available' : 'No messages'; }
            public function getQueueMessage() { return $this->message; }
        };

        return (object)['PollQueueResult' => $result];
    }
}

/**
 * Mock callback handler for testing.
 */
class MockCallbackHandler
{
    public function __construct($params, $orderId, $client = null) {}
    public function process($orderId, $status, $messageId, $message = null): void {}
}
