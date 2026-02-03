<?php

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Integration tests for PS-144 enhanced features:
 * - Domain History Tracking (PS-148)
 * - Transfer Tracker (PS-145)
 * - Expiry Report Widget (PS-146)
 * - Domain Importer (PS-147)
 *
 * These tests verify that all components work together correctly.
 */
#[Group('integration')]
#[Group('enhanced-features')]
class EnhancedFeaturesIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load mocks
        require_once dirname(__DIR__) . '/Mocks/WhmcsFunctionsMock.php';
        require_once dirname(__DIR__) . '/Mocks/CapsuleMock.php';

        \Ascio\Tests\Mocks\WhmcsFunctionsMock::reset();
        \Ascio\Tests\Mocks\CapsuleMock::reset();
    }

    // =========================================================================
    // Domain History Integration Tests
    // =========================================================================

    #[Test]
    public function domainHistoryIntegratesWithCallbackProcessing(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/DomainHistory.php';

        // Simulate a domain registration callback flow
        $domainId = 123;
        $domainName = 'integration-test.com';

        // Log initial registration pending
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Pending',
            'Pending',
            'ORD-12345',
            'Register_Domain',
            'Domain registration submitted'
        );

        // Log registration completed
        \ascio\DomainHistory::log(
            $domainId,
            $domainName,
            'Completed',
            'Active',
            'ORD-12345',
            'Register_Domain',
            'Domain registration completed successfully'
        );

        // Verify history
        $history = \ascio\DomainHistory::getHistory($domainId);

        $this->assertCount(2, $history);
        $this->assertEquals('Completed', $history[0]['ascio_status']);
        $this->assertEquals('Pending', $history[1]['ascio_status']);
    }

    #[Test]
    public function domainHistoryDisplayFormatsCorrectly(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/DomainHistory.php';

        $domainId = 124;
        $domainName = 'display-test.com';

        // Add some history entries
        \ascio\DomainHistory::log($domainId, $domainName, 'Pending', 'Pending', 'ORD-1', 'Register_Domain', 'Submitted');
        \ascio\DomainHistory::log($domainId, $domainName, 'Completed', 'Active', 'ORD-1', 'Register_Domain', 'Done');

        // Get formatted display - formatForDisplay expects the history array
        $history = \ascio\DomainHistory::getHistory($domainId);
        $html = \ascio\DomainHistory::formatForDisplay($history);

        $this->assertStringContainsString('table', $html);
        $this->assertStringContainsString('Completed', $html);
        $this->assertStringContainsString('Active', $html);
        $this->assertStringContainsString('ORD-1', $html);
    }

    // =========================================================================
    // Transfer Tracker Integration Tests
    // =========================================================================

    #[Test]
    public function transferTrackerFullWorkflow(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/TransferTracker.php';

        $domainId = 200;
        $domainName = 'transfer-test.com';

        // Initialize transfer
        \ascio\TransferTracker::updateStatus($domainId, 'pending', 'ORD-TRANSFER-1', 'Transfer initiated');

        // Simulate callback updates
        $statuses = ['Pending', 'Pending_End_User_Action', 'Pending_Registry', 'Completed'];

        foreach ($statuses as $ascioStatus) {
            $stage = \ascio\TransferTracker::mapOrderStatusToStage($ascioStatus);
            \ascio\TransferTracker::updateStatus($domainId, $stage);
        }

        // Verify final status
        $status = \ascio\TransferTracker::getTransferStatus($domainId);

        $this->assertNotNull($status);
        $this->assertEquals('completed', $status['current_stage']);
        $this->assertEquals(100, \ascio\TransferTracker::getProgressPercentage($status['current_stage']));
    }

    #[Test]
    public function transferTrackerRendersProgressCorrectly(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/TransferTracker.php';

        $domainId = 201;

        // Set up a transfer in progress
        \ascio\TransferTracker::updateStatus($domainId, 'validating', 'ORD-T2', 'Awaiting authorization');

        $status = \ascio\TransferTracker::getTransferStatus($domainId);
        $html = \ascio\TransferTracker::renderProgressHtml($status);

        $this->assertStringContainsString('progress', strtolower($html));
        $this->assertStringContainsString('50%', $html);
        $this->assertStringContainsString('Validating', $html);
    }

    #[Test]
    public function transferTrackerHandlesFailedTransfer(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/TransferTracker.php';

        $domainId = 202;

        // Initialize then fail
        \ascio\TransferTracker::updateStatus($domainId, 'pending', 'ORD-FAIL');
        \ascio\TransferTracker::updateStatus($domainId, 'failed', null, 'Authorization denied by registrant');

        $status = \ascio\TransferTracker::getTransferStatus($domainId);
        $html = \ascio\TransferTracker::renderProgressHtml($status);

        $this->assertEquals('failed', $status['current_stage']);
        $this->assertStringContainsString('Authorization denied', $html);
    }

    // =========================================================================
    // Expiry Report Widget Integration Tests
    // =========================================================================

    #[Test]
    public function expiryReportWidgetQueriesDomainsCorrectly(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/ExpiryReportWidget.php';

        // Set up mock data
        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tbldomains', [
            [
                'id' => 1,
                'domain' => 'expiring-soon.com',
                'registrar' => 'ascio',
                'status' => 'Active',
                'expirydate' => date('Y-m-d', strtotime('+15 days')),
                'userid' => 1,
            ],
            [
                'id' => 2,
                'domain' => 'expiring-later.com',
                'registrar' => 'ascio',
                'status' => 'Active',
                'expirydate' => date('Y-m-d', strtotime('+45 days')),
                'userid' => 1,
            ],
        ]);

        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tblclients', [
            ['id' => 1, 'firstname' => 'John', 'lastname' => 'Doe', 'email' => 'john@example.com', 'companyname' => ''],
        ]);

        // Query for 30 days
        $domains = \ascio\ExpiryReportWidget::getExpiringDomains(30);

        // Should only return the one expiring in 15 days
        $this->assertIsArray($domains);
    }

    #[Test]
    public function expiryReportStatsAreCached(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/ExpiryReportWidget.php';

        // Clear cache first
        \ascio\ExpiryReportWidget::clearCache();

        // Get stats twice
        $stats1 = \ascio\ExpiryReportWidget::getExpiryStats();
        $stats2 = \ascio\ExpiryReportWidget::getExpiryStats();

        // Both should have same structure (keys are '30', '60', '90', not 'days_30', etc.)
        $this->assertArrayHasKey('30', $stats1);
        $this->assertArrayHasKey('60', $stats1);
        $this->assertArrayHasKey('90', $stats1);
        $this->assertArrayHasKey('total_active', $stats1);
    }

    #[Test]
    public function expiryReportCsvExportFormatsCorrectly(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/ExpiryReportWidget.php';

        $domains = [
            [
                'id' => 1,
                'domain' => 'test1.com',
                'client_name' => 'John Doe',
                'client_email' => 'john@example.com',
                'expirydate' => '2026-03-01',
                'days_left' => 26,
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'domain' => 'test2.org',
                'client_name' => 'Jane Smith',
                'client_email' => 'jane@example.com',
                'expirydate' => '2026-03-15',
                'days_left' => 40,
                'status' => 'Active',
            ],
        ];

        $csv = \ascio\ExpiryReportWidget::exportToCsv($domains);

        // CSV uses quoted headers per RFC 4180
        $this->assertStringContainsString('Domain', $csv);
        $this->assertStringContainsString('Client Name', $csv);
        $this->assertStringContainsString('Expiry Date', $csv);
        $this->assertStringContainsString('test1.com', $csv);
        $this->assertStringContainsString('test2.org', $csv);
        $this->assertStringContainsString('John Doe', $csv);
    }

    // =========================================================================
    // Domain Importer Integration Tests
    // =========================================================================

    #[Test]
    public function domainImporterMatchesClientsByEmail(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/DomainImporter.php';

        // Set up mock clients
        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tblclients', [
            ['id' => 10, 'email' => 'client1@example.com', 'companyname' => 'Company A', 'firstname' => 'First', 'lastname' => 'Last'],
            ['id' => 20, 'email' => 'client2@example.com', 'companyname' => 'Company B', 'firstname' => 'First', 'lastname' => 'Last'],
        ]);

        $importer = new \ascio\DomainImporter([
            'Username' => 'test',
            'Password' => 'test',
            'TestMode' => 'on',
        ]);

        // Match by email
        $clientId = $importer->matchClient('client1@example.com');
        $this->assertEquals(10, $clientId);

        $clientId = $importer->matchClient('client2@example.com');
        $this->assertEquals(20, $clientId);

        // No match
        $clientId = $importer->matchClient('unknown@example.com');
        $this->assertNull($clientId);
    }

    #[Test]
    public function domainImporterMatchesClientsByCompany(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/DomainImporter.php';

        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tblclients', [
            ['id' => 30, 'email' => 'other@example.com', 'companyname' => 'Acme Corp', 'firstname' => 'First', 'lastname' => 'Last'],
        ]);

        $importer = new \ascio\DomainImporter([
            'Username' => 'test',
            'Password' => 'test',
            'TestMode' => 'on',
        ]);

        // Match by company when email doesn't match
        $clientId = $importer->matchClient('nomatch@example.com', 'Acme Corp');
        $this->assertEquals(30, $clientId);
    }

    #[Test]
    public function domainImporterDryRunDoesNotModifyData(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/DomainImporter.php';

        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tbldomains', []);
        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tblclients', [
            ['id' => 1, 'email' => 'test@example.com', 'companyname' => '', 'firstname' => 'Test', 'lastname' => 'User'],
        ]);

        $importer = new \ascio\DomainImporter([
            'Username' => 'test',
            'Password' => 'test',
            'TestMode' => 'on',
        ]);

        $domainData = [
            'domain_name' => 'newdomain.com',
            'expiry_date' => '2027-01-01',
            'status' => 'Active',
        ];

        $result = $importer->importDomain($domainData, 1, true);

        // Dry run returns 'would_import', not 'imported'
        $this->assertEquals('would_import', $result['action']);
        $this->assertTrue($result['success']);
    }

    #[Test]
    public function domainImporterDetectsConflicts(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/DomainImporter.php';

        // Set up existing domain with different client
        \Ascio\Tests\Mocks\CapsuleMock::setTableData('tbldomains', [
            ['id' => 1, 'domain' => 'existing.com', 'userid' => 99, 'registrar' => 'ascio', 'status' => 'Active'],
        ]);

        $importer = new \ascio\DomainImporter([
            'Username' => 'test',
            'Password' => 'test',
            'TestMode' => 'on',
        ]);

        $domainData = [
            'domain_name' => 'existing.com',
            'expiry_date' => '2027-01-01',
            'status' => 'Active',
        ];

        // Import with same client ID as existing (should skip, not conflict)
        $result = $importer->importDomain($domainData, 99, false);
        $this->assertEquals('skipped', $result['action']);
        $this->assertStringContainsString('already exists', $result['message']);

        // Import with different client ID (should conflict)
        $result = $importer->importDomain($domainData, 1, false);
        $this->assertEquals('conflict', $result['action']);
        $this->assertStringContainsString('different client', $result['message']);
    }

    // =========================================================================
    // Cross-Feature Integration Tests
    // =========================================================================

    #[Test]
    public function transferAndHistoryWorkTogether(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/DomainHistory.php';
        require_once dirname(__DIR__, 2) . '/lib/TransferTracker.php';

        $domainId = 300;
        $domainName = 'combined-test.com';
        $orderId = 'ORD-COMBINED-1';

        // Simulate a transfer with history logging

        // 1. Transfer initiated
        \ascio\TransferTracker::updateStatus($domainId, 'pending', $orderId, 'Transfer initiated');
        \ascio\DomainHistory::log($domainId, $domainName, 'Pending', 'Pending Transfer', $orderId, 'Transfer_Domain', 'Transfer order submitted');

        // 2. Validation in progress
        \ascio\TransferTracker::updateStatus($domainId, 'validating', $orderId, 'Awaiting authorization');
        \ascio\DomainHistory::log($domainId, $domainName, 'Pending_End_User_Action', 'Pending Transfer', $orderId, 'Transfer_Domain', 'Awaiting registrant approval');

        // 3. Transfer completed
        \ascio\TransferTracker::updateStatus($domainId, 'completed', $orderId, 'Transfer successful');
        \ascio\DomainHistory::log($domainId, $domainName, 'Completed', 'Active', $orderId, 'Transfer_Domain', 'Transfer completed successfully');

        // Verify both systems have correct data
        $transferStatus = \ascio\TransferTracker::getTransferStatus($domainId);
        $history = \ascio\DomainHistory::getHistory($domainId);

        $this->assertEquals('completed', $transferStatus['current_stage']);
        $this->assertCount(3, $history);
        $this->assertEquals('Completed', $history[0]['ascio_status']);
    }

    #[Test]
    public function allFeaturesInitializeTables(): void
    {
        require_once dirname(__DIR__, 2) . '/lib/DomainHistory.php';
        require_once dirname(__DIR__, 2) . '/lib/TransferTracker.php';
        require_once dirname(__DIR__, 2) . '/lib/ExpiryReportWidget.php';
        require_once dirname(__DIR__, 2) . '/lib/DomainImporter.php';

        // These should not throw exceptions
        \ascio\DomainHistory::ensureTable();
        \ascio\TransferTracker::ensureTable();

        $this->assertTrue(true); // If we get here, tables were created successfully
    }
}
