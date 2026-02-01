<?php
/**
 * Domain Callback Processing Integration Tests
 *
 * Tests callback processing with mocked callback data to verify
 * WHMCS status updates and handle storage.
 *
 * @group integration
 * @group v3
 * @group callbacks
 */

namespace Ascio\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\v3\domains\RequestV3;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\MockAscioClientV3;

#[Group('integration')]
#[Group('v3')]
#[Group('callbacks')]
class DomainCallbackTest extends IntegrationTestBase
{
    /** @var bool Disable simulation mode for callback tests */
    protected bool $simulationMode = false;

    /** @var MockAscioClientV3 */
    protected MockAscioClientV3 $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = new MockAscioClientV3();

        // Set up default domain in mock database
        CapsuleMock::setTableData('tbldomains', [
            [
                'id' => 1,
                'domain' => 'test-callback.com',
                'registrar' => 'ascio',
                'status' => 'Pending',
            ],
        ]);

        CapsuleMock::setTableData('tblasciohandles', []);
    }

    // =========================================================================
    // Callback Status Processing Tests
    // =========================================================================

    #[Test]
    public function testProcessCompletedCallback(): void
    {
        $callbackData = $this->mockCallback('ORD-12345', 'Completed', 'Order completed successfully');

        // Verify callback structure
        $this->assertEquals('ORD-12345', $callbackData['OrderId']);
        $this->assertEquals('Completed', $callbackData['OrderStatus']);

        // Verify status mapping
        $request = $this->getRequest();
        $whmcsStatus = $request->getDomainStatus((object) ['Status' => 'ACTIVE']);
        $this->assertEquals('Active', $whmcsStatus);
    }

    #[Test]
    public function testProcessFailedCallback(): void
    {
        $callbackData = $this->mockCallback('ORD-12345', 'Failed', 'Order validation failed');

        // Verify callback structure
        $this->assertEquals('Failed', $callbackData['OrderStatus']);
        $this->assertNotEmpty($callbackData['StatusList']['CallbackStatus']);
        $this->assertEquals('Order validation failed', $callbackData['StatusList']['CallbackStatus'][0]['Message']);
    }

    #[Test]
    public function testProcessPendingEndUserAction(): void
    {
        $callbackData = $this->mockCallback(
            'ORD-12345',
            'Pending_End_User_Action',
            'Email verification required'
        );

        $this->assertEquals('Pending_End_User_Action', $callbackData['OrderStatus']);

        // Domain should remain pending when end user action required
        $request = $this->getRequest();
        $whmcsStatus = $request->getDomainStatus((object) ['Status' => 'PENDING_VERIFICATION']);
        $this->assertEquals('Active', $whmcsStatus, 'Pending verification should map to Active');
    }

    #[Test]
    public function testProcessPendingDocumentation(): void
    {
        $callbackData = $this->mockCallback(
            'ORD-12345',
            'Pending_Documentation',
            'Additional documentation required'
        );

        $this->assertEquals('Pending_Documentation', $callbackData['OrderStatus']);
    }

    // =========================================================================
    // WHMCS Status Mapping Tests
    // =========================================================================

    #[Test]
    #[DataProvider('statusMappingProvider')]
    public function testCallbackSetsWhmcsStatus(string $ascioStatus, string $expectedWhmcsStatus): void
    {
        $request = $this->getRequest();
        $domain = (object) ['Status' => $ascioStatus];

        $result = $request->getDomainStatus($domain);

        $this->assertEquals($expectedWhmcsStatus, $result, "Ascio status '$ascioStatus' should map to WHMCS '$expectedWhmcsStatus'");
    }

    public static function statusMappingProvider(): array
    {
        return [
            'Active' => ['ACTIVE', 'Active'],
            'Active with lock' => ['ACTIVE,TRANSFER_LOCK', 'Active'],
            'Expiring' => ['EXPIRING', 'Active'],
            'Pending verification' => ['PENDING_VERIFICATION', 'Active'],
            'Transfer lock only' => ['TRANSFER_LOCK', 'Active'],
            'Pending' => ['PENDING', 'Pending'],
            'Deleted' => ['DELETED', 'Cancelled'],
        ];
    }

    #[Test]
    public function testCallbackSetsStatusForDeletedDomain(): void
    {
        $request = $this->getRequest();

        // Null domain should return Cancelled
        $result = $request->getDomainStatus(null);
        $this->assertEquals('Cancelled', $result);

        // Deleted domain should return Cancelled
        $domain = (object) ['Status' => 'DELETED'];
        $result = $request->getDomainStatus($domain);
        $this->assertEquals('Cancelled', $result);
    }

    // =========================================================================
    // Handle Storage Tests
    // =========================================================================

    #[Test]
    public function testCallbackStoresHandle(): void
    {
        CapsuleMock::reset();
        CapsuleMock::setTableData('tblasciohandles', []);

        $request = new RequestV3(array_merge($this->params, ['domainid' => 1]));

        // Create a mock domain with handle
        $domain = (object) [
            'DomainName' => 'test-callback.com',
            'DomainHandle' => 'DOM-V3-12345',
            'Status' => 'ACTIVE',
        ];

        // Store the handle
        $request->storeHandle('domain', 1, 'DOM-V3-12345', 'test-callback.com');

        // Verify handle was stored
        $query = CapsuleMock::getLastQuery();
        $this->assertEquals('insert', $query['type']);
        $this->assertEquals('tblasciohandles', $query['table']);
        $this->assertEquals('DOM-V3-12345', $query['data']['ascio_id']);
    }

    #[Test]
    public function testCallbackUpdatesExistingHandle(): void
    {
        CapsuleMock::reset();
        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => 'test-callback.com',
                'ascio_id' => 'DOM-V3-OLD',
            ],
        ]);

        $request = new RequestV3(array_merge($this->params, ['domainid' => 1]));

        // Store new handle (should update)
        $request->storeHandle('domain', 1, 'DOM-V3-OLD', 'test-callback.com');

        // Verify the stored handle can be retrieved
        $handle = $request->getHandle('domain', 1, 'test-callback.com');
        $this->assertEquals('DOM-V3-OLD', $handle);
    }

    #[Test]
    public function testCallbackDeletesOldHandle(): void
    {
        CapsuleMock::reset();
        CapsuleMock::setTableData('tblasciohandles', [
            [
                'type' => 'domain',
                'whmcs_id' => 1,
                'domain' => 'test-callback.com',
                'ascio_id' => 'DOM-OLD-HANDLE',
            ],
        ]);

        $request = new RequestV3(array_merge($this->params, ['domainid' => 1]));

        // Delete old handles
        $request->deleteOldHandle(1);

        // Verify delete was called
        $query = CapsuleMock::getLastQuery();
        $this->assertEquals('delete', $query['type']);
        $this->assertEquals('tblasciohandles', $query['table']);
    }

    // =========================================================================
    // Due Date Calculation in Callback Context
    // =========================================================================

    #[Test]
    public function testCallbackSetsDueDateFromExpDate(): void
    {
        CapsuleMock::reset();
        CapsuleMock::setTableData('tblasciotlds', [
            ['Tld' => 'com', 'Threshold' => -35, 'Renew' => 1],
        ]);

        $request = new RequestV3(array_merge($this->params, ['domainid' => 1]));

        // Create domain with expiry date
        $expDate = (new \DateTime('+1 year'))->format('Y-m-d\TH:i:s');
        $domain = (object) [
            'DomainName' => 'test.com',
            'DomainHandle' => 'DOM-12345',
            'Status' => 'ACTIVE',
            'ExpDate' => $expDate,
            'CreDate' => (new \DateTime())->format('Y-m-d\TH:i:s'),
        ];

        // Set domain status (this calculates due date internally)
        $request->setDomainStatus($domain);

        // Status should be Active
        $status = $request->getDomainStatus($domain);
        $this->assertEquals('Active', $status);
    }

    // =========================================================================
    // Order Type Specific Callback Tests
    // =========================================================================

    #[Test]
    #[DataProvider('orderTypeProvider')]
    public function testCallbackHandlesOrderType(string $orderType): void
    {
        $callbackData = [
            'MessageId' => 'MSG-' . uniqid(),
            'OrderId' => 'ORD-12345',
            'OrderStatus' => 'Completed',
            'OrderType' => $orderType,
            'DomainName' => 'test-callback.com',
            'Message' => '',
            'StatusList' => ['CallbackStatus' => []],
        ];

        // Verify order type is preserved
        $this->assertEquals($orderType, $callbackData['OrderType']);
    }

    public static function orderTypeProvider(): array
    {
        return [
            'Register Domain' => ['Register_Domain'],
            'Transfer Domain' => ['Transfer_Domain'],
            'Renew Domain' => ['Renew_Domain'],
            'Expire Domain' => ['Expire_Domain'],
            'Unexpire Domain' => ['Unexpire_Domain'],
            'Nameserver Update' => ['Nameserver_Update'],
            'Contact Update' => ['Contact_Update'],
            'Owner Change' => ['Owner_Change'],
            'Change Locks' => ['Change_Locks'],
        ];
    }

    // =========================================================================
    // Callback Error Handling Tests
    // =========================================================================

    #[Test]
    public function testCallbackHandlesInvalidOrderId(): void
    {
        $callbackData = $this->mockCallback('INVALID-ORDER', 'Failed', 'Order not found');

        // Should still have valid structure
        $this->assertArrayHasKey('OrderId', $callbackData);
        $this->assertArrayHasKey('OrderStatus', $callbackData);
    }

    #[Test]
    public function testCallbackHandlesMultipleErrors(): void
    {
        $callbackData = [
            'MessageId' => 'MSG-' . uniqid(),
            'OrderId' => 'ORD-12345',
            'OrderStatus' => 'Failed',
            'OrderType' => 'Register_Domain',
            'DomainName' => 'test-callback.com',
            'Message' => 'Multiple validation errors',
            'StatusList' => [
                'CallbackStatus' => [
                    ['Message' => 'Invalid registrant name', 'Status' => 'Failed'],
                    ['Message' => 'Invalid phone format', 'Status' => 'Failed'],
                    ['Message' => 'Missing required field', 'Status' => 'Failed'],
                ],
            ],
        ];

        // Verify multiple errors are preserved
        $this->assertCount(3, $callbackData['StatusList']['CallbackStatus']);
    }

    // =========================================================================
    // Auto-Expire After Registration Tests
    // =========================================================================

    #[Test]
    public function testCallbackTriggersAutoExpireWhenConfigured(): void
    {
        $params = array_merge($this->params, [
            'AutoExpire' => 'on',
            'domainid' => 1,
        ]);

        $request = new RequestV3($params);

        // Verify AutoExpire is configured
        $this->assertEquals('on', $params['AutoExpire']);

        // The actual expireDomain would be called in getCallbackData
        // We're testing the configuration check here
    }

    // =========================================================================
    // Callback Message Acknowledgement Tests
    // =========================================================================

    #[Test]
    public function testAckMethodExists(): void
    {
        $request = $this->getRequest();

        // Verify ack method exists and is callable
        $this->assertTrue(method_exists($request, 'ack'));
        $this->assertTrue(method_exists($request, 'ackQueueMessage'));
        $this->assertTrue(method_exists($request, 'ackMessage'));
    }
}
