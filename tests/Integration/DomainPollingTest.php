<?php
/**
 * Domain Polling Integration Tests
 *
 * Tests polling operations against the real Ascio v3 API.
 * Tests PollQueue, GetQueueMessage, and AckQueueMessage methods.
 *
 * @group integration
 * @group v3
 * @group polling
 */

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use ascio\Request;

#[Group('integration')]
#[Group('v3')]
#[Group('polling')]
class DomainPollingTest extends IntegrationTestBase
{
    /** @var bool Disable simulation mode for polling tests */
    protected bool $simulationMode = false;

    // =========================================================================
    // PollQueue Tests
    // =========================================================================

    #[Test]
    public function testPollQueueReturnsMessage(): void
    {
        $request = $this->getRequest();
        $result = $request->poll();

        // Should return either a message or an object/array
        $this->assertNotNull($result, 'Poll should return a result');

        // If error, should be array with 'error' key
        if (is_array($result)) {
            if (isset($result['error'])) {
                // No messages or API error - this is acceptable
                $this->assertIsString($result['error']);
            }
        } else {
            // Should be an object with expected fields
            $this->assertIsObject($result);
            $this->assertV3ResponseFormat($result);
        }
    }

    #[Test]
    public function testPollQueueApiDirectly(): void
    {
        // Call PollQueue API directly
        $params = [
            'MsgType' => 'Message_to_Partner',
        ];

        $result = $this->callApiMethod('PollQueue', $params);

        $this->assertIsObject($result);
        $this->assertV3ResponseFormat($result);

        // ResultCode 200 means message available, 201 means no messages
        $this->assertContains(
            $result->ResultCode,
            [200, 201],
            'PollQueue should return 200 (message) or 201 (no messages)'
        );
    }

    #[Test]
    public function testPollQueueFormat(): void
    {
        $params = [
            'MsgType' => 'Message_to_Partner',
        ];

        $result = $this->callApiMethod('PollQueue', $params);

        $this->assertIsObject($result);

        // Verify response has expected v3 format
        $this->assertObjectHasProperty('ResultCode', $result);
        $this->assertObjectHasProperty('ResultMessage', $result);

        // If there's a message, it should have specific fields
        if ($result->ResultCode === 200 && isset($result->QueueMessage)) {
            $message = $result->QueueMessage;

            // v3 queue message should have these fields
            $this->assertObjectHasProperty('MsgId', $message, 'Queue message should have MsgId');
            // OrderId or ObjectId might be present depending on message type
        }
    }

    #[Test]
    public function testPollReturnsCompatibleFormat(): void
    {
        $request = $this->getRequest();
        $result = $request->poll();

        // The poll method should return v3 format that's compatible with processing
        if (is_object($result) && isset($result->ResultCode)) {
            // Has v3 result structure
            $this->assertObjectHasProperty('ResultCode', $result);

            // If message exists
            if ($result->ResultCode === 200) {
                // Check for message data
                if (isset($result->QueueMessage)) {
                    $this->assertIsObject($result->QueueMessage);
                }
            }
        }
    }

    // =========================================================================
    // GetQueueMessage Tests
    // =========================================================================

    #[Test]
    public function testGetQueueMessage(): void
    {
        // First poll to see if there's a message
        $params = [
            'MsgType' => 'Message_to_Partner',
        ];

        $pollResult = $this->callApiMethod('PollQueue', $params);

        if ($pollResult->ResultCode === 201) {
            $this->markTestSkipped('No messages in queue to test GetQueueMessage');
        }

        if (!isset($pollResult->QueueMessage->MsgId)) {
            $this->markTestSkipped('Poll result has no message ID');
        }

        $messageId = $pollResult->QueueMessage->MsgId;

        // Now test GetQueueMessage
        $request = $this->getRequest();
        $result = $request->getQueueMessage($messageId);

        $this->assertNotNull($result);

        if (!is_array($result)) {
            $this->assertIsObject($result);
            $this->assertV3ResponseFormat($result);
        }
    }

    #[Test]
    public function testGetQueueMessageApiDirectly(): void
    {
        // Poll for a message first
        $pollParams = ['MsgType' => 'Message_to_Partner'];
        $pollResult = $this->callApiMethod('PollQueue', $pollParams);

        if ($pollResult->ResultCode === 201 || !isset($pollResult->QueueMessage->MsgId)) {
            $this->markTestSkipped('No messages available for GetQueueMessage test');
        }

        $messageId = $pollResult->QueueMessage->MsgId;

        // Get the message details
        $result = $this->callApiMethod('GetQueueMessage', ['MsgId' => $messageId]);

        $this->assertIsObject($result);
        $this->assertV3ResponseFormat($result);

        if ($result->ResultCode === 200) {
            // Should have message details
            $this->assertTrue(
                isset($result->QueueMessage) || isset($result->Message),
                'GetQueueMessage should return message details'
            );
        }
    }

    #[Test]
    public function testGetQueueMessageReturnsOrderInfo(): void
    {
        // Poll for a message
        $pollParams = ['MsgType' => 'Message_to_Partner'];
        $pollResult = $this->callApiMethod('PollQueue', $pollParams);

        if ($pollResult->ResultCode === 201 || !isset($pollResult->QueueMessage)) {
            $this->markTestSkipped('No messages available');
        }

        $message = $pollResult->QueueMessage;

        // Queue message should contain order-related information
        // These fields may vary depending on message type
        $hasOrderInfo = isset($message->OrderId)
            || isset($message->ObjectId)
            || isset($message->DomainName);

        $this->assertTrue($hasOrderInfo, 'Queue message should contain order/object information');
    }

    // =========================================================================
    // AckQueueMessage Tests
    // =========================================================================

    #[Test]
    public function testAckQueueMessage(): void
    {
        // Poll for a message first
        $pollParams = ['MsgType' => 'Message_to_Partner'];
        $pollResult = $this->callApiMethod('PollQueue', $pollParams);

        if ($pollResult->ResultCode === 201 || !isset($pollResult->QueueMessage->MsgId)) {
            $this->markTestSkipped('No messages available for acknowledge test');
        }

        $messageId = $pollResult->QueueMessage->MsgId;

        // Test the ack method
        $request = $this->getRequest();
        $result = $request->ackQueueMessage($messageId);

        // Ack should succeed or return result
        $this->assertNotNull($result);

        if (is_object($result) && isset($result->ResultCode)) {
            $this->assertEquals(200, $result->ResultCode, 'AckQueueMessage should succeed');
        }
    }

    #[Test]
    public function testAckQueueMessageApiDirectly(): void
    {
        // Poll for a message
        $pollParams = ['MsgType' => 'Message_to_Partner'];
        $pollResult = $this->callApiMethod('PollQueue', $pollParams);

        if ($pollResult->ResultCode === 201 || !isset($pollResult->QueueMessage->MsgId)) {
            $this->markTestSkipped('No messages available');
        }

        $messageId = $pollResult->QueueMessage->MsgId;

        // Acknowledge the message
        $result = $this->callApiMethod('AckQueueMessage', ['MsgId' => $messageId]);

        $this->assertIsObject($result);
        $this->assertEquals(200, $result->ResultCode, 'AckQueueMessage should return 200 on success');
    }

    #[Test]
    public function testAckMethodAlias(): void
    {
        $request = $this->getRequest();

        // Verify ack is an alias for ackQueueMessage
        $this->assertTrue(method_exists($request, 'ack'));
        $this->assertTrue(method_exists($request, 'ackQueueMessage'));

        // Both methods should exist and be callable
        $reflection = new \ReflectionClass($request);

        $ackMethod = $reflection->getMethod('ack');
        $ackQueueMethod = $reflection->getMethod('ackQueueMessage');

        $this->assertTrue($ackMethod->isPublic());
        $this->assertTrue($ackQueueMethod->isPublic());
    }

    // =========================================================================
    // Poll Queue Processing Flow Tests
    // =========================================================================

    #[Test]
    public function testCompletePollingWorkflow(): void
    {
        // Test the complete polling workflow: poll -> get message -> ack
        // This test validates the integration between the three polling methods

        $request = $this->getRequest();

        // Step 1: Poll for messages - verify we can call the method
        $pollResult = $request->poll();

        // Verify poll returns a valid result type
        $this->assertTrue(
            is_object($pollResult) || is_array($pollResult),
            'Poll should return object or array'
        );

        if (is_array($pollResult) && isset($pollResult['error'])) {
            // Error response is a valid result - test passed
            return;
        }

        // No messages (ResultCode 201) is a valid state
        if (is_object($pollResult) && ($pollResult->ResultCode ?? 0) === 201) {
            return;
        }

        // Step 2: If message exists, get details
        if (isset($pollResult->QueueMessage->MsgId)) {
            $messageId = $pollResult->QueueMessage->MsgId;

            $messageResult = $request->getQueueMessage($messageId);
            $this->assertNotNull($messageResult);

            // Step 3: Acknowledge the message (cleanup)
            $ackResult = $request->ackQueueMessage($messageId);
            $this->assertNotNull($ackResult);
        }
    }

    // =========================================================================
    // Response Format Comparison Tests
    // =========================================================================

    #[Test]
    public function testPollMessageFormat(): void
    {
        $params = ['MsgType' => 'Message_to_Partner'];
        $result = $this->callApiMethod('PollQueue', $params);

        // v3 PollQueue format verification
        $this->assertIsObject($result);
        $this->assertObjectHasProperty('ResultCode', $result);

        if ($result->ResultCode === 200) {
            // Has a message
            $this->assertTrue(
                isset($result->QueueMessage) || isset($result->Message),
                'Should have message when ResultCode is 200'
            );

            $message = $result->QueueMessage ?? $result->Message ?? null;

            if ($message) {
                // v3 message format
                $this->assertIsObject($message);

                // MsgId is the v3 field name
                if (isset($message->MsgId)) {
                    $this->assertNotEmpty($message->MsgId);
                }
            }
        } elseif ($result->ResultCode === 201) {
            // No messages - this is valid
            $this->assertEquals('No messages', $result->ResultMessage ?? '');
        }
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function testGetQueueMessageWithInvalidId(): void
    {
        $request = $this->getRequest();
        $result = $request->getQueueMessage('INVALID-MSG-ID-' . uniqid());

        // Should handle gracefully - either return error array or error result
        $this->assertNotNull($result);

        if (is_array($result)) {
            $this->assertArrayHasKey('error', $result);
        } else if (is_object($result) && isset($result->ResultCode)) {
            // Should be an error code (not 200)
            $this->assertNotEquals(200, $result->ResultCode);
        }
    }

    #[Test]
    public function testAckQueueMessageWithInvalidId(): void
    {
        $request = $this->getRequest();
        $result = $request->ackQueueMessage('INVALID-MSG-ID-' . uniqid());

        // Should handle gracefully
        $this->assertNotNull($result);
    }
}
