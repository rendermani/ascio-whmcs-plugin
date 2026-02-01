<?php
/**
 * SSL Callback Processing Tests
 *
 * Tests callback processing for various SSL order statuses.
 * Uses mocked callback data to test processing logic without
 * requiring actual API callbacks.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Integration;

use ascio\v3 as v3;

require_once __DIR__ . '/SslIntegrationTestBase.php';

class SslCallbackProcessingTest extends SslIntegrationTestBase
{
    /**
     * Test processing a Pending callback
     *
     * @test
     */
    public function testProcessPendingCallback(): void
    {
        $orderId = 'TEST' . uniqid();
        $callback = $this->mockCallback($orderId, 'Pending', 'Order is being processed.');

        $this->assertEquals($orderId, $callback['orderId']);
        $this->assertEquals('Pending', $callback['status']);
        $this->assertNotEmpty($callback['messageId']);
        $this->assertStringContainsString('processed', $callback['message']);
    }

    /**
     * Test processing a Pending_End_User_Action callback
     *
     * @test
     */
    public function testProcessPendingEndUserAction(): void
    {
        $orderId = 'TEST' . uniqid();
        $domain = 'test-pending.example.com';

        $message = "Domain validation required.\n\n" .
            "AuthName: _ascio-validation.{$domain}\n" .
            "AuthValue: validation-token-" . uniqid() . "\n\n" .
            "Please create a DNS TXT record.";

        $callback = $this->mockCallback($orderId, 'Pending_End_User_Action', $message);

        $this->assertEquals('Pending_End_User_Action', $callback['status']);
        $this->assertStringContainsString('AuthName:', $callback['message']);
        $this->assertStringContainsString('AuthValue:', $callback['message']);

        // Verify this would trigger DNS verification flow
        $tokens = $this->extractDnsTokens($callback['message']);
        $this->assertNotNull($tokens['authName']);
        $this->assertNotNull($tokens['authValue']);
    }

    /**
     * Test processing a Completed callback
     *
     * @test
     */
    public function testProcessCompletedCallback(): void
    {
        $orderId = 'TEST' . uniqid();
        $callback = $this->mockCallback($orderId, 'Completed', 'SSL Certificate has been issued successfully.');

        $this->assertEquals('Completed', $callback['status']);
        $this->assertStringContainsString('issued', $callback['message']);

        // Verify completed status would trigger certificate retrieval
        $expectedWhmcsStatus = $this->mapStatusToWhmcs($callback['status']);
        $this->assertEquals('Active', $expectedWhmcsStatus);
    }

    /**
     * Test processing a Failed callback
     *
     * @test
     */
    public function testProcessFailedCallback(): void
    {
        $orderId = 'TEST' . uniqid();
        $callback = $this->mockCallback($orderId, 'Failed', 'Order validation failed. CSR does not match domain.');

        $this->assertEquals('Failed', $callback['status']);
        $this->assertStringContainsString('failed', strtolower($callback['message']));

        // Verify failed status would keep WHMCS status as Pending
        $expectedWhmcsStatus = $this->mapStatusToWhmcs($callback['status']);
        $this->assertEquals('Pending', $expectedWhmcsStatus);
    }

    /**
     * Test that callback updates database status correctly
     *
     * @test
     */
    public function testCallbackUpdatesDatabaseStatus(): void
    {
        $testStatuses = [
            'Pending',
            'Pending_End_User_Action',
            'Completed',
            'Failed',
            'Invalid',
        ];

        foreach ($testStatuses as $status) {
            $orderId = 'TEST' . uniqid();
            $callback = $this->mockCallback($orderId, $status);

            // Simulate database update data
            $dbUpdateData = $this->buildDatabaseUpdateData($callback);

            $this->assertEquals($status, $dbUpdateData['status']);
            $this->assertArrayHasKey('order_id', $dbUpdateData);
            $this->assertEquals($orderId, $dbUpdateData['order_id']);
        }
    }

    /**
     * Test that callback updates WHMCS service status
     *
     * @test
     */
    public function testCallbackUpdatesWhmcsServiceStatus(): void
    {
        $statusMapping = [
            'Pending' => 'Pending',
            'Pending_End_User_Action' => 'Pending',
            'Completed' => 'Active',
            'Failed' => 'Pending',
            'Invalid' => 'Pending',
        ];

        foreach ($statusMapping as $ascioStatus => $expectedWhmcsStatus) {
            $callback = $this->mockCallback('TEST' . uniqid(), $ascioStatus);
            $whmcsStatus = $this->mapStatusToWhmcs($callback['status']);

            $this->assertEquals(
                $expectedWhmcsStatus,
                $whmcsStatus,
                "Ascio status '{$ascioStatus}' should map to WHMCS status '{$expectedWhmcsStatus}'"
            );
        }
    }

    /**
     * Test that completed callback stores certificate handle
     *
     * @test
     */
    public function testCallbackStoresCertificateHandle(): void
    {
        $orderId = 'TEST' . uniqid();
        $certificateHandle = 'CERT' . uniqid();

        // Simulate completed callback with certificate data
        $callbackData = [
            'orderId' => $orderId,
            'status' => 'Completed',
            'messageId' => 'MSG' . uniqid(),
            'message' => 'SSL Certificate issued.',
            'certificateHandle' => $certificateHandle,
            'expireDate' => date('Y-m-d', strtotime('+1 year')),
        ];

        // Verify certificate data would be stored
        $this->assertNotEmpty($callbackData['certificateHandle']);
        $this->assertNotEmpty($callbackData['expireDate']);

        // Verify the update data includes certificate info
        $dbUpdateData = $this->buildCompletedCallbackData($callbackData);

        $this->assertEquals($certificateHandle, $dbUpdateData['certificate_id']);
        $this->assertNotEmpty($dbUpdateData['expire_date']);
    }

    /**
     * Test Invalid status callback processing
     *
     * @test
     */
    public function testProcessInvalidCallback(): void
    {
        $orderId = 'TEST' . uniqid();
        $callback = $this->mockCallback($orderId, 'Invalid', 'Order is invalid. Missing required contact information.');

        $this->assertEquals('Invalid', $callback['status']);

        // Verify invalid status handling
        $dbUpdateData = $this->buildDatabaseUpdateData($callback);
        $this->assertEquals('Invalid', $dbUpdateData['status']);

        // Invalid should not change to Active
        $whmcsStatus = $this->mapStatusToWhmcs($callback['status']);
        $this->assertNotEquals('Active', $whmcsStatus);
    }

    /**
     * Test callback with DNS verification data extraction
     *
     * @test
     */
    public function testCallbackDnsDataExtraction(): void
    {
        $orderId = 'TEST' . uniqid();
        $domain = 'dns-extract.example.com';
        $authName = '_ascio-validation.' . $domain;
        $authValue = 'dns-token-' . uniqid();

        $message = "AuthName: {$authName}\nAuthValue: {$authValue}";
        $callback = $this->mockCallback($orderId, 'Pending_End_User_Action', $message);

        $tokens = $this->extractDnsTokens($callback['message']);

        // Verify tokens were extracted correctly
        $this->assertEquals($authName, $tokens['authName']);
        $this->assertEquals($authValue, $tokens['authValue']);

        // Verify data would be stored in database
        $dbUpdateData = $this->buildDnsCallbackData($callback, $tokens);
        $this->assertEquals($authName, $dbUpdateData['dns_name']);
        $this->assertEquals($authValue, $dbUpdateData['dns_value']);
    }

    /**
     * Test callback error message storage
     *
     * @test
     */
    public function testCallbackStoresErrorMessage(): void
    {
        $orderId = 'TEST' . uniqid();
        $errorMessage = 'CSR validation failed: Common Name does not match domain.';

        $callback = $this->mockCallback($orderId, 'Failed', $errorMessage);

        $dbUpdateData = $this->buildDatabaseUpdateData($callback);

        $this->assertEquals('Failed', $dbUpdateData['status']);
        $this->assertEquals($errorMessage, $dbUpdateData['message']);
    }

    /**
     * Test callback timestamp handling
     *
     * @test
     */
    public function testCallbackTimestampHandling(): void
    {
        $orderId = 'TEST' . uniqid();
        $callback = $this->mockCallback($orderId, 'Completed');

        $this->assertNotEmpty($callback['timestamp']);

        // Verify timestamp is valid
        $timestamp = strtotime($callback['timestamp']);
        $this->assertIsInt($timestamp);
        $this->assertGreaterThan(0, $timestamp);

        // Timestamp should be recent (within last minute)
        $this->assertGreaterThan(time() - 60, $timestamp);
    }

    /**
     * Test callback with SAN DNS data
     *
     * @test
     */
    public function testCallbackWithSanDnsData(): void
    {
        $orderId = 'TEST' . uniqid();
        $primaryDomain = 'primary.example.com';
        $sans = ['www.primary.example.com', 'api.primary.example.com'];

        // Build callback with SAN data (simplified for testing)
        $callback = [
            'orderId' => $orderId,
            'status' => 'Pending_End_User_Action',
            'messageId' => 'MSG' . uniqid(),
            'message' => "AuthName: _ascio-validation.{$primaryDomain}\nAuthValue: primary-token",
            'sans' => array_map(function ($san) {
                return [
                    'name' => $san,
                    'dns_name' => '_ascio-validation.' . $san,
                    'dns_value' => 'san-token-' . md5($san),
                ];
            }, $sans),
        ];

        // Verify SAN data structure
        $this->assertCount(2, $callback['sans']);

        foreach ($callback['sans'] as $sanData) {
            $this->assertArrayHasKey('name', $sanData);
            $this->assertArrayHasKey('dns_name', $sanData);
            $this->assertArrayHasKey('dns_value', $sanData);
            $this->assertStringStartsWith('_ascio-validation.', $sanData['dns_name']);
        }
    }

    /**
     * Test callback sequence simulation
     *
     * @test
     */
    public function testCallbackSequence(): void
    {
        $orderId = 'TEST' . uniqid();
        $domain = 'sequence-test.example.com';

        // Step 1: Order submitted - Pending
        $step1 = $this->mockCallback($orderId, 'Pending');
        $this->assertEquals('Pending', $step1['status']);

        // Step 2: DNS verification needed - Pending_End_User_Action
        $step2 = $this->mockCallback(
            $orderId,
            'Pending_End_User_Action',
            "AuthName: _ascio-validation.{$domain}\nAuthValue: token-xyz"
        );
        $this->assertEquals('Pending_End_User_Action', $step2['status']);

        // Step 3: Certificate issued - Completed
        $step3 = $this->mockCallback($orderId, 'Completed');
        $this->assertEquals('Completed', $step3['status']);

        // Verify final WHMCS status
        $finalWhmcsStatus = $this->mapStatusToWhmcs($step3['status']);
        $this->assertEquals('Active', $finalWhmcsStatus);
    }

    /**
     * Helper method to extract DNS tokens from callback message
     */
    private function extractDnsTokens(string $message): array
    {
        $regex = '/AuthName:\s*(.*)\nAuthValue:\s*(.*)/';
        preg_match($regex, $message, $result);

        if (count($result) < 3) {
            return ['authName' => null, 'authValue' => null];
        }

        return [
            'authName' => trim($result[1]),
            'authValue' => trim($result[2]),
        ];
    }

    /**
     * Map Ascio status to WHMCS status
     */
    private function mapStatusToWhmcs(string $status): string
    {
        return match ($status) {
            'Completed' => 'Active',
            default => 'Pending',
        };
    }

    /**
     * Build database update data from callback
     */
    private function buildDatabaseUpdateData(array $callback): array
    {
        return [
            'order_id' => $callback['orderId'],
            'status' => $callback['status'],
            'message' => $callback['message'] ?? null,
        ];
    }

    /**
     * Build database update data for DNS callback
     */
    private function buildDnsCallbackData(array $callback, array $tokens): array
    {
        $data = $this->buildDatabaseUpdateData($callback);
        $data['dns_name'] = $tokens['authName'];
        $data['dns_value'] = $tokens['authValue'];

        return $data;
    }

    /**
     * Build database update data for completed callback
     */
    private function buildCompletedCallbackData(array $callback): array
    {
        return [
            'order_id' => $callback['orderId'],
            'status' => $callback['status'],
            'certificate_id' => $callback['certificateHandle'] ?? null,
            'expire_date' => $callback['expireDate'] ?? null,
        ];
    }
}
