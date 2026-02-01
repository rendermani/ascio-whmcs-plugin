<?php

namespace Ascio\Tests\Mocks;

/**
 * Mock Ascio v3 API Client for unit testing
 *
 * Provides mock responses for v3 API methods without network calls.
 * Tracks all API calls for verification in tests.
 */
class MockAscioClientV3
{
    /** @var array Queued responses keyed by method name */
    private array $responses = [];

    /** @var array Recorded calls keyed by method name */
    private array $calls = [];

    /** @var int Order ID counter */
    private int $orderIdCounter = 1000;

    /** @var bool Whether to auto-generate successful responses */
    private bool $autoRespond = true;

    /**
     * Reset all mock state
     */
    public function reset(): self
    {
        $this->responses = [];
        $this->calls = [];
        $this->orderIdCounter = 1000;
        return $this;
    }

    /**
     * Queue a response for a specific method
     *
     * @param string $method The API method name
     * @param mixed $response The response to return
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
     * Queue an exception for a method
     *
     * @param string $method The API method name
     * @param \Exception $exception The exception to throw
     * @return self
     */
    public function queueException(string $method, \Exception $exception): self
    {
        return $this->queueResponse($method, $exception);
    }

    /**
     * Get recorded calls for a method
     *
     * @param string|null $method The method name or null for all calls
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
     * Get the call count for a method
     *
     * @param string $method The method name
     * @return int
     */
    public function getCallCount(string $method): int
    {
        return count($this->calls[$method] ?? []);
    }

    /**
     * Get the last call arguments for a method
     *
     * @param string $method The method name
     * @return array|null
     */
    public function getLastCall(string $method): ?array
    {
        $calls = $this->calls[$method] ?? [];
        return end($calls) ?: null;
    }

    /**
     * Disable auto-respond mode (require explicit responses)
     */
    public function disableAutoRespond(): self
    {
        $this->autoRespond = false;
        return $this;
    }

    /**
     * Enable auto-respond mode
     */
    public function enableAutoRespond(): self
    {
        $this->autoRespond = true;
        return $this;
    }

    /**
     * Record a call to a method
     */
    private function recordCall(string $method, array $args): void
    {
        if (!isset($this->calls[$method])) {
            $this->calls[$method] = [];
        }
        $this->calls[$method][] = $args;
    }

    /**
     * Get next queued response for a method
     *
     * @param string $method
     * @return mixed
     * @throws \Exception if queued exception
     */
    private function getResponse(string $method)
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

    // =========================================================================
    // V3 API Method Mocks
    // =========================================================================

    /**
     * Mock CreateOrder API call
     */
    public function createOrder($orderRequest): object
    {
        $this->recordCall('createOrder', ['orderRequest' => $orderRequest]);

        $response = $this->getResponse('createOrder');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for createOrder');
        }

        return $this->makeOrderResponse(++$this->orderIdCounter, 'Pending');
    }

    /**
     * Mock ValidateOrder API call
     */
    public function validateOrder($orderRequest): object
    {
        $this->recordCall('validateOrder', ['orderRequest' => $orderRequest]);

        $response = $this->getResponse('validateOrder');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for validateOrder');
        }

        return $this->makeValidateResponse(true);
    }

    /**
     * Mock GetDomain API call
     */
    public function getDomain(string $handle): object
    {
        $this->recordCall('getDomain', ['handle' => $handle]);

        $response = $this->getResponse('getDomain');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for getDomain');
        }

        return $this->makeGetDomainResponse($handle);
    }

    /**
     * Mock GetDomains (filter-based search) API call
     */
    public function getDomains(array $filter): object
    {
        $this->recordCall('getDomains', ['filter' => $filter]);

        $response = $this->getResponse('getDomains');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for getDomains');
        }

        return $this->makeGetDomainsResponse($filter);
    }

    /**
     * Mock GetOrder API call
     */
    public function getOrder(string $orderId): object
    {
        $this->recordCall('getOrder', ['orderId' => $orderId]);

        $response = $this->getResponse('getOrder');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for getOrder');
        }

        return $this->makeGetOrderResponse($orderId, 'Completed');
    }

    /**
     * Mock AvailabilityCheck API call
     */
    public function availabilityCheck(array $domains, array $tlds, string $quality): object
    {
        $this->recordCall('availabilityCheck', [
            'domains' => $domains,
            'tlds' => $tlds,
            'quality' => $quality
        ]);

        $response = $this->getResponse('availabilityCheck');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for availabilityCheck');
        }

        return $this->makeAvailabilityCheckResponse($domains, $tlds);
    }

    /**
     * Mock AvailabilityInfo API call (single domain)
     */
    public function availabilityInfo(string $domainName, string $quality): object
    {
        $this->recordCall('availabilityInfo', [
            'domainName' => $domainName,
            'quality' => $quality
        ]);

        $response = $this->getResponse('availabilityInfo');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for availabilityInfo');
        }

        return $this->makeAvailabilityInfoResponse($domainName);
    }

    /**
     * Mock PollQueue API call
     */
    public function pollQueue(string $objectType, string $messageType): object
    {
        $this->recordCall('pollQueue', [
            'objectType' => $objectType,
            'messageType' => $messageType
        ]);

        $response = $this->getResponse('pollQueue');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for pollQueue');
        }

        return $this->makePollQueueResponse(null);
    }

    /**
     * Mock AckQueueMessage API call
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
     * Mock GetQueueMessage API call
     */
    public function getQueueMessage(string $messageId): object
    {
        $this->recordCall('getQueueMessage', ['messageId' => $messageId]);

        $response = $this->getResponse('getQueueMessage');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for getQueueMessage');
        }

        return $this->makeQueueMessageResponse($messageId);
    }

    /**
     * Mock GetPrices API call
     */
    public function getPrices(array $params): object
    {
        $this->recordCall('getPrices', ['params' => $params]);

        $response = $this->getResponse('getPrices');
        if ($response) {
            return $response;
        }

        if (!$this->autoRespond) {
            throw new \RuntimeException('No response queued for getPrices');
        }

        return $this->makePricesResponse();
    }

    // =========================================================================
    // Response Builders
    // =========================================================================

    /**
     * Create a successful order response
     */
    public function makeOrderResponse(int $orderId, string $status, ?string $domainName = 'example.com'): object
    {
        return (object) [
            'CreateOrderResult' => (object) [
                'ResultCode' => 200,
                'ResultMessage' => 'Order created successfully',
                'Errors' => null
            ],
            'order' => (object) [
                'OrderId' => $orderId,
                'Type' => 'Register_Domain',
                'Status' => $status,
                'Domain' => (object) [
                    'DomainName' => $domainName,
                    'DomainHandle' => 'DOM-' . $orderId
                ],
                'TransactionComment' => json_encode([
                    'application' => 'WHMCS',
                    'domainId' => 1
                ])
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    /**
     * Create a validation response
     */
    public function makeValidateResponse(bool $valid, array $errors = []): object
    {
        return (object) [
            'ValidateOrderResult' => (object) [
                'ResultCode' => $valid ? 200 : 400,
                'ResultMessage' => $valid ? 'Valid' : 'Validation failed',
                'Errors' => $valid ? null : (object) ['string' => $errors]
            ],
            'status' => (object) ['ResultCode' => $valid ? 200 : 400]
        ];
    }

    /**
     * Create a GetDomain response
     */
    public function makeGetDomainResponse(string $handle, ?string $domainName = null): object
    {
        $domain = self::createMockDomain($domainName ?? 'example.com', $handle);

        return (object) [
            'GetDomainResult' => (object) [
                'ResultCode' => 200,
                'ResultMessage' => 'Domain found'
            ],
            'domain' => $domain,
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    /**
     * Create a GetDomains (filter) response
     */
    public function makeGetDomainsResponse(array $filter, array $domains = []): object
    {
        if (empty($domains)) {
            // Extract domain name from filter if possible
            $domainName = $filter['DomainName'] ?? 'example.com';
            $domains = [(object) self::createMockDomain($domainName)];
        }

        return (object) [
            'GetDomainsResult' => (object) [
                'ResultCode' => 200,
                'ResultMessage' => 'Domains found'
            ],
            'domains' => (object) [
                'Domain' => count($domains) === 1 ? $domains[0] : $domains
            ],
            'totalCount' => count($domains),
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    /**
     * Create a GetOrder response
     */
    public function makeGetOrderResponse(string $orderId, string $status): object
    {
        return (object) [
            'GetOrderResult' => (object) [
                'ResultCode' => 200,
                'ResultMessage' => 'Order found'
            ],
            'order' => (object) [
                'OrderId' => $orderId,
                'Type' => 'Register_Domain',
                'Status' => $status,
                'Domain' => self::createMockDomain('example.com'),
                'TransactionComment' => json_encode([
                    'application' => 'WHMCS',
                    'domainId' => 1
                ])
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    /**
     * Create an availability check response
     */
    public function makeAvailabilityCheckResponse(array $domains, array $tlds): object
    {
        $results = [];
        foreach ($domains as $domain) {
            foreach ($tlds as $tld) {
                $results[] = (object) [
                    'DomainName' => $domain . '.' . $tld,
                    'StatusCode' => 200,
                    'Status' => 'Available',
                    'Premium' => false,
                    'Price' => null
                ];
            }
        }

        return (object) [
            'AvailabilityCheckResult' => (object) [
                'ResultCode' => 200,
                'ResultMessage' => 'Check completed'
            ],
            'results' => (object) [
                'AvailabilityCheckResult' => $results
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    /**
     * Create an availability info response (single domain)
     */
    public function makeAvailabilityInfoResponse(string $domainName, string $status = 'Available'): object
    {
        return (object) [
            'AvailabilityInfoResult' => (object) [
                'ResultCode' => 200,
                'ResultMessage' => 'Domain checked'
            ],
            'domainName' => $domainName,
            'status' => $status,
            'premium' => false,
            'price' => null
        ];
    }

    /**
     * Create a poll queue response
     */
    public function makePollQueueResponse(?object $message): object
    {
        return (object) [
            'PollQueueResult' => (object) [
                'ResultCode' => $message ? 200 : 201,
                'ResultMessage' => $message ? 'Message available' : 'No messages'
            ],
            'queueMessage' => $message,
            'status' => (object) ['ResultCode' => $message ? 200 : 201]
        ];
    }

    /**
     * Create a queue message
     */
    public function makeQueueMessage(
        string $messageId,
        string $orderId,
        string $orderStatus,
        string $orderType = 'Register_Domain'
    ): object {
        return (object) [
            'MessageId' => $messageId,
            'OrderId' => $orderId,
            'OrderStatus' => $orderStatus,
            'OrderType' => $orderType,
            'DomainName' => 'example.com',
            'Msg' => 'Order status update',
            'StatusList' => (object) [
                'CallbackStatus' => []
            ]
        ];
    }

    /**
     * Create a queue message response
     */
    public function makeQueueMessageResponse(string $messageId): object
    {
        return (object) [
            'GetQueueMessageResult' => (object) [
                'ResultCode' => 200,
                'ResultMessage' => 'Message retrieved'
            ],
            'item' => (object) [
                'MessageId' => $messageId,
                'OrderId' => 'ORD-123',
                'DomainName' => 'example.com',
                'Msg' => 'Test message',
                'StatusList' => (object) [
                    'CallbackStatus' => []
                ]
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    /**
     * Create a prices response
     */
    public function makePricesResponse(): object
    {
        return (object) [
            'GetPricesResult' => (object) [
                'ResultCode' => 200,
                'ResultMessage' => 'Prices retrieved'
            ],
            'prices' => [],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    /**
     * Create an error response
     */
    public function makeErrorResponse(string $method, int $code, string $message, array $errors = []): object
    {
        $resultName = ucfirst($method) . 'Result';

        return (object) [
            $resultName => (object) [
                'ResultCode' => $code,
                'ResultMessage' => $message,
                'Errors' => (object) ['string' => $errors]
            ],
            'status' => (object) [
                'ResultCode' => $code,
                'ResultMessage' => $message,
                'Errors' => (object) ['string' => $errors]
            ]
        ];
    }

    /**
     * Create a mock domain object
     */
    public static function createMockDomain(string $domainName, ?string $handle = null): object
    {
        $now = new \DateTime();
        $expDate = (clone $now)->modify('+1 year');

        return (object) [
            'DomainName' => $domainName,
            'DomainHandle' => $handle ?? 'DOM-' . substr(md5($domainName), 0, 8),
            'Status' => 'ACTIVE',
            'CreDate' => $now->format('Y-m-d\TH:i:s'),
            'ExpDate' => $expDate->format('Y-m-d\TH:i:s'),
            'AuthInfo' => 'EPP-' . strtoupper(substr(md5($domainName), 0, 12)),
            'Registrant' => (object) [
                'Name' => 'John Doe',
                'OrgName' => 'Test Company',
                'Address1' => '123 Test Street',
                'City' => 'Test City',
                'State' => 'TS',
                'PostalCode' => '12345',
                'CountryCode' => 'US',
                'Email' => 'test@example.com',
                'Phone' => '+1.5551234567',
                'Handle' => 'REG-' . uniqid()
            ],
            'AdminContact' => (object) [
                'FirstName' => 'John',
                'LastName' => 'Doe',
                'OrgName' => 'Test Company',
                'Address1' => '123 Test Street',
                'City' => 'Test City',
                'State' => 'TS',
                'PostalCode' => '12345',
                'CountryCode' => 'US',
                'Email' => 'admin@example.com',
                'Phone' => '+1.5551234567',
                'Handle' => 'ADM-' . uniqid()
            ],
            'TechContact' => (object) [
                'FirstName' => 'Tech',
                'LastName' => 'Support',
                'OrgName' => 'Test Company',
                'Address1' => '123 Test Street',
                'City' => 'Test City',
                'State' => 'TS',
                'PostalCode' => '12345',
                'CountryCode' => 'US',
                'Email' => 'tech@example.com',
                'Phone' => '+1.5551234567',
                'Handle' => 'TCH-' . uniqid()
            ],
            'BillingContact' => (object) [
                'FirstName' => 'Billing',
                'LastName' => 'Dept',
                'OrgName' => 'Test Company',
                'Address1' => '123 Test Street',
                'City' => 'Test City',
                'State' => 'TS',
                'PostalCode' => '12345',
                'CountryCode' => 'US',
                'Email' => 'billing@example.com',
                'Phone' => '+1.5551234567',
                'Handle' => 'BIL-' . uniqid()
            ],
            'NameServers' => (object) [
                'NameServer1' => (object) ['HostName' => 'ns1.example.com'],
                'NameServer2' => (object) ['HostName' => 'ns2.example.com'],
                'NameServer3' => (object) ['HostName' => ''],
                'NameServer4' => (object) ['HostName' => ''],
                'NameServer5' => (object) ['HostName' => '']
            ],
            'PrivacyProxy' => (object) [
                'Type' => 'None'
            ],
            'TransferLock' => 'Lock'
        ];
    }
}
