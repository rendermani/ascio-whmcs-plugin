<?php

namespace Ascio\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Response Format Documentation Generator
 *
 * This test class documents all response format differences between v2 and v3 APIs
 * and outputs a markdown file for review before migration.
 *
 * Run with: ./vendor/bin/phpunit tests/Integration/ResponseFormatDocumentation.php
 *
 * Output file: tests/Integration/format_differences.md
 */
class ResponseFormatDocumentation extends TestCase
{
    /**
     * Output file path
     */
    protected const OUTPUT_FILE = __DIR__ . '/format_differences.md';

    /**
     * @var array All documented differences
     */
    protected static array $allDifferences = [];

    /**
     * Document all response format differences between v2 and v3
     * Output to a markdown file for review
     */
    public function testDocumentAllFormatDifferences(): void
    {
        $differences = [];

        // Order Operations
        $differences['Order Operations'] = $this->documentOrderOperations();

        // Domain Operations
        $differences['Domain Operations'] = $this->documentDomainOperations();

        // Polling Operations
        $differences['Polling Operations'] = $this->documentPollingOperations();

        // Contact Operations
        $differences['Contact Operations'] = $this->documentContactOperations();

        // Error Handling
        $differences['Error Handling'] = $this->documentErrorHandling();

        // Authentication
        $differences['Authentication'] = $this->documentAuthentication();

        // Data Type Mappings
        $differences['Data Type Mappings'] = $this->documentDataTypeMappings();

        // Write documentation file
        $this->writeDocumentation($differences);

        // Test passes if documentation was written
        $this->assertFileExists(self::OUTPUT_FILE);
    }

    /**
     * Document Order operation differences
     */
    protected function documentOrderOperations(): array
    {
        return [
            'CreateOrder' => [
                'v2_method' => 'CreateOrder',
                'v3_method' => 'CreateOrder',
                'request_structure' => [
                    'v2' => [
                        'sessionId' => 'string (from login)',
                        'order' => 'Order object (lowercase key)',
                    ],
                    'v3' => [
                        'request' => [
                            'Order' => 'Order object (PascalCase key)',
                        ],
                        'auth' => 'SOAP header (SecurityHeaderDetails)',
                    ],
                ],
                'response_structure' => [
                    'v2' => [
                        'CreateOrderResult' => [
                            'ResultCode' => '200|201|400|401|...',
                            'ResultMessage' => 'string',
                            'Values' => ['string' => ['error1', 'error2']],
                            'order' => 'Order object with OrderId',
                        ],
                    ],
                    'v3' => [
                        'CreateOrderResult' => [
                            'ResultCode' => '200|201|400|401|...',
                            'ResultMessage' => 'string',
                            'Errors' => ['string' => ['error1', 'error2']],
                            'Order' => 'Order object with OrderId',
                        ],
                    ],
                ],
                'migration_notes' => [
                    'Key case change: order -> Order',
                    'Error key change: Values -> Errors',
                    'Auth moved from sessionId to SOAP header',
                ],
            ],

            'ValidateOrder' => [
                'v2_method' => 'ValidateOrder',
                'v3_method' => 'ValidateOrder',
                'description' => 'Same as CreateOrder but validates without executing',
                'differences' => 'Same structure differences as CreateOrder',
            ],

            'GetOrder' => [
                'v2_method' => 'GetOrder',
                'v3_method' => 'GetOrder',
                'request_structure' => [
                    'v2' => [
                        'sessionId' => 'string',
                        'orderId' => 'string',
                    ],
                    'v3' => [
                        'OrderId' => 'string (PascalCase)',
                    ],
                ],
                'response_structure' => [
                    'v2' => [
                        'GetOrderResult' => [
                            'ResultCode' => 'int',
                            'order' => 'Full Order object',
                        ],
                    ],
                    'v3' => [
                        'GetOrderResult' => [
                            'ResultCode' => 'int',
                            'Order' => 'Full Order object',
                        ],
                    ],
                ],
                'migration_notes' => [
                    'Parameter case: orderId -> OrderId',
                    'Response key case: order -> Order',
                ],
            ],
        ];
    }

    /**
     * Document Domain operation differences
     */
    protected function documentDomainOperations(): array
    {
        return [
            'GetDomain' => [
                'v2_method' => 'GetDomain',
                'v3_method' => 'GetDomain',
                'request_structure' => [
                    'v2' => [
                        'sessionId' => 'string',
                        'domainHandle' => 'string',
                    ],
                    'v3' => [
                        'DomainHandle' => 'string (PascalCase)',
                    ],
                ],
                'response_structure' => [
                    'v2' => [
                        'GetDomainResult' => [
                            'ResultCode' => 'int',
                            'domain' => 'Domain object',
                        ],
                    ],
                    'v3' => [
                        'GetDomainResult' => [
                            'ResultCode' => 'int',
                            'Domain' => 'Domain object',
                        ],
                    ],
                ],
                'migration_notes' => [
                    'Parameter case: domainHandle -> DomainHandle',
                    'Response key case: domain -> Domain',
                ],
            ],

            'SearchDomain' => [
                'v2_method' => 'SearchDomain',
                'v3_method' => 'SearchDomain (or GetDomains)',
                'request_structure' => [
                    'v2' => [
                        'sessionId' => 'string',
                        'criteria' => [
                            'Mode' => 'Strict',
                            'Withoutstates' => ['string' => 'deleted'],
                            'Clauses' => [
                                'Clause' => [
                                    'Attribute' => 'DomainName',
                                    'Value' => 'domain.com',
                                    'Operator' => 'Is',
                                ],
                            ],
                        ],
                    ],
                    'v3' => [
                        'Criteria' => [
                            'Mode' => 'Strict',
                            'WithoutStates' => ['deleted'],
                            'Clauses' => [
                                [
                                    'Attribute' => 'DomainName',
                                    'Value' => 'domain.com',
                                    'Operator' => 'Is',
                                ],
                            ],
                        ],
                    ],
                ],
                'response_structure' => [
                    'v2' => [
                        'SearchDomainResult' => [
                            'domains' => [
                                'Domain' => 'Domain object or array',
                            ],
                        ],
                    ],
                    'v3' => [
                        'SearchDomainResult' => [
                            'Domains' => [
                                'Domain' => 'Domain object or array',
                            ],
                        ],
                    ],
                ],
                'migration_notes' => [
                    'Criteria key: Withoutstates -> WithoutStates',
                    'Clauses structure: Clause wrapper removed in v3',
                    'Response key: domains -> Domains',
                ],
            ],

            'AvailabilityInfo' => [
                'v2_method' => 'AvailabilityInfo',
                'v3_method' => 'AvailabilityInfo',
                'request_structure' => [
                    'v2' => [
                        'sessionId' => 'string',
                        'domainName' => 'string',
                        'quality' => 'Live',
                    ],
                    'v3' => [
                        'DomainName' => 'string',
                        'Quality' => 'Live',
                    ],
                ],
                'response_structure' => [
                    'v2' => [
                        'AvailabilityInfoResult' => [
                            'ResultCode' => 'int',
                            'ResultMessage' => 'string',
                            'DomainName' => 'string',
                            'DomainNameAvailable' => 'bool',
                        ],
                    ],
                    'v3' => [
                        'AvailabilityInfoResult' => [
                            'ResultCode' => 'int',
                            'ResultMessage' => 'string',
                            'DomainName' => 'string',
                            'DomainNameAvailable' => 'bool',
                        ],
                    ],
                ],
                'migration_notes' => [
                    'Parameter case: domainName -> DomainName, quality -> Quality',
                    'Response structure is compatible',
                ],
            ],
        ];
    }

    /**
     * Document Polling operation differences
     */
    protected function documentPollingOperations(): array
    {
        return [
            'PollMessage_vs_PollQueue' => [
                'v2_method' => 'PollMessage',
                'v3_method' => 'PollQueue',
                'description' => 'Different method name but similar functionality',
                'request_structure' => [
                    'v2' => [
                        'sessionId' => 'string',
                        'msgType' => 'Message_to_Partner',
                    ],
                    'v3' => [
                        'MsgType' => 'Message_to_Partner',
                    ],
                ],
                'response_structure' => [
                    'v2' => [
                        'PollMessageResult' => [
                            'ResultCode' => 'int (200=message, 201=empty)',
                            'item' => [
                                'MsgId' => 'int',
                                'OrderStatus' => 'string',
                                'OrderId' => 'string',
                                'DomainName' => 'string',
                                'Msg' => 'string',
                                'StatusList' => 'CallbackStatus array',
                            ],
                        ],
                    ],
                    'v3' => [
                        'PollQueueResult' => [
                            'ResultCode' => 'int (200=message, 201=empty)',
                            'QueueMessage' => [
                                'MessageId' => 'int (or MsgId)',
                                'OrderStatus' => 'string',
                                'OrderId' => 'string',
                                'ObjectName' => 'string (was DomainName)',
                                'Message' => 'string (was Msg)',
                                'StatusList' => 'CallbackStatus array',
                            ],
                        ],
                    ],
                ],
                'migration_notes' => [
                    'Method name: PollMessage -> PollQueue',
                    'Parameter case: msgType -> MsgType',
                    'Response: item -> QueueMessage',
                    'Field: MsgId -> MessageId',
                    'Field: DomainName -> ObjectName (more generic)',
                    'Field: Msg -> Message',
                ],
            ],

            'GetMessageQueue_vs_GetQueueMessage' => [
                'v2_method' => 'GetMessageQueue',
                'v3_method' => 'GetQueueMessage',
                'request_structure' => [
                    'v2' => [
                        'sessionId' => 'string',
                        'msgId' => 'int',
                    ],
                    'v3' => [
                        'MsgId' => 'int',
                    ],
                ],
                'response_structure' => [
                    'v2' => [
                        'GetMessageQueueResult' => [
                            'item' => 'Message details object',
                        ],
                    ],
                    'v3' => [
                        'GetQueueMessageResult' => [
                            'Message' => 'Message details object',
                        ],
                    ],
                ],
                'migration_notes' => [
                    'Method name: GetMessageQueue -> GetQueueMessage',
                    'Response wrapper: item -> Message',
                ],
            ],

            'AckMessage_vs_AckQueueMessage' => [
                'v2_method' => 'AckMessage',
                'v3_method' => 'AckQueueMessage',
                'request_structure' => [
                    'v2' => [
                        'sessionId' => 'string',
                        'msgId' => 'int',
                    ],
                    'v3' => [
                        'MsgId' => 'int',
                    ],
                ],
                'response_structure' => [
                    'v2' => 'AckMessageResult with ResultCode',
                    'v3' => 'AckQueueMessageResult with ResultCode',
                ],
                'migration_notes' => [
                    'Method name: AckMessage -> AckQueueMessage',
                    'RequestV3::ack() wraps ackQueueMessage() for compatibility',
                ],
            ],
        ];
    }

    /**
     * Document Contact operation differences
     */
    protected function documentContactOperations(): array
    {
        return [
            'Contact_Structure' => [
                'description' => 'Contact/Registrant object structure',
                'fields' => [
                    'FirstName' => 'Same in both (Contact only)',
                    'LastName' => 'Same in both (Contact only)',
                    'Name' => 'Same in both (Registrant only)',
                    'OrgName' => 'Same in both',
                    'Address1' => 'Same in both',
                    'Address2' => 'Same in both',
                    'City' => 'Same in both',
                    'State' => 'Same in both',
                    'PostalCode' => 'Same in both',
                    'CountryCode' => 'Same in both',
                    'Email' => 'Same in both',
                    'Phone' => 'Same in both',
                    'Fax' => 'Same in both',
                ],
                'registrant_specific' => [
                    'RegistrantType' => 'Same in both',
                    'VatNumber' => 'Same in both',
                    'NexusCategory' => 'Same in both',
                    'RegistrantNumber' => 'Same in both',
                    'Details' => 'Same in both',
                ],
                'migration_notes' => [
                    'Contact structure is fully compatible between v2 and v3',
                    'Both mapToContact and mapToContact2 work the same way',
                ],
            ],

            'UpdateContacts' => [
                'operations' => [
                    'Owner_Change' => 'Registrant name/org change',
                    'Registrant_Details_Update' => 'Registrant other details',
                    'Contact_Update' => 'Admin/Tech/Billing contact changes',
                ],
                'migration_notes' => [
                    'Same operation types in both APIs',
                    'Order structure follows standard CreateOrder format',
                ],
            ],
        ];
    }

    /**
     * Document Error handling differences
     */
    protected function documentErrorHandling(): array
    {
        return [
            'Result_Codes' => [
                200 => 'Success',
                201 => 'Success (no content/empty queue)',
                400 => 'Bad request / Validation error',
                401 => 'Authentication failed',
                403 => 'Forbidden',
                404 => 'Not found',
                413 => 'Success with warning',
                500 => 'Server error',
                554 => 'Temporary error - retry later',
            ],

            'Error_Extraction' => [
                'v2' => [
                    'source' => '$result->status->Values->string',
                    'single_error' => '$status->Values->string',
                    'multiple_errors' => 'is_array($status->Values->string)',
                    'join_method' => 'join(", \\r\\n", $status->Values->string)',
                ],
                'v3' => [
                    'source' => '$result->Errors->string or $result->ResultMessage',
                    'single_error' => '$result->Errors->string ?? $result->ResultMessage',
                    'multiple_errors' => 'is_array($result->Errors->string)',
                    'join_method' => 'join(", \\r\\n", $result->Errors->string)',
                ],
            ],

            'Error_Return_Format' => [
                'both_versions' => "['error' => 'cleaned message string']",
                'cleaning' => 'Tools::cleanString() applied to message',
            ],

            'Session_Errors' => [
                'v2' => [
                    'code' => 401,
                    'handling' => 'SessionCache::clear($account); retry login; retry request',
                    'max_retries' => '1 (automatic re-login)',
                ],
                'v3' => [
                    'code' => 401,
                    'handling' => 'Immediate error return (no session to clear)',
                    'max_retries' => 'None (header auth is stateless)',
                ],
            ],

            'migration_notes' => [
                'Error format compatible: both return ["error" => "message"]',
                'Key difference: Values->string vs Errors->string',
                'Session retry logic not needed in v3',
            ],
        ];
    }

    /**
     * Document Authentication differences
     */
    protected function documentAuthentication(): array
    {
        return [
            'v2_Session_Auth' => [
                'login_method' => 'LogIn',
                'login_params' => [
                    'session' => [
                        'Account' => 'username',
                        'Password' => 'password',
                    ],
                ],
                'login_response' => [
                    'sessionId' => 'returned session token',
                ],
                'usage' => 'Include sessionId in every request',
                'caching' => 'SessionCache stores sessionId by account',
                'expiry' => 'Session can expire, requiring re-login on 401',
            ],

            'v3_Header_Auth' => [
                'method' => 'SOAP header authentication',
                'header_namespace' => 'http://www.ascio.com/2013/02',
                'header_name' => 'SecurityHeaderDetails',
                'header_contents' => [
                    'Account' => 'username',
                    'Password' => 'password',
                ],
                'usage' => 'Set once on SOAP client via __setSoapHeaders()',
                'caching' => 'None needed - credentials sent with every request',
                'benefits' => [
                    'No session management complexity',
                    'No session expiry handling',
                    'Simpler error handling',
                    'Stateless requests',
                ],
            ],

            'migration_impact' => [
                'SessionCache class' => 'Not used in v3',
                'mod_asciosession table' => 'Not used in v3',
                'LogIn method' => 'Not called in v3',
                're-login on 401' => 'Not needed in v3',
            ],
        ];
    }

    /**
     * Document Data Type mappings
     */
    protected function documentDataTypeMappings(): array
    {
        return [
            'Order_Object' => [
                'fields' => [
                    'Type' => 'OrderType enum (Register_Domain, Transfer_Domain, etc)',
                    'OrderId' => 'string',
                    'Status' => 'OrderStatus enum',
                    'TransactionComment' => 'JSON string with WHMCS metadata',
                    'Domain' => 'Domain object',
                    'Options' => 'Optional order options',
                    'AgreedPrice' => 'Premium domain price',
                ],
                'key_case' => [
                    'v2' => 'order (lowercase in response)',
                    'v3' => 'Order (PascalCase)',
                ],
            ],

            'Domain_Object' => [
                'fields' => [
                    'DomainName' => 'string',
                    'DomainHandle' => 'string (unique identifier)',
                    'RegPeriod' => 'int (years)',
                    'AuthInfo' => 'EPP code',
                    'ExpDate' => 'ISO datetime',
                    'CreDate' => 'ISO datetime',
                    'Status' => 'Domain status string',
                    'TransferLock' => 'Lock/UnLock',
                    'PrivacyProxy' => 'Privacy settings object',
                    'Registrant' => 'Registrant object',
                    'AdminContact' => 'Contact object',
                    'TechContact' => 'Contact object',
                    'BillingContact' => 'Contact object',
                    'NameServers' => 'NameServers object',
                ],
                'compatible' => true,
            ],

            'NameServers_Object' => [
                'structure' => [
                    'NameServer1' => ['HostName' => 'string', 'IpAddress' => 'optional'],
                    'NameServer2' => ['HostName' => 'string', 'IpAddress' => 'optional'],
                    'NameServer3' => ['HostName' => 'string', 'IpAddress' => 'optional'],
                    'NameServer4' => ['HostName' => 'string', 'IpAddress' => 'optional'],
                    'NameServer5' => ['HostName' => 'string', 'IpAddress' => 'optional'],
                ],
                'compatible' => true,
            ],

            'Date_Formats' => [
                'format' => 'ISO 8601 / XML datetime (e.g., 2024-12-31T00:00:00)',
                'null_value' => '0001-01-01T00:00:00',
                'parsing' => 'DateTime::createFromFormat(DateTime::ATOM, ...)',
                'compatible' => true,
            ],
        ];
    }

    /**
     * Write documentation to markdown file
     */
    protected function writeDocumentation(array $differences): void
    {
        $markdown = $this->generateMarkdown($differences);
        file_put_contents(self::OUTPUT_FILE, $markdown);
    }

    /**
     * Generate markdown content
     */
    protected function generateMarkdown(array $differences): string
    {
        $md = "# Ascio API V2 to V3 Format Differences\n\n";
        $md .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $md .= "This document describes all format differences between Ascio v2 and v3 APIs ";
        $md .= "to ensure compatibility when migrating.\n\n";
        $md .= "---\n\n";

        // Table of Contents
        $md .= "## Table of Contents\n\n";
        foreach (array_keys($differences) as $section) {
            $anchor = strtolower(str_replace(' ', '-', $section));
            $md .= "- [{$section}](#{$anchor})\n";
        }
        $md .= "\n---\n\n";

        // Content sections
        foreach ($differences as $section => $content) {
            $md .= "## {$section}\n\n";
            $md .= $this->formatSection($content, 0);
            $md .= "\n---\n\n";
        }

        // Summary
        $md .= "## Migration Summary\n\n";
        $md .= "### Key Changes\n\n";
        $md .= "1. **Authentication**: Session-based -> SOAP header authentication\n";
        $md .= "2. **Key Case**: lowercase keys -> PascalCase keys (e.g., `order` -> `Order`)\n";
        $md .= "3. **Error Structure**: `Values->string` -> `Errors->string`\n";
        $md .= "4. **Polling Methods**: `PollMessage` -> `PollQueue`, `GetMessageQueue` -> `GetQueueMessage`\n";
        $md .= "5. **Message Fields**: `MsgId` -> `MessageId`, `DomainName` -> `ObjectName`\n\n";

        $md .= "### Compatibility Layer (RequestV3)\n\n";
        $md .= "The `RequestV3` class provides compatibility by:\n\n";
        $md .= "- Using PascalCase for v3 API calls internally\n";
        $md .= "- Mapping response structures to match v2 expectations where possible\n";
        $md .= "- Providing alias methods (e.g., `ack()` calls `ackQueueMessage()`)\n";
        $md .= "- Returning errors in the same `['error' => 'message']` format\n\n";

        $md .= "### Testing Recommendations\n\n";
        $md .= "1. Run `V3CompatibilityTest` with API credentials to verify live compatibility\n";
        $md .= "2. Test all order types: Register, Transfer, Renew, Update, etc.\n";
        $md .= "3. Test polling and callback processing\n";
        $md .= "4. Test error handling scenarios\n";
        $md .= "5. Verify TLD-specific handlers work with v3\n";

        return $md;
    }

    /**
     * Format a section recursively
     */
    protected function formatSection($content, int $depth): string
    {
        $md = "";
        $indent = str_repeat("  ", $depth);

        if (is_array($content)) {
            foreach ($content as $key => $value) {
                if (is_numeric($key)) {
                    // List item
                    if (is_array($value)) {
                        $md .= $this->formatSection($value, $depth);
                    } else {
                        $md .= "{$indent}- {$value}\n";
                    }
                } elseif (is_array($value)) {
                    // Nested section
                    $md .= "\n{$indent}### {$key}\n\n";
                    $md .= $this->formatSection($value, $depth + 1);
                } else {
                    // Key-value pair
                    $md .= "{$indent}- **{$key}**: {$value}\n";
                }
            }
        } else {
            $md .= "{$indent}{$content}\n";
        }

        return $md;
    }

    /**
     * Test that generates a comparison table
     */
    public function testGenerateComparisonTable(): void
    {
        $table = $this->generateComparisonTable();

        $outputPath = __DIR__ . '/api_comparison_table.md';
        file_put_contents($outputPath, $table);

        $this->assertFileExists($outputPath);
    }

    /**
     * Generate a quick reference comparison table
     */
    protected function generateComparisonTable(): string
    {
        $md = "# Ascio API Quick Reference: V2 vs V3\n\n";

        // Method mapping table
        $md .= "## Method Mapping\n\n";
        $md .= "| V2 Method | V3 Method | Notes |\n";
        $md .= "|-----------|-----------|-------|\n";
        $md .= "| LogIn | (not needed) | V3 uses SOAP headers |\n";
        $md .= "| CreateOrder | CreateOrder | Same, different auth |\n";
        $md .= "| ValidateOrder | ValidateOrder | Same, different auth |\n";
        $md .= "| GetOrder | GetOrder | Same |\n";
        $md .= "| GetDomain | GetDomain | Same |\n";
        $md .= "| SearchDomain | SearchDomain | Same |\n";
        $md .= "| AvailabilityInfo | AvailabilityInfo | Same |\n";
        $md .= "| AvailabilityCheck | AvailabilityCheck | Same |\n";
        $md .= "| PollMessage | PollQueue | Method renamed |\n";
        $md .= "| GetMessageQueue | GetQueueMessage | Method renamed |\n";
        $md .= "| AckMessage | AckQueueMessage | Method renamed |\n";
        $md .= "\n";

        // Parameter case mapping
        $md .= "## Parameter Case Changes\n\n";
        $md .= "| V2 Parameter | V3 Parameter |\n";
        $md .= "|--------------|---------------|\n";
        $md .= "| sessionId | (SOAP header) |\n";
        $md .= "| order | Order |\n";
        $md .= "| orderId | OrderId |\n";
        $md .= "| domainHandle | DomainHandle |\n";
        $md .= "| domainName | DomainName |\n";
        $md .= "| msgId | MsgId |\n";
        $md .= "| msgType | MsgType |\n";
        $md .= "| criteria | Criteria |\n";
        $md .= "\n";

        // Response key changes
        $md .= "## Response Key Changes\n\n";
        $md .= "| V2 Response Key | V3 Response Key |\n";
        $md .= "|-----------------|------------------|\n";
        $md .= "| order | Order |\n";
        $md .= "| domain | Domain |\n";
        $md .= "| domains | Domains |\n";
        $md .= "| item | QueueMessage |\n";
        $md .= "| Values->string | Errors->string |\n";
        $md .= "\n";

        // WSDL endpoints
        $md .= "## WSDL Endpoints\n\n";
        $md .= "| Environment | V2 WSDL | V3 WSDL |\n";
        $md .= "|-------------|---------|----------|\n";
        $md .= "| Production | https://aws.ascio.com/2012/01/01/AscioService.wsdl | https://aws.ascio.com/v3/aws.wsdl |\n";
        $md .= "| Test | https://aws.demo.ascio.com/2012/01/01/AscioService.wsdl | https://aws.demo.ascio.com/v3/aws.wsdl |\n";

        return $md;
    }
}
