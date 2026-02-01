<?php

/**
 * Unit tests for TMCH Callback handler.
 *
 * Tests the TmchCallback class for processing order status updates.
 */

declare(strict_types=1);

namespace Ascio\Tmch\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Ascio\Tmch\TmchCallback;
use Ascio\Core\ObjectType;

require_once __DIR__ . '/bootstrap.php';

#[Group('unit')]
#[Group('tmch')]
#[Group('callback')]
class TmchCallbackTest extends TestCase
{
    // =========================================================================
    // Interface Method Tests
    // =========================================================================

    #[Test]
    public function getTableNameReturnsCorrectTable(): void
    {
        $callback = $this->createPartialMock(TmchCallback::class, []);

        $this->assertEquals('mod_ascio_tmch', $callback->getTableName());
    }

    #[Test]
    public function getObjectTypeReturnsMark(): void
    {
        $callback = $this->createPartialMock(TmchCallback::class, []);

        $this->assertEquals(ObjectType::MARK, $callback->getObjectType());
    }

    // =========================================================================
    // Module Name Tests
    // =========================================================================

    #[Test]
    public function getModuleNameReturnsAsciotmch(): void
    {
        // Use reflection to test protected method
        $callback = $this->createPartialMock(TmchCallback::class, []);
        $reflection = new \ReflectionClass($callback);
        $method = $reflection->getMethod('getModuleName');
        $method->setAccessible(true);

        $result = $method->invoke($callback);

        $this->assertEquals('asciotmch', $result);
    }

    // =========================================================================
    // Object Type Constant Tests
    // =========================================================================

    #[Test]
    public function objectTypeMarkConstantExists(): void
    {
        $this->assertTrue(defined(ObjectType::class . '::MARK'));
        $this->assertEquals('MarkType', ObjectType::MARK);
    }
}
