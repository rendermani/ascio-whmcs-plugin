<?php

namespace Ascio\Core\Tests;

use Ascio\Core\Contracts\AscioClientInterface;
use Ascio\Core\AscioApiException;

/**
 * Mock Ascio API client for unit testing.
 */
class MockAscioClient implements AscioClientInterface
{
    /** @var array Queued responses */
    protected array $responses = [];

    /** @var array Recorded calls */
    protected array $calls = [];

    /** @var bool Whether to auto-generate successful responses */
    protected bool $autoRespond = true;

    /** @var int Order ID counter */
    protected int $orderIdCounter = 1000;

    /**
     * Queue a response for a specific method.
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
     * Queue an exception for a method.
     *
     * @param string $method
     * @param \Exception $exception
     * @return self
     */
    public function queueException(string $method, \Exception $exception): self
    {
        return $this->queueResponse($method, $exception);
    }

    /**
     * Get recorded calls for a method.
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
     * Get the next queued response for a method.
     *
     * @param string $method
     * @return mixed
     */
    protected function getResponse(string $method)
    {
        if (!empty($this->responses[$method])) {
            $response = array_shift($this->responses[$method]);
            if ($response instanceof \Exception) {
                throw $response;
            }
            return $response;
        }
        return null;
    }

    /**
     * Record a call.
     *
     * @param string $method
     * @param array $args
     */
    protected function recordCall(string $method, array $args): void
    {
        if (!isset($this->calls[$method])) {
            $this->calls[$method] = [];
        }
        $this->calls[$method][] = $args;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrder($orderRequest)
    {
        $this->recordCall('createOrder', ['orderRequest' => $orderRequest]);

        $response = $this->getResponse('createOrder');
        if ($response) {
            return $response;
        }

        // Auto-generate success response
        return $this->makeOrderResponse(++$this->orderIdCounter, 'Pending');
    }

    /**
     * {@inheritdoc}
     */
    public function validateOrder($orderRequest)
    {
        $this->recordCall('validateOrder', ['orderRequest' => $orderRequest]);

        $response = $this->getResponse('validateOrder');
        if ($response) {
            return $response;
        }

        return $this->makeValidateResponse(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(string $orderId)
    {
        $this->recordCall('getOrder', ['orderId' => $orderId]);

        $response = $this->getResponse('getOrder');
        if ($response) {
            return $response;
        }

        return $this->makeGetOrderResponse($orderId, 'Completed');
    }

    /**
     * {@inheritdoc}
     */
    public function pollQueue(string $objectType, string $messageType)
    {
        $this->recordCall('pollQueue', ['objectType' => $objectType, 'messageType' => $messageType]);

        $response = $this->getResponse('pollQueue');
        if ($response) {
            return $response;
        }

        // Return empty queue by default
        return $this->makePollResponse(null);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueMessage(string $messageId)
    {
        $this->recordCall('getQueueMessage', ['messageId' => $messageId]);

        $response = $this->getResponse('getQueueMessage');
        if ($response) {
            return $response;
        }

        return $this->makeQueueMessageResponse($messageId);
    }

    /**
     * {@inheritdoc}
     */
    public function ackQueueMessage(string $messageId): void
    {
        $this->recordCall('ackQueueMessage', ['messageId' => $messageId]);

        $response = $this->getResponse('ackQueueMessage');
        if ($response instanceof \Exception) {
            throw $response;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSslCertificate(string $handle)
    {
        $this->recordCall('getSslCertificate', ['handle' => $handle]);

        $response = $this->getResponse('getSslCertificate');
        if ($response) {
            return $response;
        }

        return $this->makeSslCertificateResponse($handle);
    }

    /**
     * {@inheritdoc}
     */
    public function getNameWatch(string $handle)
    {
        $this->recordCall('getNameWatch', ['handle' => $handle]);

        $response = $this->getResponse('getNameWatch');
        if ($response) {
            return $response;
        }

        return $this->makeNameWatchResponse($handle);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefensive(string $handle)
    {
        $this->recordCall('getDefensive', ['handle' => $handle]);

        $response = $this->getResponse('getDefensive');
        if ($response) {
            return $response;
        }

        return $this->makeDefensiveResponse($handle);
    }

    /**
     * {@inheritdoc}
     */
    public function getMark(string $handle)
    {
        $this->recordCall('getMark', ['handle' => $handle]);

        $response = $this->getResponse('getMark');
        if ($response) {
            return $response;
        }

        return $this->makeMarkResponse($handle);
    }

    /**
     * {@inheritdoc}
     */
    public function uploadDocumentation($uploadRequest)
    {
        $this->recordCall('uploadDocumentation', ['uploadRequest' => $uploadRequest]);

        $response = $this->getResponse('uploadDocumentation');
        if ($response) {
            return $response;
        }

        return $this->makeUploadResponse();
    }

    // Response builders

    protected function makeOrderResponse(int $orderId, string $status): object
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

    protected function makeValidateResponse(bool $valid): object
    {
        $result = new class($valid) {
            private $valid;
            public function __construct($valid) { $this->valid = $valid; }
            public function getResultCode() { return $this->valid ? 200 : 400; }
            public function getResultMessage() { return $this->valid ? 'Valid' : 'Invalid'; }
            public function getErrors() { return null; }
        };

        return (object)['ValidateOrderResult' => $result];
    }

    protected function makeGetOrderResponse(string $orderId, string $status): object
    {
        $orderRequest = new class {
            public function getNameWatch() { return new class { public function getHandle() { return 'NW123'; } }; }
            public function getDefensive() { return new class { public function getHandle() { return 'DEF123'; } }; }
            public function getMark() { return new class { public function getHandle() { return 'MK123'; } public function getMarkId() { return 'TMCH123'; } }; }
            public function getSslCertificate() { return new class { public function getHandle() { return 'SSL123'; } }; }
        };

        $orderInfo = new class($orderId, $status, $orderRequest) {
            private $orderId;
            private $status;
            private $request;
            public function __construct($orderId, $status, $request) {
                $this->orderId = $orderId;
                $this->status = $status;
                $this->request = $request;
            }
            public function getOrderId() { return $this->orderId; }
            public function getStatus() { return $this->status; }
            public function getOrderRequest() { return $this->request; }
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

    protected function makeQueueMessageResponse(string $messageId): object
    {
        $message = new class($messageId) {
            private $messageId;
            public function __construct($messageId) { $this->messageId = $messageId; }
            public function getMessage() { return "Test message for {$this->messageId}"; }
        };

        $result = new class($message) {
            private $message;
            public function __construct($message) { $this->message = $message; }
            public function getResultCode() { return 200; }
            public function getMessage() { return $this->message; }
        };

        return (object)['GetQueueMessageResult' => $result];
    }

    protected function makeNameWatchResponse(string $handle): object
    {
        $info = new class($handle) {
            private $handle;
            public function __construct($handle) { $this->handle = $handle; }
            public function getHandle() { return $this->handle; }
            public function getName() { return 'testbrand'; }
            public function getStatus() { return 'Active'; }
            public function getTier() { return 3; }
            public function getCreated() { return new \DateTime('2024-01-01'); }
            public function getExpires() { return new \DateTime('2025-12-31'); }
            public function getNotificationFrequency() { return 'Daily'; }
            public function getEmailNotification1() { return 'admin@test.com'; }
            public function getLabels() { return null; }
            public function getExpDate() { return '2025-12-31'; }
        };

        $result = new class($info) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
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
            public function getMarkInfo() { return $this->info; }
        };

        return (object)['GetMarkResult' => $result];
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
            public function getSslCertificateInfo() { return $this->info; }
        };

        return (object)['GetSslCertificateResult' => $result];
    }

    protected function makeUploadResponse(): object
    {
        $result = new class {
            public function getResultCode() { return 200; }
            public function getResultMessage() { return 'Upload successful'; }
        };

        return (object)['UploadDocumentationResult' => $result];
    }

    /**
     * Reset mock state.
     *
     * @return self
     */
    public function reset(): self
    {
        $this->responses = [];
        $this->calls = [];
        return $this;
    }
}
