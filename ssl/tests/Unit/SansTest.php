<?php
/**
 * Unit Tests for Sans class
 *
 * Tests Subject Alternative Names handling, approval address
 * generation, and SAN data management.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/bootstrap.php';

/**
 * Testable Sans class for unit testing without database
 */
class TestableSans
{
    public array $data = [];
    public bool $hasDbData = false;
    private ?TestableSslForSans $ssl;
    private int $freeSans;
    private int $paidSans;
    public string $verificationType = 'Dns';

    public function __construct(?TestableSslForSans $ssl = null, int $freeSans = 0, int $paidSans = 0)
    {
        $this->ssl = $ssl;
        $this->freeSans = $freeSans;
        $this->paidSans = $paidSans;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
        $this->hasDbData = !empty($data);
    }

    public function getArray(): array
    {
        $out = [];
        foreach ($this->data as $san) {
            $out[] = $san['name'];
        }
        return $out;
    }

    public function getSansIncluded(): int
    {
        return $this->freeSans;
    }

    public function getTotalSans(): int
    {
        return $this->freeSans + $this->paidSans;
    }

    public function getApprovalAddresses(): string
    {
        if (empty($this->data)) {
            return '';
        }

        $addresses = [];
        foreach ($this->data as $san) {
            if ($this->verificationType === 'Email') {
                $addresses[] = $san['email'] ?? 'admin@' . $san['name'];
            } else {
                $addresses[] = 'admin@' . $san['name'];
            }
        }

        if (!empty($addresses)) {
            return ',' . implode(',', $addresses);
        }

        return '';
    }

    public function validateSanName(string $name): bool
    {
        // Basic validation - must be valid domain format
        if (empty($name)) {
            return false;
        }

        // Check for wildcard
        if (str_starts_with($name, '*.')) {
            $name = substr($name, 2);
        }

        // Simple domain validation
        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+[a-zA-Z0-9]$/', $name) === 1;
    }

    public function buildSanFromForm(int $index, string $name, ?string $email, int $serviceId): array
    {
        $fqdn = new TestableFqdn($name);

        return [
            'name' => $name,
            'email' => $email ?: 'admin@' . $fqdn->getDomain(),
            'service_id' => $serviceId,
        ];
    }
}

/**
 * Mock SSL class for Sans tests
 */
class TestableSslForSans
{
    public int $serviceId = 1;
    public string $verificationType = 'Dns';
    public ?string $dnsName = null;
    public ?string $dnsValue = null;
    public $fqdn;
    public MockParams $params;

    public function __construct()
    {
        $this->params = new MockParams();
        $this->fqdn = new TestableFqdn('primary.example.com');
    }
}

class SansTest extends TestCase
{
    private TestableSans $sans;
    private TestableSslForSans $ssl;

    protected function setUp(): void
    {
        parent::setUp();
        MockQueryBuilder::reset();

        $this->ssl = new TestableSslForSans();
        $this->sans = new TestableSans($this->ssl, 2, 3);
    }

    #[Test]
    public function getArrayReturnsEmptyArrayWhenNoSans(): void
    {
        $result = $this->sans->getArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function getArrayReturnsSanNames(): void
    {
        $this->sans->setData([
            ['name' => 'www.example.com', 'email' => 'admin@example.com'],
            ['name' => 'api.example.com', 'email' => 'admin@example.com'],
        ]);

        $result = $this->sans->getArray();

        $this->assertEquals(['www.example.com', 'api.example.com'], $result);
    }

    #[Test]
    public function getSansIncludedReturnsFreeSansCount(): void
    {
        $this->assertEquals(2, $this->sans->getSansIncluded());
    }

    #[Test]
    public function getTotalSansReturnsCombinedCount(): void
    {
        $this->assertEquals(5, $this->sans->getTotalSans());
    }

    #[Test]
    public function getApprovalAddressesReturnsEmptyForNoSans(): void
    {
        $result = $this->sans->getApprovalAddresses();

        $this->assertEquals('', $result);
    }

    #[Test]
    public function getApprovalAddressesReturnsDnsApprovalEmails(): void
    {
        $this->sans->verificationType = 'Dns';
        $this->sans->setData([
            ['name' => 'www.example.com', 'email' => 'custom@example.com'],
        ]);

        $result = $this->sans->getApprovalAddresses();

        // For DNS verification, uses admin@ prefix
        $this->assertEquals(',admin@www.example.com', $result);
    }

    #[Test]
    public function getApprovalAddressesReturnsEmailVerificationEmails(): void
    {
        $this->sans->verificationType = 'Email';
        $this->sans->setData([
            ['name' => 'www.example.com', 'email' => 'custom@example.com'],
        ]);

        $result = $this->sans->getApprovalAddresses();

        // For Email verification, uses provided email
        $this->assertEquals(',custom@example.com', $result);
    }

    #[Test]
    public function getApprovalAddressesJoinsMultipleSans(): void
    {
        $this->sans->verificationType = 'Dns';
        $this->sans->setData([
            ['name' => 'www.example.com'],
            ['name' => 'api.example.com'],
            ['name' => 'mail.example.com'],
        ]);

        $result = $this->sans->getApprovalAddresses();

        $this->assertEquals(',admin@www.example.com,admin@api.example.com,admin@mail.example.com', $result);
    }

    #[Test]
    public function validateSanNameReturnsTrueForValidDomain(): void
    {
        $this->assertTrue($this->sans->validateSanName('www.example.com'));
        $this->assertTrue($this->sans->validateSanName('api.subdomain.example.com'));
        $this->assertTrue($this->sans->validateSanName('example.com'));
    }

    #[Test]
    public function validateSanNameReturnsTrueForWildcard(): void
    {
        $this->assertTrue($this->sans->validateSanName('*.example.com'));
    }

    #[Test]
    public function validateSanNameReturnsFalseForEmptyString(): void
    {
        $this->assertFalse($this->sans->validateSanName(''));
    }

    #[Test]
    public function validateSanNameReturnsFalseForInvalidDomain(): void
    {
        // Leading hyphen is invalid
        $this->assertFalse($this->sans->validateSanName('-invalid.com'));
        // Trailing hyphen at end of string is invalid
        $this->assertFalse($this->sans->validateSanName('invalid-'));
        // Single character is too short
        $this->assertFalse($this->sans->validateSanName('a'));
    }

    #[Test]
    public function buildSanFromFormCreatesCorrectStructure(): void
    {
        $san = $this->sans->buildSanFromForm(0, 'www.example.com', 'test@example.com', 1);

        $this->assertEquals('www.example.com', $san['name']);
        $this->assertEquals('test@example.com', $san['email']);
        $this->assertEquals(1, $san['service_id']);
    }

    #[Test]
    public function buildSanFromFormUsesDefaultEmailWhenNotProvided(): void
    {
        $san = $this->sans->buildSanFromForm(0, 'www.example.com', null, 1);

        $this->assertEquals('admin@example.com', $san['email']);
    }

    #[Test]
    public function buildSanFromFormUsesEmptyStringEmailWhenNotProvided(): void
    {
        $san = $this->sans->buildSanFromForm(0, 'mail.subdomain.example.org', '', 1);

        $this->assertEquals('admin@example.org', $san['email']);
    }

    #[Test]
    public function hasDbDataIsFalseInitially(): void
    {
        $this->assertFalse($this->sans->hasDbData);
    }

    #[Test]
    public function hasDbDataIsTrueAfterSetData(): void
    {
        $this->sans->setData([['name' => 'test.com']]);

        $this->assertTrue($this->sans->hasDbData);
    }

    #[Test]
    public function setDataStoresDataCorrectly(): void
    {
        $data = [
            ['name' => 'www.example.com', 'email' => 'admin@example.com', 'service_id' => 1],
            ['name' => 'api.example.com', 'email' => 'admin@example.com', 'service_id' => 1],
        ];

        $this->sans->setData($data);

        $this->assertEquals($data, $this->sans->data);
    }

    #[Test]
    #[DataProvider('sanCountProvider')]
    public function getTotalSansCalculatesCorrectly(int $free, int $paid, int $expected): void
    {
        $sans = new TestableSans(null, $free, $paid);

        $this->assertEquals($expected, $sans->getTotalSans());
    }

    public static function sanCountProvider(): array
    {
        return [
            'no sans' => [0, 0, 0],
            'free only' => [3, 0, 3],
            'paid only' => [0, 5, 5],
            'both' => [2, 3, 5],
            'large numbers' => [10, 90, 100],
        ];
    }

    #[Test]
    public function getArrayPreservesOrder(): void
    {
        $this->sans->setData([
            ['name' => 'first.example.com'],
            ['name' => 'second.example.com'],
            ['name' => 'third.example.com'],
        ]);

        $result = $this->sans->getArray();

        $this->assertEquals('first.example.com', $result[0]);
        $this->assertEquals('second.example.com', $result[1]);
        $this->assertEquals('third.example.com', $result[2]);
    }

    #[Test]
    public function emailVerificationUsesCustomEmailWhenAvailable(): void
    {
        $this->sans->verificationType = 'Email';
        $this->sans->setData([
            ['name' => 'www.example.com', 'email' => 'webmaster@example.com'],
        ]);

        $result = $this->sans->getApprovalAddresses();

        $this->assertStringContainsString('webmaster@example.com', $result);
    }

    #[Test]
    public function emailVerificationFallsBackToAdminEmail(): void
    {
        $this->sans->verificationType = 'Email';
        $this->sans->setData([
            ['name' => 'www.example.com'], // no email key
        ]);

        $result = $this->sans->getApprovalAddresses();

        $this->assertStringContainsString('admin@www.example.com', $result);
    }
}
