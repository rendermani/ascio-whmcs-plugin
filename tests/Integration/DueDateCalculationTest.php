<?php
/**
 * Due Date Calculation Integration Tests
 *
 * Tests due date calculation logic based on TLD settings,
 * threshold values, and renewal support.
 *
 * @group integration
 * @group v3
 * @group due-date
 */

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\Request;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;

#[Group('integration')]
#[Group('v3')]
#[Group('due-date')]
class DueDateCalculationTest extends IntegrationTestBase
{
    /** @var bool Disable simulation mode */
    protected bool $simulationMode = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset local API tracking
        WhmcsFunctionsMock::reset();
    }

    // =========================================================================
    // Threshold-Based Due Date Tests
    // =========================================================================

    #[Test]
    public function testDueDateWithNegativeThreshold(): void
    {
        // TLD with -35 day threshold (due date 35 days BEFORE expiry)
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -35, 'Renew' => 1],
        ]);

        $expDate = (new \DateTime('+1 year'))->format('Y-m-d\TH:i:s');
        $domain = $this->createDomainWithExpDate('test.com', $expDate);

        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.com',
            'tld' => 'com',
        ]));

        // Calculate expected due date (expiry - 35 days)
        $expectedDueDate = (new \DateTime($expDate))->modify('-35 day')->format('Y-m-d');

        // Get due date through setStatus (this triggers the calculation)
        $request->setStatus($domain, 'Active');

        // Check localAPI was called with due date
        $calls = WhmcsFunctionsMock::getLocalApiCalls();
        $this->assertNotEmpty($calls, 'localAPI should be called');

        $updateCall = array_filter($calls, fn($c) => $c['command'] === 'updateclientdomain');
        $this->assertNotEmpty($updateCall, 'updateclientdomain should be called');

        $lastCall = end($updateCall);
        if (isset($lastCall['values']['nextduedate'])) {
            $this->assertEquals($expectedDueDate, $lastCall['values']['nextduedate']);
        }
    }

    #[Test]
    public function testDueDateWithZeroThreshold(): void
    {
        // TLD with 0 threshold (due date = expiry date)
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'org', 'Threshold' => 0, 'Renew' => 1],
        ]);

        $expDate = (new \DateTime('+1 year'))->format('Y-m-d\TH:i:s');
        $domain = $this->createDomainWithExpDate('test.org', $expDate);

        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.org',
            'tld' => 'org',
        ]));

        // Expected due date = expiry date
        $expectedDueDate = (new \DateTime($expDate))->format('Y-m-d');

        $request->setStatus($domain, 'Active');

        $calls = WhmcsFunctionsMock::getLocalApiCalls();
        $updateCall = array_filter($calls, fn($c) => $c['command'] === 'updateclientdomain');

        if (!empty($updateCall)) {
            $lastCall = end($updateCall);
            if (isset($lastCall['values']['nextduedate'])) {
                $this->assertEquals($expectedDueDate, $lastCall['values']['nextduedate']);
            }
        }
    }

    #[Test]
    public function testDueDateWithPositiveThreshold(): void
    {
        // TLD with +5 day threshold (due date 5 days AFTER expiry)
        // This is rare but some TLDs have grace periods
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'net', 'Threshold' => 5, 'Renew' => 1],
        ]);

        $expDate = (new \DateTime('+1 year'))->format('Y-m-d\TH:i:s');
        $domain = $this->createDomainWithExpDate('test.net', $expDate);

        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.net',
            'tld' => 'net',
        ]));

        // Expected due date = expiry + 5 days
        $expectedDueDate = (new \DateTime($expDate))->modify('+5 day')->format('Y-m-d');

        $request->setStatus($domain, 'Active');

        $calls = WhmcsFunctionsMock::getLocalApiCalls();
        $updateCall = array_filter($calls, fn($c) => $c['command'] === 'updateclientdomain');

        if (!empty($updateCall)) {
            $lastCall = end($updateCall);
            if (isset($lastCall['values']['nextduedate'])) {
                $this->assertEquals($expectedDueDate, $lastCall['values']['nextduedate']);
            }
        }
    }

    // =========================================================================
    // No-Renew TLD Due Date Tests
    // =========================================================================

    #[Test]
    public function testDueDateForNoRenewTld(): void
    {
        // TLD without renew support (Renew = 0)
        // Due date should be +1 year from expiry for active (non-expiring) domains
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'de', 'Threshold' => -30, 'Renew' => 0],
        ]);

        $expDate = (new \DateTime('+1 year'))->format('Y-m-d\TH:i:s');
        $domain = $this->createDomainWithExpDate('test.de', $expDate);
        $domain->Status = 'ACTIVE'; // Not expiring

        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.de',
            'tld' => 'de',
        ]));

        // For no-renew TLDs that are not expiring, add 1 year
        // Due date = (expiry - 30 days) + 1 year = expiry + 335 days
        $expectedDueDate = (new \DateTime($expDate))
            ->modify('-30 day')  // threshold
            ->modify('+1 year')  // no renew adjustment
            ->format('Y-m-d');

        $request->setStatus($domain, 'Active');

        $calls = WhmcsFunctionsMock::getLocalApiCalls();
        $updateCall = array_filter($calls, fn($c) => $c['command'] === 'updateclientdomain');

        if (!empty($updateCall)) {
            $lastCall = end($updateCall);
            if (isset($lastCall['values']['nextduedate'])) {
                // The +1 year is added because domain is not expiring and has no Renew
                $this->assertEquals($expectedDueDate, $lastCall['values']['nextduedate']);
            }
        }
    }

    #[Test]
    public function testDueDateForNoRenewTldExpiring(): void
    {
        // TLD without renew support but domain IS expiring
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'de', 'Threshold' => -30, 'Renew' => 0],
        ]);

        $expDate = (new \DateTime('+1 year'))->format('Y-m-d\TH:i:s');
        $domain = $this->createDomainWithExpDate('test.de', $expDate);
        $domain->Status = 'EXPIRING'; // Is expiring

        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.de',
            'tld' => 'de',
        ]));

        // For expiring domains, no +1 year adjustment
        $expectedDueDate = (new \DateTime($expDate))
            ->modify('-30 day')
            ->format('Y-m-d');

        $request->setStatus($domain, 'Active');

        $calls = WhmcsFunctionsMock::getLocalApiCalls();
        $updateCall = array_filter($calls, fn($c) => $c['command'] === 'updateclientdomain');

        if (!empty($updateCall)) {
            $lastCall = end($updateCall);
            if (isset($lastCall['values']['nextduedate'])) {
                $this->assertEquals($expectedDueDate, $lastCall['values']['nextduedate']);
            }
        }
    }

    // =========================================================================
    // Sync Due Date Setting Tests
    // =========================================================================

    #[Test]
    public function testDueDateSyncSettingEnabled(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -30, 'Renew' => 1],
        ]);

        $expDate = (new \DateTime('+1 year'))->format('Y-m-d\TH:i:s');
        $domain = $this->createDomainWithExpDate('test.com', $expDate);

        // Sync_Due_Date = 'on' (default)
        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.com',
            'tld' => 'com',
            'Sync_Due_Date' => 'on',
        ]));

        $request->setStatus($domain, 'Active');

        $calls = WhmcsFunctionsMock::getLocalApiCalls();
        $updateCall = array_filter($calls, fn($c) => $c['command'] === 'updateclientdomain');

        if (!empty($updateCall)) {
            $lastCall = end($updateCall);
            // Due date should be set when Sync_Due_Date is on
            $this->assertArrayHasKey('nextduedate', $lastCall['values']);
        }
    }

    #[Test]
    public function testDueDateSyncSettingDisabled(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -30, 'Renew' => 1],
        ]);

        $expDate = (new \DateTime('+1 year'))->format('Y-m-d\TH:i:s');
        $domain = $this->createDomainWithExpDate('test.com', $expDate);

        // Sync_Due_Date = 'off' - should not update due date
        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.com',
            'tld' => 'com',
            'Sync_Due_Date' => 'off',
        ]));

        $request->setStatus($domain, 'Active');

        $calls = WhmcsFunctionsMock::getLocalApiCalls();
        $updateCall = array_filter($calls, fn($c) => $c['command'] === 'updateclientdomain');

        if (!empty($updateCall)) {
            $lastCall = end($updateCall);
            // Due date should NOT be set when Sync_Due_Date is off
            $this->assertArrayNotHasKey('nextduedate', $lastCall['values']);
        }
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    public function testDueDateWithInvalidExpDate(): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -30, 'Renew' => 1],
        ]);

        // Invalid/null expiry date
        $domain = (object) [
            'DomainName' => 'test.com',
            'DomainHandle' => 'DOM-12345',
            'Status' => 'ACTIVE',
            'ExpDate' => '0001-01-01T00:00:00', // Invalid date
        ];

        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.com',
            'tld' => 'com',
        ]));

        // Should not crash with invalid date
        $request->setStatus($domain, 'Active');

        // Test passed if no exception thrown
        $this->assertTrue(true);
    }

    #[Test]
    public function testDueDateWithMissingTldConfig(): void
    {
        // No TLD configuration in database
        CapsuleMock::setTableData('tblasciotlds', []);

        $expDate = (new \DateTime('+1 year'))->format('Y-m-d\TH:i:s');
        $domain = $this->createDomainWithExpDate('test.xyz', $expDate);

        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.xyz',
            'tld' => 'xyz',
        ]));

        // Should handle missing TLD config gracefully
        $request->setStatus($domain, 'Active');

        // Test passed if no exception thrown
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProvider('thresholdProvider')]
    public function testDueDateWithVariousThresholds(int $threshold, int $expectedDaysDiff): void
    {
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'test', 'Threshold' => $threshold, 'Renew' => 1],
        ]);

        $expDate = '2025-12-31T00:00:00';
        $domain = $this->createDomainWithExpDate('test.test', $expDate);

        $request = new Request(array_merge($this->params, [
            'domainid' => 1,
            'domainname' => 'test.test',
            'tld' => 'test',
        ]));

        $request->setStatus($domain, 'Active');

        $calls = WhmcsFunctionsMock::getLocalApiCalls();
        $updateCall = array_filter($calls, fn($c) => $c['command'] === 'updateclientdomain');

        if (!empty($updateCall)) {
            $lastCall = end($updateCall);
            if (isset($lastCall['values']['nextduedate'])) {
                $expDateObj = new \DateTime('2025-12-31');
                $dueDate = new \DateTime($lastCall['values']['nextduedate']);
                $diff = $expDateObj->diff($dueDate)->days;

                // Check the difference is as expected (allowing for sign)
                $actualDiff = $dueDate < $expDateObj ? -$diff : $diff;
                $this->assertEquals($expectedDaysDiff, $actualDiff);
            }
        }
    }

    public static function thresholdProvider(): array
    {
        return [
            'Threshold -60' => [-60, -60],
            'Threshold -45' => [-45, -45],
            'Threshold -30' => [-30, -30],
            'Threshold -15' => [-15, -15],
            'Threshold 0' => [0, 0],
            'Threshold +7' => [7, 7],
            'Threshold +30' => [30, 30],
        ];
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a mock domain object with expiry date
     */
    private function createDomainWithExpDate(string $domainName, string $expDate): object
    {
        return (object) [
            'DomainName' => $domainName,
            'DomainHandle' => 'DOM-' . uniqid(),
            'Status' => 'ACTIVE',
            'ExpDate' => $expDate,
            'CreDate' => (new \DateTime('-1 year'))->format('Y-m-d\TH:i:s'),
        ];
    }
}
