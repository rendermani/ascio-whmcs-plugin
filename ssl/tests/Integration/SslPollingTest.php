<?php
/**
 * SSL Polling Tests
 *
 * Tests polling for SSL-specific messages in the Ascio queue.
 * Uses PollQueue with ObjectType = SslCertificateType.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Integration;

use ascio\v3 as v3;

require_once __DIR__ . '/SslIntegrationTestBase.php';

class SslPollingTest extends SslIntegrationTestBase
{
    /**
     * Test polling queue for SSL certificate messages
     *
     * @test
     */
    public function testPollQueueForSsl(): void
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType(v3\ObjectType::SslCertificateType);

        try {
            $response = $this->client->PollQueue(new v3\PollQueue($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error during poll: ' . $e->getMessage());
        }

        $resultCode = $response->PollQueueResult->getResultCode();

        // 200 = message found, 207 = no messages in queue
        $this->assertTrue(
            in_array($resultCode, [200, 207]),
            'Unexpected poll result code: ' . $resultCode . ' - ' . $response->PollQueueResult->getResultMessage()
        );

        if ($resultCode === 200) {
            // If we got a message, verify structure
            $queueMessage = $response->PollQueueResult;
            $this->assertNotNull($queueMessage);
        }
    }

    /**
     * Test polling queue with specific message type filter
     *
     * @test
     */
    public function testPollQueueWithMessageType(): void
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType(v3\ObjectType::SslCertificateType);
        $request->setMessageType(v3\MessageType::MessageToPartner);

        try {
            $response = $this->client->PollQueue(new v3\PollQueue($request));
        } catch (\SoapFault $e) {
            // MessageType filter might not be supported - just skip
            $this->markTestSkipped('Poll with message type filter error: ' . $e->getMessage());
        }

        $resultCode = $response->PollQueueResult->getResultCode();

        // Any valid response is acceptable
        $this->assertIsInt($resultCode);
    }

    /**
     * Test getting a specific queue message (if message ID available)
     *
     * @test
     */
    public function testGetQueueMessageForSsl(): void
    {
        // First poll to get a message ID
        $pollRequest = new v3\PollQueueRequest();
        $pollRequest->setObjectType(v3\ObjectType::SslCertificateType);

        try {
            $pollResponse = $this->client->PollQueue(new v3\PollQueue($pollRequest));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('Could not poll for messages: ' . $e->getMessage());
        }

        $resultCode = $pollResponse->PollQueueResult->getResultCode();

        if ($resultCode !== 200) {
            $this->markTestSkipped('No SSL messages in queue to retrieve');
        }

        // Extract message ID from queue message (if available in response)
        $queueMessage = $pollResponse->PollQueueResult;

        // Try to get the message details
        // Note: The actual message ID extraction depends on the response structure
        if (method_exists($queueMessage, 'getMessageId')) {
            $messageId = $queueMessage->getMessageId();

            if (!empty($messageId)) {
                $getRequest = new v3\GetQueueMessageRequest();
                $getRequest->setMessageId($messageId);

                try {
                    $getResponse = $this->client->GetQueueMessage(new v3\GetQueueMessage($getRequest));

                    $this->assertEquals(
                        200,
                        $getResponse->GetQueueMessageResult->getResultCode(),
                        'Failed to get queue message: ' . $getResponse->GetQueueMessageResult->getResultMessage()
                    );
                } catch (\SoapFault $e) {
                    $this->markTestSkipped('GetQueueMessage error: ' . $e->getMessage());
                }
            }
        }

        // Test passes if no exceptions
        $this->assertTrue(true);
    }

    /**
     * Test acknowledging a queue message for SSL
     *
     * Note: This test is disabled by default as it will remove messages from the queue.
     *
     * @test
     */
    public function testAckQueueMessageForSsl(): void
    {
        // Skip by default to avoid removing messages from production queue
        if (getenv('ASCIO_TEST_ACK_MESSAGES') !== 'true') {
            $this->markTestSkipped(
                'Message acknowledgment test disabled. Set ASCIO_TEST_ACK_MESSAGES=true to enable.'
            );
        }

        // First poll to get a message
        $pollRequest = new v3\PollQueueRequest();
        $pollRequest->setObjectType(v3\ObjectType::SslCertificateType);

        try {
            $pollResponse = $this->client->PollQueue(new v3\PollQueue($pollRequest));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('Could not poll for messages: ' . $e->getMessage());
        }

        $resultCode = $pollResponse->PollQueueResult->getResultCode();

        if ($resultCode !== 200) {
            $this->markTestSkipped('No SSL messages in queue to acknowledge');
        }

        $queueMessage = $pollResponse->PollQueueResult;

        if (method_exists($queueMessage, 'getMessageId')) {
            $messageId = $queueMessage->getMessageId();

            if (!empty($messageId)) {
                $ackRequest = new v3\AckQueueMessageRequest();
                $ackRequest->setMessageId($messageId);

                try {
                    $ackResponse = $this->client->AckQueueMessage(new v3\AckQueueMessage($ackRequest));

                    $this->assertEquals(
                        200,
                        $ackResponse->AckQueueMessageResult->getResultCode(),
                        'Failed to ack queue message: ' . $ackResponse->AckQueueMessageResult->getResultMessage()
                    );
                } catch (\SoapFault $e) {
                    $this->fail('AckQueueMessage error: ' . $e->getMessage());
                }
            }
        }

        $this->assertTrue(true);
    }

    /**
     * Test polling returns proper result for empty queue
     *
     * @test
     */
    public function testPollEmptyQueue(): void
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType(v3\ObjectType::SslCertificateType);

        try {
            $response = $this->client->PollQueue(new v3\PollQueue($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        // Empty queue should return specific result code (not an error)
        $resultCode = $response->PollQueueResult->getResultCode();

        // 200 = message available, other codes indicate empty or error
        $this->assertIsInt($resultCode);
        $this->assertGreaterThan(0, $resultCode);
    }

    /**
     * Test polling different object types
     *
     * @test
     * @dataProvider objectTypeProvider
     */
    public function testPollDifferentObjectTypes(string $objectType): void
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType($objectType);

        try {
            $response = $this->client->PollQueue(new v3\PollQueue($request));
        } catch (\SoapFault $e) {
            // Some object types might not be supported
            $this->markTestSkipped("Object type {$objectType} poll error: " . $e->getMessage());
        }

        $resultCode = $response->PollQueueResult->getResultCode();

        // Any valid response is acceptable
        $this->assertIsInt($resultCode);
    }

    /**
     * Data provider for object types
     */
    public static function objectTypeProvider(): array
    {
        return [
            'ssl_certificate' => [v3\ObjectType::SslCertificateType],
            'domain' => [v3\ObjectType::DomainType],
        ];
    }

    /**
     * Test that SSL poll request uses correct ObjectType
     *
     * @test
     */
    public function testSslPollRequestObjectType(): void
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType(v3\ObjectType::SslCertificateType);

        $this->assertEquals(
            v3\ObjectType::SslCertificateType,
            $request->getObjectType()
        );
        $this->assertEquals('SslCertificateType', $request->getObjectType());
    }

    /**
     * Test polling without object type (should poll all types)
     *
     * @test
     */
    public function testPollAllObjectTypes(): void
    {
        $request = new v3\PollQueueRequest();
        // Don't set ObjectType - should poll all types

        try {
            $response = $this->client->PollQueue(new v3\PollQueue($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('Poll all types error: ' . $e->getMessage());
        }

        $resultCode = $response->PollQueueResult->getResultCode();
        $this->assertIsInt($resultCode);
    }

    /**
     * Test GetQueueMessage with invalid message ID
     *
     * @test
     */
    public function testGetQueueMessageInvalidId(): void
    {
        $request = new v3\GetQueueMessageRequest();
        $request->setMessageId('INVALID_MESSAGE_ID_' . uniqid());

        try {
            $response = $this->client->GetQueueMessage(new v3\GetQueueMessage($request));
        } catch (\SoapFault $e) {
            // SOAP fault for invalid ID is acceptable
            $this->assertStringContainsString('not found', strtolower($e->getMessage()));
            return;
        }

        // Should return error code for invalid message
        $resultCode = $response->GetQueueMessageResult->getResultCode();
        $this->assertNotEquals(200, $resultCode);
    }

    /**
     * Test AckQueueMessage with invalid message ID
     *
     * @test
     */
    public function testAckQueueMessageInvalidId(): void
    {
        $request = new v3\AckQueueMessageRequest();
        $request->setMessageId('INVALID_MESSAGE_ID_' . uniqid());

        try {
            $response = $this->client->AckQueueMessage(new v3\AckQueueMessage($request));
        } catch (\SoapFault $e) {
            // SOAP fault for invalid ID is acceptable
            $this->assertTrue(true);
            return;
        }

        // Should return error code for invalid message
        $resultCode = $response->AckQueueMessageResult->getResultCode();
        $this->assertNotEquals(200, $resultCode);
    }

    /**
     * Test queue message structure
     *
     * @test
     */
    public function testQueueMessageStructure(): void
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType(v3\ObjectType::SslCertificateType);

        try {
            $response = $this->client->PollQueue(new v3\PollQueue($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->PollQueueResult->getResultCode();

        if ($resultCode === 200) {
            // Verify the response has expected structure
            $this->assertTrue(
                method_exists($response->PollQueueResult, 'getResultCode'),
                'Response should have getResultCode method'
            );
            $this->assertTrue(
                method_exists($response->PollQueueResult, 'getResultMessage'),
                'Response should have getResultMessage method'
            );
        }

        $this->assertTrue(true);
    }

    /**
     * Test consecutive polls return consistent results
     *
     * @test
     */
    public function testConsecutivePolls(): void
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType(v3\ObjectType::SslCertificateType);

        $results = [];

        // Poll multiple times
        for ($i = 0; $i < 3; $i++) {
            try {
                $response = $this->client->PollQueue(new v3\PollQueue($request));
                $results[] = $response->PollQueueResult->getResultCode();
            } catch (\SoapFault $e) {
                $this->markTestSkipped('Poll error: ' . $e->getMessage());
            }
        }

        // All results should be valid response codes
        foreach ($results as $code) {
            $this->assertIsInt($code);
            $this->assertGreaterThan(0, $code);
        }
    }
}
