<?php
/**
 * Unit Tests for Status and related classes
 *
 * Tests status message generation, instructions rendering,
 * and verification type handling.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/bootstrap.php';

/**
 * Testable StatusMessage class
 */
class TestableStatusMessage
{
    public string $type;
    public string $icon = '';
    public string $text = '';
    public string $status = '';
    public string $title = '';

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getHtml(): string
    {
        $status = $this->status === 'Order not validated' ? 'failed' : $this->status;

        return sprintf(
            '<div class="row %s"><div class="col-sm-4" id="%s"><p><span class="glyphicon glyphicon-%s"> </span> %s</p></div><div class="col-sm-8"><p>%s</p></div></div>',
            $status,
            $this->type,
            $this->icon,
            $this->title,
            $this->text
        );
    }
}

/**
 * Testable Instructions class
 */
class TestableInstructions
{
    public string $type;
    public string $message = '';
    public array $fields = [];
    public object $data;
    private TestableFqdn $fqdn;

    public function __construct(string $type, object $data)
    {
        $this->type = $type;
        $this->data = $data;
        $this->fqdn = new TestableFqdn($data->common_name ?? 'example.com');
    }

    public function getHtml(): string
    {
        $this->buildInstructions();

        if (empty($this->message)) {
            return '';
        }

        $html = '<div class="alert alert-warning">' . $this->message . '</div>';

        foreach ($this->fields as $field) {
            foreach ($field as $key => $value) {
                $html .= sprintf(
                    '<div class="row" style="font-size:11px"><div class="col-sm-1">%s</div><div class="col-sm-11">%s</div></div>',
                    $key,
                    $value
                );
            }
        }

        return $html;
    }

    private function buildInstructions(): void
    {
        match ($this->type) {
            'Email' => $this->setEmail(),
            'File' => $this->setFile(),
            'Cname' => $this->setCname(),
            'Txt' => $this->setTxt(),
            default => null,
        };
    }

    private function setTxt(): void
    {
        $this->message = 'Please add a <b>TXT</b>-Record to the zone: <b>' . $this->fqdn->getDomain() . '</b>';
        $this->fields = [
            ['Source' => $this->getSslAuth()],
            ['Target' => $this->data->dns_value ?? ''],
        ];
    }

    private function setCname(): void
    {
        $this->message = 'Please add a <b>CNAME</b>-Record to the zone: <b>' . $this->fqdn->getDomain() . '</b>';
        $this->fields = [
            ['Source' => $this->data->dns_name ?? ''],
            ['Target' => $this->data->dns_value ?? ''],
        ];
    }

    private function setFile(): void
    {
        $url = 'http://' . $this->fqdn->getFqdn() . '/' . ($this->data->dns_name ?? '');
        $this->message = 'Please place this file your webspace: <b>' . ($this->data->dns_name ?? '') . '</b>';
        $this->fields = [
            ['Location of the file' => $url],
            ['Content of the file' => $this->data->dns_value ?? ''],
        ];
    }

    private function setEmail(): void
    {
        $this->message = 'Please confirm the E-Mail that was sent to: ' . ($this->data->approval_email ?? '');
    }

    private function getSslAuth(): string
    {
        return '_dnsauth.' . $this->fqdn->getFqdn();
    }
}

/**
 * Testable Status class
 */
class TestableStatus
{
    private int $serviceId;
    public TestableFqdn $fqdn;
    public object $data;
    private array $messages = [];
    private ?TestableInstructions $instructions = null;
    public string $type = '';

    public function __construct(int $serviceId)
    {
        $this->serviceId = $serviceId;
    }

    public function setData(object $data): void
    {
        $this->data = $data;
        $this->fqdn = new TestableFqdn($data->common_name ?? 'example.com');

        // Determine verification type
        if (($data->verification_type ?? '') === 'Dns') {
            $this->type = ($data->dns_name ?? '') === 'DNS TXT Record' ? 'Txt' : 'Cname';
        } else {
            $this->type = $data->verification_type ?? 'Email';
        }

        $this->instructions = new TestableInstructions($this->type, $data);
    }

    public function setOrderMessage(): void
    {
        $message = new TestableStatusMessage('order');

        $code = $this->data->code ?? 0;
        $status = $this->data->status ?? 'Unknown';

        if ($code > 200) {
            $message->icon = 'remove';
            $message->status = $status;
            $message->text = $status;
        } elseif ($code == 200) {
            $message->icon = 'ok';
            $message->status = $status;
            $message->text = $status === 'Pending_End_User_Action' ? 'Pending SSL Verification' : $status;
        } elseif (str_contains($status, 'Pending')) {
            $message->status = 'Pending';
            $message->icon = 'time';
            $message->text = 'Pending';
        } else {
            $message->status = $status;
            $message->text = $status;
        }

        $message->title = '<b>Order Status</b>';
        $this->messages['order'] = $message;
    }

    public function getStatusHtml(): string
    {
        $html = '';
        foreach ($this->messages as $message) {
            $html .= $message->getHtml();
        }
        return $html;
    }

    public function getInstructionsHtml(): string
    {
        if ($this->isFinished()) {
            return '';
        }

        if (($this->data->status ?? '') !== 'Pending_End_User_Action') {
            return '';
        }

        if (($this->data->dns_created ?? 0) == 1) {
            return '';
        }

        return $this->instructions?->getHtml() ?? '';
    }

    public function isFinished(): bool
    {
        $finished = ['Completed', 'Failed', 'Order not validated', 'Invalid'];
        $status = $this->data->status ?? '';

        return in_array($status, $finished);
    }

    public function getName(): string
    {
        return $this->data->common_name ?? $this->data->name ?? '';
    }
}

class StatusMessageTest extends TestCase
{
    #[Test]
    public function constructorSetsType(): void
    {
        $message = new TestableStatusMessage('order');

        $this->assertEquals('order', $message->type);
    }

    #[Test]
    public function getHtmlIncludesType(): void
    {
        $message = new TestableStatusMessage('order');
        $message->title = 'Test';

        $html = $message->getHtml();

        $this->assertStringContainsString('id="order"', $html);
    }

    #[Test]
    public function getHtmlIncludesIcon(): void
    {
        $message = new TestableStatusMessage('test');
        $message->icon = 'ok';

        $html = $message->getHtml();

        $this->assertStringContainsString('glyphicon-ok', $html);
    }

    #[Test]
    public function getHtmlIncludesTitle(): void
    {
        $message = new TestableStatusMessage('test');
        $message->title = '<b>Order Status</b>';

        $html = $message->getHtml();

        $this->assertStringContainsString('<b>Order Status</b>', $html);
    }

    #[Test]
    public function getHtmlIncludesText(): void
    {
        $message = new TestableStatusMessage('test');
        $message->text = 'Certificate issued successfully';

        $html = $message->getHtml();

        $this->assertStringContainsString('Certificate issued successfully', $html);
    }

    #[Test]
    public function getHtmlIncludesStatusClass(): void
    {
        $message = new TestableStatusMessage('test');
        $message->status = 'Completed';

        $html = $message->getHtml();

        $this->assertStringContainsString('class="row Completed"', $html);
    }

    #[Test]
    public function getHtmlConvertsOrderNotValidatedToFailed(): void
    {
        $message = new TestableStatusMessage('test');
        $message->status = 'Order not validated';

        $html = $message->getHtml();

        $this->assertStringContainsString('class="row failed"', $html);
    }
}

class InstructionsTest extends TestCase
{
    #[Test]
    public function getHtmlReturnsEmptyForUnknownType(): void
    {
        $data = (object) ['common_name' => 'example.com'];
        $instructions = new TestableInstructions('Unknown', $data);

        $html = $instructions->getHtml();

        $this->assertEquals('', $html);
    }

    #[Test]
    public function getHtmlReturnsEmailInstructions(): void
    {
        $data = (object) [
            'common_name' => 'example.com',
            'approval_email' => 'admin@example.com',
        ];
        $instructions = new TestableInstructions('Email', $data);

        $html = $instructions->getHtml();

        $this->assertStringContainsString('confirm the E-Mail', $html);
        $this->assertStringContainsString('admin@example.com', $html);
    }

    #[Test]
    public function getHtmlReturnsTxtInstructions(): void
    {
        $data = (object) [
            'common_name' => 'www.example.com',
            'dns_value' => 'verification-token-123',
        ];
        $instructions = new TestableInstructions('Txt', $data);

        $html = $instructions->getHtml();

        $this->assertStringContainsString('TXT', $html);
        $this->assertStringContainsString('_dnsauth', $html);
        $this->assertStringContainsString('verification-token-123', $html);
    }

    #[Test]
    public function getHtmlReturnsCnameInstructions(): void
    {
        $data = (object) [
            'common_name' => 'example.com',
            'dns_name' => '_dcv.example.com',
            'dns_value' => 'dcv.comodoca.com',
        ];
        $instructions = new TestableInstructions('Cname', $data);

        $html = $instructions->getHtml();

        $this->assertStringContainsString('CNAME', $html);
        $this->assertStringContainsString('_dcv.example.com', $html);
        $this->assertStringContainsString('dcv.comodoca.com', $html);
    }

    #[Test]
    public function getHtmlReturnsFileInstructions(): void
    {
        $data = (object) [
            'common_name' => 'example.com',
            'dns_name' => '.well-known/pki-validation/verify.txt',
            'dns_value' => 'file-content-token',
        ];
        $instructions = new TestableInstructions('File', $data);

        $html = $instructions->getHtml();

        $this->assertStringContainsString('file your webspace', $html);
        $this->assertStringContainsString('.well-known/pki-validation/verify.txt', $html);
        $this->assertStringContainsString('file-content-token', $html);
    }

    #[Test]
    public function instructionsIncludeAlertWarning(): void
    {
        $data = (object) [
            'common_name' => 'example.com',
            'approval_email' => 'admin@example.com',
        ];
        $instructions = new TestableInstructions('Email', $data);

        $html = $instructions->getHtml();

        $this->assertStringContainsString('alert alert-warning', $html);
    }
}

class StatusTest extends TestCase
{
    private TestableStatus $status;

    protected function setUp(): void
    {
        parent::setUp();
        $this->status = new TestableStatus(1);
    }

    #[Test]
    public function setDataInitializesFqdn(): void
    {
        $data = (object) ['common_name' => 'www.example.com', 'status' => 'Pending'];
        $this->status->setData($data);

        $this->assertEquals('www.example.com', $this->status->fqdn->getFqdn());
    }

    #[Test]
    public function setDataDeterminesTxtTypeCorrectly(): void
    {
        $data = (object) [
            'common_name' => 'example.com',
            'verification_type' => 'Dns',
            'dns_name' => 'DNS TXT Record',
        ];
        $this->status->setData($data);

        $this->assertEquals('Txt', $this->status->type);
    }

    #[Test]
    public function setDataDeterminesCnameTypeCorrectly(): void
    {
        $data = (object) [
            'common_name' => 'example.com',
            'verification_type' => 'Dns',
            'dns_name' => '_dcv.example.com',
        ];
        $this->status->setData($data);

        $this->assertEquals('Cname', $this->status->type);
    }

    #[Test]
    public function setDataDeterminesEmailTypeCorrectly(): void
    {
        $data = (object) [
            'common_name' => 'example.com',
            'verification_type' => 'Email',
        ];
        $this->status->setData($data);

        $this->assertEquals('Email', $this->status->type);
    }

    #[Test]
    public function isFinishedReturnsTrueForCompleted(): void
    {
        $data = (object) ['common_name' => 'example.com', 'status' => 'Completed'];
        $this->status->setData($data);

        $this->assertTrue($this->status->isFinished());
    }

    #[Test]
    public function isFinishedReturnsTrueForFailed(): void
    {
        $data = (object) ['common_name' => 'example.com', 'status' => 'Failed'];
        $this->status->setData($data);

        $this->assertTrue($this->status->isFinished());
    }

    #[Test]
    public function isFinishedReturnsTrueForInvalid(): void
    {
        $data = (object) ['common_name' => 'example.com', 'status' => 'Invalid'];
        $this->status->setData($data);

        $this->assertTrue($this->status->isFinished());
    }

    #[Test]
    public function isFinishedReturnsFalseForPending(): void
    {
        $data = (object) ['common_name' => 'example.com', 'status' => 'Pending'];
        $this->status->setData($data);

        $this->assertFalse($this->status->isFinished());
    }

    #[Test]
    public function isFinishedReturnsFalseForPendingEndUserAction(): void
    {
        $data = (object) ['common_name' => 'example.com', 'status' => 'Pending_End_User_Action'];
        $this->status->setData($data);

        $this->assertFalse($this->status->isFinished());
    }

    #[Test]
    public function getNameReturnsCommonName(): void
    {
        $data = (object) ['common_name' => 'www.example.com', 'status' => 'Pending'];
        $this->status->setData($data);

        $this->assertEquals('www.example.com', $this->status->getName());
    }

    #[Test]
    public function getInstructionsHtmlReturnsEmptyWhenFinished(): void
    {
        $data = (object) ['common_name' => 'example.com', 'status' => 'Completed'];
        $this->status->setData($data);

        $html = $this->status->getInstructionsHtml();

        $this->assertEquals('', $html);
    }

    #[Test]
    public function getInstructionsHtmlReturnsEmptyWhenNotPendingEndUserAction(): void
    {
        $data = (object) ['common_name' => 'example.com', 'status' => 'Pending'];
        $this->status->setData($data);

        $html = $this->status->getInstructionsHtml();

        $this->assertEquals('', $html);
    }

    #[Test]
    public function getInstructionsHtmlReturnsEmptyWhenDnsCreated(): void
    {
        $data = (object) [
            'common_name' => 'example.com',
            'status' => 'Pending_End_User_Action',
            'dns_created' => 1,
            'verification_type' => 'Dns',
        ];
        $this->status->setData($data);

        $html = $this->status->getInstructionsHtml();

        $this->assertEquals('', $html);
    }

    #[Test]
    public function getInstructionsHtmlReturnsInstructionsWhenNeeded(): void
    {
        $data = (object) [
            'common_name' => 'example.com',
            'status' => 'Pending_End_User_Action',
            'dns_created' => 0,
            'verification_type' => 'Email',
            'approval_email' => 'admin@example.com',
        ];
        $this->status->setData($data);

        $html = $this->status->getInstructionsHtml();

        $this->assertStringContainsString('E-Mail', $html);
    }

    #[Test]
    #[DataProvider('finishedStatusProvider')]
    public function isFinishedRecognizesAllFinishedStatuses(string $status, bool $expected): void
    {
        $data = (object) ['common_name' => 'example.com', 'status' => $status];
        $this->status->setData($data);

        $this->assertEquals($expected, $this->status->isFinished());
    }

    public static function finishedStatusProvider(): array
    {
        return [
            'Completed is finished' => ['Completed', true],
            'Failed is finished' => ['Failed', true],
            'Invalid is finished' => ['Invalid', true],
            'Order not validated is finished' => ['Order not validated', true],
            'Pending is not finished' => ['Pending', false],
            'Pending_End_User_Action is not finished' => ['Pending_End_User_Action', false],
            'Unknown status is not finished' => ['Unknown', false],
        ];
    }
}
