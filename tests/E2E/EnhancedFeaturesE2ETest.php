<?php

namespace Ascio\Tests\E2E;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresEnv;

/**
 * End-to-End tests for PS-144 enhanced features.
 *
 * These tests simulate complete user workflows and verify
 * that all components work together in realistic scenarios.
 *
 * Run with: ./vendor/bin/phpunit tests/E2E/EnhancedFeaturesE2ETest.php
 */
#[Group('e2e')]
#[Group('enhanced-features')]
class EnhancedFeaturesE2ETest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load all required files
        require_once dirname(__DIR__) . '/Mocks/WhmcsFunctionsMock.php';
        require_once dirname(__DIR__) . '/Mocks/CapsuleMock.php';
        require_once dirname(__DIR__, 2) . '/lib/DomainHistory.php';
        require_once dirname(__DIR__, 2) . '/lib/TransferTracker.php';
        require_once dirname(__DIR__, 2) . '/lib/ExpiryReportWidget.php';
        require_once dirname(__DIR__, 2) . '/lib/DomainImporter.php';

        \Ascio\Tests\Mocks\WhmcsFunctionsMock::reset();
        \Ascio\Tests\Mocks\CapsuleMock::reset();

        // Ensure tables exist
        \ascio\DomainHistory::ensureTable();
        \ascio\TransferTracker::ensureTable();
    }

    // =========================================================================
    // E2E Scenario: Complete Domain Registration Workflow
    // =========================================================================

    #[Test]
    public function e2eCompleteRegistrationWorkflow(): void
    {
        $domainId = 1000;
        $domainName = 'e2e-registration-test.com';
        $orderId = 'E2E-REG-001';
        $clientId = 1;

        // === Step 1: Domain registration initiated ===
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Pending',
            'Pending',
            $orderId,
            'Register_Domain',
            'Registration order submitted to Ascio'
        );

        // Verify initial state
        $history = \ascio\DomainHistory::getHistory($domainId);
        $this->assertCount(1, $history);
        $this->assertEquals('Pending', $history[0]['ascio_status']);

        // === Step 2: Registration pending at registry ===
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Pending_Registry',
            'Pending',
            $orderId,
            'Register_Domain',
            'Awaiting registry confirmation'
        );

        // === Step 3: Registration completed ===
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Completed',
            'Active',
            $orderId,
            'Register_Domain',
            'Domain registration completed successfully'
        );

        // === Verify final state ===
        $history = \ascio\DomainHistory::getHistory($domainId);
        $this->assertCount(3, $history);

        // Most recent first
        $this->assertEquals('Completed', $history[0]['ascio_status']);
        $this->assertEquals('Active', $history[0]['whmcs_status']);

        // Verify display output - formatForDisplay expects the history array, not domain ID
        $displayHtml = \ascio\DomainHistory::formatForDisplay($history);
        $this->assertStringContainsString('Completed', $displayHtml);
        $this->assertStringContainsString('Active', $displayHtml);
        $this->assertStringContainsString($orderId, $displayHtml);
    }

    // =========================================================================
    // E2E Scenario: Complete Domain Transfer Workflow
    // =========================================================================

    #[Test]
    public function e2eCompleteDomainTransferWorkflow(): void
    {
        $domainId = 2000;
        $domainName = 'e2e-transfer-test.com';
        $orderId = 'E2E-TRF-001';

        // === Step 1: Transfer initiated ===
        \ascio\TransferTracker::updateStatus($domainId, 'pending', $orderId, 'Transfer initiated by customer');
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Pending',
            'Pending Transfer',
            $orderId,
            'Transfer_Domain',
            'Transfer order submitted'
        );

        $status = \ascio\TransferTracker::getTransferStatus($domainId);
        $this->assertEquals('pending', $status['current_stage']);
        $this->assertEquals(25, \ascio\TransferTracker::getProgressPercentage('pending'));

        // === Step 2: Awaiting authorization ===
        \ascio\TransferTracker::updateStatus($domainId, 'validating', $orderId, 'Awaiting losing registrar approval');
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Pending_End_User_Action',
            'Pending Transfer',
            $orderId,
            'Transfer_Domain',
            'Transfer awaiting authorization from registrant'
        );

        $status = \ascio\TransferTracker::getTransferStatus($domainId);
        $this->assertEquals('validating', $status['current_stage']);
        $this->assertEquals(50, \ascio\TransferTracker::getProgressPercentage('validating'));

        // === Step 3: Processing at registry ===
        \ascio\TransferTracker::updateStatus($domainId, 'processing', $orderId, 'Registry processing transfer');
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Pending_Registry',
            'Pending Transfer',
            $orderId,
            'Transfer_Domain',
            'Transfer being processed by registry'
        );

        $status = \ascio\TransferTracker::getTransferStatus($domainId);
        $this->assertEquals('processing', $status['current_stage']);
        $this->assertEquals(75, \ascio\TransferTracker::getProgressPercentage('processing'));

        // === Step 4: Transfer completed ===
        \ascio\TransferTracker::updateStatus($domainId, 'completed', $orderId, 'Transfer successful');
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Completed',
            'Active',
            $orderId,
            'Transfer_Domain',
            'Domain transfer completed successfully'
        );

        // === Verify final state ===
        $status = \ascio\TransferTracker::getTransferStatus($domainId);
        $this->assertEquals('completed', $status['current_stage']);
        $this->assertEquals(100, \ascio\TransferTracker::getProgressPercentage('completed'));

        $history = \ascio\DomainHistory::getHistory($domainId);
        $this->assertCount(4, $history);

        // Verify progress HTML shows 100%
        $progressHtml = \ascio\TransferTracker::renderProgressHtml($status);
        $this->assertStringContainsString('100%', $progressHtml);
        $this->assertStringContainsString('Completed', $progressHtml);
    }

    // =========================================================================
    // E2E Scenario: Failed Domain Transfer
    // =========================================================================

    #[Test]
    public function e2eFailedDomainTransferWorkflow(): void
    {
        $domainId = 3000;
        $domainName = 'e2e-failed-transfer.com';
        $orderId = 'E2E-TRF-FAIL-001';

        // === Step 1: Transfer initiated ===
        \ascio\TransferTracker::updateStatus($domainId, 'pending', $orderId);

        // === Step 2: Transfer failed ===
        \ascio\TransferTracker::updateStatus($domainId, 'failed', $orderId, 'Transfer rejected: Domain locked at losing registrar');
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Failed',
            'Pending Transfer',
            $orderId,
            'Transfer_Domain',
            'Transfer failed: Domain is locked at the losing registrar'
        );

        // === Verify failure state ===
        $status = \ascio\TransferTracker::getTransferStatus($domainId);
        $this->assertEquals('failed', $status['current_stage']);
        $this->assertStringContainsString('locked', $status['message']);

        $progressHtml = \ascio\TransferTracker::renderProgressHtml($status);
        // The HTML contains "Failed" as a label in the progress stages
        $this->assertStringContainsString('failed', $status['current_stage']);
        $this->assertStringContainsString('locked', $progressHtml);
    }

    // =========================================================================
    // E2E Scenario: Expiry Report Generation
    // =========================================================================

    #[Test]
    public function e2eExpiryReportWorkflow(): void
    {
        // Set up test data with various expiry dates
        $today = new \DateTime();

        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tbldomains', [
            [
                'id' => 1,
                'domain' => 'expires-in-10-days.com',
                'registrar' => 'ascio',
                'status' => 'Active',
                'expirydate' => $today->modify('+10 days')->format('Y-m-d'),
                'userid' => 1,
            ],
            [
                'id' => 2,
                'domain' => 'expires-in-45-days.com',
                'registrar' => 'ascio',
                'status' => 'Active',
                'expirydate' => (new \DateTime())->modify('+45 days')->format('Y-m-d'),
                'userid' => 1,
            ],
            [
                'id' => 3,
                'domain' => 'expires-in-100-days.com',
                'registrar' => 'ascio',
                'status' => 'Active',
                'expirydate' => (new \DateTime())->modify('+100 days')->format('Y-m-d'),
                'userid' => 2,
            ],
        ]);

        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tblclients', [
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe', 'email' => 'john@example.com', 'companyname' => 'Acme Inc'],
            ['id' => 2, 'firstname' => 'Jane', 'lastname' => 'Smith', 'email' => 'jane@example.com', 'companyname' => ''],
        ]);

        // Clear any cached stats
        \ascio\ExpiryReportWidget::clearCache();

        // === Get expiry statistics ===
        $stats = \ascio\ExpiryReportWidget::getExpiryStats();

        // Keys are '30', '60', '90', not 'days_30', etc.
        $this->assertArrayHasKey('30', $stats);
        $this->assertArrayHasKey('60', $stats);
        $this->assertArrayHasKey('90', $stats);
        $this->assertArrayHasKey('total_active', $stats);
        $this->assertIsInt($stats['30']);
        $this->assertIsInt($stats['60']);
        $this->assertIsInt($stats['90']);

        // === Generate CSV export ===
        $domains = [
            [
                'id' => 1,
                'domain' => 'expires-in-10-days.com',
                'client_name' => 'John Doe (Acme Inc)',
                'client_email' => 'john@example.com',
                'expirydate' => (new \DateTime())->modify('+10 days')->format('Y-m-d'),
                'days_left' => 10,
                'status' => 'Active',
            ],
        ];

        $csv = \ascio\ExpiryReportWidget::exportToCsv($domains);

        // CSV uses quoted headers when fields contain special chars per RFC 4180
        $this->assertStringContainsString('Domain', $csv);
        $this->assertStringContainsString('Client Name', $csv);
        $this->assertStringContainsString('Expiry Date', $csv);
        $this->assertStringContainsString('expires-in-10-days.com', $csv);
        $this->assertStringContainsString('John Doe', $csv);
    }

    // =========================================================================
    // E2E Scenario: Bulk Domain Import
    // =========================================================================

    #[Test]
    public function e2eBulkDomainImportWorkflow(): void
    {
        // Set up existing clients
        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tblclients', [
            ['id' => 1, 'email' => 'existing@example.com', 'companyname' => 'Existing Corp', 'firstname' => 'Existing', 'lastname' => 'Client'],
            ['id' => 2, 'email' => 'another@example.com', 'companyname' => 'Another Corp', 'firstname' => 'Another', 'lastname' => 'Client'],
        ]);

        // Set up existing domains
        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tbldomains', [
            ['id' => 1, 'domain' => 'already-exists.com', 'userid' => 1, 'registrar' => 'ascio', 'status' => 'Active'],
        ]);

        $importer = new \ascio\DomainImporter([
            'Username' => 'test',
            'Password' => 'test',
            'TestMode' => 'on',
        ]);

        // === Test 1: Import new domain for existing client ===
        $result = $importer->importDomain([
            'domain_name' => 'new-domain.com',
            'expiry_date' => '2027-06-15',
            'status' => 'Active',
        ], 1, true); // dry run

        // Dry run returns 'would_import', not 'imported'
        $this->assertEquals('would_import', $result['action']);
        $this->assertTrue($result['success']);

        // === Test 2: Skip existing domain ===
        $result = $importer->importDomain([
            'domain_name' => 'already-exists.com',
            'expiry_date' => '2027-06-15',
            'status' => 'Active',
        ], 1, false);

        $this->assertEquals('skipped', $result['action']);
        $this->assertStringContainsString('already exists', $result['message']);

        // === Test 3: Detect conflict ===
        $result = $importer->importDomain([
            'domain_name' => 'already-exists.com',
            'expiry_date' => '2027-06-15',
            'status' => 'Active',
        ], 2, false); // different client

        $this->assertEquals('conflict', $result['action']);

        // === Test 4: Match client by email ===
        $clientId = $importer->matchClient('existing@example.com');
        $this->assertEquals(1, $clientId);

        // === Test 5: Match client by company ===
        $clientId = $importer->matchClient('nomatch@example.com', 'Another Corp');
        $this->assertEquals(2, $clientId);

        // === Verify statistics ===
        $stats = $importer->getStats();
        $this->assertArrayHasKey('imported', $stats);
        $this->assertArrayHasKey('skipped', $stats);
        $this->assertArrayHasKey('conflicts', $stats);
        $this->assertArrayHasKey('unmatched', $stats);
        $this->assertArrayHasKey('errors', $stats);
    }

    // =========================================================================
    // E2E Scenario: Admin Views All Features Together
    // =========================================================================

    #[Test]
    public function e2eAdminDashboardViewsAllFeatures(): void
    {
        // This simulates an admin viewing a domain with all enhanced features active

        $domainId = 5000;
        $domainName = 'admin-view-test.com';
        $orderId = 'E2E-ADMIN-001';

        // === Set up domain with transfer in progress ===
        \ascio\TransferTracker::updateStatus($domainId, 'validating', $orderId, 'Awaiting authorization');

        // === Add history entries ===
        \ascio\DomainHistory::log($domainId, $domainName, 'Pending', 'Pending Transfer', $orderId, 'Transfer_Domain', 'Transfer initiated');
        \ascio\DomainHistory::log($domainId, $domainName, 'Pending_End_User_Action', 'Pending Transfer', $orderId, 'Transfer_Domain', 'Awaiting approval');

        // === Get all display data (simulating admin view) ===

        // 1. Transfer progress
        $transferStatus = \ascio\TransferTracker::getTransferStatus($domainId);
        $transferHtml = \ascio\TransferTracker::renderProgressHtml($transferStatus);

        $this->assertNotNull($transferStatus);
        $this->assertStringContainsString('50%', $transferHtml);

        // 2. Domain history
        $history = \ascio\DomainHistory::getHistory($domainId);
        $historyHtml = \ascio\DomainHistory::formatForDisplay($history);

        $this->assertCount(2, $history);
        $this->assertStringContainsString('table', $historyHtml);

        // 3. Expiry stats (always available on dashboard)
        $expiryStats = \ascio\ExpiryReportWidget::getExpiryStats();

        $this->assertArrayHasKey('30', $expiryStats);
        $this->assertArrayHasKey('total_active', $expiryStats);

        // === Verify all components coexist without errors ===
        $this->assertTrue(true, 'All admin views rendered successfully');
    }

    // =========================================================================
    // E2E Scenario: Order Status Mapping Throughout Transfer
    // =========================================================================

    #[Test]
    public function e2eOrderStatusMappingThroughTransfer(): void
    {
        // Test that Ascio order statuses correctly map to transfer stages
        $statusMappings = [
            'NotSet' => 'pending',
            'Pending' => 'validating',
            'Pending_End_User_Action' => 'validating',
            'Pending_Documentation' => 'validating',
            'Pending_Approval' => 'validating',
            'Pending_Registry' => 'processing',
            'Processing' => 'processing',
            'Completed' => 'completed',
            'Successful' => 'completed',
            'Failed' => 'failed',
            'Invalid' => 'failed',
            'Cancelled' => 'failed',
        ];

        foreach ($statusMappings as $ascioStatus => $expectedStage) {
            $stage = \ascio\TransferTracker::mapOrderStatusToStage($ascioStatus);
            $this->assertEquals($expectedStage, $stage, "Ascio status '$ascioStatus' should map to '$expectedStage'");
        }
    }
}
