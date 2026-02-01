<?php

namespace Ascio\Core;

use Ascio\Core\Contracts\OrderManagerInterface;
use Ascio\Core\Contracts\AscioClientInterface;
use Ascio\Core\Contracts\DatabaseInterface;

/**
 * Generic order submission manager for all Ascio products.
 */
class OrderManager implements OrderManagerInterface
{
    /** @var AscioClientInterface API client */
    protected AscioClientInterface $client;

    /** @var ResponseHandler Response handler */
    protected ResponseHandler $responseHandler;

    /** @var DatabaseInterface|null Database adapter */
    protected ?DatabaseInterface $db;

    /** @var bool Test mode flag */
    protected bool $testmode;

    /**
     * @param AscioClientInterface $client API client
     * @param ResponseHandler|null $responseHandler Response handler
     * @param DatabaseInterface|null $db Database adapter
     * @param bool $testmode Test mode flag
     */
    public function __construct(
        AscioClientInterface $client,
        ?ResponseHandler $responseHandler = null,
        ?DatabaseInterface $db = null,
        bool $testmode = true
    ) {
        $this->client = $client;
        $this->responseHandler = $responseHandler ?? new ResponseHandler();
        $this->db = $db;
        $this->testmode = $testmode;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(string $orderType, $orderRequest, array $context): string
    {
        // Validate order type
        if (!OrderType::isValid($orderType)) {
            throw new \InvalidArgumentException("Invalid order type: {$orderType}");
        }

        try {
            $response = $this->client->createOrder($orderRequest);
            $result = $this->responseHandler->handleResponse($response, 'CreateOrder');

            $orderId = $this->formatOrderId($result->getOrderInfo()->getOrderId());

            // Log the call
            $this->responseHandler->logCall(
                'CreateOrder',
                $orderRequest,
                $response,
                null
            );

            return $orderId;

        } catch (AscioApiException $e) {
            $this->responseHandler->logCall(
                'CreateOrder',
                $orderRequest,
                null,
                $e->toArray()
            );
            throw $e;
        } catch (\SoapFault $e) {
            $this->responseHandler->logCall(
                'CreateOrder',
                $orderRequest,
                null,
                ['soapFault' => $e->getMessage()]
            );
            throw new AscioApiException(
                "SOAP Error: " . $e->getMessage(),
                500,
                [],
                $orderRequest,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $orderType, $orderRequest): array
    {
        try {
            $response = $this->client->validateOrder($orderRequest);
            $result = $response->ValidateOrderResult;

            return [
                'valid' => $result->getResultCode() === 200,
                'code' => $result->getResultCode(),
                'message' => $result->getResultMessage(),
                'errors' => $this->responseHandler->extractErrors($result),
            ];

        } catch (\SoapFault $e) {
            return [
                'valid' => false,
                'code' => 500,
                'message' => "SOAP Error: " . $e->getMessage(),
                'errors' => [],
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(string $orderId)
    {
        $response = $this->client->getOrder($orderId);
        return $this->responseHandler->handleResponse($response, 'GetOrder');
    }

    /**
     * Format order ID with environment prefix.
     *
     * @param string|int $orderId Raw order ID
     * @return string Prefixed order ID
     */
    protected function formatOrderId($orderId): string
    {
        if (str_starts_with((string)$orderId, 'TEST') || str_starts_with((string)$orderId, 'A')) {
            return (string)$orderId;
        }

        return $this->testmode ? "TEST{$orderId}" : "A{$orderId}";
    }

    /**
     * Store order result in database.
     *
     * @param string $table Table name
     * @param string $orderId Order ID
     * @param array $context WHMCS context
     * @param string $status Order status
     * @return void
     */
    public function storeOrderResult(string $table, string $orderId, array $context, string $status): void
    {
        if ($this->db === null) {
            return;
        }

        $data = [
            'order_id' => $orderId,
            'whmcs_service_id' => $context['serviceId'] ?? null,
            'user_id' => $context['userId'] ?? null,
            'status' => $status,
        ];

        $this->db->update($table, $data, ['whmcs_service_id' => $context['serviceId']]);
    }
}
