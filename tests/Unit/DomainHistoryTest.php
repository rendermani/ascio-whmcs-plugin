<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ascio\DomainHistory;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SchemaMock;

/**
 * Unit tests for ascio\DomainHistory class (PS-148)
 *
 * @covers \ascio\DomainHistory
 */
class DomainHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CapsuleMock::reset();
        SchemaMock::reset();
    }

    // =========================================================================
    // log() tests
    // =========================================================================

    #[Test]
    public function logCreatesHistoryEntry(): void
    {
        $domainId = 123;
        $domainName = 'example.com';
        $ascioStatus = 'Completed';
        $whmcsStatus = 'Active';
        $orderId = 'O-12345';
        $orderType = 'Register_Domain';
        $message = 'Domain registered successfully';

        $result = DomainHistory::log(
            $domainId,
            $domainName,
            $ascioStatus,
            $whmcsStatus,
            $orderId,
            $orderType,
            $message
        );

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);

        // Verify the data was stored
        $lastQuery = CapsuleMock::getLastQuery();
        $this->assertEquals('insertGetId', $lastQuery['type']);
        $this->assertEquals(DomainHistory::TABLE_NAME, $lastQuery['table']);
        $this->assertEquals($domainId, $lastQuery['data']['domain_id']);
        $this->assertEquals($domainName, $lastQuery['data']['domain_name']);
        $this->assertEquals($ascioStatus, $lastQuery['data']['ascio_status']);
    }

    #[Test]
    public function logHandlesNullOrderIdAndType(): void
    {
        $result = DomainHistory::log(
            456,
            'test.org',
            'Pending',
            'Pending',
            null,
            null,
            'Waiting for confirmation'
        );

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);

        $lastQuery = CapsuleMock::getLastQuery();
        $this->assertNull($lastQuery['data']['order_id']);
        $this->assertNull($lastQuery['data']['order_type']);
    }

    #[Test]
    public function logRecordsTimestamp(): void
    {
        $beforeTime = date('Y-m-d H:i:s');

        $result = DomainHistory::log(
            789,
            'timestamp-test.com',
            'Completed',
            'Active',
            'O-999',
            'Transfer_Domain',
            'Transfer complete'
        );

        $afterTime = date('Y-m-d H:i:s');

        $lastQuery = CapsuleMock::getLastQuery();
        $createdAt = $lastQuery['data']['created_at'];

        $this->assertGreaterThanOrEqual($beforeTime, $createdAt);
        $this->assertLessThanOrEqual($afterTime, $createdAt);
    }

    // =========================================================================
    // getHistory() tests
    // =========================================================================

    #[Test]
    public function getHistoryReturnsEmptyArrayForNewDomain(): void
    {
        $history = DomainHistory::getHistory(999);

        $this->assertIsArray($history);
        $this->assertEmpty($history);
    }

    #[Test]
    public function getHistoryReturnsRecordsForDomain(): void
    {
        // Insert some test data
        CapsuleMock::setTableData(DomainHistory::TABLE_NAME, [
            [
                'id' => 1,
                'domain_id' => 100,
                'domain_name' => 'test.com',
                'ascio_status' => 'Pending',
                'whmcs_status' => 'Pending',
                'order_id' => 'O-1',
                'order_type' => 'Register',
                'message' => 'First entry',
                'created_at' => '2024-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'domain_id' => 100,
                'domain_name' => 'test.com',
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => 'O-1',
                'order_type' => 'Register',
                'message' => 'Second entry',
                'created_at' => '2024-01-01 11:00:00',
            ],
            [
                'id' => 3,
                'domain_id' => 200, // Different domain
                'domain_name' => 'other.com',
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => 'O-2',
                'order_type' => 'Register',
                'message' => 'Other domain',
                'created_at' => '2024-01-01 12:00:00',
            ],
        ]);

        $history = DomainHistory::getHistory(100);

        $this->assertIsArray($history);
        $this->assertCount(2, $history);

        // Results should be ordered by created_at desc
        $this->assertEquals('2024-01-01 11:00:00', $history[0]['created_at']);
        $this->assertEquals('Completed', $history[0]['ascio_status']);
    }

    #[Test]
    public function getHistoryRespectsLimit(): void
    {
        // Insert more than limit
        $data = [];
        for ($i = 1; $i <= 30; $i++) {
            $data[] = [
                'id' => $i,
                'domain_id' => 100,
                'domain_name' => 'test.com',
                'ascio_status' => 'Status_' . $i,
                'whmcs_status' => 'Active',
                'order_id' => 'O-' . $i,
                'order_type' => 'Update',
                'message' => 'Entry ' . $i,
                'created_at' => sprintf('2024-01-%02d 10:00:00', $i),
            ];
        }
        CapsuleMock::setTableData(DomainHistory::TABLE_NAME, $data);

        $history = DomainHistory::getHistory(100, 10);

        $this->assertCount(10, $history);
    }

    // =========================================================================
    // getLatest() tests
    // =========================================================================

    #[Test]
    public function getLatestReturnsNullForNoHistory(): void
    {
        $latest = DomainHistory::getLatest(999);

        $this->assertNull($latest);
    }

    #[Test]
    public function getLatestReturnsMostRecentEntry(): void
    {
        CapsuleMock::setTableData(DomainHistory::TABLE_NAME, [
            [
                'id' => 1,
                'domain_id' => 100,
                'domain_name' => 'test.com',
                'ascio_status' => 'Pending',
                'whmcs_status' => 'Pending',
                'order_id' => 'O-1',
                'order_type' => 'Register',
                'message' => 'First',
                'created_at' => '2024-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'domain_id' => 100,
                'domain_name' => 'test.com',
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => 'O-1',
                'order_type' => 'Register',
                'message' => 'Latest',
                'created_at' => '2024-01-02 10:00:00',
            ],
        ]);

        $latest = DomainHistory::getLatest(100);

        $this->assertIsArray($latest);
        $this->assertEquals('Completed', $latest['ascio_status']);
        $this->assertEquals('Latest', $latest['message']);
    }

    // =========================================================================
    // getHistoryByName() tests
    // =========================================================================

    #[Test]
    public function getHistoryByNameReturnsMatchingRecords(): void
    {
        CapsuleMock::setTableData(DomainHistory::TABLE_NAME, [
            [
                'id' => 1,
                'domain_id' => 100,
                'domain_name' => 'example.com',
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => 'O-1',
                'order_type' => 'Register',
                'message' => 'Match 1',
                'created_at' => '2024-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'domain_id' => 200,
                'domain_name' => 'other.com',
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => 'O-2',
                'order_type' => 'Register',
                'message' => 'No match',
                'created_at' => '2024-01-01 11:00:00',
            ],
            [
                'id' => 3,
                'domain_id' => 300,
                'domain_name' => 'example.com',
                'ascio_status' => 'Pending',
                'whmcs_status' => 'Pending',
                'order_id' => 'O-3',
                'order_type' => 'Transfer',
                'message' => 'Match 2',
                'created_at' => '2024-01-02 10:00:00',
            ],
        ]);

        $history = DomainHistory::getHistoryByName('example.com');

        $this->assertCount(2, $history);
    }

    // =========================================================================
    // deleteHistory() tests
    // =========================================================================

    #[Test]
    public function deleteHistoryRemovesAllEntriesForDomain(): void
    {
        CapsuleMock::setTableData(DomainHistory::TABLE_NAME, [
            [
                'id' => 1,
                'domain_id' => 100,
                'domain_name' => 'test.com',
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => 'O-1',
                'order_type' => 'Register',
                'message' => 'Entry 1',
                'created_at' => '2024-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'domain_id' => 100,
                'domain_name' => 'test.com',
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => 'O-2',
                'order_type' => 'Renew',
                'message' => 'Entry 2',
                'created_at' => '2024-01-02 10:00:00',
            ],
        ]);

        $deleted = DomainHistory::deleteHistory(100);

        $this->assertEquals(2, $deleted);
    }

    #[Test]
    public function deleteHistoryReturnsZeroForNonexistentDomain(): void
    {
        $deleted = DomainHistory::deleteHistory(999);

        $this->assertEquals(0, $deleted);
    }

    // =========================================================================
    // formatForDisplay() tests
    // =========================================================================

    #[Test]
    public function formatForDisplayReturnsMessageForEmptyHistory(): void
    {
        $html = DomainHistory::formatForDisplay([]);

        $this->assertStringContainsString('No status history available', $html);
        $this->assertStringContainsString('text-muted', $html);
    }

    #[Test]
    public function formatForDisplayReturnsTableWithHistory(): void
    {
        $history = [
            [
                'id' => 1,
                'domain_id' => 100,
                'domain_name' => 'test.com',
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => 'O-12345',
                'order_type' => 'Register_Domain',
                'message' => 'Domain registered',
                'created_at' => '2024-01-15 14:30:00',
            ],
            [
                'id' => 2,
                'domain_id' => 100,
                'domain_name' => 'test.com',
                'ascio_status' => 'Pending',
                'whmcs_status' => 'Pending',
                'order_id' => 'O-12345',
                'order_type' => 'Register_Domain',
                'message' => 'Registration pending',
                'created_at' => '2024-01-15 14:00:00',
            ],
        ];

        $html = DomainHistory::formatForDisplay($history);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('table-striped', $html);
        $this->assertStringContainsString('Completed', $html);
        $this->assertStringContainsString('Pending', $html);
        $this->assertStringContainsString('O-12345', $html);
        $this->assertStringContainsString('Register_Domain', $html);
        $this->assertStringContainsString('Domain registered', $html);
        $this->assertStringContainsString('2024-01-15 14:30:00', $html);
    }

    #[Test]
    public function formatForDisplayUsesCorrectStatusBadgeColors(): void
    {
        $history = [
            [
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => null,
                'order_type' => null,
                'message' => null,
                'created_at' => '2024-01-01 10:00:00',
            ],
        ];

        $html = DomainHistory::formatForDisplay($history);

        $this->assertStringContainsString('badge-success', $html);
        $this->assertStringContainsString('bg-success', $html);
    }

    #[Test]
    public function formatForDisplayHandlesFailedStatus(): void
    {
        $history = [
            [
                'ascio_status' => 'Failed',
                'whmcs_status' => 'Pending',
                'order_id' => null,
                'order_type' => null,
                'message' => 'Error occurred',
                'created_at' => '2024-01-01 10:00:00',
            ],
        ];

        $html = DomainHistory::formatForDisplay($history);

        $this->assertStringContainsString('badge-danger', $html);
        $this->assertStringContainsString('bg-danger', $html);
    }

    #[Test]
    public function formatForDisplayHandlesPendingEndUserAction(): void
    {
        $history = [
            [
                'ascio_status' => 'Pending_End_User_Action',
                'whmcs_status' => 'Pending',
                'order_id' => null,
                'order_type' => null,
                'message' => null,
                'created_at' => '2024-01-01 10:00:00',
            ],
        ];

        $html = DomainHistory::formatForDisplay($history);

        $this->assertStringContainsString('badge-warning', $html);
        $this->assertStringContainsString('Pending End User Action', $html);
    }

    #[Test]
    public function formatForDisplayEscapesHtmlInMessage(): void
    {
        $history = [
            [
                'ascio_status' => 'Completed',
                'whmcs_status' => 'Active',
                'order_id' => null,
                'order_type' => null,
                'message' => '<script>alert("XSS")</script>',
                'created_at' => '2024-01-01 10:00:00',
            ],
        ];

        $html = DomainHistory::formatForDisplay($history);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // =========================================================================
    // ensureTable() tests
    // =========================================================================

    #[Test]
    public function ensureTableCreatesTableIfNotExists(): void
    {
        // The table is already in the mock by default
        // This test just ensures the method doesn't throw
        DomainHistory::ensureTable();

        // If we get here without exception, the test passes
        $this->assertTrue(true);
    }

    // =========================================================================
    // TABLE_NAME constant tests
    // =========================================================================

    #[Test]
    public function tableNameConstantIsCorrect(): void
    {
        $this->assertEquals('tblascio_domain_history', DomainHistory::TABLE_NAME);
    }
}
