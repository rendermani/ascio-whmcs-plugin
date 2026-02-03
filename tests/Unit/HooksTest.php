<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SoapClientMock;

// Load hooks file (defines functions globally - add_hook is mocked in bootstrap.php)
require_once __DIR__ . '/../../hooks.php';

/**
 * Unit tests for WHMCS hooks (hooks.php)
 *
 * Tests the JavaScript injection hooks and domain status display hook.
 */
class HooksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
    }

    // ========================================================================
    // Client Area Head Output Hook
    // ========================================================================

    #[Test]
    public function clientAreaHeadOutputReturnsScriptForCartPage(): void
    {
        $vars = ['filename' => 'cart.php'];
        $result = hook_ascio_client_area_head_output($vars);

        $this->assertStringContainsString('<script', $result);
        $this->assertStringContainsString('ascio-fields.js', $result);
    }

    #[Test]
    public function clientAreaHeadOutputReturnsScriptForDomainRegister(): void
    {
        $vars = ['filename' => 'domainregister.php'];
        $result = hook_ascio_client_area_head_output($vars);

        $this->assertStringContainsString('ascio-fields.js', $result);
    }

    #[Test]
    public function clientAreaHeadOutputReturnsEmptyForIrrelevantPage(): void
    {
        $_GET = []; // Clear any GET params
        $vars = ['filename' => 'invoices.php'];
        $result = hook_ascio_client_area_head_output($vars);

        $this->assertEmpty($result);
    }

    #[Test]
    public function clientAreaHeadOutputIncludesCacheBuster(): void
    {
        $vars = ['filename' => 'cart.php'];
        $result = hook_ascio_client_area_head_output($vars);

        $this->assertMatchesRegularExpression('/\?v=\d+/', $result);
    }

    // ========================================================================
    // Domain Status Hook
    // ========================================================================

    #[Test]
    public function clientDomainStatusReturnsEmptyWithoutDomainId(): void
    {
        $vars = [];
        $result = hook_ascio_client_domain_status($vars);

        $this->assertEmpty($result);
    }

    #[Test]
    public function clientDomainStatusReturnsEmptyWhenNoAscioStatus(): void
    {
        $vars = ['domainid' => 999];
        // CapsuleMock returns null by default for missing data
        $result = hook_ascio_client_domain_status($vars);

        $this->assertEmpty($result);
    }

    #[Test]
    public function clientDomainStatusReturnsSuccessAlertForCompleted(): void
    {
        CapsuleMock::setTableData('tbldomains_extra', [
            ['domain_id' => 1, 'name' => 'ascio_order_status', 'value' => 'Completed']
        ]);

        $vars = ['domainid' => 1];
        $result = hook_ascio_client_domain_status($vars);

        $this->assertStringContainsString('alert-success', $result);
        $this->assertStringContainsString('Completed', $result);
    }

    #[Test]
    public function clientDomainStatusReturnsDangerAlertForFailed(): void
    {
        CapsuleMock::setTableData('tbldomains_extra', [
            ['domain_id' => 2, 'name' => 'ascio_order_status', 'value' => 'Failed']
        ]);

        $vars = ['domainid' => 2];
        $result = hook_ascio_client_domain_status($vars);

        $this->assertStringContainsString('alert-danger', $result);
        $this->assertStringContainsString('Failed', $result);
    }

    #[Test]
    public function clientDomainStatusReturnsWarningForPendingAction(): void
    {
        CapsuleMock::setTableData('tbldomains_extra', [
            ['domain_id' => 3, 'name' => 'ascio_order_status', 'value' => 'Pending_End_User_Action']
        ]);

        $vars = ['domainid' => 3];
        $result = hook_ascio_client_domain_status($vars);

        $this->assertStringContainsString('alert-warning', $result);
        $this->assertStringContainsString('Action Required', $result);
    }

    #[Test]
    public function clientDomainStatusEscapesHtmlInOutput(): void
    {
        CapsuleMock::setTableData('tbldomains_extra', [
            ['domain_id' => 4, 'name' => 'ascio_order_status', 'value' => 'Completed']
        ]);

        $vars = ['domainid' => 4];
        $result = hook_ascio_client_domain_status($vars);

        // Output should use htmlspecialchars for the label
        $this->assertStringContainsString('Ascio Status:', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }
}
