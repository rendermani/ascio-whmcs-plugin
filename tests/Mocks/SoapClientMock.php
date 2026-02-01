<?php

namespace Ascio\Tests\Mocks;

/**
 * Mock for SoapClient
 *
 * Provides mock SOAP responses for unit testing without network calls
 */
class SoapClientMock extends \SoapClient
{
    /** @var array Mock responses keyed by method name and optionally order type */
    private static array $responses = [];

    /** @var array Last request parameters for each method */
    private static array $lastRequests = [];

    /** @var int Call count for each method */
    private static array $callCounts = [];

    /** @var bool Whether to enable mock mode */
    private static bool $mockEnabled = true;

    /**
     * Override constructor to prevent actual SOAP initialization
     */
    public function __construct(?string $wsdl = null, array $options = [])
    {
        // Don't call parent constructor in mock mode
        if (!self::$mockEnabled) {
            parent::__construct($wsdl, $options);
        }
    }

    /**
     * Reset all mock state
     */
    public static function reset(): void
    {
        self::$responses = [];
        self::$lastRequests = [];
        self::$callCounts = [];
    }

    /**
     * Enable or disable mock mode
     */
    public static function setMockEnabled(bool $enabled): void
    {
        self::$mockEnabled = $enabled;
    }

    /**
     * Set mock response for a method
     *
     * @param string $method The SOAP method name
     * @param mixed $response The response object to return
     * @param string|null $orderType Optional order type for CreateOrder calls
     */
    public static function setResponse(string $method, mixed $response, ?string $orderType = null): void
    {
        $key = $orderType ? "{$method}_{$orderType}" : $method;
        self::$responses[$key] = $response;
    }

    /**
     * Set response from JSON file
     *
     * @param string $method The SOAP method name
     * @param string $jsonFile Path to JSON fixture file
     * @param string|null $orderType Optional order type
     */
    public static function setResponseFromFile(string $method, string $jsonFile, ?string $orderType = null): void
    {
        if (!file_exists($jsonFile)) {
            throw new \InvalidArgumentException("Fixture file not found: {$jsonFile}");
        }

        $data = json_decode(file_get_contents($jsonFile), false);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Invalid JSON in fixture file: {$jsonFile}");
        }

        self::setResponse($method, $data, $orderType);
    }

    /**
     * Get the last request parameters for a method
     */
    public static function getLastRequest(string $method): ?array
    {
        return self::$lastRequests[$method] ?? null;
    }

    /**
     * Get call count for a method
     */
    public static function getCallCount(string $method): int
    {
        return self::$callCounts[$method] ?? 0;
    }

    /**
     * Override __call to return mock responses
     */
    public function __call(string $functionName, array $arguments): mixed
    {
        // Store the request
        self::$lastRequests[$functionName] = $arguments;
        self::$callCounts[$functionName] = (self::$callCounts[$functionName] ?? 0) + 1;

        // Check for order type specific response
        $params = $arguments[0]['parameters'] ?? $arguments[0] ?? [];
        $orderType = $params['order']['Type'] ?? null;

        $key = $orderType ? "{$functionName}_{$orderType}" : null;

        // Look for specific response first, then generic
        if ($key && isset(self::$responses[$key])) {
            return self::$responses[$key];
        }

        if (isset(self::$responses[$functionName])) {
            return self::$responses[$functionName];
        }

        // Return default mock response based on method name
        return self::getDefaultResponse($functionName, $params);
    }

    /**
     * Get default mock response for common API methods
     */
    private static function getDefaultResponse(string $method, array $params): object
    {
        $resultName = $method . 'Result';

        return match ($method) {
            'LogIn' => self::createLoginResponse(),
            'CreateOrder' => self::createOrderResponse($params),
            'GetDomain' => self::createGetDomainResponse($params),
            'SearchDomain' => self::createSearchDomainResponse($params),
            'GetOrder' => self::createGetOrderResponse($params),
            'AvailabilityInfo' => self::createAvailabilityInfoResponse($params),
            'AvailabilityCheck' => self::createAvailabilityCheckResponse($params),
            'PollMessage' => self::createPollMessageResponse(),
            'AckMessage' => self::createAckMessageResponse(),
            'GetMessageQueue' => self::createGetMessageQueueResponse($params),
            default => self::createGenericSuccessResponse($resultName)
        };
    }

    private static function createLoginResponse(): object
    {
        return (object) [
            'LogInResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'Login successful'
            ],
            'sessionId' => 'mock-session-' . uniqid(),
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createOrderResponse(array $params): object
    {
        $orderType = $params['order']['Type'] ?? 'Unknown';
        $domainName = $params['order']['Domain']['DomainName'] ?? 'example.com';

        return (object) [
            'CreateOrderResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'Order created successfully'
            ],
            'order' => (object) [
                'OrderId' => 'ORD-' . uniqid(),
                'Type' => $orderType,
                'Status' => 'Completed',
                'Domain' => (object) [
                    'DomainName' => $domainName,
                    'DomainHandle' => 'DOM-' . uniqid()
                ],
                'TransactionComment' => json_encode(['application' => 'WHMCS', 'domainId' => 1])
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createGetDomainResponse(array $params): object
    {
        $handle = $params['domainHandle'] ?? 'DOM-123';

        return (object) [
            'GetDomainResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'Domain found'
            ],
            'domain' => self::createMockDomain('example.com'),
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createSearchDomainResponse(array $params): object
    {
        $domainName = 'example.com';

        // Extract domain name from criteria if available
        if (isset($params['criteria']['Clauses']['Clause'])) {
            $clause = $params['criteria']['Clauses']['Clause'];
            if ($clause['Attribute'] === 'DomainName') {
                $domainName = $clause['Value'];
            }
        }

        return (object) [
            'SearchDomainResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'Domain found'
            ],
            'domains' => (object) [
                'Domain' => self::createMockDomain($domainName)
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createGetOrderResponse(array $params): object
    {
        return (object) [
            'GetOrderResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'Order found'
            ],
            'order' => (object) [
                'OrderId' => $params['orderId'] ?? 'ORD-123',
                'Type' => 'Register_Domain',
                'Status' => 'Completed',
                'Domain' => self::createMockDomain('example.com'),
                'TransactionComment' => json_encode(['application' => 'WHMCS', 'domainId' => 1])
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createAvailabilityInfoResponse(array $params): object
    {
        return (object) [
            'AvailabilityInfoResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'Domain available'
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createAvailabilityCheckResponse(array $params): object
    {
        $results = [];
        $domains = $params['domains'] ?? ['example'];
        $tlds = $params['tlds'] ?? ['com'];

        foreach ($domains as $domain) {
            foreach ($tlds as $tld) {
                $results[] = (object) [
                    'DomainName' => $domain . '.' . $tld,
                    'StatusCode' => 200
                ];
            }
        }

        return (object) [
            'AvailabilityCheckResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'Check completed'
            ],
            'results' => (object) [
                'AvailabilityCheckResult' => $results
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createPollMessageResponse(): object
    {
        return (object) [
            'PollMessageResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'No messages'
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createAckMessageResponse(): object
    {
        return (object) [
            'AckMessageResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'Message acknowledged'
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createGetMessageQueueResponse(array $params): object
    {
        return (object) [
            'GetMessageQueueResult' => (object) [
                'ResultCode' => 200,
                'Message' => 'Queue retrieved'
            ],
            'item' => (object) [
                'DomainName' => 'example.com',
                'Msg' => 'Test message',
                'StatusList' => (object) [
                    'CallbackStatus' => []
                ]
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    private static function createGenericSuccessResponse(string $resultName): object
    {
        return (object) [
            $resultName => (object) [
                'ResultCode' => 200,
                'Message' => 'Success'
            ],
            'status' => (object) ['ResultCode' => 200]
        ];
    }

    /**
     * Create a mock domain object
     */
    public static function createMockDomain(string $domainName): object
    {
        $now = new \DateTime();
        $expDate = (clone $now)->modify('+1 year');

        return (object) [
            'DomainName' => $domainName,
            'DomainHandle' => 'DOM-' . substr(md5($domainName), 0, 8),
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
                'Fax' => '+1.5551234568',
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
                'Fax' => '+1.5551234568',
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

    /**
     * Create an error response
     */
    public static function createErrorResponse(string $method, int $code, string $message, array $values = []): object
    {
        $resultName = $method . 'Result';

        return (object) [
            $resultName => (object) [
                'ResultCode' => $code,
                'Message' => $message,
                'Values' => (object) [
                    'string' => $values
                ]
            ],
            'status' => (object) [
                'ResultCode' => $code,
                'Message' => $message,
                'Values' => (object) [
                    'string' => $values
                ]
            ]
        ];
    }
}
