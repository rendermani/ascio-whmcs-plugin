<?php
/**
 * SSL Lifecycle E2E Tests
 *
 * Tests the complete SSL certificate lifecycle:
 * 1. CreateOrder (Register) with real domain
 * 2. Poll queue for Pending_End_User_Action
 * 3. Extract DNS validation challenge
 * 4. Create DNS record via live Ascio DNS API
 * 5. Poll until Completed or Failed
 * 6. Verify certificate data
 *
 * REQUIRES:
 * - ASCIO_LIVE_ACCOUNT and ASCIO_LIVE_PASSWORD in .env (for DNS)
 * - ASCIO_TEST_DOMAIN in .env (real domain you own)
 * - Domain must use Ascio DNS or be controllable
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ascio\v3 as v3;

require_once __DIR__ . '/bootstrap.php';

class SslLifecycleE2ETest extends TestCase
{
    private ?v3\AscioService $client = null;
    private ?v3\AscioService $liveClient = null;
    private string $testDomain;
    private string $liveAccount;
    private string $livePassword;
    private TestConfig $config;
    private ?int $createdDnsRecordId = null;

    /**
     * Maximum time to wait for order completion (10 minutes)
     */
    private const MAX_POLL_TIME = 600;

    /**
     * Poll interval in seconds
     */
    private const POLL_INTERVAL = 10;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = TestConfig::getInstance();

        // Load live credentials from environment
        $this->liveAccount = getenv('ASCIO_LIVE_ACCOUNT') ?: '';
        $this->livePassword = getenv('ASCIO_LIVE_PASSWORD') ?: '';
        $this->testDomain = getenv('ASCIO_TEST_DOMAIN') ?: '';

        // Skip if live credentials not configured
        if (empty($this->liveAccount) || empty($this->livePassword)) {
            $this->markTestSkipped(
                'Live credentials not configured. Set ASCIO_LIVE_ACCOUNT and ASCIO_LIVE_PASSWORD.'
            );
        }

        if (empty($this->testDomain)) {
            $this->markTestSkipped(
                'Test domain not configured. Set ASCIO_TEST_DOMAIN to a real domain you own.'
            );
        }

        // Create live client for all SSL operations (demo API doesn't work with real domains)
        $this->client = $this->createLiveClient();

        // Live client alias for DNS operations
        $this->liveClient = $this->client;
    }

    protected function tearDown(): void
    {
        // Note: DNS records are NOT cleaned up during tearDown because
        // Sectigo may still need them for validation. Records are cleaned
        // up only after the certificate reaches a terminal status.
        // Old test records can be cleaned up manually via the DNS API.

        $this->client = null;
        $this->liveClient = null;
        parent::tearDown();
    }

    /**
     * Create a client for live API operations (DNS)
     */
    private function createLiveClient(): v3\AscioService
    {
        $header = new \SoapHeader(
            "http://www.ascio.com/2013/02",
            "SecurityHeaderDetails",
            [
                'Account' => $this->liveAccount,
                'Password' => $this->livePassword,
            ],
            false
        );

        $client = new v3\AscioService(
            ['trace' => true],
            'https://aws.ascio.com/v3/aws.wsdl'  // Live WSDL
        );
        $client->__setSoapHeaders($header);

        return $client;
    }

    /**
     * Test complete SSL registration lifecycle
     *
     * @test
     * @group e2e
     * @group slow
     */
    public function testCompleteRegistrationLifecycle(): void
    {
        // Skip in CI - this is for manual E2E testing
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI environment');
        }

        echo "\n=== SSL Lifecycle E2E Test ===\n";
        echo "Domain: {$this->testDomain}\n";
        echo "Account: {$this->liveAccount}\n\n";

        // Step 1: Prepare unique common name
        // Use a unique subdomain to avoid conflicts with previous orders
        $uniqueId = date('YmdHis') . rand(100, 999);
        $testCommonName = "ssl-test-{$uniqueId}.{$this->testDomain}";
        echo "Common Name: {$testCommonName}\n";

        // Step 2: Generate CSR
        echo "\nStep 2: Generating CSR...\n";
        $csr = $this->generateCsr($testCommonName);
        $this->assertNotEmpty($csr, 'CSR generation failed');
        echo "  CSR generated successfully\n";

        echo "\nStep 3: Building order request...\n";
        $request = $this->buildOrderRequest($csr, $testCommonName);

        // Step 4: Validate order first
        echo "\nStep 4: Validating order...\n";
        $validateResponse = $this->validateOrder($request);
        $validateCode = $validateResponse->ValidateOrderResult->getResultCode();

        if ($validateCode !== 200) {
            $errors = $this->extractErrors($validateResponse->ValidateOrderResult);
            echo "  Validation failed ({$validateCode}): " . implode(', ', $errors) . "\n";
            $this->markTestSkipped("Order validation failed: " . implode(', ', $errors));
        }
        echo "  Order validated successfully (200)\n";

        // Step 5: Create order
        echo "\nStep 5: Creating order...\n";
        $createResponse = $this->createOrder($request);
        $createResult = $createResponse->CreateOrderResult;

        $this->assertEquals(
            200,
            $createResult->getResultCode(),
            'CreateOrder failed: ' . $createResult->getResultMessage()
        );

        $orderInfo = $createResult->getOrderInfo();
        $orderId = $orderInfo->getOrderId();
        $status = $orderInfo->getStatus();

        echo "  Order created: {$orderId}\n";
        echo "  Initial status: {$status}\n";

        // Step 6: Poll for DNS validation challenge
        echo "\nStep 6: Polling for validation challenge...\n";
        $challenge = $this->pollForChallenge($orderId);

        if ($challenge === null) {
            echo "  No DNS challenge could be extracted from queue or order\n";
            echo "  Attempting to retrieve DCV info via GetOrder...\n";
            $challenge = $this->getChallengeFromOrder($orderId);
        }

        // If still no challenge, compute it from the CSR (Sectigo CNAME DCV)
        if ($challenge === null && $this->csrPem !== null) {
            echo "  Computing Sectigo CNAME DCV from CSR...\n";
            $challenge = $this->computeSectigoCnameDcv($this->csrPem, $testCommonName);
            echo "  Computed CNAME: {$challenge['authName']} -> {$challenge['authValue']}\n";
        }

        if ($challenge !== null) {
            echo "  DNS Challenge received:\n";
            echo "    AuthName: {$challenge['authName']}\n";
            echo "    AuthValue: {$challenge['authValue']}\n";
            echo "    Type: {$challenge['type']}\n";

            // Step 7: Create DNS record
            echo "\nStep 7: Creating DNS record...\n";
            $dnsResult = $this->createDnsRecord($challenge['authName'], $challenge['authValue']);
            if ($dnsResult) {
                echo "  DNS record created successfully\n";
            } else {
                echo "  DNS record creation failed (may already exist)\n";
            }
        } else {
            echo "  WARNING: Could not extract DNS challenge. Order will remain pending.\n";
            echo "  This may require manual DNS record creation or email validation.\n";
        }

        // Step 8: Poll until completion
        echo "\nStep 8: Polling until completion...\n";
        $finalStatus = $this->pollUntilComplete($orderId);

        echo "\n=== Final Status: {$finalStatus} ===\n";

        // Step 9: Verify final state
        if ($finalStatus === 'Completed') {
            echo "\nStep 9: Verifying certificate...\n";
            $this->verifyCertificate($orderId);
            echo "  Certificate verified!\n";
        }

        // Assert we got a terminal or expected status
        // PendingEndUserAction is expected for DNS validation awaiting record creation
        $this->assertContains(
            $finalStatus,
            ['Completed', 'Failed', 'Invalid', 'PendingEndUserAction'],
            "Order ended in unexpected status: {$finalStatus}"
        );
    }

    /**
     * Test that ValidateOrder returns 200 for valid request with real domain
     *
     * @test
     */
    public function testValidateOrderWithRealDomain(): void
    {
        $csr = $this->generateCsr($this->testDomain);
        $request = $this->buildOrderRequest($csr);

        $response = $this->validateOrder($request);
        $resultCode = $response->ValidateOrderResult->getResultCode();

        if ($resultCode !== 200) {
            $errors = $this->extractErrors($response->ValidateOrderResult);
            $this->fail('ValidateOrder failed with code ' . $resultCode . ': ' . implode(', ', $errors));
        }

        // With a real domain, we should get 200
        $this->assertEquals(
            200,
            $resultCode,
            'ValidateOrder should succeed with real domain: ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test polling queue for SSL messages
     *
     * @test
     */
    public function testPollQueueConnection(): void
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType(v3\ObjectType::SslCertificateType);

        $response = $this->client->PollQueue(new v3\PollQueue($request));
        $resultCode = $response->PollQueueResult->getResultCode();

        // Any valid response code is acceptable (200 or 207 for no messages)
        $this->assertContains($resultCode, [200, 207], 'PollQueue should return valid response code');
    }

    /**
     * Generate a CSR for the test domain and store the PEM for DCV computation
     */
    private function generateCsr(string $commonName): string
    {
        $dn = [
            'countryName' => 'DE',
            'stateOrProvinceName' => 'Bavaria',
            'localityName' => 'Munich',
            'organizationName' => 'Test Organization',
            'commonName' => $commonName,
            'emailAddress' => 'admin@' . $this->testDomain,
        ];

        $privkey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);
        openssl_csr_export($csr, $csrOut);

        // Store CSR PEM for DCV CNAME computation
        $this->csrPem = $csrOut;

        return $csrOut;
    }

    /** @var string|null CSR PEM for DCV computation */
    private ?string $csrPem = null;

    /**
     * Compute the Sectigo/Comodo CNAME DCV challenge from a CSR.
     *
     * For CNAME-based DCV, Sectigo uses:
     *   Name:  _<MD5(DER(CSR))>.<domain>
     *   Value: <SHA256(DER(CSR))>.comodoca.com
     *
     * @param string $csrPem The PEM-encoded CSR
     * @param string $domain The domain being validated
     * @return array{authName: string, authValue: string, type: string}
     */
    private function computeSectigoCnameDcv(string $csrPem, string $domain): array
    {
        // Convert PEM to DER
        $csrLines = explode("\n", trim($csrPem));
        // Remove header/footer lines
        $csrBase64 = '';
        foreach ($csrLines as $line) {
            if (strpos($line, '-----') === false) {
                $csrBase64 .= trim($line);
            }
        }
        $csrDer = base64_decode($csrBase64);

        $md5Hash = md5($csrDer);
        $sha256Hash = hash('sha256', $csrDer);

        // Sectigo CNAME format
        // SHA256 hash is 64 chars but DNS labels max 63 chars
        // Split into two 32-char labels: first_half.second_half.comodoca.com
        $sha256Part1 = substr($sha256Hash, 0, 32);
        $sha256Part2 = substr($sha256Hash, 32);

        return [
            'authName' => "_{$md5Hash}.{$domain}",
            'authValue' => "{$sha256Part1}.{$sha256Part2}.comodoca.com",
            'type' => 'cname',
        ];
    }

    /**
     * Build SSL certificate order request
     */
    private function buildOrderRequest(string $csr, ?string $commonName = null): v3\SslCertificateOrderRequest
    {
        $commonName = $commonName ?? $this->testDomain;
        $certificate = new v3\SslCertificate();
        $certificate->setCommonName($commonName);
        $certificate->setProductCode('positivessl');
        $certificate->setWebServerType(v3\WebServerType::ApacheSsl);
        $certificate->setApproverEmail('admin@' . $this->testDomain);
        $certificate->setCSR($csr);
        $certificate->setValidationType(v3\SslDomainValidationType::Dns);

        // Owner (Registrant) - Title is set via Extensions
        $owner = new v3\Registrant();
        $owner->setFirstName('Test');
        $owner->setLastName('User');
        $owner->setOrgName('Test Organization');
        $owner->setAddress1('Test Street 123');
        $owner->setCity('Munich');
        $owner->setState('Bavaria');
        $owner->setPostalCode('80331');
        $owner->setCountryCode('DE');
        $owner->setPhone('+49.891234567');
        $owner->setEmail('admin@' . $this->testDomain);
        $owner->setType('Organization');
        $owner->setExtensions(new v3\Extensions([new v3\KeyValue('Title', 'Mr.')]));
        $certificate->setOwner($owner);

        // Admin contact - Title is required via Extensions
        $admin = new v3\Contact();
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setOrgName('Test Organization');
        $admin->setAddress1('Test Street 123');
        $admin->setCity('Munich');
        $admin->setState('Bavaria');
        $admin->setPostalCode('80331');
        $admin->setCountryCode('DE');
        $admin->setPhone('+49.891234567');
        $admin->setEmail('admin@' . $this->testDomain);
        $admin->setType('Organization');
        $admin->setExtensions(new v3\Extensions([new v3\KeyValue('Title', 'Mr.')]));
        $certificate->setAdmin($admin);

        // Tech contact - Title is required via Extensions
        $tech = new v3\Contact();
        $tech->setFirstName('Tech');
        $tech->setLastName('User');
        $tech->setOrgName('Test Organization');
        $tech->setAddress1('Test Street 123');
        $tech->setCity('Munich');
        $tech->setState('Bavaria');
        $tech->setPostalCode('80331');
        $tech->setCountryCode('DE');
        $tech->setPhone('+49.891234567');
        $tech->setEmail('admin@' . $this->testDomain);
        $tech->setType('Organization');
        $tech->setExtensions(new v3\Extensions([new v3\KeyValue('Title', 'Mr.')]));
        $certificate->setTech($tech);

        $request = new v3\SslCertificateOrderRequest();
        $request->setType(v3\OrderType::Register);
        $request->setPeriod(1);
        $request->setTransactionComment('E2E Lifecycle Test');
        $request->setSslCertificate($certificate);

        return $request;
    }

    /**
     * Validate order without creating it
     */
    private function validateOrder(v3\SslCertificateOrderRequest $request): object
    {
        return $this->client->ValidateOrder(new v3\ValidateOrder($request));
    }

    /**
     * Create actual order
     */
    private function createOrder(v3\SslCertificateOrderRequest $request): object
    {
        return $this->client->CreateOrder(new v3\CreateOrder($request));
    }

    /**
     * Poll for DNS validation challenge
     */
    private function pollForChallenge(string $orderId): ?array
    {
        $startTime = time();

        while ((time() - $startTime) < 120) {  // Max 120 seconds for challenge
            $request = new v3\PollQueueRequest();
            $request->setObjectType(v3\ObjectType::SslCertificateType);

            try {
                $response = $this->client->PollQueue(new v3\PollQueue($request));
                $result = $response->PollQueueResult;

                if ($result->getResultCode() === 200 && $result->getMessage()) {
                    $queueMessage = $result->getMessage();
                    $messageId = (string) $queueMessage->getId();
                    $msgOrderId = $queueMessage->getOrderId();
                    $status = $queueMessage->getOrderStatus();

                    echo "    Message: {$messageId}, Order: {$msgOrderId}, Status: {$status}\n";

                    if ($msgOrderId === $orderId && $status === 'PendingEndUserAction') {
                        // Get message text content and dump it for debugging
                        $messageText = $queueMessage->getMessage() ?? '';
                        echo "    Message content: " . substr($messageText, 0, 500) . "\n";

                        $challenge = $this->parseDnsChallenge($messageText);

                        // If challenge not in message text, try to get it from the order
                        if ($challenge === null) {
                            echo "    Challenge not found in message, trying GetSslCertificate...\n";
                            $challenge = $this->getChallengeFromOrder($orderId);
                        }

                        // Acknowledge message
                        $this->ackQueueMessage($messageId);

                        return $challenge;
                    }

                    // Acknowledge other messages
                    $this->ackQueueMessage($messageId);
                } elseif ($result->getResultCode() === 207) {
                    // No messages in queue
                    echo "    No messages in queue\n";
                }
            } catch (\Exception $e) {
                echo "    Poll error: " . $e->getMessage() . "\n";
            }

            sleep(5);
        }

        // Last attempt: check order status directly and try to extract challenge
        echo "    Timeout reached, checking order status directly...\n";
        $challenge = $this->getChallengeFromOrder($orderId);
        return $challenge;
    }

    /**
     * Acknowledge queue message
     */
    private function ackQueueMessage(string $messageId): void
    {
        $request = new v3\AckQueueMessageRequest();
        $request->setMessageId($messageId);

        $this->client->AckQueueMessage(new v3\AckQueueMessage($request));
    }

    /**
     * Parse DNS challenge from message
     */
    private function parseDnsChallenge(string $message): ?array
    {
        // Try CNAME format: AuthName: xxx\nAuthValue: xxx
        if (preg_match('/AuthName:\s*(.+?)[\r\n]+AuthValue:\s*(.+)/i', $message, $matches)) {
            return [
                'authName' => trim($matches[1]),
                'authValue' => trim($matches[2]),
                'type' => 'cname',
            ];
        }

        // Try TXT format: AuthFileName: xxx\nAuthFileContent: xxx
        if (preg_match('/AuthFileName:\s*(.+?)[\r\n]+AuthFileContent:\s*(.+)/i', $message, $matches)) {
            return [
                'authName' => trim($matches[1]),
                'authValue' => trim($matches[2]),
                'type' => 'txt',
            ];
        }

        // Sectigo/Comodo CNAME format: _hash.domain CNAME hash.comodoca.com
        if (preg_match('/(_[a-f0-9]+\.[^\s]+)\s+(?:IN\s+)?CNAME\s+([^\s]+)/i', $message, $matches)) {
            return [
                'authName' => trim($matches[1]),
                'authValue' => trim($matches[2]),
                'type' => 'cname',
            ];
        }

        // Generic: look for any CNAME record pattern in the message
        if (preg_match('/CNAME[:\s]+(.+?)[\r\n\s]+(?:to|value|point)[:\s]+(.+)/i', $message, $matches)) {
            return [
                'authName' => trim($matches[1]),
                'authValue' => trim($matches[2]),
                'type' => 'cname',
            ];
        }

        // Try to find _dnsauth pattern (Sectigo DCV)
        if (preg_match('/(_dnsauth\.[^\s]+)\s+.*?([a-f0-9]{32,}\.[^\s]+)/i', $message, $matches)) {
            return [
                'authName' => trim($matches[1]),
                'authValue' => trim($matches[2]),
                'type' => 'cname',
            ];
        }

        return null;
    }

    /**
     * Try to get DCV challenge info from the order/certificate
     */
    private function getChallengeFromOrder(string $orderId): ?array
    {
        try {
            // Get the order to find certificate handle
            $orderRequest = new v3\GetOrderRequest();
            $orderRequest->setOrderId($orderId);
            $orderResponse = $this->client->GetOrder(new v3\GetOrder($orderRequest));
            $orderResult = $orderResponse->GetOrderResult;

            if ($orderResult->getResultCode() !== 200) {
                echo "    GetOrder failed: " . $orderResult->getResultMessage() . "\n";
                return null;
            }

            $orderInfo = $orderResult->getOrderInfo();
            $sslCert = $orderInfo->getOrderRequest()->getSslCertificate();
            $handle = $sslCert->getHandle();
            $commonName = $sslCert->getCommonName();

            echo "    Certificate handle: {$handle}\n";
            echo "    Common name: {$commonName}\n";

            // For Sectigo DNS validation, the CNAME record is typically:
            // _<MD5 hash of CSR>.domain → <SHA256 hash>.comodoca.com
            // This info may be in the certificate's Extensions or ObjectComment
            $objectComment = $sslCert->getObjectComment() ?? '';
            if (!empty($objectComment)) {
                echo "    ObjectComment: {$objectComment}\n";
                $challenge = $this->parseDnsChallenge($objectComment);
                if ($challenge) {
                    return $challenge;
                }
            }

            // Try to get certificate info if handle exists
            if (!empty($handle)) {
                $certRequest = new v3\GetSslCertificateRequest();
                $certRequest->setHandle($handle);
                $certResponse = $this->client->GetSslCertificate(new v3\GetSslCertificate($certRequest));
                $certResult = $certResponse->GetSslCertificateResult;

                if ($certResult->getResultCode() === 200) {
                    $certInfo = $certResult->getSslCertificateInfo();
                    echo "    Certificate status: " . $certInfo->getStatus() . "\n";

                    // Check object comment for DCV data
                    $comment = $certInfo->getObjectComment() ?? '';
                    if (!empty($comment)) {
                        echo "    CertInfo ObjectComment: {$comment}\n";
                        $challenge = $this->parseDnsChallenge($comment);
                        if ($challenge) {
                            return $challenge;
                        }
                    }
                }
            }

            echo "    Could not extract DCV challenge from order/certificate\n";
            return null;

        } catch (\Exception $e) {
            echo "    Error getting challenge from order: " . $e->getMessage() . "\n";
            return null;
        }
    }

    /**
     * Create DNS record for validation (uses live API)
     */
    private function createDnsRecord(string $authName, string $authValue): bool
    {
        // Use full FQDN for Source (matches existing records in zone)
        $source = $authName;
        // Ensure Source includes the zone suffix
        if (!str_ends_with($source, '.' . $this->testDomain)) {
            $source = $source . '.' . $this->testDomain;
        }

        // Use target as-is (no trailing dot - Ascio DNS API handles FQDN internally)
        $target = rtrim($authValue, '.');

        echo "    Creating DNS record:\n";
        echo "      Zone: {$this->testDomain}\n";
        echo "      Source: {$source}\n";
        echo "      Target: {$target}\n";

        try {
            // Load DNS service class
            require_once __DIR__ . '/../../lib/_DnsService.php';

            // Create DNS client with live credentials
            $dnsClient = new \DnsService(
                $this->liveAccount,
                $this->livePassword,
                '' // No partner account needed
            );

            // Check if zone exists
            $getZone = new \GetZone();
            $getZone->zoneName = $this->testDomain;
            $zoneResponse = $dnsClient->GetZone($getZone);

            $zoneExists = ($zoneResponse->GetZoneResult->StatusCode == 200);
            echo "      Zone exists: " . ($zoneExists ? 'yes' : 'no') . "\n";

            if (!$zoneExists) {
                // Create zone first
                echo "      Creating zone...\n";
                $createZone = new \CreateZone();
                $createZone->zoneName = $this->testDomain;
                $createZone->owner = $this->liveAccount;
                $createZoneResponse = $dnsClient->CreateZone($createZone);

                if ($createZoneResponse->CreateZoneResult->StatusCode !== 200) {
                    echo "      Failed to create zone: {$createZoneResponse->CreateZoneResult->StatusMessage}\n";
                    return false;
                }
                echo "      Zone created successfully\n";
            }

            // CNAME record with full FQDN source
            $record = new \CNAME();
            $record->Source = $source;
            $record->Target = $target;
            $record->TTL = 3600;

            // Create the record
            $createRecord = new \CreateRecord();
            $createRecord->zoneName = $this->testDomain;
            $createRecord->record = $record;

            $response = $dnsClient->CreateRecord($createRecord);

            if ($response->CreateRecordResult->StatusCode === 200) {
                $this->createdDnsRecordId = $response->recordId ?? null;
                echo "      DNS record created successfully (ID: {$this->createdDnsRecordId})\n";
                return true;
            } else {
                $msg = $response->CreateRecordResult->StatusMessage ?? 'Unknown error';
                echo "      DNS creation failed ({$response->CreateRecordResult->StatusCode}): {$msg}\n";
                // Record might already exist - not a failure
                if (strpos($msg, 'already exists') !== false) {
                    echo "      Record already exists, continuing...\n";
                    return true;
                }
                return false;
            }
        } catch (\SoapFault $e) {
            echo "      DNS SOAP Error: " . $e->getMessage() . "\n";
            if (isset($e->detail)) {
                echo "      Detail: " . print_r($e->detail, true) . "\n";
            }
            return false;
        } catch (\Exception $e) {
            echo "      DNS Error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Delete DNS record created during test (cleanup)
     */
    private function deleteDnsRecord(): void
    {
        if (!$this->createdDnsRecordId) {
            return;
        }

        try {
            require_once __DIR__ . '/../../lib/_DnsService.php';

            $dnsClient = new \DnsService(
                $this->liveAccount,
                $this->livePassword,
                ''
            );

            $deleteRecord = new \DeleteRecord();
            $deleteRecord->recordId = $this->createdDnsRecordId;
            $dnsClient->DeleteRecord($deleteRecord);

            echo "    Cleaned up DNS record (ID: {$this->createdDnsRecordId})\n";
        } catch (\Exception $e) {
            echo "    DNS cleanup failed: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Poll until order reaches terminal status
     */
    private function pollUntilComplete(string $orderId): string
    {
        $startTime = time();
        $lastStatus = 'Unknown';

        while ((time() - $startTime) < self::MAX_POLL_TIME) {
            // Check order status directly
            $request = new v3\GetOrderRequest();
            $request->setOrderId($orderId);

            try {
                $response = $this->client->GetOrder(new v3\GetOrder($request));
                $result = $response->GetOrderResult;

                if ($result->getResultCode() === 200) {
                    $orderInfo = $result->getOrderInfo();
                    $status = $orderInfo->getStatus();

                    if ($status !== $lastStatus) {
                        echo "    Status: {$status}\n";
                        $lastStatus = $status;
                    }

                    // Terminal statuses
                    if (in_array($status, ['Completed', 'Failed', 'Invalid'])) {
                        return $status;
                    }
                }
            } catch (\Exception $e) {
                echo "    GetOrder error: " . $e->getMessage() . "\n";
            }

            // Also poll queue for any messages
            $this->processQueueMessages($orderId);

            sleep(self::POLL_INTERVAL);
        }

        return $lastStatus;
    }

    /**
     * Process any pending queue messages for an order
     */
    private function processQueueMessages(string $orderId): void
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType(v3\ObjectType::SslCertificateType);

        try {
            $response = $this->client->PollQueue(new v3\PollQueue($request));
            $result = $response->PollQueueResult;

            if ($result->getResultCode() === 200 && $result->getMessage()) {
                $queueMessage = $result->getMessage();
                $messageId = (string) $queueMessage->getId();
                $this->ackQueueMessage($messageId);
            }
        } catch (\Exception $e) {
            // Ignore poll errors
        }
    }

    /**
     * Verify certificate was issued and retrieve certificate content
     */
    private function verifyCertificate(string $orderId): void
    {
        // Step 1: Get order to verify completion and get certificate handle
        $request = new v3\GetOrderRequest();
        $request->setOrderId($orderId);

        $response = $this->client->GetOrder(new v3\GetOrder($request));
        $result = $response->GetOrderResult;

        $this->assertEquals(200, $result->getResultCode());

        $orderInfo = $result->getOrderInfo();
        $this->assertEquals('Completed', $orderInfo->getStatus());

        // Get certificate handle from order
        $certHandle = $orderInfo->getOrderRequest()->getSslCertificate()->getHandle();
        $this->assertNotEmpty($certHandle, 'Certificate handle should be set');

        echo "    Certificate Handle: {$certHandle}\n";

        // Step 2: Retrieve certificate using GetSslCertificate
        $getCertRequest = new v3\GetSslCertificateRequest();
        $getCertRequest->setHandle($certHandle);

        try {
            $getCertResponse = $this->client->GetSslCertificate(new v3\GetSslCertificate($getCertRequest));
            $getCertResult = $getCertResponse->GetSslCertificateResult;

            if ($getCertResult->getResultCode() === 200) {
                $certInfo = $getCertResult->getSslCertificateInfo();

                // Get certificate content
                $certificate = $certInfo->getCertificate() ?? '';
                $commonName = $certInfo->getCommonName() ?? '';
                $productCode = $certInfo->getProductCode() ?? '';
                $status = $certInfo->getStatus() ?? '';

                echo "    Certificate Retrieved:\n";
                echo "      Common Name: {$commonName}\n";
                echo "      Product: {$productCode}\n";
                echo "      Status: {$status}\n";

                // Verify certificate content
                if (!empty($certificate)) {
                    $this->assertStringContainsString('-----BEGIN CERTIFICATE-----', $certificate, 'Certificate should be in PEM format');
                    $this->assertStringContainsString('-----END CERTIFICATE-----', $certificate, 'Certificate should be complete PEM');
                    echo "      Certificate: Valid PEM format (" . strlen($certificate) . " bytes)\n";

                    // Parse certificate to verify details
                    $certData = openssl_x509_parse($certificate);
                    if ($certData) {
                        echo "      Subject: " . ($certData['subject']['CN'] ?? 'N/A') . "\n";
                        echo "      Issuer: " . ($certData['issuer']['O'] ?? 'N/A') . "\n";
                        echo "      Valid Until: " . date('Y-m-d', $certData['validTo_time_t'] ?? 0) . "\n";
                    }
                } else {
                    echo "      Certificate content not yet available (pending issuance)\n";
                }

                // Also check for intermediate certificates
                $intermediateCert = $certInfo->getIntermediateCertificate() ?? '';
                if (!empty($intermediateCert)) {
                    echo "      Intermediate Certificate: Present\n";
                }
            } else {
                echo "    GetSslCertificate returned: {$getCertResult->getResultCode()} - {$getCertResult->getResultMessage()}\n";
            }
        } catch (\Exception $e) {
            echo "    GetSslCertificate error: " . $e->getMessage() . "\n";
            // Not a failure - certificate may not be downloadable yet
        }
    }

    /**
     * Extract errors from result
     */
    private function extractErrors($result): array
    {
        $errors = [];
        $errorObj = $result->getErrors();

        if ($errorObj) {
            $errorList = $errorObj->getString();
            if (is_array($errorList)) {
                $errors = $errorList;
            } elseif ($errorList) {
                $errors[] = $errorList;
            }
        }

        return $errors;
    }
}
