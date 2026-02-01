<?php

/**
 * Unit Tests for Defensive Callback Handler
 *
 * Tests the DefensiveCallback class for processing API status updates.
 *
 * @copyright Copyright (c) Tucows Inc.
 */

declare(strict_types=1);

namespace Ascio\Defensive\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\CoversClass;
use Ascio\Defensive\DefensiveCallback;
use Ascio\Core\ObjectType;
use Ascio\Core\Tests\MockAscioClient;
use Ascio\Core\Tests\MockDatabase;
use Ascio\Core\Tests\MockParams;

require_once __DIR__ . '/bootstrap.php';

#[Group('unit')]
#[Group('defensive')]
#[Group('callback')]
#[CoversClass(DefensiveCallback::class)]
class DefensiveCallbackTest extends TestCase
{
    private MockAscioClient $mockClient;
    private MockDatabase $mockDb;
    private MockParams $mockParams;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockAscioClient();
        $this->mockDb = new MockDatabase();
        $this->mockParams = new MockParams();
    }

    protected function tearDown(): void
    {
        $this->mockClient->reset();
        $this->mockDb->clear();
        parent::tearDown();
    }

    // =========================================================================
    // Configuration Tests
    // =========================================================================

    #[Test]
    public function getTableNameReturnsCorrectTable(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        $this->assertEquals('mod_ascio_defensive', $callback->getTableName());
    }

    #[Test]
    public function getObjectTypeReturnsDefensive(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        $this->assertEquals(ObjectType::DEFENSIVE, $callback->getObjectType());
    }

    #[Test]
    public function getModuleNameReturnsCorrectModule(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        $reflection = new \ReflectionMethod($callback, 'getModuleName');
        $reflection->setAccessible(true);

        $this->assertEquals('asciodefensive', $reflection->invoke($callback));
    }

    // =========================================================================
    // Order Object Extraction Tests
    // =========================================================================

    #[Test]
    public function getObjectFromOrderReturnsDefensive(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        // Create mock order with defensive
        $defensive = new class {
            public function getHandle() { return 'DEF-CALLBACK'; }
        };

        $orderRequest = new class($defensive) {
            private $defensive;
            public function __construct($defensive) { $this->defensive = $defensive; }
            public function getDefensive() { return $this->defensive; }
        };

        $order = new class($orderRequest) {
            private $request;
            public function __construct($request) { $this->request = $request; }
            public function getOrderRequest() { return $this->request; }
        };

        // Set the order via reflection
        $reflection = new \ReflectionProperty($callback, 'order');
        $reflection->setAccessible(true);
        $reflection->setValue($callback, $order);

        // Get the object
        $method = new \ReflectionMethod($callback, 'getObjectFromOrder');
        $method->setAccessible(true);

        $result = $method->invoke($callback);

        $this->assertEquals('DEF-CALLBACK', $result->getHandle());
    }

    #[Test]
    public function getObjectFromOrderReturnsNullWhenNoOrder(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        $method = new \ReflectionMethod($callback, 'getObjectFromOrder');
        $method->setAccessible(true);

        $result = $method->invoke($callback);

        $this->assertNull($result);
    }

    // =========================================================================
    // Status Processing Tests
    // =========================================================================

    #[Test]
    public function processStatusHandlesCompleted(): void
    {
        // Seed database with pending defensive
        $this->mockDb->seed('mod_ascio_defensive', [
            [
                'whmcs_service_id' => 2001,
                'order_id' => 'TEST99999',
                'name' => 'callback-test.dpml',
                'status' => 'Pending',
            ],
        ]);

        // Mock getDefensive response
        $defensiveInfo = new class {
            public function getHandle() { return 'DEF-COMPLETED'; }
            public function getExpDate() { return '2026-12-31'; }
            public function getAuthInfo() { return 'AUTH-XYZ'; }
        };

        $result = new class($defensiveInfo) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
            public function getDefensiveInfo() { return $this->info; }
        };

        $this->mockClient->queueResponse('getDefensive', (object)['GetDefensiveResult' => $result]);

        $callback = new DefensiveCallback($this->mockParams, 'TEST99999', $this->mockClient, $this->mockDb);

        // Create completed order
        $defensive = new class {
            public function getHandle() { return 'DEF-COMPLETED'; }
        };

        $orderRequest = new class($defensive) {
            private $defensive;
            public function __construct($defensive) { $this->defensive = $defensive; }
            public function getDefensive() { return $this->defensive; }
        };

        $order = new class($orderRequest) {
            private $request;
            public function __construct($request) { $this->request = $request; }
            public function getOrderId() { return 'TEST99999'; }
            public function getStatus() { return 'Completed'; }
            public function getOrderRequest() { return $this->request; }
        };

        // Set the order
        $orderReflection = new \ReflectionProperty($callback, 'order');
        $orderReflection->setAccessible(true);
        $orderReflection->setValue($callback, $order);

        // Set the status
        $statusReflection = new \ReflectionProperty($callback, 'status');
        $statusReflection->setAccessible(true);
        $statusReflection->setValue($callback, 'Completed');

        // Test isCompleted
        $isCompletedMethod = new \ReflectionMethod($callback, 'isCompleted');
        $isCompletedMethod->setAccessible(true);
        $this->assertTrue($isCompletedMethod->invoke($callback));
    }

    #[Test]
    public function processStatusHandlesFailed(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TESTFAILED', $this->mockClient, $this->mockDb);

        // Create failed order
        $order = new class {
            public function getOrderId() { return 'TESTFAILED'; }
            public function getStatus() { return 'Failed'; }
            public function getOrderRequest() { return null; }
        };

        // Set the order
        $orderReflection = new \ReflectionProperty($callback, 'order');
        $orderReflection->setAccessible(true);
        $orderReflection->setValue($callback, $order);

        // Set the status
        $statusReflection = new \ReflectionProperty($callback, 'status');
        $statusReflection->setAccessible(true);
        $statusReflection->setValue($callback, 'Failed');

        // Test isFailed
        $isFailedMethod = new \ReflectionMethod($callback, 'isFailed');
        $isFailedMethod->setAccessible(true);
        $this->assertTrue($isFailedMethod->invoke($callback));
    }

    #[Test]
    public function processStatusHandlesPendingUserAction(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TESTPENDING', $this->mockClient, $this->mockDb);

        // Create pending user action order
        $order = new class {
            public function getOrderId() { return 'TESTPENDING'; }
            public function getStatus() { return 'Pending_End_User_Action'; }
            public function getOrderRequest() { return null; }
        };

        // Set the order
        $orderReflection = new \ReflectionProperty($callback, 'order');
        $orderReflection->setAccessible(true);
        $orderReflection->setValue($callback, $order);

        // Set the status
        $statusReflection = new \ReflectionProperty($callback, 'status');
        $statusReflection->setAccessible(true);
        $statusReflection->setValue($callback, 'Pending_End_User_Action');

        // Test isPendingUserAction
        $isPendingMethod = new \ReflectionMethod($callback, 'isPendingUserAction');
        $isPendingMethod->setAccessible(true);
        $this->assertTrue($isPendingMethod->invoke($callback));
    }

    // =========================================================================
    // Data Setting Tests
    // =========================================================================

    #[Test]
    public function setDataUpdatesInternalData(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        $setDataMethod = new \ReflectionMethod($callback, 'setData');
        $setDataMethod->setAccessible(true);

        $setDataMethod->invoke($callback, 'handle', 'DEF-SET-DATA');
        $setDataMethod->invoke($callback, 'expire_date', '2027-01-01');
        $setDataMethod->invoke($callback, 'auth_info', 'SECRET-AUTH');

        // Verify data was set via getData reflection
        $dataProperty = new \ReflectionProperty($callback, 'data');
        $dataProperty->setAccessible(true);
        $data = $dataProperty->getValue($callback);

        $this->assertEquals('DEF-SET-DATA', $data['handle']);
        $this->assertEquals('2027-01-01', $data['expire_date']);
        $this->assertEquals('SECRET-AUTH', $data['auth_info']);
    }

    // =========================================================================
    // Process Completed Tests
    // =========================================================================

    #[Test]
    public function processCompletedSetsHandleFromOrder(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        // Create mock defensive with handle
        $defensive = new class {
            public function getHandle() { return 'DEF-PROC-COMP'; }
        };

        $orderRequest = new class($defensive) {
            private $defensive;
            public function __construct($defensive) { $this->defensive = $defensive; }
            public function getDefensive() { return $this->defensive; }
        };

        $order = new class($orderRequest) {
            private $request;
            public function __construct($request) { $this->request = $request; }
            public function getOrderRequest() { return $this->request; }
            public function getStatus() { return 'Completed'; }
        };

        // Set the order
        $orderReflection = new \ReflectionProperty($callback, 'order');
        $orderReflection->setAccessible(true);
        $orderReflection->setValue($callback, $order);

        // Mock getDefensive API call
        $defensiveInfo = new class {
            public function getHandle() { return 'DEF-PROC-COMP'; }
            public function getExpDate() { return '2028-06-30'; }
            public function getAuthInfo() { return 'AUTH-PROC'; }
        };

        $apiResult = new class($defensiveInfo) {
            private $info;
            public function __construct($info) { $this->info = $info; }
            public function getResultCode() { return 200; }
            public function getDefensiveInfo() { return $this->info; }
        };

        $this->mockClient->queueResponse('getDefensive', (object)['GetDefensiveResult' => $apiResult]);

        // Process completed
        $processMethod = new \ReflectionMethod($callback, 'processCompleted');
        $processMethod->setAccessible(true);
        $processMethod->invoke($callback);

        // Verify data was set
        $dataProperty = new \ReflectionProperty($callback, 'data');
        $dataProperty->setAccessible(true);
        $data = $dataProperty->getValue($callback);

        $this->assertEquals('DEF-PROC-COMP', $data['handle']);
        $this->assertEquals('2028-06-30', $data['expire_date']);
        $this->assertEquals('AUTH-PROC', $data['auth_info']);
    }

    #[Test]
    public function processCompletedHandlesApiFailure(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        // Create mock defensive
        $defensive = new class {
            public function getHandle() { return 'DEF-API-FAIL'; }
        };

        $orderRequest = new class($defensive) {
            private $defensive;
            public function __construct($defensive) { $this->defensive = $defensive; }
            public function getDefensive() { return $this->defensive; }
        };

        $order = new class($orderRequest) {
            private $request;
            public function __construct($request) { $this->request = $request; }
            public function getOrderRequest() { return $this->request; }
            public function getStatus() { return 'Completed'; }
        };

        $orderReflection = new \ReflectionProperty($callback, 'order');
        $orderReflection->setAccessible(true);
        $orderReflection->setValue($callback, $order);

        // Mock API exception
        $this->mockClient->queueException('getDefensive', new \Exception('API unavailable'));

        // Should not throw - exception is caught and logged
        $processMethod = new \ReflectionMethod($callback, 'processCompleted');
        $processMethod->setAccessible(true);
        $processMethod->invoke($callback);

        // Handle should still be set from order
        $dataProperty = new \ReflectionProperty($callback, 'data');
        $dataProperty->setAccessible(true);
        $data = $dataProperty->getValue($callback);

        $this->assertEquals('DEF-API-FAIL', $data['handle']);
    }

    // =========================================================================
    // Process Failed Tests
    // =========================================================================

    #[Test]
    public function processFailedSetsMessageFromStringMessage(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        // Set a string message
        $messageReflection = new \ReflectionProperty($callback, 'message');
        $messageReflection->setAccessible(true);
        $messageReflection->setValue($callback, 'Order failed due to validation error');

        // Process failed
        $processMethod = new \ReflectionMethod($callback, 'processFailed');
        $processMethod->setAccessible(true);
        $processMethod->invoke($callback);

        // Verify message was set
        $dataProperty = new \ReflectionProperty($callback, 'data');
        $dataProperty->setAccessible(true);
        $data = $dataProperty->getValue($callback);

        $this->assertEquals('Order failed due to validation error', $data['message']);
    }

    #[Test]
    public function processFailedSetsMessageFromObjectMessage(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        // Set an object message with getMessage method
        $messageObj = new class {
            public function getMessage() { return 'Structured error message'; }
        };

        $messageReflection = new \ReflectionProperty($callback, 'message');
        $messageReflection->setAccessible(true);
        $messageReflection->setValue($callback, $messageObj);

        // Process failed
        $processMethod = new \ReflectionMethod($callback, 'processFailed');
        $processMethod->setAccessible(true);
        $processMethod->invoke($callback);

        // Verify message was set
        $dataProperty = new \ReflectionProperty($callback, 'data');
        $dataProperty->setAccessible(true);
        $data = $dataProperty->getValue($callback);

        $this->assertEquals('Structured error message', $data['message']);
    }

    // =========================================================================
    // Process Pending User Action Tests
    // =========================================================================

    #[Test]
    public function processPendingUserActionSetsMessage(): void
    {
        $callback = new DefensiveCallback($this->mockParams, 'TEST00001', $this->mockClient, $this->mockDb);

        // Set a message
        $messageReflection = new \ReflectionProperty($callback, 'message');
        $messageReflection->setAccessible(true);
        $messageReflection->setValue($callback, 'Please verify your email address');

        // Process pending
        $processMethod = new \ReflectionMethod($callback, 'processPendingUserAction');
        $processMethod->setAccessible(true);
        $processMethod->invoke($callback);

        // Verify message was set
        $dataProperty = new \ReflectionProperty($callback, 'data');
        $dataProperty->setAccessible(true);
        $data = $dataProperty->getValue($callback);

        $this->assertEquals('Please verify your email address', $data['message']);
    }
}
