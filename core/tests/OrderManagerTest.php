<?php

namespace Ascio\Core\Tests;

use PHPUnit\Framework\TestCase;
use Ascio\Core\OrderManager;
use Ascio\Core\OrderType;
use Ascio\Core\AscioApiException;

/**
 * Unit tests for OrderManager.
 */
class OrderManagerTest extends TestCase
{
    protected MockAscioClient $client;
    protected OrderManager $orderManager;

    protected function setUp(): void
    {
        $this->client = new MockAscioClient();
        $this->orderManager = new OrderManager($this->client, null, null, true);
    }

    public function testSubmitReturnsOrderId(): void
    {
        $orderRequest = new \stdClass();
        $orderRequest->Type = OrderType::REGISTER;

        $orderId = $this->orderManager->submit(
            OrderType::REGISTER,
            $orderRequest,
            ['serviceId' => 1, 'userId' => 1]
        );

        $this->assertStringStartsWith('TEST', $orderId);
        $this->assertCount(1, $this->client->getCalls('createOrder'));
    }

    public function testSubmitInvalidOrderTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order type');

        $this->orderManager->submit(
            'InvalidType',
            new \stdClass(),
            []
        );
    }

    public function testSubmitHandlesApiError(): void
    {
        // Queue an error response
        $errorResult = new class {
            public function getResultCode() { return 400; }
            public function getResultMessage() { return 'Invalid request'; }
            public function getOrderInfo() { return null; }
            public function getErrors() { return null; }
        };

        $this->client->queueResponse('createOrder', (object)['CreateOrderResult' => $errorResult]);

        $this->expectException(AscioApiException::class);

        $this->orderManager->submit(
            OrderType::REGISTER,
            new \stdClass(),
            []
        );
    }

    public function testValidateReturnsValidationResult(): void
    {
        $orderRequest = new \stdClass();

        $result = $this->orderManager->validate(OrderType::REGISTER, $orderRequest);

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['valid']);
    }

    public function testGetOrderReturnsOrderInfo(): void
    {
        $result = $this->orderManager->getOrder('TEST12345');

        $this->assertNotNull($result);
        $this->assertCount(1, $this->client->getCalls('getOrder'));
    }

    public function testOrderIdPrefixInTestMode(): void
    {
        $orderManager = new OrderManager($this->client, null, null, true);

        $orderId = $orderManager->submit(
            OrderType::REGISTER,
            new \stdClass(),
            []
        );

        $this->assertStringStartsWith('TEST', $orderId);
    }

    public function testOrderIdPrefixInLiveMode(): void
    {
        $orderManager = new OrderManager($this->client, null, null, false);

        $orderId = $orderManager->submit(
            OrderType::REGISTER,
            new \stdClass(),
            []
        );

        $this->assertStringStartsWith('A', $orderId);
    }
}
