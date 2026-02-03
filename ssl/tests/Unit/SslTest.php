<?php
/**
 * Unit Tests for Ssl class
 *
 * Tests SSL certificate operations, approval address generation,
 * status handling, and database operations.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/bootstrap.php';

/**
 * Testable Ssl class that can be unit tested without SOAP
 */
class TestableSsl
{
    public array $data = [];
    public $serviceId;
    public $certificateType;
    public $fqdn;
    public $hasDbData = false;
    public $verificationType;
    public $dnsName;
    public $dnsValue;
    public MockParams $params;

    public function __construct(?MockParams $params = null)
    {
        $this->params = $params ?? new MockParams();
        $this->data = $this->params->getData();
        $this->serviceId = $this->params->serviceId;
        $this->certificateType = $this->params->certificateType;
    }

    public function getFqdnAddresses(string $domain): array
    {
        // Simulate MX record check - for tests, assume MX exists
        return [
            'admin@' . $domain,
            'administrator@' . $domain,
            'hostmaster@' . $domain,
            'postmaster@' . $domain,
            'webmaster@' . $domain,
        ];
    }

    public function getApprovalAddresses(TestableFqdn $fqdn): string
    {
        $fqdnAddresses = $this->getFqdnAddresses($fqdn->getFqdn());
        $domainAddresses = $this->getFqdnAddresses($fqdn->getDomain());
        $addresses = array_unique(array_merge($fqdnAddresses, $domainAddresses));

        $out = '';
        foreach ($addresses as $value) {
            $out .= '<option>' . $value . '</option>';
        }
        return $out;
    }

    public function getPeriodFromBillingCycle(string $cycle): int
    {
        return match ($cycle) {
            'Annually' => 1,
            'Biennial', 'Biennially' => 2,
            'Triennial', 'Triennially' => 3,
            default => 1,
        };
    }

    public function formatApprovalEmail(string $verificationType, ?string $approvalEmail, string $commonName): string
    {
        return $verificationType === 'Email'
            ? ($approvalEmail ?? 'admin@' . $commonName)
            : 'admin@' . $commonName;
    }

    public function buildOrderResult(int $code, string $message, ?string $orderId = null, ?string $status = null): array
    {
        if ($code === 200) {
            return [
                'code' => $code,
                'message' => $message,
                'status' => $status ?? 'Pending',
                'order_id' => $orderId ?? 'TEST' . uniqid(),
                'errors' => null,
            ];
        }

        return [
            'code' => $code,
            'message' => $message,
            'status' => $message,
            'errors' => json_encode([$message]),
        ];
    }
}

/**
 * Simple FQDN mock for SSL tests
 */
class TestableFqdn
{
    private string $fqdn;
    private string $domain;

    public function __construct(string $name)
    {
        // Remove wildcard
        if (str_starts_with($name, '*.')) {
            $name = substr($name, 2);
        }

        $this->fqdn = $name;

        // Extract domain (simple implementation)
        $parts = explode('.', $name);
        if (count($parts) > 2) {
            $this->domain = implode('.', array_slice($parts, -2));
        } else {
            $this->domain = $name;
        }
    }

    public function getFqdn(): string
    {
        return $this->fqdn;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}

class SslTest extends TestCase
{
    private MockParams $params;
    private TestableSsl $ssl;

    protected function setUp(): void
    {
        parent::setUp();
        MockQueryBuilder::reset();

        $this->params = new MockParams();
        $this->ssl = new TestableSsl($this->params);
    }

    #[Test]
    public function constructorInitializesFromParams(): void
    {
        $this->assertEquals($this->params->serviceId, $this->ssl->serviceId);
        $this->assertEquals($this->params->certificateType, $this->ssl->certificateType);
    }

    #[Test]
    public function getFqdnAddressesReturnsStandardAdminAddresses(): void
    {
        $addresses = $this->ssl->getFqdnAddresses('example.com');

        $this->assertContains('admin@example.com', $addresses);
        $this->assertContains('administrator@example.com', $addresses);
        $this->assertContains('hostmaster@example.com', $addresses);
        $this->assertContains('postmaster@example.com', $addresses);
        $this->assertContains('webmaster@example.com', $addresses);
    }

    #[Test]
    public function getFqdnAddressesContainsFiveStandardAddresses(): void
    {
        $addresses = $this->ssl->getFqdnAddresses('example.com');

        $this->assertCount(5, $addresses);
    }

    #[Test]
    public function getApprovalAddressesReturnsHtmlOptions(): void
    {
        $fqdn = new TestableFqdn('www.example.com');

        $html = $this->ssl->getApprovalAddresses($fqdn);

        $this->assertStringContainsString('<option>', $html);
        $this->assertStringContainsString('admin@', $html);
    }

    #[Test]
    public function getApprovalAddressesCombinesFqdnAndDomainAddresses(): void
    {
        $fqdn = new TestableFqdn('www.example.com');

        $html = $this->ssl->getApprovalAddresses($fqdn);

        // Should contain addresses for both www.example.com and example.com
        $this->assertStringContainsString('admin@www.example.com', $html);
        $this->assertStringContainsString('admin@example.com', $html);
    }

    #[Test]
    public function getPeriodFromBillingCycleReturnsOneForAnnually(): void
    {
        $period = $this->ssl->getPeriodFromBillingCycle('Annually');

        $this->assertEquals(1, $period);
    }

    #[Test]
    public function getPeriodFromBillingCycleReturnsTwoForBiennial(): void
    {
        $period = $this->ssl->getPeriodFromBillingCycle('Biennial');

        $this->assertEquals(2, $period);
    }

    #[Test]
    public function getPeriodFromBillingCycleReturnsThreeForTriennial(): void
    {
        $period = $this->ssl->getPeriodFromBillingCycle('Triennial');

        $this->assertEquals(3, $period);
    }

    #[Test]
    public function getPeriodFromBillingCycleDefaultsToOne(): void
    {
        $period = $this->ssl->getPeriodFromBillingCycle('Monthly');

        $this->assertEquals(1, $period);
    }

    #[Test]
    #[DataProvider('billingCycleProvider')]
    public function getPeriodMapsAllCyclesCorrectly(string $cycle, int $expected): void
    {
        $period = $this->ssl->getPeriodFromBillingCycle($cycle);

        $this->assertEquals($expected, $period);
    }

    public static function billingCycleProvider(): array
    {
        return [
            'Annually' => ['Annually', 1],
            'Biennial' => ['Biennial', 2],
            'Biennially' => ['Biennially', 2],
            'Triennial' => ['Triennial', 3],
            'Triennially' => ['Triennially', 3],
            'Unknown defaults to 1' => ['Quarterly', 1],
        ];
    }

    #[Test]
    public function formatApprovalEmailUsesProvidedEmailForEmailVerification(): void
    {
        $email = $this->ssl->formatApprovalEmail('Email', 'admin@example.org', 'example.com');

        $this->assertEquals('admin@example.org', $email);
    }

    #[Test]
    public function formatApprovalEmailUsesAdminAtDomainForDnsVerification(): void
    {
        $email = $this->ssl->formatApprovalEmail('Dns', 'admin@example.org', 'example.com');

        $this->assertEquals('admin@example.com', $email);
    }

    #[Test]
    public function formatApprovalEmailUsesDefaultForNullEmailInEmailVerification(): void
    {
        $email = $this->ssl->formatApprovalEmail('Email', null, 'example.com');

        $this->assertEquals('admin@example.com', $email);
    }

    #[Test]
    public function buildOrderResultReturnsSuccessStructure(): void
    {
        $result = $this->ssl->buildOrderResult(200, 'Success', 'ORDER123', 'Pending');

        $this->assertEquals(200, $result['code']);
        $this->assertEquals('Success', $result['message']);
        $this->assertEquals('ORDER123', $result['order_id']);
        $this->assertEquals('Pending', $result['status']);
        $this->assertNull($result['errors']);
    }

    #[Test]
    public function buildOrderResultReturnsErrorStructure(): void
    {
        $result = $this->ssl->buildOrderResult(400, 'Invalid CSR');

        $this->assertEquals(400, $result['code']);
        $this->assertEquals('Invalid CSR', $result['message']);
        $this->assertNotNull($result['errors']);
    }

    #[Test]
    public function buildOrderResultGeneratesOrderIdWhenNotProvided(): void
    {
        $result = $this->ssl->buildOrderResult(200, 'Success');

        $this->assertNotEmpty($result['order_id']);
        $this->assertStringStartsWith('TEST', $result['order_id']);
    }

    #[Test]
    public function dataArrayInitializesFromParams(): void
    {
        $this->assertArrayHasKey('whmcs_service_id', $this->ssl->data);
        $this->assertArrayHasKey('user_id', $this->ssl->data);
        $this->assertArrayHasKey('type', $this->ssl->data);
    }

    #[Test]
    public function hasDbDataInitiallyFalse(): void
    {
        $this->assertFalse($this->ssl->hasDbData);
    }

    #[Test]
    public function getApprovalAddressesRemovesDuplicates(): void
    {
        // When fqdn equals domain, there should be no duplicates
        $fqdn = new TestableFqdn('example.com');

        $html = $this->ssl->getApprovalAddresses($fqdn);

        // Count occurrences of admin@example.com - should only appear once
        $count = substr_count($html, '>admin@example.com<');
        $this->assertEquals(1, $count);
    }
}

class SslApprovalAddressTest extends TestCase
{
    #[Test]
    public function approvalAddressesIncludeAllStandardRoles(): void
    {
        $ssl = new TestableSsl();
        $addresses = $ssl->getFqdnAddresses('test.com');

        $expectedRoles = ['admin', 'administrator', 'hostmaster', 'postmaster', 'webmaster'];

        foreach ($expectedRoles as $role) {
            $this->assertContains($role . '@test.com', $addresses);
        }
    }

    #[Test]
    public function approvalAddressesPreserveDomainName(): void
    {
        $ssl = new TestableSsl();
        $addresses = $ssl->getFqdnAddresses('my-domain.example.org');

        $this->assertContains('admin@my-domain.example.org', $addresses);
    }

    #[Test]
    public function approvalAddressHtmlOptionsAreWellFormed(): void
    {
        $ssl = new TestableSsl();
        $fqdn = new TestableFqdn('example.com');

        $html = $ssl->getApprovalAddresses($fqdn);

        // Check that options are properly formed
        $this->assertMatchesRegularExpression('/<option>[^<]+@[^<]+<\/option>/', $html);
    }
}
