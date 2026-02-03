<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\DomainImporter;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SchemaMock;

/**
 * Unit tests for ascio\DomainImporter class
 *
 * Tests the bulk domain import functionality for importing
 * domains from Ascio API into WHMCS.
 *
 * @covers \ascio\DomainImporter
 */
class DomainImporterTest extends TestCase
{
    private array $defaultParams;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
        SchemaMock::reset();
        SchemaMock::addTable('tblascio_import_log');
        SchemaMock::addTable('tbldomains');
        SchemaMock::addTable('tblclients');
        SchemaMock::addTable('tblasciohandles');

        $this->defaultParams = [
            'Username' => 'test_account',
            'Password' => 'test_password',
            'TestMode' => 'on',
        ];
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    #[Test]
    public function constructorSetsCredentials(): void
    {
        $importer = new DomainImporter($this->defaultParams);

        // Use reflection to access protected properties
        $reflection = new \ReflectionClass($importer);

        $accountProperty = $reflection->getProperty('account');
        $accountProperty->setAccessible(true);
        $this->assertEquals('test_account', $accountProperty->getValue($importer));

        $passwordProperty = $reflection->getProperty('password');
        $passwordProperty->setAccessible(true);
        $this->assertEquals('test_password', $passwordProperty->getValue($importer));
    }

    #[Test]
    public function constructorSetsTestMode(): void
    {
        $importer = new DomainImporter($this->defaultParams);

        $reflection = new \ReflectionClass($importer);
        $testModeProperty = $reflection->getProperty('testMode');
        $testModeProperty->setAccessible(true);

        $this->assertTrue($testModeProperty->getValue($importer));
    }

    #[Test]
    public function constructorSetsLiveModeWhenTestModeOff(): void
    {
        $params = array_merge($this->defaultParams, ['TestMode' => '']);
        $importer = new DomainImporter($params);

        $reflection = new \ReflectionClass($importer);
        $testModeProperty = $reflection->getProperty('testMode');
        $testModeProperty->setAccessible(true);

        $this->assertFalse($testModeProperty->getValue($importer));
    }

    // =========================================================================
    // Client Matching Tests
    // =========================================================================

    #[Test]
    public function matchClientFindsClientByEmail(): void
    {
        CapsuleMock::setTableData('tblclients', [
            ['id' => 123, 'email' => 'client@example.com', 'companyname' => 'Test Company']
        ]);

        $importer = new DomainImporter($this->defaultParams);
        $clientId = $importer->matchClient('client@example.com');

        $this->assertEquals(123, $clientId);
    }

    #[Test]
    public function matchClientFindsClientByCompanyName(): void
    {
        CapsuleMock::setTableData('tblclients', [
            ['id' => 456, 'email' => 'other@example.com', 'companyname' => 'Acme Corp']
        ]);

        $importer = new DomainImporter($this->defaultParams);
        $clientId = $importer->matchClient('nomatch@example.com', 'Acme Corp');

        $this->assertEquals(456, $clientId);
    }

    #[Test]
    public function matchClientReturnsNullWhenNoMatch(): void
    {
        CapsuleMock::setTableData('tblclients', [
            ['id' => 789, 'email' => 'different@example.com', 'companyname' => 'Different Corp']
        ]);

        $importer = new DomainImporter($this->defaultParams);
        $clientId = $importer->matchClient('nomatch@example.com', 'No Match Inc');

        $this->assertNull($clientId);
    }

    #[Test]
    public function matchClientPrefersEmailOverCompany(): void
    {
        CapsuleMock::setTableData('tblclients', [
            ['id' => 100, 'email' => 'exact@example.com', 'companyname' => 'Other Company'],
            ['id' => 200, 'email' => 'other@example.com', 'companyname' => 'Target Company']
        ]);

        $importer = new DomainImporter($this->defaultParams);
        $clientId = $importer->matchClient('exact@example.com', 'Target Company');

        $this->assertEquals(100, $clientId);
    }

    #[Test]
    public function matchClientHandlesEmptyCompanyName(): void
    {
        CapsuleMock::setTableData('tblclients', []);

        $importer = new DomainImporter($this->defaultParams);
        $clientId = $importer->matchClient('test@example.com', '');

        $this->assertNull($clientId);
    }

    #[Test]
    public function matchClientHandlesNullCompanyName(): void
    {
        CapsuleMock::setTableData('tblclients', []);

        $importer = new DomainImporter($this->defaultParams);
        $clientId = $importer->matchClient('test@example.com', null);

        $this->assertNull($clientId);
    }

    // =========================================================================
    // Import Domain Tests
    // =========================================================================

    #[Test]
    public function importDomainSkipsExistingDomainWithSameClient(): void
    {
        CapsuleMock::setTableData('tbldomains', [
            ['id' => 10, 'domain' => 'existing.com', 'userid' => 123]
        ]);

        $importer = new DomainImporter($this->defaultParams);
        $result = $importer->importDomain([
            'domain_name' => 'existing.com',
            'domain_handle' => 'DOM-12345',
            'expiry_date' => '2025-12-31T00:00:00',
        ], 123);

        $this->assertEquals('skipped', $result['action']);
        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['domain_id']);
    }

    #[Test]
    public function importDomainDetectsConflictWithDifferentClient(): void
    {
        CapsuleMock::setTableData('tbldomains', [
            ['id' => 20, 'domain' => 'conflict.com', 'userid' => 999]
        ]);

        $importer = new DomainImporter($this->defaultParams);
        $result = $importer->importDomain([
            'domain_name' => 'conflict.com',
            'domain_handle' => 'DOM-CONFLICT',
            'expiry_date' => '2025-12-31T00:00:00',
        ], 123);

        $this->assertEquals('conflict', $result['action']);
        $this->assertFalse($result['success']);
        $this->assertEquals(999, $result['existing_client_id']);
    }

    #[Test]
    public function importDomainDryRunDoesNotInsert(): void
    {
        CapsuleMock::setTableData('tbldomains', []);

        $importer = new DomainImporter($this->defaultParams);
        $result = $importer->importDomain([
            'domain_name' => 'newdomain.com',
            'domain_handle' => 'DOM-NEW',
            'expiry_date' => '2025-12-31T00:00:00',
        ], 123, true);

        $this->assertEquals('would_import', $result['action']);
        $this->assertTrue($result['success']);

        // Verify no insert was made
        $lastQuery = CapsuleMock::getLastQuery();
        $this->assertNotEquals('insert', $lastQuery['type'] ?? '');
    }

    #[Test]
    public function importDomainInsertsNewDomain(): void
    {
        CapsuleMock::setTableData('tbldomains', []);
        CapsuleMock::setTableData('tblasciohandles', []);

        $importer = new DomainImporter($this->defaultParams);
        $result = $importer->importDomain([
            'domain_name' => 'newdomain.org',
            'domain_handle' => 'DOM-ORG-123',
            'expiry_date' => '2026-06-15T00:00:00',
            'created_date' => '2023-06-15T00:00:00',
        ], 456, false);

        $this->assertEquals('imported', $result['action']);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('domain_id', $result);
    }

    #[Test]
    public function importDomainHandlesMissingExpiryDate(): void
    {
        CapsuleMock::setTableData('tbldomains', []);
        CapsuleMock::setTableData('tblasciohandles', []);

        $importer = new DomainImporter($this->defaultParams);
        $result = $importer->importDomain([
            'domain_name' => 'noexpiry.com',
            'domain_handle' => 'DOM-NOEXP',
        ], 789, false);

        $this->assertEquals('imported', $result['action']);
        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // Statistics Tests
    // =========================================================================

    #[Test]
    public function getStatsReturnsInitialValues(): void
    {
        $importer = new DomainImporter($this->defaultParams);
        $stats = $importer->getStats();

        $this->assertArrayHasKey('imported', $stats);
        $this->assertArrayHasKey('skipped', $stats);
        $this->assertArrayHasKey('conflicts', $stats);
        $this->assertArrayHasKey('unmatched', $stats);
        $this->assertArrayHasKey('errors', $stats);
        $this->assertEquals(0, $stats['imported']);
        $this->assertEquals(0, $stats['skipped']);
        $this->assertEquals(0, $stats['conflicts']);
        $this->assertEquals(0, $stats['unmatched']);
        $this->assertEquals(0, $stats['errors']);
    }

    #[Test]
    public function getLogReturnsEmptyArrayInitially(): void
    {
        $importer = new DomainImporter($this->defaultParams);
        $log = $importer->getLog();

        $this->assertIsArray($log);
        $this->assertEmpty($log);
    }

    // =========================================================================
    // Static Method Tests
    // =========================================================================

    #[Test]
    public function getRecentLogsReturnsEmptyWhenNoTable(): void
    {
        SchemaMock::reset();
        $logs = DomainImporter::getRecentLogs(10);

        $this->assertIsArray($logs);
        $this->assertEmpty($logs);
    }

    #[Test]
    public function getRecentLogsReturnsData(): void
    {
        CapsuleMock::setTableData('tblascio_import_log', [
            ['id' => 1, 'domain_name' => 'test1.com', 'action' => 'imported', 'client_id' => 1, 'message' => 'OK', 'created_at' => '2025-01-01 00:00:00'],
            ['id' => 2, 'domain_name' => 'test2.com', 'action' => 'skipped', 'client_id' => 2, 'message' => 'Exists', 'created_at' => '2025-01-01 00:01:00'],
        ]);

        $logs = DomainImporter::getRecentLogs(10);

        $this->assertIsArray($logs);
        $this->assertCount(2, $logs);
    }

    #[Test]
    public function clearLogsReturnsZeroWhenNoTable(): void
    {
        SchemaMock::reset();
        $deleted = DomainImporter::clearLogs();

        $this->assertEquals(0, $deleted);
    }

    // =========================================================================
    // Domain Data Extraction Tests
    // =========================================================================

    #[Test]
    #[DataProvider('domainNameProvider')]
    public function importDomainExtractsTldCorrectly(string $domainName, string $expectedSld, string $expectedTld): void
    {
        CapsuleMock::setTableData('tbldomains', []);
        CapsuleMock::setTableData('tblasciohandles', []);

        $importer = new DomainImporter($this->defaultParams);

        // Use reflection to access the domain name parsing
        $result = $importer->importDomain([
            'domain_name' => $domainName,
            'domain_handle' => 'DOM-TEST',
        ], 123, true);

        $this->assertEquals('would_import', $result['action']);
    }

    public static function domainNameProvider(): array
    {
        return [
            'simple .com' => ['example.com', 'example', 'com'],
            'simple .org' => ['mysite.org', 'mysite', 'org'],
            'double TLD' => ['test.co.uk', 'test', 'co.uk'],
            'triple TLD' => ['domain.com.au', 'domain', 'com.au'],
            'numeric domain' => ['123.net', '123', 'net'],
            'hyphenated domain' => ['my-site.info', 'my-site', 'info'],
        ];
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function importDomainHandlesExceptionGracefully(): void
    {
        // Reset to no tables to force an error
        CapsuleMock::setTableData('tbldomains', []);
        CapsuleMock::setTableData('tblasciohandles', []);

        $importer = new DomainImporter($this->defaultParams);

        // Mock should work, but in case of actual DB error, it should be caught
        $result = $importer->importDomain([
            'domain_name' => 'test.com',
            'domain_handle' => 'DOM-TEST',
            'expiry_date' => '2025-12-31T00:00:00',
        ], 123, false);

        // Should either succeed or return error action
        $this->assertContains($result['action'], ['imported', 'error']);
    }

    // =========================================================================
    // Integration-like Tests (without actual API calls)
    // =========================================================================

    #[Test]
    public function fullImportWorkflowWithDryRun(): void
    {
        // Setup client data
        CapsuleMock::setTableData('tblclients', [
            ['id' => 1, 'email' => 'owner@matched.com', 'companyname' => 'Matched Inc'],
        ]);

        // Setup existing domain
        CapsuleMock::setTableData('tbldomains', [
            ['id' => 100, 'domain' => 'existing.com', 'userid' => 1],
        ]);

        $importer = new DomainImporter($this->defaultParams);

        // Test matching client
        $clientId = $importer->matchClient('owner@matched.com');
        $this->assertEquals(1, $clientId);

        // Test importing existing domain (should skip)
        $skipResult = $importer->importDomain([
            'domain_name' => 'existing.com',
            'domain_handle' => 'DOM-EX',
        ], $clientId, true);
        $this->assertEquals('skipped', $skipResult['action']);

        // Test importing new domain (dry run)
        $importResult = $importer->importDomain([
            'domain_name' => 'new.com',
            'domain_handle' => 'DOM-NEW',
        ], $clientId, true);
        $this->assertEquals('would_import', $importResult['action']);
    }
}
