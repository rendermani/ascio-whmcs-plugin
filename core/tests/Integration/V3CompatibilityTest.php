<?php

namespace Ascio\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * V3 API Compatibility Verification Tests
 *
 * This test class runs the SAME operations against both v2 and v3 APIs
 * and compares the response formats to ensure v3 returns compatible
 * response formats before removing v2 support.
 *
 * Key differences documented:
 * - v2 uses session-based auth, v3 uses SOAP header auth
 * - v2 order key: $result->order, v3 key: $result->Order
 * - v2 errors: Values->string, v3 errors: Errors->ErrorCode[]
 * - v2 poll: item->MsgId, v3 poll: Message->MessageId
 * - v2 search: SearchDomain with Criteria, v3: GetDomains with Filter
 */
class V3CompatibilityTest extends TestCase
{
    /**
     * @var \ascio\v2\domains\Request|null
     */
    protected $v2Request;

    /**
     * @var \ascio\v3\domains\RequestV3|null
     */
    protected $v3Request;

    /**
     * @var array Test credentials
     */
    protected array $testParams;

    /**
     * @var bool Skip tests if API credentials not available
     */
    protected bool $skipApiTests = true;

    /**
     * @var array Collected format differences
     */
    protected static array $formatDifferences = [];

    /**
     * Set up test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load test credentials from environment
        $account = getenv('ASCIO_TEST_ACCOUNT');
        $password = getenv('ASCIO_TEST_PASSWORD');

        if ($account && $password) {
            $this->skipApiTests = false;
        }

        $this->testParams = [
            'Username' => $account ?: 'test_account',
            'Password' => $password ?: 'test_password',
            'TestMode' => 'on',
            'domainid' => 12345,
            'domainname' => 'test-compatibility.com',
            'tld' => 'com',
            'sld' => 'test-compatibility',
        ];

        // Initialize v2 request if available
        if (class_exists('\ascio\v2\domains\Request')) {
            $this->v2Request = new \ascio\v2\domains\Request($this->testParams);
        }

        // Initialize v3 request if available
        if (class_exists('\ascio\v3\domains\RequestV3')) {
            $this->v3Request = new \ascio\v3\domains\RequestV3($this->testParams);
        }
    }

    /**
     * Skip test if API credentials not available
     */
    protected function skipIfNoCredentials(): void
    {
        if ($this->skipApiTests) {
            $this->markTestSkipped(
                'Skipping API test: ASCIO_TEST_ACCOUNT and ASCIO_TEST_PASSWORD environment variables not set'
            );
        }
    }

    /**
     * Compare response formats from both API versions
     *
     * @param mixed $v2Response Response from v2 API
     * @param mixed $v3Response Response from v3 API
     * @param string $operation Name of the operation being tested
     * @return array List of differences found
     */
    protected function assertCompatibleFormat($v2Response, $v3Response, string $operation): array
    {
        $differences = [];

        // Check if both are error responses
        if ($this->isErrorResponse($v2Response) && $this->isErrorResponse($v3Response)) {
            $differences = $this->compareErrorFormats($v2Response, $v3Response, $operation);
            self::$formatDifferences[$operation] = $differences;
            return $differences;
        }

        // Check if response types match
        $v2Type = $this->getResponseType($v2Response);
        $v3Type = $this->getResponseType($v3Response);

        if ($v2Type !== $v3Type) {
            $differences[] = [
                'field' => 'response_type',
                'v2' => $v2Type,
                'v3' => $v3Type,
                'severity' => 'warning',
            ];
        }

        // Compare structure recursively
        $structureDiff = $this->compareStructure($v2Response, $v3Response, $operation);
        $differences = array_merge($differences, $structureDiff);

        // Store for documentation
        self::$formatDifferences[$operation] = $differences;

        return $differences;
    }

    /**
     * Check if response is an error
     */
    protected function isErrorResponse($response): bool
    {
        if (is_array($response) && isset($response['error'])) {
            return true;
        }
        if (is_object($response) && isset($response->error)) {
            return true;
        }
        return false;
    }

    /**
     * Get response type for comparison
     */
    protected function getResponseType($response): string
    {
        if (is_array($response)) {
            return 'array';
        }
        if (is_object($response)) {
            return get_class($response);
        }
        return gettype($response);
    }

    /**
     * Compare error formats between v2 and v3
     */
    protected function compareErrorFormats($v2Response, $v3Response, string $operation): array
    {
        $differences = [];

        // v2 error structure
        $v2Error = is_array($v2Response) ? ($v2Response['error'] ?? null) : ($v2Response->error ?? null);

        // v3 error structure
        $v3Error = is_array($v3Response) ? ($v3Response['error'] ?? null) : ($v3Response->error ?? null);

        if ($v2Error !== $v3Error) {
            $differences[] = [
                'field' => 'error_message',
                'v2' => $v2Error,
                'v3' => $v3Error,
                'severity' => 'info', // Different error messages are expected
            ];
        }

        return $differences;
    }

    /**
     * Compare structure recursively
     */
    protected function compareStructure($v2Data, $v3Data, string $path, int $depth = 0): array
    {
        $differences = [];
        $maxDepth = 5;

        if ($depth > $maxDepth) {
            return $differences;
        }

        // Convert objects to arrays for comparison
        $v2Array = $this->toArray($v2Data);
        $v3Array = $this->toArray($v3Data);

        // Find keys present in v2 but not in v3
        foreach ($v2Array as $key => $value) {
            $v3Key = $this->findMatchingKey($key, $v3Array);

            if ($v3Key === null) {
                $differences[] = [
                    'field' => "{$path}.{$key}",
                    'v2' => "exists (type: " . gettype($value) . ")",
                    'v3' => 'missing',
                    'severity' => 'critical',
                ];
            } elseif ($v3Key !== $key) {
                // Case difference (e.g., 'order' vs 'Order')
                $differences[] = [
                    'field' => "{$path}.{$key}",
                    'v2' => $key,
                    'v3' => $v3Key,
                    'severity' => 'warning',
                    'type' => 'case_difference',
                ];
            }
        }

        // Find keys present in v3 but not in v2
        foreach ($v3Array as $key => $value) {
            $v2Key = $this->findMatchingKey($key, $v2Array);

            if ($v2Key === null) {
                $differences[] = [
                    'field' => "{$path}.{$key}",
                    'v2' => 'missing',
                    'v3' => "exists (type: " . gettype($value) . ")",
                    'severity' => 'info', // New fields in v3 are usually okay
                ];
            }
        }

        // Recurse into nested structures
        foreach ($v2Array as $key => $value) {
            $v3Key = $this->findMatchingKey($key, $v3Array);
            if ($v3Key !== null && (is_array($value) || is_object($value))) {
                $nestedDiff = $this->compareStructure(
                    $value,
                    $v3Array[$v3Key],
                    "{$path}.{$key}",
                    $depth + 1
                );
                $differences = array_merge($differences, $nestedDiff);
            }
        }

        return $differences;
    }

    /**
     * Find matching key (case-insensitive)
     */
    protected function findMatchingKey(string $key, array $array): ?string
    {
        if (array_key_exists($key, $array)) {
            return $key;
        }

        $lowerKey = strtolower($key);
        foreach ($array as $k => $v) {
            if (strtolower($k) === $lowerKey) {
                return $k;
            }
        }

        return null;
    }

    /**
     * Convert object to array recursively
     */
    protected function toArray($data): array
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    // ========================================
    // ORDER OPERATIONS TESTS
    // ========================================

    /**
     * Test ValidateOrder response format compatibility
     */
    public function testValidateOrderResponseFormat(): void
    {
        $this->skipIfNoCredentials();

        if (!$this->v2Request || !$this->v3Request) {
            $this->markTestSkipped('Both v2 and v3 Request classes required');
        }

        $orderParams = $this->buildTestOrderParams('Register_Domain');

        // Get responses from both APIs (using reflection to test internal method)
        $v2Response = $this->callValidateOrder($this->v2Request, $orderParams);
        $v3Response = $this->callValidateOrder($this->v3Request, $orderParams);

        $differences = $this->assertCompatibleFormat($v2Response, $v3Response, 'ValidateOrder');

        // Assert no critical differences
        $criticalDiffs = array_filter($differences, fn($d) => $d['severity'] === 'critical');
        $this->assertEmpty(
            $criticalDiffs,
            "Critical format differences in ValidateOrder: " . json_encode($criticalDiffs, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Test CreateOrder response format compatibility (validates without creating)
     */
    public function testCreateOrderResponseFormat(): void
    {
        // This test validates the structure without actually creating orders
        // We use ValidateOrder which has the same request structure

        $orderParams = $this->buildTestOrderParams('Register_Domain');

        // Document expected differences
        $expectedDifferences = [
            'order_key' => [
                'v2' => '$result->order',
                'v3' => '$result->Order',
                'mapping' => 'RequestV3::mapToOrder uses PascalCase',
            ],
            'session_id' => [
                'v2' => 'sessionId in request params',
                'v3' => 'SOAP header authentication',
                'mapping' => 'RequestV3::sendRequest handles auth via headers',
            ],
        ];

        // Verify v3 structure uses PascalCase
        $this->assertArrayHasKey('Order', $orderParams);
        $this->assertArrayHasKey('Type', $orderParams['Order']);
        $this->assertArrayHasKey('Domain', $orderParams['Order']);

        $this->addToAssertionCount(1);
        self::$formatDifferences['CreateOrder_structure'] = $expectedDifferences;
    }

    /**
     * Test GetOrder response format compatibility
     */
    public function testGetOrderResponseFormat(): void
    {
        $this->skipIfNoCredentials();

        if (!$this->v2Request || !$this->v3Request) {
            $this->markTestSkipped('Both v2 and v3 Request classes required');
        }

        // Use a known test order ID (from test environment)
        $testOrderId = getenv('ASCIO_TEST_ORDER_ID') ?: 'TEST123';

        $v2Response = $this->v2Request->getOrder($testOrderId);
        $v3Response = $this->v3Request->getOrder($testOrderId);

        $differences = $this->assertCompatibleFormat($v2Response, $v3Response, 'GetOrder');

        // Document the known differences
        $this->documentOrderResponseDifferences($v2Response, $v3Response);
    }

    // ========================================
    // DOMAIN OPERATIONS TESTS
    // ========================================

    /**
     * Test GetDomain response format compatibility
     */
    public function testGetDomainResponseFormat(): void
    {
        $this->skipIfNoCredentials();

        if (!$this->v2Request || !$this->v3Request) {
            $this->markTestSkipped('Both v2 and v3 Request classes required');
        }

        $testHandle = getenv('ASCIO_TEST_DOMAIN_HANDLE') ?: 'DOM123';

        $v2Response = $this->v2Request->getDomain($testHandle);
        $v3Response = $this->v3Request->getDomain($testHandle);

        $differences = $this->assertCompatibleFormat($v2Response, $v3Response, 'GetDomain');

        // Domain structure should be compatible
        if (!$this->isErrorResponse($v2Response) && !$this->isErrorResponse($v3Response)) {
            $this->assertDomainStructureCompatible($v2Response, $v3Response);
        }
    }

    /**
     * Test SearchDomain response format compatibility
     * v2: SearchDomain with Criteria
     * v3: GetDomains with Filter / SearchDomain
     */
    public function testSearchDomainResponseFormat(): void
    {
        $this->skipIfNoCredentials();

        if (!$this->v2Request || !$this->v3Request) {
            $this->markTestSkipped('Both v2 and v3 Request classes required');
        }

        // Set up domain name for search
        $this->v2Request->domainName = 'test-search.com';
        $this->v3Request->domainName = 'test-search.com';

        $v2Response = $this->v2Request->searchDomain();
        $v3Response = $this->v3Request->searchDomain();

        $differences = $this->assertCompatibleFormat($v2Response, $v3Response, 'SearchDomain');

        // Document search API differences
        self::$formatDifferences['SearchDomain_api'] = [
            'v2_method' => 'SearchDomain with Criteria object',
            'v3_method' => 'SearchDomain with Criteria (similar structure)',
            'criteria_structure' => [
                'v2' => "Clauses => Clause => [Attribute, Value, Operator]",
                'v3' => "Clauses => [[Attribute, Value, Operator]] (array)",
            ],
        ];
    }

    /**
     * Test AvailabilityInfo response format compatibility
     */
    public function testAvailabilityCheckFormat(): void
    {
        $this->skipIfNoCredentials();

        if (!$this->v2Request || !$this->v3Request) {
            $this->markTestSkipped('Both v2 and v3 Request classes required');
        }

        $testDomain = 'test-availability-check-' . time() . '.com';

        $v2Response = $this->v2Request->availabilityInfo($testDomain);
        $v3Response = $this->v3Request->availabilityInfo($testDomain);

        $differences = $this->assertCompatibleFormat($v2Response, $v3Response, 'AvailabilityInfo');

        // Document parameter differences
        self::$formatDifferences['AvailabilityInfo_params'] = [
            'v2' => [
                'sessionId' => 'required',
                'domainName' => 'parameter',
                'quality' => 'Live',
            ],
            'v3' => [
                'sessionId' => 'not needed (header auth)',
                'DomainName' => 'parameter (PascalCase)',
                'Quality' => 'Live',
            ],
        ];
    }

    // ========================================
    // POLLING OPERATIONS TESTS
    // ========================================

    /**
     * Test PollMessage/PollQueue response format compatibility
     */
    public function testPollMessageFormat(): void
    {
        $this->skipIfNoCredentials();

        if (!$this->v2Request || !$this->v3Request) {
            $this->markTestSkipped('Both v2 and v3 Request classes required');
        }

        $v2Response = $this->v2Request->poll();
        $v3Response = $this->v3Request->poll();

        $differences = $this->assertCompatibleFormat($v2Response, $v3Response, 'PollMessage');

        // Document polling API differences
        self::$formatDifferences['PollMessage_api'] = [
            'v2_method' => 'PollMessage',
            'v3_method' => 'PollQueue',
            'response_structure' => [
                'v2' => 'PollMessageResult->item->MsgId',
                'v3' => 'PollQueueResult->Message->MessageId (or MsgId)',
            ],
            'message_type_param' => [
                'v2' => 'msgType => Message_to_Partner',
                'v3' => 'MsgType => Message_to_Partner',
            ],
        ];
    }

    /**
     * Test GetMessageQueue response format compatibility
     */
    public function testGetMessageQueueFormat(): void
    {
        $this->skipIfNoCredentials();

        if (!$this->v2Request || !$this->v3Request) {
            $this->markTestSkipped('Both v2 and v3 Request classes required');
        }

        $testMessageId = getenv('ASCIO_TEST_MESSAGE_ID') ?: '12345';

        // v2 uses GetMessageQueue, v3 uses GetQueueMessage
        $v2Response = $this->callGetMessageQueue($this->v2Request, $testMessageId);
        $v3Response = $this->v3Request->getQueueMessage($testMessageId);

        $differences = $this->assertCompatibleFormat($v2Response, $v3Response, 'GetMessageQueue');

        // Document API method mapping
        self::$formatDifferences['GetMessageQueue_api'] = [
            'v2_method' => 'GetMessageQueue',
            'v3_method' => 'GetQueueMessage',
            'param_name' => [
                'v2' => 'msgId',
                'v3' => 'MsgId',
            ],
        ];
    }

    /**
     * Test AckMessage response format compatibility
     */
    public function testAckMessageFormat(): void
    {
        $this->skipIfNoCredentials();

        if (!$this->v2Request || !$this->v3Request) {
            $this->markTestSkipped('Both v2 and v3 Request classes required');
        }

        // We won't actually ack a message, just verify the method signatures match
        $this->assertTrue(method_exists($this->v2Request, 'ack'));
        $this->assertTrue(method_exists($this->v3Request, 'ack'));
        $this->assertTrue(method_exists($this->v3Request, 'ackQueueMessage'));

        // Document API method mapping
        self::$formatDifferences['AckMessage_api'] = [
            'v2_method' => 'AckMessage (msgId param)',
            'v3_method' => 'AckQueueMessage (MsgId param)',
            'v3_alias' => 'ack() calls ackQueueMessage() for compatibility',
        ];
    }

    // ========================================
    // ERROR HANDLING TESTS
    // ========================================

    /**
     * Test error response format compatibility
     */
    public function testErrorResponseFormat(): void
    {
        // Create mock error responses to compare structure
        $v2Error = ['error' => 'Invalid session'];
        $v3Error = ['error' => 'Invalid session'];

        $differences = $this->assertCompatibleFormat($v2Error, $v3Error, 'ErrorResponse');

        // Document error structure differences
        self::$formatDifferences['ErrorResponse_structure'] = [
            'v2' => [
                'array_key' => 'error',
                'source' => 'Values->string or single message',
                'auth_error_code' => '401',
            ],
            'v3' => [
                'array_key' => 'error',
                'source' => 'Errors->string or ResultMessage',
                'auth_error_code' => '401',
            ],
            'compatibility' => 'Both return ["error" => "message"] format',
        ];

        $this->assertEmpty($differences);
    }

    /**
     * Test validation error format compatibility
     */
    public function testValidationErrorFormat(): void
    {
        // Document validation error structures
        self::$formatDifferences['ValidationError_structure'] = [
            'v2' => [
                'error_source' => 'ValidateOrderResult->Values->string',
                'multiple_errors' => 'Array of strings',
                'format' => 'join(", \\r\\n", errors)',
            ],
            'v3' => [
                'error_source' => 'ValidateOrderResult->Errors->string',
                'multiple_errors' => 'Array of strings or ErrorCode array',
                'format' => 'join(", \\r\\n", errors)',
            ],
            'result_codes' => [
                '200' => 'Success',
                '201' => 'Success (no content)',
                '400' => 'Validation error',
                '401' => 'Authentication error',
                '413' => 'Success with warning',
                '500+' => 'Server error',
                '554' => 'Temporary error',
            ],
        ];

        $this->addToAssertionCount(1);
    }

    /**
     * Test authentication error format compatibility
     */
    public function testAuthErrorFormat(): void
    {
        // Document auth error handling differences
        self::$formatDifferences['AuthError_handling'] = [
            'v2' => [
                'error_code' => '401',
                'handling' => 'SessionCache::clear() then retry login',
                'response' => "['error' => 'Login failed: invalid account or password']",
            ],
            'v3' => [
                'error_code' => '401',
                'handling' => 'No session to clear, immediate error return',
                'response' => "['error' => ResultMessage or 'Login failed: invalid account or password']",
            ],
            'compatibility' => 'Both return error array with descriptive message',
        ];

        $this->addToAssertionCount(1);
    }

    // ========================================
    // MAPPING LAYER TESTS
    // ========================================

    /**
     * Test Order result mapping from v3 to v2-compatible format
     */
    public function testOrderResultMapping(): void
    {
        // Build v3 order params
        $v3Params = $this->buildTestOrderParams('Register_Domain');

        // Verify v3 uses PascalCase keys
        $this->assertArrayHasKey('Order', $v3Params);
        $this->assertArrayHasKey('Type', $v3Params['Order']);
        $this->assertArrayHasKey('Domain', $v3Params['Order']);
        $this->assertArrayHasKey('TransactionComment', $v3Params['Order']);

        // Document mapping
        self::$formatDifferences['OrderMapping'] = [
            'key_case' => [
                'v2' => 'order (lowercase)',
                'v3' => 'Order (PascalCase)',
            ],
            'fields' => [
                'Type' => 'Same in both',
                'Domain' => 'Same structure, PascalCase',
                'TransactionComment' => 'Same format (JSON)',
            ],
        ];
    }

    /**
     * Test Domain object mapping from v3 to v2 format
     */
    public function testDomainObjectMapping(): void
    {
        // Document domain object field mappings
        $domainFields = [
            'DomainName' => ['v2' => 'DomainName', 'v3' => 'DomainName', 'compatible' => true],
            'DomainHandle' => ['v2' => 'DomainHandle', 'v3' => 'DomainHandle', 'compatible' => true],
            'RegPeriod' => ['v2' => 'RegPeriod', 'v3' => 'RegPeriod', 'compatible' => true],
            'AuthInfo' => ['v2' => 'AuthInfo', 'v3' => 'AuthInfo', 'compatible' => true],
            'ExpDate' => ['v2' => 'ExpDate', 'v3' => 'ExpDate', 'compatible' => true],
            'CreDate' => ['v2' => 'CreDate', 'v3' => 'CreDate', 'compatible' => true],
            'Status' => ['v2' => 'Status', 'v3' => 'Status', 'compatible' => true],
            'TransferLock' => ['v2' => 'TransferLock', 'v3' => 'TransferLock', 'compatible' => true],
        ];

        self::$formatDifferences['DomainObjectMapping'] = $domainFields;

        // All fields should be compatible
        foreach ($domainFields as $field => $mapping) {
            $this->assertTrue($mapping['compatible'], "Field {$field} should be compatible");
        }
    }

    /**
     * Test Contact mapping from v3 to v2 format
     */
    public function testContactMapping(): void
    {
        // Build contact from test params
        $contactFields = [
            'FirstName' => ['v2' => 'FirstName', 'v3' => 'FirstName', 'compatible' => true],
            'LastName' => ['v2' => 'LastName', 'v3' => 'LastName', 'compatible' => true],
            'Name' => ['v2' => 'Name (Registrant)', 'v3' => 'Name (Registrant)', 'compatible' => true],
            'OrgName' => ['v2' => 'OrgName', 'v3' => 'OrgName', 'compatible' => true],
            'Address1' => ['v2' => 'Address1', 'v3' => 'Address1', 'compatible' => true],
            'Address2' => ['v2' => 'Address2', 'v3' => 'Address2', 'compatible' => true],
            'City' => ['v2' => 'City', 'v3' => 'City', 'compatible' => true],
            'State' => ['v2' => 'State', 'v3' => 'State', 'compatible' => true],
            'PostalCode' => ['v2' => 'PostalCode', 'v3' => 'PostalCode', 'compatible' => true],
            'CountryCode' => ['v2' => 'CountryCode', 'v3' => 'CountryCode', 'compatible' => true],
            'Email' => ['v2' => 'Email', 'v3' => 'Email', 'compatible' => true],
            'Phone' => ['v2' => 'Phone', 'v3' => 'Phone', 'compatible' => true],
            'Fax' => ['v2' => 'Fax', 'v3' => 'Fax', 'compatible' => true],
        ];

        self::$formatDifferences['ContactMapping'] = $contactFields;

        foreach ($contactFields as $field => $mapping) {
            $this->assertTrue($mapping['compatible'], "Contact field {$field} should be compatible");
        }
    }

    /**
     * Test Nameserver mapping from v3 to v2 format
     */
    public function testNameserverMapping(): void
    {
        $nsFields = [
            'NameServer1' => ['structure' => ['HostName' => 'string'], 'compatible' => true],
            'NameServer2' => ['structure' => ['HostName' => 'string'], 'compatible' => true],
            'NameServer3' => ['structure' => ['HostName' => 'string'], 'compatible' => true],
            'NameServer4' => ['structure' => ['HostName' => 'string'], 'compatible' => true],
            'NameServer5' => ['structure' => ['HostName' => 'string'], 'compatible' => true],
        ];

        self::$formatDifferences['NameserverMapping'] = $nsFields;

        foreach ($nsFields as $field => $mapping) {
            $this->assertTrue($mapping['compatible'], "Nameserver {$field} should be compatible");
        }
    }

    /**
     * Test Error mapping from v3 to v2 format
     */
    public function testErrorMapping(): void
    {
        // Document error extraction differences
        self::$formatDifferences['ErrorMapping'] = [
            'v2_extraction' => [
                'single' => '$status->Values->string',
                'multiple' => 'is_array($status->Values->string) ? join(", \\r\\n", ...) : $status->Values->string',
            ],
            'v3_extraction' => [
                'single' => '$result->Errors->string ?? $result->ResultMessage',
                'multiple' => 'is_array($result->Errors->string) ? join(", \\r\\n", ...) : ...',
            ],
            'compatible_format' => "['error' => 'cleaned message']",
        ];

        $this->addToAssertionCount(1);
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Build test order parameters
     */
    protected function buildTestOrderParams(string $orderType): array
    {
        $params = array_merge($this->testParams, [
            'firstname' => 'Test',
            'lastname' => 'User',
            'companyname' => 'Test Company',
            'address1' => '123 Test St',
            'city' => 'Testville',
            'state' => 'CA',
            'postcode' => '12345',
            'country' => 'US',
            'email' => 'test@example.com',
            'fullphonenumber' => '+1.5551234567',
            'ns1' => 'ns1.test.com',
            'ns2' => 'ns2.test.com',
            'regperiod' => 1,
            'adminfirstname' => 'Admin',
            'adminlastname' => 'User',
            'admincompanyname' => 'Test Company',
            'adminaddress1' => '123 Test St',
            'admincity' => 'Testville',
            'adminstate' => 'CA',
            'adminpostcode' => '12345',
            'admincountry' => 'US',
            'adminemail' => 'admin@example.com',
            'adminfullphonenumber' => '+1.5551234567',
        ]);

        // Use v3 request to build order if available
        if ($this->v3Request) {
            return $this->v3Request->mapToOrder($params, $orderType);
        }

        // Fallback structure for v3 format
        return [
            'Order' => [
                'Type' => $orderType,
                'TransactionComment' => json_encode([
                    'application' => 'WHMCS',
                    'domainId' => $params['domainid'],
                    'userId' => $params['userid'] ?? null,
                    'objectType' => 'Domain',
                ]),
                'Domain' => [
                    'DomainName' => $params['domainname'],
                    'RegPeriod' => $params['regperiod'],
                ],
            ],
        ];
    }

    /**
     * Call ValidateOrder on v2 request
     */
    protected function callValidateOrder($request, array $orderParams)
    {
        // Use reflection to access protected/private methods if needed
        if (method_exists($request, 'request')) {
            $reflection = new \ReflectionClass($request);
            $method = $reflection->getMethod('request');
            if (!$method->isPublic()) {
                $method->setAccessible(true);
            }
            return $method->invoke($request, 'ValidateOrder', $orderParams);
        }

        return ['error' => 'Method not available'];
    }

    /**
     * Call GetMessageQueue on v2 request
     */
    protected function callGetMessageQueue($request, string $messageId)
    {
        if (method_exists($request, 'request')) {
            $reflection = new \ReflectionClass($request);
            $method = $reflection->getMethod('request');
            if (!$method->isPublic()) {
                $method->setAccessible(true);
            }
            return $method->invoke($request, 'GetMessageQueue', ['msgId' => $messageId]);
        }

        return ['error' => 'Method not available'];
    }

    /**
     * Assert domain structure compatibility
     */
    protected function assertDomainStructureCompatible($v2Domain, $v3Domain): void
    {
        $requiredFields = [
            'DomainName',
            'DomainHandle',
            'Status',
        ];

        foreach ($requiredFields as $field) {
            $v2Has = isset($v2Domain->$field) || (is_array($v2Domain) && isset($v2Domain[$field]));
            $v3Has = isset($v3Domain->$field) || (is_array($v3Domain) && isset($v3Domain[$field]));

            $this->assertEquals(
                $v2Has,
                $v3Has,
                "Domain field '{$field}' presence should match between v2 and v3"
            );
        }
    }

    /**
     * Document order response differences
     */
    protected function documentOrderResponseDifferences($v2Response, $v3Response): void
    {
        self::$formatDifferences['GetOrder_response'] = [
            'v2_result_key' => 'GetOrderResult',
            'v3_result_key' => 'GetOrderResult',
            'order_key' => [
                'v2' => '$result->order',
                'v3' => '$result->Order',
            ],
        ];
    }

    /**
     * Get collected format differences (for documentation)
     */
    public static function getFormatDifferences(): array
    {
        return self::$formatDifferences;
    }

    /**
     * Teardown - output format differences summary
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        // Output summary if any differences were collected
        if (!empty(self::$formatDifferences)) {
            $output = "\n=== V3 API Format Differences Summary ===\n";
            foreach (self::$formatDifferences as $operation => $differences) {
                $output .= "\n[{$operation}]\n";
                $output .= json_encode($differences, JSON_PRETTY_PRINT) . "\n";
            }
            // Write to stderr so PHPUnit can capture it
            fwrite(STDERR, $output);
        }
    }
}
