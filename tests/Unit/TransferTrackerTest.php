<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\TransferTracker;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SchemaMock;

/**
 * Unit tests for ascio\TransferTracker class
 *
 * Tests the transfer tracking functionality for domain transfers.
 *
 * @covers \ascio\TransferTracker
 */
class TransferTrackerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
        SchemaMock::addTable('tblascio_transfer_status');
    }

    // =========================================================================
    // getStages Tests
    // =========================================================================

    #[Test]
    public function getStagesReturnsCorrectStages(): void
    {
        $stages = TransferTracker::getStages();

        $this->assertIsArray($stages);
        $this->assertCount(5, $stages);
        $this->assertEquals(['pending', 'validating', 'processing', 'completed', 'failed'], $stages);
    }

    #[Test]
    public function getStagesReturnsStagesInOrder(): void
    {
        $stages = TransferTracker::getStages();

        $this->assertEquals('pending', $stages[0]);
        $this->assertEquals('validating', $stages[1]);
        $this->assertEquals('processing', $stages[2]);
        $this->assertEquals('completed', $stages[3]);
        $this->assertEquals('failed', $stages[4]);
    }

    // =========================================================================
    // getStageIndex Tests
    // =========================================================================

    #[Test]
    public function getStageIndexReturnsCorrectIndex(): void
    {
        $this->assertEquals(0, TransferTracker::getStageIndex('pending'));
        $this->assertEquals(1, TransferTracker::getStageIndex('validating'));
        $this->assertEquals(2, TransferTracker::getStageIndex('processing'));
        $this->assertEquals(3, TransferTracker::getStageIndex('completed'));
        $this->assertEquals(4, TransferTracker::getStageIndex('failed'));
    }

    #[Test]
    public function getStageIndexReturnsNegativeOneForInvalidStage(): void
    {
        $this->assertEquals(-1, TransferTracker::getStageIndex('invalid'));
        $this->assertEquals(-1, TransferTracker::getStageIndex(''));
        $this->assertEquals(-1, TransferTracker::getStageIndex('unknown'));
    }

    // =========================================================================
    // getStageLabel Tests
    // =========================================================================

    #[Test]
    public function getStageLabelReturnsCorrectLabels(): void
    {
        $this->assertEquals('Pending', TransferTracker::getStageLabel('pending'));
        $this->assertEquals('Validating', TransferTracker::getStageLabel('validating'));
        $this->assertEquals('Processing', TransferTracker::getStageLabel('processing'));
        $this->assertEquals('Completed', TransferTracker::getStageLabel('completed'));
        $this->assertEquals('Failed', TransferTracker::getStageLabel('failed'));
    }

    #[Test]
    public function getStageLabelReturnsCapitalizedUnknownStage(): void
    {
        $this->assertEquals('Unknown', TransferTracker::getStageLabel('unknown'));
        $this->assertEquals('Custom', TransferTracker::getStageLabel('custom'));
    }

    // =========================================================================
    // getProgressPercentage Tests
    // =========================================================================

    #[Test]
    public function getProgressPercentageReturnsCorrectPercentages(): void
    {
        $this->assertEquals(25, TransferTracker::getProgressPercentage('pending'));
        $this->assertEquals(50, TransferTracker::getProgressPercentage('validating'));
        $this->assertEquals(75, TransferTracker::getProgressPercentage('processing'));
        $this->assertEquals(100, TransferTracker::getProgressPercentage('completed'));
        $this->assertEquals(100, TransferTracker::getProgressPercentage('failed'));
    }

    #[Test]
    public function getProgressPercentageReturnsZeroForInvalidStage(): void
    {
        $this->assertEquals(0, TransferTracker::getProgressPercentage('invalid'));
        $this->assertEquals(0, TransferTracker::getProgressPercentage(''));
    }

    // =========================================================================
    // mapOrderStatusToStage Tests
    // =========================================================================

    #[Test]
    #[DataProvider('orderStatusProvider')]
    public function mapOrderStatusToStageReturnsCorrectStage(string $orderStatus, string $expectedStage): void
    {
        $this->assertEquals($expectedStage, TransferTracker::mapOrderStatusToStage($orderStatus));
    }

    public static function orderStatusProvider(): array
    {
        return [
            ['NotSet', 'pending'],
            ['Pending', 'validating'],
            ['Pending_End_User_Action', 'validating'],
            ['Pending_Documentation', 'validating'],
            ['Pending_Approval', 'validating'],
            ['Pending_Registry', 'processing'],
            ['Processing', 'processing'],
            ['Completed', 'completed'],
            ['Successful', 'completed'],
            ['Failed', 'failed'],
            ['Invalid', 'failed'],
            ['Cancelled', 'failed'],
            ['UnknownStatus', 'pending'], // Default case
        ];
    }

    // =========================================================================
    // updateStatus Tests
    // =========================================================================

    #[Test]
    public function updateStatusCreatesNewRecord(): void
    {
        // Set up mock domain data
        CapsuleMock::setTableData('tbldomains', [
            ['id' => 123, 'domain' => 'example.com', 'status' => 'Pending Transfer']
        ]);

        $result = TransferTracker::updateStatus(123, 'pending', 'ORD-12345', 'Transfer initiated');

        $this->assertTrue($result);

        $lastQuery = CapsuleMock::getLastQuery();
        $this->assertEquals('insert', $lastQuery['type']);
        $this->assertEquals('tblascio_transfer_status', $lastQuery['table']);
    }

    #[Test]
    public function updateStatusUpdatesExistingRecord(): void
    {
        // Set up existing transfer status and domain
        CapsuleMock::setTableData('tbldomains', [
            ['id' => 123, 'domain' => 'example.com', 'status' => 'Pending Transfer']
        ]);
        CapsuleMock::setTableData('tblascio_transfer_status', [
            [
                'id' => 1,
                'domain_id' => 123,
                'domain_name' => 'example.com',
                'current_stage' => 'pending',
                'order_id' => 'ORD-12345',
                'started_at' => '2024-01-01 00:00:00',
                'updated_at' => '2024-01-01 00:00:00',
                'message' => 'Transfer initiated'
            ]
        ]);

        $result = TransferTracker::updateStatus(123, 'validating', null, 'Validation in progress');

        $this->assertTrue($result);
    }

    #[Test]
    public function updateStatusReturnsFalseForInvalidStage(): void
    {
        $result = TransferTracker::updateStatus(123, 'invalid_stage');

        $this->assertFalse($result);
    }

    // =========================================================================
    // getTransferStatus Tests
    // =========================================================================

    #[Test]
    public function getTransferStatusReturnsNullWhenNotFound(): void
    {
        CapsuleMock::setTableData('tblascio_transfer_status', []);

        $status = TransferTracker::getTransferStatus(999);

        $this->assertNull($status);
    }

    #[Test]
    public function getTransferStatusReturnsStatusArray(): void
    {
        CapsuleMock::setTableData('tblascio_transfer_status', [
            [
                'id' => 1,
                'domain_id' => 123,
                'domain_name' => 'example.com',
                'current_stage' => 'validating',
                'order_id' => 'ORD-12345',
                'started_at' => '2024-01-01 10:00:00',
                'updated_at' => '2024-01-01 12:00:00',
                'message' => 'Awaiting authorization'
            ]
        ]);

        $status = TransferTracker::getTransferStatus(123);

        $this->assertIsArray($status);
        $this->assertEquals(1, $status['id']);
        $this->assertEquals(123, $status['domain_id']);
        $this->assertEquals('example.com', $status['domain_name']);
        $this->assertEquals('validating', $status['current_stage']);
        $this->assertEquals('ORD-12345', $status['order_id']);
        $this->assertEquals('2024-01-01 10:00:00', $status['started_at']);
        $this->assertEquals('2024-01-01 12:00:00', $status['updated_at']);
        $this->assertEquals('Awaiting authorization', $status['message']);
    }

    // =========================================================================
    // deleteStatus Tests
    // =========================================================================

    #[Test]
    public function deleteStatusRemovesRecord(): void
    {
        CapsuleMock::setTableData('tblascio_transfer_status', [
            [
                'id' => 1,
                'domain_id' => 123,
                'domain_name' => 'example.com',
                'current_stage' => 'completed',
                'order_id' => 'ORD-12345',
                'started_at' => '2024-01-01 10:00:00',
                'updated_at' => '2024-01-02 10:00:00',
                'message' => null
            ]
        ]);

        $result = TransferTracker::deleteStatus(123);

        $this->assertTrue($result);
    }

    #[Test]
    public function deleteStatusReturnsFalseWhenNotFound(): void
    {
        CapsuleMock::setTableData('tblascio_transfer_status', []);

        $result = TransferTracker::deleteStatus(999);

        $this->assertFalse($result);
    }

    // =========================================================================
    // renderProgressHtml Tests
    // =========================================================================

    #[Test]
    public function renderProgressHtmlReturnsHtml(): void
    {
        $status = [
            'id' => 1,
            'domain_id' => 123,
            'domain_name' => 'example.com',
            'current_stage' => 'validating',
            'order_id' => 'ORD-12345',
            'started_at' => '2024-01-01 10:00:00',
            'updated_at' => '2024-01-01 12:00:00',
            'message' => 'Awaiting authorization'
        ];

        $html = TransferTracker::renderProgressHtml($status);

        $this->assertIsString($html);
        $this->assertStringContainsString('Transfer Progress', $html);
        $this->assertStringContainsString('ORD-12345', $html);
        $this->assertStringContainsString('Awaiting authorization', $html);
    }

    #[Test]
    public function renderProgressHtmlShowsCorrectProgressForPending(): void
    {
        $status = [
            'id' => 1,
            'domain_id' => 123,
            'domain_name' => 'example.com',
            'current_stage' => 'pending',
            'order_id' => 'ORD-12345',
            'started_at' => '2024-01-01 10:00:00',
            'updated_at' => '2024-01-01 10:00:00',
            'message' => null
        ];

        $html = TransferTracker::renderProgressHtml($status);

        $this->assertStringContainsString('width: 25%', $html);
    }

    #[Test]
    public function renderProgressHtmlShowsCorrectProgressForCompleted(): void
    {
        $status = [
            'id' => 1,
            'domain_id' => 123,
            'domain_name' => 'example.com',
            'current_stage' => 'completed',
            'order_id' => 'ORD-12345',
            'started_at' => '2024-01-01 10:00:00',
            'updated_at' => '2024-01-02 10:00:00',
            'message' => null
        ];

        $html = TransferTracker::renderProgressHtml($status);

        $this->assertStringContainsString('width: 100%', $html);
        $this->assertStringContainsString('#5cb85c', $html); // Green color for completed
    }

    #[Test]
    public function renderProgressHtmlShowsFailedStatus(): void
    {
        $status = [
            'id' => 1,
            'domain_id' => 123,
            'domain_name' => 'example.com',
            'current_stage' => 'failed',
            'order_id' => 'ORD-12345',
            'started_at' => '2024-01-01 10:00:00',
            'updated_at' => '2024-01-02 10:00:00',
            'message' => 'Authorization denied by registrant'
        ];

        $html = TransferTracker::renderProgressHtml($status);

        $this->assertStringContainsString('width: 100%', $html);
        $this->assertStringContainsString('#d9534f', $html); // Red color for failed
        $this->assertStringContainsString('Authorization denied by registrant', $html);
    }

    #[Test]
    public function renderProgressHtmlHandlesNullValues(): void
    {
        $status = [
            'id' => 1,
            'domain_id' => 123,
            'domain_name' => 'example.com',
            'current_stage' => 'pending',
            'order_id' => null,
            'started_at' => null,
            'updated_at' => null,
            'message' => null
        ];

        $html = TransferTracker::renderProgressHtml($status);

        $this->assertIsString($html);
        $this->assertStringContainsString('N/A', $html);
    }

    // =========================================================================
    // ensureTable Tests
    // =========================================================================

    #[Test]
    public function ensureTableCreatesTableIfNotExists(): void
    {
        // Remove the table from schema mock
        SchemaMock::reset();

        // This should trigger table creation
        TransferTracker::ensureTable();

        // Table should now exist
        $this->assertTrue(CapsuleMock::schema()->hasTable('tblascio_transfer_status'));
    }

    // =========================================================================
    // Integration-style Tests
    // =========================================================================

    #[Test]
    public function fullTransferWorkflow(): void
    {
        // Set up mock domain
        CapsuleMock::setTableData('tbldomains', [
            ['id' => 100, 'domain' => 'transfer-test.com', 'status' => 'Pending Transfer']
        ]);

        // 1. Create initial status
        $result = TransferTracker::updateStatus(100, 'pending', 'ORD-001', 'Transfer initiated');
        $this->assertTrue($result);

        // Verify stage progression
        $this->assertEquals(0, TransferTracker::getStageIndex('pending'));
        $this->assertEquals(25, TransferTracker::getProgressPercentage('pending'));

        // 2. Move to validating stage
        TransferTracker::updateStatus(100, 'validating', null, 'Awaiting authorization');
        $this->assertEquals(50, TransferTracker::getProgressPercentage('validating'));

        // 3. Move to processing stage
        TransferTracker::updateStatus(100, 'processing', null, 'Transfer in progress');
        $this->assertEquals(75, TransferTracker::getProgressPercentage('processing'));

        // 4. Complete transfer
        TransferTracker::updateStatus(100, 'completed', null, 'Transfer completed successfully');
        $this->assertEquals(100, TransferTracker::getProgressPercentage('completed'));
    }

    #[Test]
    public function orderStatusMappingWorksWithCallbackFlow(): void
    {
        // Simulate the callback flow

        // Initial transfer request
        $stage = TransferTracker::mapOrderStatusToStage('NotSet');
        $this->assertEquals('pending', $stage);

        // Registry processing
        $stage = TransferTracker::mapOrderStatusToStage('Pending');
        $this->assertEquals('validating', $stage);

        // Awaiting user action
        $stage = TransferTracker::mapOrderStatusToStage('Pending_End_User_Action');
        $this->assertEquals('validating', $stage);

        // Registry processing
        $stage = TransferTracker::mapOrderStatusToStage('Pending_Registry');
        $this->assertEquals('processing', $stage);

        // Success
        $stage = TransferTracker::mapOrderStatusToStage('Completed');
        $this->assertEquals('completed', $stage);
    }
}
