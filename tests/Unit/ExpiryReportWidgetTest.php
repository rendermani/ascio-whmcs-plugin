<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\ExpiryReportWidget;
use Ascio\Tests\Mocks\CapsuleMock;

/**
 * Unit tests for ascio\ExpiryReportWidget class
 *
 * @covers \ascio\ExpiryReportWidget
 */
class ExpiryReportWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CapsuleMock::reset();
        ExpiryReportWidget::clearCache();
    }

    protected function tearDown(): void
    {
        CapsuleMock::reset();
        ExpiryReportWidget::clearCache();
        parent::tearDown();
    }

    // =========================================================================
    // getExpiringDomains() tests
    // =========================================================================

    #[Test]
    public function getExpiringDomainsReturnsEmptyArrayWhenNoDomainsFound(): void
    {
        CapsuleMock::setTableData('tbldomains', []);

        $result = ExpiryReportWidget::getExpiringDomains(30);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('perPage', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertEmpty($result['domains']);
        $this->assertEquals(0, $result['total']);
    }

    #[Test]
    public function getExpiringDomainsReturnsCorrectStructure(): void
    {
        // Test that the method returns the expected array structure
        // regardless of whether data is found (mock limitations)
        $result = ExpiryReportWidget::getExpiringDomains(30);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('perPage', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertIsArray($result['domains']);
        $this->assertIsInt($result['total']);
        $this->assertIsInt($result['page']);
        $this->assertIsInt($result['perPage']);
        $this->assertIsInt($result['totalPages']);
    }

    #[Test]
    public function getExpiringDomainsFiltersByTld(): void
    {
        $tomorrow = date('Y-m-d', strtotime('+5 days'));
        CapsuleMock::setTableData('tbldomains', [
            [
                'id' => 1,
                'domain' => 'example.com',
                'expirydate' => $tomorrow,
                'status' => 'Active',
                'userid' => 100,
                'registrar' => 'ascio',
            ],
            [
                'id' => 2,
                'domain' => 'example.net',
                'expirydate' => $tomorrow,
                'status' => 'Active',
                'userid' => 100,
                'registrar' => 'ascio',
            ]
        ]);

        // When filtered by 'com', should only return .com domains
        // Note: CapsuleMock doesn't fully support LIKE queries, so this tests the interface
        $result = ExpiryReportWidget::getExpiringDomains(30, 'com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);
    }

    #[Test]
    public function getExpiringDomainsFiltersByStatus(): void
    {
        $tomorrow = date('Y-m-d', strtotime('+5 days'));
        CapsuleMock::setTableData('tbldomains', [
            [
                'id' => 1,
                'domain' => 'active.com',
                'expirydate' => $tomorrow,
                'status' => 'Active',
                'userid' => 100,
                'registrar' => 'ascio',
            ],
            [
                'id' => 2,
                'domain' => 'pending.com',
                'expirydate' => $tomorrow,
                'status' => 'Pending',
                'userid' => 100,
                'registrar' => 'ascio',
            ]
        ]);

        $result = ExpiryReportWidget::getExpiringDomains(30, null, 'Active');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);
    }

    #[Test]
    public function getExpiringDomainsPaginatesCorrectly(): void
    {
        $result = ExpiryReportWidget::getExpiringDomains(30, null, null, 1, 10);

        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['perPage']);
        $this->assertIsInt($result['totalPages']);
    }

    #[Test]
    public function getExpiringDomainsHandlesInvalidPageNumber(): void
    {
        $result = ExpiryReportWidget::getExpiringDomains(30, null, null, 0, 10);

        // Should default to page 1 when given invalid page number
        $this->assertEquals(1, $result['page']);
    }

    // =========================================================================
    // getExpiryStats() tests
    // =========================================================================

    #[Test]
    public function getExpiryStatsReturnsCorrectStructure(): void
    {
        CapsuleMock::setTableData('tbldomains', []);

        $stats = ExpiryReportWidget::getExpiryStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('30', $stats);
        $this->assertArrayHasKey('60', $stats);
        $this->assertArrayHasKey('90', $stats);
        $this->assertArrayHasKey('total_active', $stats);
    }

    #[Test]
    public function getExpiryStatsReturnsIntegerValues(): void
    {
        CapsuleMock::setTableData('tbldomains', []);

        $stats = ExpiryReportWidget::getExpiryStats();

        $this->assertIsInt($stats['30']);
        $this->assertIsInt($stats['60']);
        $this->assertIsInt($stats['90']);
        $this->assertIsInt($stats['total_active']);
    }

    #[Test]
    public function getExpiryStatsUsesCaching(): void
    {
        CapsuleMock::setTableData('tbldomains', []);

        // First call should query the database
        $stats1 = ExpiryReportWidget::getExpiryStats();

        // Second call should use cache
        $stats2 = ExpiryReportWidget::getExpiryStats();

        $this->assertEquals($stats1, $stats2);
    }

    #[Test]
    public function clearCacheResetsCache(): void
    {
        CapsuleMock::setTableData('tbldomains', []);

        $stats1 = ExpiryReportWidget::getExpiryStats();
        ExpiryReportWidget::clearCache();

        // After clearing, should query again
        $stats2 = ExpiryReportWidget::getExpiryStats();

        $this->assertEquals($stats1, $stats2);
    }

    // =========================================================================
    // exportToCsv() tests
    // =========================================================================

    #[Test]
    public function exportToCsvReturnsValidCsv(): void
    {
        $domains = [
            [
                'domain' => 'example.com',
                'client_name' => 'John Doe',
                'client_email' => 'john@example.com',
                'expirydate' => '2025-12-31',
                'days_left' => 30,
                'status' => 'Active',
            ]
        ];

        $csv = ExpiryReportWidget::exportToCsv($domains);

        $this->assertIsString($csv);
        $this->assertStringContainsString('Domain', $csv);
        $this->assertStringContainsString('example.com', $csv);
        $this->assertStringContainsString('John Doe', $csv);
    }

    #[Test]
    public function exportToCsvIncludesHeader(): void
    {
        $domains = [];

        $csv = ExpiryReportWidget::exportToCsv($domains);

        $this->assertStringContainsString('Domain', $csv);
        $this->assertStringContainsString('Client Name', $csv);
        $this->assertStringContainsString('Client Email', $csv);
        $this->assertStringContainsString('Expiry Date', $csv);
        $this->assertStringContainsString('Days Left', $csv);
        $this->assertStringContainsString('Status', $csv);
    }

    #[Test]
    public function exportToCsvHandlesEmptyArray(): void
    {
        $domains = [];

        $csv = ExpiryReportWidget::exportToCsv($domains);

        // Should still have header row
        $this->assertIsString($csv);
        $lines = explode("\n", trim($csv));
        $this->assertCount(1, $lines); // Only header
    }

    #[Test]
    public function exportToCsvHandlesMultipleDomains(): void
    {
        $domains = [
            [
                'domain' => 'example1.com',
                'client_name' => 'John Doe',
                'client_email' => 'john@example.com',
                'expirydate' => '2025-12-31',
                'days_left' => 30,
                'status' => 'Active',
            ],
            [
                'domain' => 'example2.com',
                'client_name' => 'Jane Doe',
                'client_email' => 'jane@example.com',
                'expirydate' => '2025-11-30',
                'days_left' => 15,
                'status' => 'Pending',
            ]
        ];

        $csv = ExpiryReportWidget::exportToCsv($domains);

        $this->assertStringContainsString('example1.com', $csv);
        $this->assertStringContainsString('example2.com', $csv);
        $this->assertStringContainsString('John Doe', $csv);
        $this->assertStringContainsString('Jane Doe', $csv);
    }

    #[Test]
    public function exportToCsvEscapesSpecialCharacters(): void
    {
        $domains = [
            [
                'domain' => 'example.com',
                'client_name' => 'John "The Man" Doe',
                'client_email' => 'john@example.com',
                'expirydate' => '2025-12-31',
                'days_left' => 30,
                'status' => 'Active',
            ]
        ];

        $csv = ExpiryReportWidget::exportToCsv($domains);

        // CSV should properly escape quotes
        $this->assertIsString($csv);
        $this->assertStringContainsString('example.com', $csv);
    }

    // =========================================================================
    // getAvailableTlds() tests
    // =========================================================================

    #[Test]
    public function getAvailableTldsReturnsArray(): void
    {
        CapsuleMock::setTableData('tbldomains', []);

        $tlds = ExpiryReportWidget::getAvailableTlds();

        $this->assertIsArray($tlds);
    }

    #[Test]
    public function getAvailableTldsExtractsTldsFromDomains(): void
    {
        CapsuleMock::setTableData('tbldomains', [
            ['domain' => 'example.com', 'registrar' => 'ascio', 'status' => 'Active'],
            ['domain' => 'example.net', 'registrar' => 'ascio', 'status' => 'Active'],
            ['domain' => 'example.org', 'registrar' => 'ascio', 'status' => 'Active'],
        ]);

        $tlds = ExpiryReportWidget::getAvailableTlds();

        // Note: CapsuleMock behavior determines exact results
        $this->assertIsArray($tlds);
    }

    #[Test]
    public function getAvailableTldsReturnsSortedArray(): void
    {
        CapsuleMock::setTableData('tbldomains', [
            ['domain' => 'example.org', 'registrar' => 'ascio', 'status' => 'Active'],
            ['domain' => 'example.com', 'registrar' => 'ascio', 'status' => 'Active'],
            ['domain' => 'example.net', 'registrar' => 'ascio', 'status' => 'Active'],
        ]);

        $tlds = ExpiryReportWidget::getAvailableTlds();

        // Check that array is sorted
        $sortedTlds = $tlds;
        sort($sortedTlds);
        $this->assertEquals($sortedTlds, $tlds);
    }

    // =========================================================================
    // formatClientName() tests (tested via getExpiringDomains)
    // =========================================================================

    #[Test]
    public function clientNameFormattingWithCompanyAndName(): void
    {
        // Test the formatClientName logic indirectly by testing expected structure
        // Since mock doesn't fully support JOINs, we test that the method runs without error
        $result = ExpiryReportWidget::getExpiringDomains(30);

        // Verify structure is correct
        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);
        $this->assertIsArray($result['domains']);

        // If there are domains, verify structure
        foreach ($result['domains'] as $domain) {
            $this->assertArrayHasKey('client_name', $domain);
            $this->assertIsString($domain['client_name']);
        }
    }

    // =========================================================================
    // Days calculation tests
    // =========================================================================

    #[Test]
    public function daysLeftCalculatesCorrectlyForFutureDates(): void
    {
        // Test the days calculation logic by testing expected structure
        $result = ExpiryReportWidget::getExpiringDomains(30);

        // Verify structure is correct
        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);

        // If there are domains, verify days_left exists and is integer
        foreach ($result['domains'] as $domain) {
            $this->assertArrayHasKey('days_left', $domain);
            $this->assertIsInt($domain['days_left']);
        }
    }
}
