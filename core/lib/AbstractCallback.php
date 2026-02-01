<?php

namespace Ascio\Core;

use Ascio\Core\Contracts\CallbackInterface;
use Ascio\Core\Contracts\AscioClientInterface;
use Ascio\Core\Contracts\DatabaseInterface;
use Ascio\Core\Contracts\ParamsInterface;

/**
 * Abstract base class for callback handlers.
 * Processes async order status updates from Ascio queue.
 */
abstract class AbstractCallback implements CallbackInterface
{
    /** @var AscioClientInterface API client */
    protected AscioClientInterface $client;

    /** @var ResponseHandler Response handler */
    protected ResponseHandler $responseHandler;

    /** @var DatabaseInterface Database adapter */
    protected DatabaseInterface $db;

    /** @var ParamsInterface Parameters */
    protected ParamsInterface $params;

    /** @var string Order ID being processed */
    protected string $orderId;

    /** @var string Current status */
    protected string $status;

    /** @var string Message ID */
    protected string $messageId;

    /** @var mixed Queue message content */
    protected $message;

    /** @var object|null Order info from API */
    protected $order;

    /** @var array Data to be written to database */
    protected array $data = [];

    /** @var int|null WHMCS service ID */
    protected ?int $serviceId = null;

    /** @var int|null WHMCS user ID */
    protected ?int $userId = null;

    /**
     * @param ParamsInterface $params Credential provider
     * @param string $orderId Order ID to process
     * @param AscioClientInterface|null $client Optional API client
     * @param DatabaseInterface|null $db Optional database adapter
     */
    public function __construct(
        ParamsInterface $params,
        string $orderId,
        ?AscioClientInterface $client = null,
        ?DatabaseInterface $db = null
    ) {
        $this->params = $params;
        $this->orderId = $this->normalizeOrderId($orderId);
        $this->client = $client ?? new AscioClient($params);
        $this->db = $db ?? new CapsuleDatabase();
        $this->responseHandler = new ResponseHandler($this->getModuleName());

        $this->loadServiceData();
    }

    /**
     * {@inheritdoc}
     */
    public function process(string $orderId, string $status, string $messageId, $message = null): void
    {
        $this->status = $status;
        $this->messageId = $messageId;

        // Get message details if not provided
        if ($message !== null) {
            $this->message = $message;
        } else {
            $this->fetchMessage($messageId);
        }

        // Fetch order if status requires it
        if (OrderStatus::requiresOrderFetch($status)) {
            $this->fetchOrder();
        }

        // Module-specific processing
        $this->processStatus();

        // Update database
        $this->data['status'] = $this->status;
        $this->writeStatus();

        // Acknowledge message
        $this->ack();
    }

    /**
     * Process the status update. Override in subclasses for product-specific logic.
     *
     * @return void
     */
    abstract protected function processStatus(): void;

    /**
     * Get the object from the order (SSL certificate, NameWatch, etc.).
     *
     * @return mixed
     */
    abstract protected function getObjectFromOrder();

    /**
     * Get the module name for logging.
     *
     * @return string
     */
    abstract protected function getModuleName(): string;

    /**
     * Normalize order ID format.
     *
     * @param string|int $orderId
     * @return string
     */
    protected function normalizeOrderId($orderId): string
    {
        if (intval($orderId) > 0 && !str_starts_with((string)$orderId, 'TEST') && !str_starts_with((string)$orderId, 'A')) {
            return $this->params->isTestMode() ? "TEST{$orderId}" : "A{$orderId}";
        }
        return (string)$orderId;
    }

    /**
     * Load service data from database.
     */
    protected function loadServiceData(): void
    {
        $data = $this->db->first(
            $this->getTableName(),
            ['whmcs_service_id', 'user_id'],
            ['order_id' => $this->orderId]
        );

        if ($data) {
            $this->serviceId = $data->whmcs_service_id;
            $this->userId = $data->user_id;
        }
    }

    /**
     * Fetch order information from API.
     */
    protected function fetchOrder(): void
    {
        try {
            $response = $this->client->getOrder($this->orderId);
            $result = $this->responseHandler->handleResponse($response, 'GetOrder');
            $this->order = $result->getOrderInfo();
        } catch (AscioApiException $e) {
            $this->responseHandler->logCall('GetOrder', $this->orderId, null, $e->toArray());
            throw $e;
        }
    }

    /**
     * Fetch queue message from API.
     *
     * @param string $messageId
     */
    protected function fetchMessage(string $messageId): void
    {
        try {
            $response = $this->client->getQueueMessage($messageId);
            $result = $response->GetQueueMessageResult;
            $this->message = $result->getMessage();
        } catch (\Exception $e) {
            $this->responseHandler->logCall('GetQueueMessage', $messageId, null, $e->getMessage());
            throw new AscioApiException($e->getMessage(), 500, [], $messageId, $e);
        }
    }

    /**
     * Acknowledge the queue message.
     */
    protected function ack(): void
    {
        $this->client->ackQueueMessage($this->messageId);
    }

    /**
     * Write status and data to database.
     */
    protected function writeStatus(): void
    {
        $this->db->update(
            $this->getTableName(),
            $this->data,
            ['order_id' => $this->orderId]
        );

        $this->setWhmcsStatus();
    }

    /**
     * Update WHMCS service status.
     */
    protected function setWhmcsStatus(): void
    {
        $whmcsStatus = OrderStatus::toWhmcsStatus($this->status);

        if ($this->serviceId && function_exists('localAPI')) {
            $result = localAPI('UpdateClientProduct', [
                'serviceid' => $this->serviceId,
                'status' => $whmcsStatus,
            ]);

            if (($result['result'] ?? '') !== 'success') {
                $this->responseHandler->logCall(
                    'UpdateClientProduct',
                    ['serviceid' => $this->serviceId, 'status' => $whmcsStatus],
                    $result,
                    $result['message'] ?? 'Unknown error'
                );
            }
        }
    }

    /**
     * Get service ID.
     *
     * @return int|null
     */
    public function getServiceId(): ?int
    {
        return $this->serviceId;
    }

    /**
     * Get user ID.
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get current status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get order info.
     *
     * @return object|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Get message content.
     *
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get data array.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set data value.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    protected function setData(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Check if status indicates failure.
     *
     * @return bool
     */
    protected function isFailed(): bool
    {
        return OrderStatus::isFailure($this->status);
    }

    /**
     * Check if status indicates completion.
     *
     * @return bool
     */
    protected function isCompleted(): bool
    {
        return OrderStatus::isSuccess($this->status);
    }

    /**
     * Check if status indicates pending user action.
     *
     * @return bool
     */
    protected function isPendingUserAction(): bool
    {
        return $this->status === OrderStatus::PENDING_END_USER_ACTION;
    }
}
