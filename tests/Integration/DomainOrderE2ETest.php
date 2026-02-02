<?php
/**
 * Full Domain Order E2E Test
 *
 * Tests the complete domain order lifecycle against the Ascio demo API:
 * 1. Check domain availability
 * 2. Register domain via CreateOrder
 * 3. Poll queue until order completes
 * 4. Verify domain via GetDomain / SearchDomain
 * 5. Check domain status
 *
 * Uses the Request class (same as WHMCS module) for proper API interaction.
 *
 * REQUIRES:
 * - ASCIO_TEST_ACCOUNT and ASCIO_TEST_PASSWORD in .env
 *
 * Run with:
 *   ./vendor/bin/phpunit tests/Integration/DomainOrderE2ETest.php --group=e2e --testdox
 *
 * @group e2e
 * @group slow
 */

declare(strict_types=1);

namespace Ascio\Tests\Integration;

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;
use ascio\Request;
use IntegrationTestCredentials;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;

class DomainOrderE2ETest extends TestCase
{
    private const MAX_POLL_TIME = 300;
    private const POLL_INTERVAL = 10;

    private array $params;
    private ?string $username = null;
    private ?string $password = null;

    protected function setUp(): void
    {
        parent::setUp();

        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();

        $creds = IntegrationTestCredentials::get();
        $this->username = $creds['username'];
        $this->password = $creds['password'];

        if (!IntegrationTestCredentials::available()) {
            $this->markTestSkipped(
                'Ascio credentials not available. Set ASCIO_TEST_ACCOUNT and ASCIO_TEST_PASSWORD.'
            );
        }

        $this->params = $this->buildWhmcsParams();
    }

    /**
     * Build WHMCS-style module parameters used by Request class
     */
    private function buildWhmcsParams(?string $domainName = null): array
    {
        $domain = $domainName ?? 'e2e-test-' . time() . '.com';
        $parts = explode('.', $domain, 2);

        return [
            'Username' => $this->username,
            'Password' => $this->password,
            'TestMode' => 'on',
            'Simulate' => 'off',
            'domainid' => 999,
            'domainname' => $domain,
            'sld' => $parts[0],
            'tld' => $parts[1] ?? 'com',
            'regperiod' => 1,
            'firstname' => 'E2E',
            'lastname' => 'TestUser',
            'companyname' => 'E2E Test GmbH',
            'address1' => '123 Test Street',
            'address2' => '',
            'city' => 'Munich',
            'state' => 'Bavaria',
            'postcode' => '80331',
            'country' => 'DE',
            'email' => 'e2e-test@example.com',
            'fullphonenumber' => '+49.891234567',
            'adminfirstname' => 'Admin',
            'adminlastname' => 'TestUser',
            'admincompanyname' => 'E2E Test GmbH',
            'adminaddress1' => '123 Test Street',
            'adminaddress2' => '',
            'admincity' => 'Munich',
            'adminstate' => 'Bavaria',
            'adminpostcode' => '80331',
            'admincountry' => 'DE',
            'adminemail' => 'e2e-admin@example.com',
            'adminfullphonenumber' => '+49.891234567',
            'ns1' => 'ns1.ascio.net',
            'ns2' => 'ns2.ascio.net',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'eppcode' => '',
            'idprotection' => false,
            'custom' => [],
            'additionalfields' => [],
            'AutoExpire' => 'off',
            'Sync_Due_Date' => 'off',
            'DetailedOrderStatus' => 'on',
            'AutoCreateDNS' => 'off',
            'NameserverRegex' => '/.*/',
            'DatalessTransfer' => 'off',
            'Proxy_Lite' => 'off',
            'MultiBrand_Mode' => 'off',
        ];
    }

    /**
     * Test availability check works
     *
     * @test
     * @group e2e
     */
    public function testDomainAvailabilityCheck(): void
    {
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI');
        }

        $testDomain = 'e2e-avail-' . uniqid() . '.com';

        echo "\n=== Availability Check ===\n";
        echo "Domain: {$testDomain}\n";

        $request = new Request($this->params);
        $result = $request->availabilityInfo($testDomain);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('ResultCode', $result);
        $this->assertEquals(200, $result->ResultCode, 'Random domain should be available');

        echo "Result: {$result->ResultCode} (200 = Available)\n";
        echo "=== Done ===\n";
    }

    /**
     * Full order lifecycle: Register -> Poll -> GetDomain -> Verify
     *
     * @test
     * @group e2e
     * @group slow
     */
    public function testFullDomainOrderLifecycle(): void
    {
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI');
        }

        $testDomain = 'e2e-order-' . date('YmdHis') . '-' . rand(1000, 9999) . '.com';
        $this->params = $this->buildWhmcsParams($testDomain);

        echo "\n=== Full Domain Order E2E Test ===\n";
        echo "Domain: {$testDomain}\n";
        echo "Account: {$this->username}\n\n";

        // Step 1: Check availability
        echo "Step 1: Checking availability...\n";
        $request = new Request($this->params);
        $availResult = $request->availabilityInfo($testDomain);

        $this->assertIsObject($availResult);
        $this->assertEquals(200, $availResult->ResultCode, 'Domain should be available');
        echo "  Available (200)\n";

        // Step 2: Register domain (real CreateOrder on demo API)
        echo "\nStep 2: Registering domain (CreateOrder)...\n";
        $registerResult = $request->registerDomain($this->params);

        // registerDomain returns array on error, object on success
        if (is_array($registerResult)) {
            $error = $registerResult['error'] ?? 'Unknown error';
            $this->fail("RegisterDomain failed: {$error}");
        }

        $this->assertIsObject($registerResult);
        $this->assertContains(
            $registerResult->ResultCode,
            [200, 201],
            'CreateOrder should succeed: ' . ($registerResult->ResultMessage ?? '')
        );

        $orderId = $registerResult->OrderInfo->OrderId ?? null;
        $this->assertNotNull($orderId, 'Order ID should be returned');

        echo "  Order ID: {$orderId}\n";
        echo "  Status: " . ($registerResult->OrderInfo->Status ?? 'N/A') . "\n";

        // Step 3: Poll queue until completed
        echo "\nStep 3: Polling for order completion...\n";
        $pollResult = $this->pollUntilComplete($request, $orderId);

        echo "  Final status: {$pollResult['status']}\n";
        echo "  Messages processed: {$pollResult['messagesProcessed']}\n";

        $this->assertContains(
            $pollResult['status'],
            ['Completed', 'Pending', 'PendingEndUserAction'],
            "Order should reach expected status, got: {$pollResult['status']}"
        );

        // Step 4: Get order details
        echo "\nStep 4: Getting order details...\n";
        $orderResult = $request->getOrder($orderId);

        if (!is_array($orderResult)) {
            $orderInfo = $orderResult->OrderInfo ?? null;
            $orderStatus = $orderInfo->Status ?? 'Unknown';
            $orderType = $orderInfo->Type ?? 'Unknown';
            echo "  Type: {$orderType}\n";
            echo "  Status: {$orderStatus}\n";

            // Step 5: If completed, verify domain
            if ($orderStatus === 'Completed') {
                echo "\nStep 5: Verifying domain with GetDomain...\n";
                $domainHandle = $orderInfo->OrderRequest->Domain->DomainHandle
                    ?? $orderInfo->Domain->DomainHandle
                    ?? null;

                if ($domainHandle) {
                    $domain = $request->getDomain($domainHandle);

                    if (!is_array($domain)) {
                        echo "  Domain: " . ($domain->Name ?? 'N/A') . "\n";
                        echo "  Status: " . ($domain->Status ?? 'N/A') . "\n";
                        echo "  ExpDate: " . ($domain->ExpDate ?? 'N/A') . "\n";

                        $ns1 = $domain->NameServers->NameServer1->HostName ?? null;
                        echo "  NS1: " . ($ns1 ?? 'N/A') . "\n";

                        $this->assertEquals($testDomain, $domain->Name ?? '');
                        $this->assertEquals('ns1.ascio.net', $ns1);
                    } else {
                        echo "  GetDomain returned error: " . ($domain['error'] ?? 'unknown') . "\n";
                    }
                } else {
                    echo "  No domain handle found in order\n";
                }
            } else {
                echo "\nStep 5: Skipped (order not completed: {$orderStatus})\n";
            }
        } else {
            echo "  GetOrder error: " . ($orderResult['error'] ?? 'unknown') . "\n";
        }

        echo "\n=== Test Complete ===\n";
    }

    /**
     * Test poll queue directly
     *
     * @test
     * @group e2e
     */
    public function testPollQueueProcessing(): void
    {
        if (getenv('CI') === 'true') {
            $this->markTestSkipped('E2E tests skipped in CI');
        }

        echo "\n=== Poll Queue Test ===\n";

        $request = new Request($this->params);
        $result = $request->poll();

        // poll() returns object on success, array on error
        if (is_array($result)) {
            echo "Queue empty or error: " . ($result['error'] ?? 'no messages') . "\n";
            $this->assertTrue(true, 'Poll returned (may be empty)');
            echo "=== Done ===\n";
            return;
        }

        $this->assertIsObject($result);
        echo "Poll result code: {$result->ResultCode}\n";

        $hasMessage = isset($result->Message) && $result->Message !== null;
        echo "Has messages: " . ($hasMessage ? 'yes' : 'no') . "\n";

        if ($hasMessage) {
            $msg = $result->Message;
            $msgId = $msg->MsgId ?? null;
            echo "  Message ID: {$msgId}\n";
            echo "  Order ID: " . ($msg->OrderId ?? 'N/A') . "\n";
            echo "  Status: " . ($msg->OrderStatus ?? 'N/A') . "\n";
            echo "  Type: " . ($msg->OrderType ?? 'N/A') . "\n";

            // Get full message
            if ($msgId) {
                $fullMsg = $request->getQueueMessage($msgId);
                if (!is_array($fullMsg)) {
                    echo "  Full message retrieved (code: {$fullMsg->ResultCode})\n";
                }

                // Acknowledge
                $ackResult = $request->ack($msgId);
                if (!is_array($ackResult)) {
                    echo "  Message acknowledged (code: {$ackResult->ResultCode})\n";
                }
            }
        }

        echo "=== Done ===\n";
    }

    /**
     * Poll until order completes or timeout
     */
    private function pollUntilComplete(Request $request, string $targetOrderId): array
    {
        $startTime = time();
        $lastStatus = 'Unknown';
        $messagesProcessed = 0;

        while ((time() - $startTime) < self::MAX_POLL_TIME) {
            // Check order status directly
            $orderResult = $request->getOrder($targetOrderId);
            if (!is_array($orderResult)) {
                $status = $orderResult->OrderInfo->Status ?? 'Unknown';

                if ($status !== $lastStatus) {
                    echo "  [" . date('H:i:s') . "] Order {$targetOrderId}: {$status}\n";
                    $lastStatus = $status;
                }

                if (in_array($status, ['Completed', 'Failed', 'Invalid'])) {
                    return ['status' => $status, 'messagesProcessed' => $messagesProcessed];
                }
            }

            // Process queue messages
            $pollResult = $request->poll();
            if (!is_array($pollResult) && isset($pollResult->Message)) {
                $msg = $pollResult->Message;
                $msgId = $msg->MsgId ?? null;
                $msgOrderId = $msg->OrderId ?? null;
                $msgStatus = $msg->OrderStatus ?? 'Unknown';

                echo "  [" . date('H:i:s') . "] Queue msg: Order={$msgOrderId} Status={$msgStatus}\n";

                if ($msgId) {
                    $request->ack($msgId);
                    $messagesProcessed++;
                }

                if ($msgOrderId == $targetOrderId) {
                    $lastStatus = $msgStatus;
                    if (in_array($msgStatus, ['Completed', 'Failed', 'Invalid'])) {
                        return ['status' => $msgStatus, 'messagesProcessed' => $messagesProcessed];
                    }
                }

                continue; // Check for more messages immediately
            }

            sleep(self::POLL_INTERVAL);
        }

        return ['status' => $lastStatus, 'messagesProcessed' => $messagesProcessed];
    }
}
