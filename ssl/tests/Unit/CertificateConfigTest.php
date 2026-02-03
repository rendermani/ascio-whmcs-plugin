<?php
/**
 * Unit Tests for CertificateConfig class
 *
 * Tests certificate configuration loading from JSON,
 * certificate lookup by ID and name, and error handling.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ascio\ssl\CertificateConfig;
use ascio\ssl\CertConfig;
use ascio\whmcs\ssl\AscioException;

require_once __DIR__ . '/bootstrap.php';

class CertificateConfigTest extends TestCase
{
    private CertificateConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new CertificateConfig();
    }

    #[Test]
    public function constructorLoadsCertificateDefinitions(): void
    {
        // The constructor should load definitions from cert-def.json
        // This verifies the file exists and is valid JSON
        $this->assertInstanceOf(CertificateConfig::class, $this->config);
    }

    #[Test]
    public function getReturnsValidCertConfigById(): void
    {
        // Get a known certificate by ID (positivessl is common)
        $cert = $this->config->get('positivessl');

        $this->assertInstanceOf(CertConfig::class, $cert);
        $this->assertNotEmpty($cert->name);
    }

    #[Test]
    public function getReturnsValidCertConfigByName(): void
    {
        // First get a cert by ID to know its name
        $certById = $this->config->get('positivessl');
        $certName = $certById->name;

        // Now get it by name
        $certByName = $this->config->get($certName);

        $this->assertInstanceOf(CertConfig::class, $certByName);
        $this->assertEquals($certName, $certByName->name);
    }

    #[Test]
    public function getThrowsExceptionForInvalidCertificate(): void
    {
        $this->expectException(AscioException::class);
        $this->expectExceptionCode(404);

        $this->config->get('nonexistent_certificate_type');
    }

    #[Test]
    public function getCertificateReturnsCorrectType(): void
    {
        $cert = $this->config->get('positivessl');

        // PositiveSSL is typically a DV certificate
        $this->assertContains($cert->type, ['DV', 'OV', 'EV']);
    }
}
