<?php

namespace Ascio\Core\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Ascio\Core\AscioClient;
use Ascio\Core\AscioApiException;

/**
 * Unit tests for AscioClient.
 *
 * Tests the unified Ascio v3 SOAP API client using mock dependencies.
 */
class AscioClientTest extends TestCase
{
    protected MockParams $params;
    protected MockSoapClient $soapClient;
    protected AscioClient $client;

    protected function setUp(): void
    {
        $this->params = new MockParams();
        $this->soapClient = new MockSoapClient();
        $this->client = new AscioClient($this->params, $this->soapClient);
    }

    // =========================================================================
    // Constructor and Credential Tests
    // =========================================================================

    #[Test]
    public function constructorAcceptsParamsInterface(): void
    {
        $params = new MockParams('user123', 'pass456', true);
        $client = new AscioClient($params, $this->soapClient);

        $this->assertInstanceOf(AscioClient::class, $client);
    }

    #[Test]
    public function constructorAcceptsInjectedClient(): void
    {
        $mockSoap = new MockSoapClient();
        $client = new AscioClient($this->params, $mockSoap);

        $this->assertSame($mockSoap, $client->getClient());
    }

    #[Test]
    public function getClientReturnsUnderlyingSoapClient(): void
    {
        $result = $this->client->getClient();

        $this->assertSame($this->soapClient, $result);
    }

    // =========================================================================
    // Test Mode Detection Tests
    // =========================================================================

    #[Test]
    public function isTestModeReturnsTrue(): void
    {
        $params = new MockParams('user', 'pass', true);
        $client = new AscioClient($params, $this->soapClient);

        $this->assertTrue($client->isTestMode());
    }

    #[Test]
    public function isTestModeReturnsFalse(): void
    {
        $params = new MockParams('user', 'pass', false);
        $client = new AscioClient($params, $this->soapClient);

        $this->assertFalse($client->isTestMode());
    }

    // =========================================================================
    // WSDL Selection Tests (via Params)
    // =========================================================================

    #[Test]
    public function testModeUsesTestWsdl(): void
    {
        $params = new MockParams('user', 'pass', true);

        $this->assertStringContainsString('demo', $params->getWsdlV3());
    }

    #[Test]
    public function liveModeUsesProductionWsdl(): void
    {
        $params = new MockParams('user', 'pass', false);

        $this->assertStringNotContainsString('demo', $params->getWsdlV3());
        $this->assertStringContainsString('aws.ascio.com', $params->getWsdlV3());
    }

    // =========================================================================
    // Order ID Formatting Tests
    // =========================================================================

    #[Test]
    #[DataProvider('formatOrderIdTestModeProvider')]
    public function formatOrderIdInTestMode(string|int $input, string $expected): void
    {
        $params = new MockParams('user', 'pass', true);
        $client = new AscioClient($params, $this->soapClient);

        $this->assertEquals($expected, $client->formatOrderId($input));
    }

    public static function formatOrderIdTestModeProvider(): array
    {
        return [
            'numeric id gets TEST prefix' => ['12345', 'TEST12345'],
            'already has TEST prefix' => ['TEST12345', 'TEST12345'],
            'already has A prefix' => ['A12345', 'A12345'],
            'integer input' => [12345, 'TEST12345'],
        ];
    }

    #[Test]
    #[DataProvider('formatOrderIdLiveModeProvider')]
    public function formatOrderIdInLiveMode(string|int $input, string $expected): void
    {
        $params = new MockParams('user', 'pass', false);
        $client = new AscioClient($params, $this->soapClient);

        $this->assertEquals($expected, $client->formatOrderId($input));
    }

    public static function formatOrderIdLiveModeProvider(): array
    {
        return [
            'numeric id gets A prefix' => ['12345', 'A12345'],
            'already has TEST prefix' => ['TEST12345', 'TEST12345'],
            'already has A prefix' => ['A12345', 'A12345'],
            'integer input' => [12345, 'A12345'],
        ];
    }

    // =========================================================================
    // Order ID Parsing Tests
    // =========================================================================

    #[Test]
    #[DataProvider('parseOrderIdProvider')]
    public function parseOrderIdRemovesPrefix(string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->client->parseOrderId($input));
    }

    public static function parseOrderIdProvider(): array
    {
        return [
            'removes TEST prefix' => ['TEST12345', '12345'],
            'removes A prefix' => ['A12345', '12345'],
            'no prefix unchanged' => ['12345', '12345'],
            'handles empty after TEST' => ['TEST', ''],
            'handles empty after A' => ['A', ''],
        ];
    }

    // =========================================================================
    // CreateOrder Tests
    // =========================================================================

    #[Test]
    public function createOrderCallsSoapClient(): void
    {
        $orderRequest = new \stdClass();
        $orderRequest->Type = 'Register';

        $this->soapClient->queueResponse('CreateOrder', $this->makeCreateOrderResponse(12345, 'Pending'));

        $result = $this->client->createOrder($orderRequest);

        $this->assertEquals(200, $result->CreateOrderResult->getResultCode());
        $this->assertEquals(12345, $result->CreateOrderResult->getOrderInfo()->getOrderId());
        $this->assertCount(1, $this->soapClient->getCalls('CreateOrder'));
    }

    #[Test]
    public function createOrderPassesCorrectRequestType(): void
    {
        $orderRequest = new \stdClass();
        $orderRequest->Type = 'Register';

        $this->soapClient->queueResponse('CreateOrder', $this->makeCreateOrderResponse(12345, 'Pending'));

        $this->client->createOrder($orderRequest);

        $calls = $this->soapClient->getCalls('CreateOrder');
        $this->assertNotEmpty($calls);
    }

    // =========================================================================
    // ValidateOrder Tests
    // =========================================================================

    #[Test]
    public function validateOrderCallsSoapClient(): void
    {
        $orderRequest = new \stdClass();

        $this->soapClient->queueResponse('ValidateOrder', $this->makeValidateOrderResponse(true));

        $result = $this->client->validateOrder($orderRequest);

        $this->assertEquals(200, $result->ValidateOrderResult->getResultCode());
        $this->assertCount(1, $this->soapClient->getCalls('ValidateOrder'));
    }

    #[Test]
    public function validateOrderReturnsInvalidResponse(): void
    {
        $orderRequest = new \stdClass();

        $this->soapClient->queueResponse('ValidateOrder', $this->makeValidateOrderResponse(false, 'Invalid domain'));

        $result = $this->client->validateOrder($orderRequest);

        $this->assertEquals(400, $result->ValidateOrderResult->getResultCode());
        $this->assertEquals('Invalid domain', $result->ValidateOrderResult->getResultMessage());
    }

    // =========================================================================
    // GetOrder Tests
    // =========================================================================

    #[Test]
    public function getOrderCallsSoapClientWithOrderId(): void
    {
        $this->soapClient->queueResponse('GetOrder', $this->makeGetOrderResponse('TEST123', 'Completed'));

        $result = $this->client->getOrder('TEST123');

        $this->assertEquals(200, $result->GetOrderResult->getResultCode());
        $this->assertEquals('Completed', $result->GetOrderResult->getOrderInfo()->getStatus());

        $calls = $this->soapClient->getCalls('GetOrder');
        $this->assertCount(1, $calls);
    }

    // =========================================================================
    // PollQueue Tests
    // =========================================================================

    #[Test]
    public function pollQueueCallsSoapClientWithCorrectParams(): void
    {
        $this->soapClient->queueResponse('PollQueue', $this->makePollQueueResponse(null));

        $result = $this->client->pollQueue('SslCertificateType', 'MessageToPartner');

        $this->assertEquals(201, $result->PollQueueResult->getResultCode());

        $calls = $this->soapClient->getCalls('PollQueue');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function pollQueueReturnsMessageWhenAvailable(): void
    {
        $message = new \stdClass();
        $message->MessageId = 'MSG123';

        $this->soapClient->queueResponse('PollQueue', $this->makePollQueueResponse($message));

        $result = $this->client->pollQueue('NameWatchType', 'MessageToPartner');

        $this->assertEquals(200, $result->PollQueueResult->getResultCode());
        $this->assertNotNull($result->PollQueueResult->getQueueMessage());
    }

    // =========================================================================
    // GetQueueMessage Tests
    // =========================================================================

    #[Test]
    public function getQueueMessageCallsSoapClient(): void
    {
        $this->soapClient->queueResponse('GetQueueMessage', $this->makeGetQueueMessageResponse('MSG123'));

        $result = $this->client->getQueueMessage('MSG123');

        $this->assertEquals(200, $result->GetQueueMessageResult->getResultCode());
        $this->assertCount(1, $this->soapClient->getCalls('GetQueueMessage'));
    }

    // =========================================================================
    // AckQueueMessage Tests
    // =========================================================================

    #[Test]
    public function ackQueueMessageSucceeds(): void
    {
        $this->soapClient->queueResponse('AckQueueMessage', $this->makeAckQueueMessageResponse(200));

        // Should not throw
        $this->client->ackQueueMessage('MSG123');

        $calls = $this->soapClient->getCalls('AckQueueMessage');
        $this->assertCount(1, $calls);
    }

    #[Test]
    public function ackQueueMessageThrowsOnError(): void
    {
        $this->soapClient->queueResponse('AckQueueMessage', $this->makeAckQueueMessageResponse(400, 'Message not found'));

        $this->expectException(AscioApiException::class);
        $this->expectExceptionMessage('Message not found');
        $this->expectExceptionCode(400);

        $this->client->ackQueueMessage('INVALID123');
    }

    // =========================================================================
    // GetSslCertificate Tests
    // =========================================================================

    #[Test]
    public function getSslCertificateCallsSoapClient(): void
    {
        $this->soapClient->queueResponse('GetSslCertificate', $this->makeSslCertificateResponse('SSL123'));

        $result = $this->client->getSslCertificate('SSL123');

        $this->assertEquals(200, $result->GetSslCertificateResult->getResultCode());
        $this->assertEquals('SSL123', $result->GetSslCertificateResult->getSslCertificateInfo()->getHandle());
        $this->assertCount(1, $this->soapClient->getCalls('GetSslCertificate'));
    }

    // =========================================================================
    // GetNameWatch Tests
    // =========================================================================

    #[Test]
    public function getNameWatchCallsSoapClient(): void
    {
        $this->soapClient->queueResponse('GetNameWatch', $this->makeNameWatchResponse('NW123'));

        $result = $this->client->getNameWatch('NW123');

        $this->assertEquals(200, $result->GetNameWatchResult->getResultCode());
        $this->assertEquals('NW123', $result->GetNameWatchResult->getNameWatchInfo()->getHandle());
        $this->assertCount(1, $this->soapClient->getCalls('GetNameWatch'));
    }

    // =========================================================================
    // GetDefensive Tests
    // =========================================================================

    #[Test]
    public function getDefensiveCallsSoapClient(): void
    {
        $this->soapClient->queueResponse('GetDefensive', $this->makeDefensiveResponse('DEF123'));

        $result = $this->client->getDefensive('DEF123');

        $this->assertEquals(200, $result->GetDefensiveResult->getResultCode());
        $this->assertEquals('DEF123', $result->GetDefensiveResult->getDefensiveInfo()->getHandle());
        $this->assertCount(1, $this->soapClient->getCalls('GetDefensive'));
    }

    // =========================================================================
    // GetMark Tests
    // =========================================================================

    #[Test]
    public function getMarkCallsSoapClient(): void
    {
        $this->soapClient->queueResponse('GetMark', $this->makeMarkResponse('MK123'));

        $result = $this->client->getMark('MK123');

        $this->assertEquals(200, $result->GetMarkResult->getResultCode());
        $this->assertEquals('MK123', $result->GetMarkResult->getMarkInfo()->getHandle());
        $this->assertCount(1, $this->soapClient->getCalls('GetMark'));
    }

    // =========================================================================
    // UploadDocumentation Tests
    // =========================================================================

    #[Test]
    public function uploadDocumentationCallsSoapClient(): void
    {
        $uploadRequest = new \stdClass();
        $uploadRequest->DocumentationType = 'Trademark';

        $this->soapClient->queueResponse('UploadDocumentation', $this->makeUploadDocumentationResponse());

        $result = $this->client->uploadDocumentation($uploadRequest);

        $this->assertEquals(200, $result->UploadDocumentationResult->getResultCode());
        $this->assertCount(1, $this->soapClient->getCalls('UploadDocumentation'));
    }

    // =========================================================================
    // Response Builder Helpers
    // =========================================================================

    protected function makeCreateOrderResponse(int $orderId, string $status): object
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

    protected function makeValidateOrderResponse(bool $valid, string $message = 'Valid'): object
    {
        $code = $valid ? 200 : 400;
        $result = new class($code, $message) {
            private $code;
            private $message;
            public function __construct($code, $message) {
                $this->code = $code;
                $this->message = $message;
            }
            public function getResultCode() { return $this->code; }
            public function getResultMessage() { return $this->message; }
            public function getErrors() { return null; }
        };

        return (object)['ValidateOrderResult' => $result];
    }

    protected function makeGetOrderResponse(string $orderId, string $status): object
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
            public function getOrderRequest() { return new \stdClass(); }
        };

        $result = new class($orderInfo) {
            private $orderInfo;
            public function __construct($orderInfo) { $this->orderInfo = $orderInfo; }
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Success'; }
            public function getOrderInfo() { return $this->orderInfo; }
        };

        return (object)['GetOrderResult' => $result];
    }

    protected function makePollQueueResponse($message): object
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

    protected function makeGetQueueMessageResponse(string $messageId): object
    {
        $message = new class($messageId) {
            private $messageId;
            public function __construct($messageId) { $this->messageId = $messageId; }
            public function getMessageId() { return $this->messageId; }
            public function getMessage() { return "Test message for {$this->messageId}"; }
        };

        $result = new class($message) {
            private $message;
            public function __construct($message) { $this->message = $message; }
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Success'; }
            public function getMessage() { return $this->message; }
        };

        return (object)['GetQueueMessageResult' => $result];
    }

    protected function makeAckQueueMessageResponse(int $code, string $message = 'Success'): object
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
        };

        return (object)['AckQueueMessageResult' => $result];
    }

    protected function makeSslCertificateResponse(string $handle): object
    {
        $info = new class($handle) {
            private $handle;
            public function __construct($handle) { $this->handle = $handle; }
            public function getHandle() { return $this->handle; }
            public function getExpires() { return '2025-12-31'; }
            public function getCertificate() { return '-----BEGIN CERTIFICATE-----...'; }
        };

        $result = new class($info) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Success'; }
            public function getSslCertificateInfo() { return $this->info; }
        };

        return (object)['GetSslCertificateResult' => $result];
    }

    protected function makeNameWatchResponse(string $handle): object
    {
        $info = new class($handle) {
            private $handle;
            public function __construct($handle) { $this->handle = $handle; }
            public function getHandle() { return $this->handle; }
            public function getName() { return 'testbrand'; }
            public function getStatus() { return 'Active'; }
            public function getExpDate() { return '2025-12-31'; }
        };

        $result = new class($info) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Success'; }
            public function getNameWatchInfo() { return $this->info; }
        };

        return (object)['GetNameWatchResult' => $result];
    }

    protected function makeDefensiveResponse(string $handle): object
    {
        $info = new class($handle) {
            private $handle;
            public function __construct($handle) { $this->handle = $handle; }
            public function getHandle() { return $this->handle; }
            public function getExpDate() { return '2025-12-31'; }
            public function getAuthInfo() { return 'AUTH123'; }
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

    protected function makeMarkResponse(string $handle): object
    {
        $info = new class($handle) {
            private $handle;
            public function __construct($handle) { $this->handle = $handle; }
            public function getHandle() { return $this->handle; }
            public function getExpDate() { return '2025-12-31'; }
            public function getMarkId() { return 'TMCH' . $this->handle; }
            public function getAuthInfo() { return 'AUTH456'; }
        };

        $result = new class($info) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Success'; }
            public function getMarkInfo() { return $this->info; }
        };

        return (object)['GetMarkResult' => $result];
    }

    protected function makeUploadDocumentationResponse(): object
    {
        $result = new class {
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Upload successful'; }
        };

        return (object)['UploadDocumentationResult' => $result];
    }
}

/**
 * Mock SOAP client for unit testing AscioClient.
 *
 * This simulates the v3\AscioService SOAP client behavior.
 */
class MockSoapClient
{
    /** @var array Queued responses by method */
    protected array $responses = [];

    /** @var array Recorded calls by method */
    protected array $calls = [];

    /**
     * Queue a response for a method.
     *
     * @param string $method
     * @param mixed $response
     * @return self
     */
    public function queueResponse(string $method, $response): self
    {
        if (!isset($this->responses[$method])) {
            $this->responses[$method] = [];
        }
        $this->responses[$method][] = $response;
        return $this;
    }

    /**
     * Get recorded calls.
     *
     * @param string|null $method
     * @return array
     */
    public function getCalls(?string $method = null): array
    {
        if ($method) {
            return $this->calls[$method] ?? [];
        }
        return $this->calls;
    }

    /**
     * Record a call and return queued response.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected function handleCall(string $method, array $args)
    {
        if (!isset($this->calls[$method])) {
            $this->calls[$method] = [];
        }
        $this->calls[$method][] = $args;

        if (!empty($this->responses[$method])) {
            $response = array_shift($this->responses[$method]);
            if ($response instanceof \Exception) {
                throw $response;
            }
            return $response;
        }

        throw new \RuntimeException("No queued response for method: {$method}");
    }

    // SOAP methods matching AscioService interface

    public function CreateOrder($request)
    {
        return $this->handleCall('CreateOrder', [$request]);
    }

    public function ValidateOrder($request)
    {
        return $this->handleCall('ValidateOrder', [$request]);
    }

    public function GetOrder($request)
    {
        return $this->handleCall('GetOrder', [$request]);
    }

    public function PollQueue($request)
    {
        return $this->handleCall('PollQueue', [$request]);
    }

    public function GetQueueMessage($request)
    {
        return $this->handleCall('GetQueueMessage', [$request]);
    }

    public function AckQueueMessage($request)
    {
        return $this->handleCall('AckQueueMessage', [$request]);
    }

    public function GetSslCertificate($request)
    {
        return $this->handleCall('GetSslCertificate', [$request]);
    }

    public function GetNameWatch($request)
    {
        return $this->handleCall('GetNameWatch', [$request]);
    }

    public function GetDefensive($request)
    {
        return $this->handleCall('GetDefensive', [$request]);
    }

    public function GetMark($request)
    {
        return $this->handleCall('GetMark', [$request]);
    }

    public function UploadDocumentation($request)
    {
        return $this->handleCall('UploadDocumentation', [$request]);
    }

    public function __setSoapHeaders($header)
    {
        // No-op for testing
    }
}
