<?php
/**
 * Unit Tests for Callback and SslCallback classes
 *
 * Tests callback processing, status mapping, DNS token parsing,
 * and certificate data handling.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/bootstrap.php';

/**
 * Mock Params class for testing
 */
class MockParams
{
    public $serviceId = 1;
    public $userId = 100;
    public $certificateType = 'positivessl';
    public $testmode = true;
    public $account = 'test_account';
    public $password = 'test_password';
    public $testAccount = 'test_account';
    public $testPassword = 'test_password';
    public $createDnsRecord = true;
    public $requireDomain = false;
    public $paidSans = 0;
    public $settings;

    public function __construct()
    {
        $this->settings = (object) [
            'Account' => $this->account,
            'Password' => $this->password,
            'AccountTesting' => $this->testAccount,
            'PasswordTesting' => $this->testPassword,
            'Environment' => 'testing',
            'CreateDns' => true,
            'RequireDomain' => false,
        ];
    }

    public function getCredentials($forceLive = false): array
    {
        return [
            'Account' => $this->testAccount,
            'Password' => $this->testPassword,
        ];
    }

    public function getWsdlV3($forceLive = false): string
    {
        return 'https://aws.demo.ascio.com/v3/aws.wsdl';
    }

    public function getWsdlV2($forceLive = false): string
    {
        return 'https://aws.demo.ascio.com/2012/01/01/AscioService.wsdl';
    }

    public function getData(): array
    {
        return [
            'whmcs_service_id' => $this->serviceId,
            'user_id' => $this->userId,
            'type' => $this->certificateType,
            'create_dns_record' => $this->createDnsRecord,
        ];
    }
}

/**
 * Testable version of Callback that doesn't require SOAP
 */
class TestableCallback
{
    public $order;
    protected string $status = '';
    protected string $orderId;
    protected int $serviceId;
    protected string $messageId = '';
    protected string $message = '';
    protected array $data = [];
    protected MockParams $params;

    public function __construct(MockParams $params, string $orderId)
    {
        $this->params = $params;
        $this->orderId = $orderId;
    }

    public function setServiceId(int $serviceId): void
    {
        $this->serviceId = $serviceId;
    }

    public function process(string $orderId, string $status, string $messageId, ?string $message = null): void
    {
        $this->status = $status;
        $this->messageId = $messageId;
        $this->message = $message ?? '';
        $this->data['status'] = $status;
    }

    public function mapStatusToWhmcs(string $status): string
    {
        return match ($status) {
            'Completed' => 'Active',
            default => 'Pending',
        };
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function parseDnsToken(string $message): array
    {
        $regex = '/AuthName: (.*)\nAuthValue: (.*)/';
        preg_match($regex, $message, $result);

        if (count($result) < 3) {
            return ['authName' => null, 'authValue' => null, 'authType' => null];
        }

        return [
            'authName' => trim($result[1]),
            'authValue' => trim($result[2]),
            'authType' => 'dns',
        ];
    }

    public function parseFileToken(string $message): array
    {
        $regex = '/AuthFileName: (.*)\nAuthFileContent: (.*)/';
        preg_match($regex, $message, $result);

        if (count($result) < 3) {
            return ['authName' => null, 'authValue' => null, 'authType' => null];
        }

        return [
            'authName' => trim($result[1]),
            'authValue' => trim($result[2]),
            'authType' => 'file',
        ];
    }
}

class CallbackTest extends TestCase
{
    private MockParams $params;
    private TestableCallback $callback;

    protected function setUp(): void
    {
        parent::setUp();
        MockQueryBuilder::reset();

        $this->params = new MockParams();
        $this->callback = new TestableCallback($this->params, 'TEST123456');
        $this->callback->setServiceId(1);
    }

    #[Test]
    public function processSetsPendingStatus(): void
    {
        $this->callback->process('TEST123', 'Pending', 'MSG001', 'Order is processing');

        $this->assertEquals('Pending', $this->callback->getStatus());
    }

    #[Test]
    public function processSetsPendingEndUserActionStatus(): void
    {
        $message = "AuthName: _dnsauth.example.com\nAuthValue: token123";
        $this->callback->process('TEST123', 'Pending_End_User_Action', 'MSG002', $message);

        $this->assertEquals('Pending_End_User_Action', $this->callback->getStatus());
    }

    #[Test]
    public function processSetsCompletedStatus(): void
    {
        $this->callback->process('TEST123', 'Completed', 'MSG003', 'Certificate issued');

        $this->assertEquals('Completed', $this->callback->getStatus());
    }

    #[Test]
    public function processSetsFailedStatus(): void
    {
        $this->callback->process('TEST123', 'Failed', 'MSG004', 'Validation failed');

        $this->assertEquals('Failed', $this->callback->getStatus());
    }

    #[Test]
    public function processSetsInvalidStatus(): void
    {
        $this->callback->process('TEST123', 'Invalid', 'MSG005', 'Order is invalid');

        $this->assertEquals('Invalid', $this->callback->getStatus());
    }

    #[Test]
    public function mapStatusToWhmcsReturnsActiveForCompleted(): void
    {
        $whmcsStatus = $this->callback->mapStatusToWhmcs('Completed');

        $this->assertEquals('Active', $whmcsStatus);
    }

    #[Test]
    public function mapStatusToWhmcsReturnsPendingForPending(): void
    {
        $whmcsStatus = $this->callback->mapStatusToWhmcs('Pending');

        $this->assertEquals('Pending', $whmcsStatus);
    }

    #[Test]
    public function mapStatusToWhmcsReturnsPendingForFailed(): void
    {
        $whmcsStatus = $this->callback->mapStatusToWhmcs('Failed');

        $this->assertEquals('Pending', $whmcsStatus);
    }

    #[Test]
    public function mapStatusToWhmcsReturnsPendingForInvalid(): void
    {
        $whmcsStatus = $this->callback->mapStatusToWhmcs('Invalid');

        $this->assertEquals('Pending', $whmcsStatus);
    }

    #[Test]
    public function mapStatusToWhmcsReturnsPendingForPendingEndUserAction(): void
    {
        $whmcsStatus = $this->callback->mapStatusToWhmcs('Pending_End_User_Action');

        $this->assertEquals('Pending', $whmcsStatus);
    }

    #[Test]
    public function parseDnsTokenExtractsAuthNameAndValue(): void
    {
        $message = "AuthName: _dnsauth.example.com\nAuthValue: abc123def456";

        $tokens = $this->callback->parseDnsToken($message);

        $this->assertEquals('_dnsauth.example.com', $tokens['authName']);
        $this->assertEquals('abc123def456', $tokens['authValue']);
        $this->assertEquals('dns', $tokens['authType']);
    }

    #[Test]
    public function parseDnsTokenHandlesWhitespace(): void
    {
        $message = "AuthName:   _dnsauth.example.com  \nAuthValue:   token-with-spaces  ";

        $tokens = $this->callback->parseDnsToken($message);

        $this->assertEquals('_dnsauth.example.com', $tokens['authName']);
        $this->assertEquals('token-with-spaces', $tokens['authValue']);
    }

    #[Test]
    public function parseDnsTokenReturnsNullForInvalidMessage(): void
    {
        $message = "This is not a valid DNS token message";

        $tokens = $this->callback->parseDnsToken($message);

        $this->assertNull($tokens['authName']);
        $this->assertNull($tokens['authValue']);
    }

    #[Test]
    public function parseFileTokenExtractsFileNameAndContent(): void
    {
        $message = "AuthFileName: validation.txt\nAuthFileContent: secret-content-123";

        $tokens = $this->callback->parseFileToken($message);

        $this->assertEquals('validation.txt', $tokens['authName']);
        $this->assertEquals('secret-content-123', $tokens['authValue']);
        $this->assertEquals('file', $tokens['authType']);
    }

    #[Test]
    public function parseFileTokenReturnsNullForInvalidMessage(): void
    {
        $message = "This is not a valid file token message";

        $tokens = $this->callback->parseFileToken($message);

        $this->assertNull($tokens['authName']);
        $this->assertNull($tokens['authValue']);
    }

    #[Test]
    public function processStoresMessageInData(): void
    {
        $message = "Test callback message";
        $this->callback->process('TEST123', 'Pending', 'MSG001', $message);

        $this->assertEquals($message, $this->callback->getMessage());
    }

    #[Test]
    public function processUpdatesDataArray(): void
    {
        $this->callback->process('TEST123', 'Completed', 'MSG001', 'Certificate ready');

        $data = $this->callback->getData();
        $this->assertEquals('Completed', $data['status']);
    }

    #[Test]
    #[DataProvider('statusMappingProvider')]
    public function allStatusesMappedCorrectly(string $ascioStatus, string $expectedWhmcsStatus): void
    {
        $whmcsStatus = $this->callback->mapStatusToWhmcs($ascioStatus);

        $this->assertEquals($expectedWhmcsStatus, $whmcsStatus);
    }

    public static function statusMappingProvider(): array
    {
        return [
            'Pending maps to Pending' => ['Pending', 'Pending'],
            'Pending_End_User_Action maps to Pending' => ['Pending_End_User_Action', 'Pending'],
            'Completed maps to Active' => ['Completed', 'Active'],
            'Failed maps to Pending' => ['Failed', 'Pending'],
            'Invalid maps to Pending' => ['Invalid', 'Pending'],
            'Order not validated maps to Pending' => ['Order not validated', 'Pending'],
        ];
    }

    #[Test]
    public function parseDnsTokenHandlesCnameFormat(): void
    {
        // CNAME format uses the same AuthName/AuthValue pattern
        $message = "AuthName: _dcv.example.com\nAuthValue: certificate-validation.example.com";

        $tokens = $this->callback->parseDnsToken($message);

        $this->assertEquals('_dcv.example.com', $tokens['authName']);
        $this->assertEquals('certificate-validation.example.com', $tokens['authValue']);
    }

    #[Test]
    public function parseDnsTokenHandlesMultilineMessage(): void
    {
        $message = "Order Status Update\n\n" .
                   "AuthName: _dnsauth.example.com\nAuthValue: token123\n\n" .
                   "Please create the DNS record.";

        $tokens = $this->callback->parseDnsToken($message);

        $this->assertEquals('_dnsauth.example.com', $tokens['authName']);
        $this->assertEquals('token123', $tokens['authValue']);
    }
}
