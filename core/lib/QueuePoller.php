<?php

namespace Ascio\Core;

use Ascio\Core\Contracts\AscioClientInterface;
use Ascio\Core\Contracts\ParamsInterface;

/**
 * Unified queue poller for all Ascio product types.
 * Polls the message queue and dispatches to appropriate callback handlers.
 */
class QueuePoller
{
    /** @var AscioClientInterface API client */
    protected AscioClientInterface $client;

    /** @var ParamsInterface Parameters */
    protected ParamsInterface $params;

    /** @var ResponseHandler Response handler */
    protected ResponseHandler $responseHandler;

    /** @var array Map of object types to callback classes */
    protected array $callbackMap = [];

    /** @var int Maximum messages to process per poll cycle */
    protected int $maxMessages = 100;

    /** @var callable|null Logger callback */
    protected $logger;

    /**
     * @param ParamsInterface $params Credential provider
     * @param AscioClientInterface|null $client Optional API client
     */
    public function __construct(ParamsInterface $params, ?AscioClientInterface $client = null)
    {
        $this->params = $params;
        $this->client = $client ?? new AscioClient($params);
        $this->responseHandler = new ResponseHandler('ascio_poller');
    }

    /**
     * Register a callback handler for an object type.
     *
     * @param string $objectType Object type constant
     * @param string $callbackClass Fully qualified callback class name
     * @return self
     */
    public function registerCallback(string $objectType, string $callbackClass): self
    {
        $this->callbackMap[$objectType] = $callbackClass;
        return $this;
    }

    /**
     * Register all default callback handlers.
     *
     * @return self
     */
    public function registerDefaultCallbacks(): self
    {
        // These will be registered by individual modules
        // Example: $this->registerCallback(ObjectType::NAME_WATCH, MonitoringCallback::class);
        return $this;
    }

    /**
     * Set maximum messages per poll cycle.
     *
     * @param int $max
     * @return self
     */
    public function setMaxMessages(int $max): self
    {
        $this->maxMessages = $max;
        return $this;
    }

    /**
     * Set logger callback.
     *
     * @param callable $logger Function(string $message, array $context)
     * @return self
     */
    public function setLogger(callable $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Poll all registered object types.
     *
     * @return array Results by object type
     */
    public function poll(): array
    {
        $results = [];

        foreach ($this->callbackMap as $objectType => $callbackClass) {
            $results[$objectType] = $this->pollObjectType($objectType, $callbackClass);
        }

        return $results;
    }

    /**
     * Poll a specific object type.
     *
     * @param string $objectType Object type to poll
     * @param string|null $callbackClass Optional callback class override
     * @return array Processing results
     */
    public function pollObjectType(string $objectType, ?string $callbackClass = null): array
    {
        $callbackClass = $callbackClass ?? ($this->callbackMap[$objectType] ?? null);

        if (!$callbackClass) {
            throw new \InvalidArgumentException("No callback registered for object type: {$objectType}");
        }

        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $messageCount = 0;

        while ($messageCount < $this->maxMessages) {
            try {
                $pollResult = $this->client->pollQueue($objectType, MessageType::MESSAGE_TO_PARTNER);
                $result = $pollResult->PollQueueResult;

                // No more messages
                if ($result->getResultCode() !== 200 || !$result->getQueueMessage()) {
                    break;
                }

                $queueMessage = $result->getQueueMessage();
                $orderId = $queueMessage->getOrderId();
                $status = $queueMessage->getOrderStatus();
                $messageId = $queueMessage->getMessageId();

                $this->log("Processing {$objectType}: Order {$orderId}, Status {$status}");

                // Create and run callback
                $callback = new $callbackClass($this->params, $orderId, $this->client);
                $callback->process($orderId, $status, $messageId, $queueMessage);

                $results['processed']++;
                $results['success']++;
                $messageCount++;

            } catch (AscioApiException $e) {
                $results['processed']++;
                $results['failed']++;
                $results['errors'][] = [
                    'orderId' => $orderId ?? 'unknown',
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ];
                $this->log("Error processing: " . $e->getMessage(), ['error' => $e->toArray()]);
                $messageCount++;

            } catch (\Exception $e) {
                $results['processed']++;
                $results['failed']++;
                $results['errors'][] = [
                    'orderId' => $orderId ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
                $this->log("Unexpected error: " . $e->getMessage());
                $messageCount++;
                break; // Stop on unexpected errors
            }
        }

        return $results;
    }

    /**
     * Poll specific object types only.
     *
     * @param array $objectTypes Array of object type constants
     * @return array Results by object type
     */
    public function pollTypes(array $objectTypes): array
    {
        $results = [];

        foreach ($objectTypes as $objectType) {
            if (isset($this->callbackMap[$objectType])) {
                $results[$objectType] = $this->pollObjectType($objectType);
            }
        }

        return $results;
    }

    /**
     * Log a message.
     *
     * @param string $message
     * @param array $context
     */
    protected function log(string $message, array $context = []): void
    {
        if ($this->logger) {
            ($this->logger)($message, $context);
        }
    }

    /**
     * Get registered callback map.
     *
     * @return array
     */
    public function getCallbackMap(): array
    {
        return $this->callbackMap;
    }

    /**
     * Check if an object type has a registered callback.
     *
     * @param string $objectType
     * @return bool
     */
    public function hasCallback(string $objectType): bool
    {
        return isset($this->callbackMap[$objectType]);
    }
}
