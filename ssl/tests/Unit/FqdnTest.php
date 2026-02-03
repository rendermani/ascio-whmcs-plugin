<?php
/**
 * Unit Tests for Fqdn class
 *
 * Tests FQDN parsing, domain extraction, wildcard handling,
 * and SSL auth record generation.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\whmcs\ssl\Fqdn;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../lib/Fqdn.php';

class FqdnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset mock data
        MockQueryBuilder::reset();
    }

    #[Test]
    public function constructorParsesSimpleDomain(): void
    {
        $fqdn = new Fqdn('example.com');

        $this->assertEquals('com', $fqdn->tld);
        $this->assertEquals(['example', 'com'], $fqdn->tokens);
        $this->assertFalse($fqdn->isWildCard);
    }

    #[Test]
    public function constructorParsesSubdomain(): void
    {
        $fqdn = new Fqdn('www.example.com');

        $this->assertEquals('com', $fqdn->tld);
        $this->assertEquals(['www', 'example', 'com'], $fqdn->tokens);
    }

    #[Test]
    public function constructorParsesDeepSubdomain(): void
    {
        $fqdn = new Fqdn('api.v2.staging.example.com');

        $this->assertEquals('com', $fqdn->tld);
        $this->assertEquals(['api', 'v2', 'staging', 'example', 'com'], $fqdn->tokens);
    }

    #[Test]
    public function constructorHandlesWildcardDomain(): void
    {
        $fqdn = new Fqdn('*.example.com');

        $this->assertTrue($fqdn->isWildCard);
        $this->assertEquals('example.com', $fqdn->getFqdn());
    }

    #[Test]
    public function constructorHandlesWildcardSubdomain(): void
    {
        $fqdn = new Fqdn('*.subdomain.example.com');

        $this->assertTrue($fqdn->isWildCard);
        $this->assertEquals('subdomain.example.com', $fqdn->getFqdn());
    }

    #[Test]
    public function getFqdnReturnsFullDomainName(): void
    {
        $fqdn = new Fqdn('www.example.com');

        $this->assertEquals('www.example.com', $fqdn->getFqdn());
    }

    #[Test]
    public function getFqdnReturnsSimpleDomain(): void
    {
        $fqdn = new Fqdn('example.com');

        $this->assertEquals('example.com', $fqdn->getFqdn());
    }

    #[Test]
    public function getDomainReturnsBaseDomainFromSubdomain(): void
    {
        $fqdn = new Fqdn('www.example.com');

        // For simple TLDs, should return example.com
        $domain = $fqdn->getDomain();
        $this->assertStringContainsString('example.com', $domain);
    }

    #[Test]
    public function getDomainReturnsSimpleDomain(): void
    {
        $fqdn = new Fqdn('example.com');

        $this->assertEquals('example.com', $fqdn->getDomain());
    }

    #[Test]
    public function getCommonNameReturnsWildcardPrefixedName(): void
    {
        $fqdn = new Fqdn('*.example.com');

        $this->assertEquals('*.example.com', $fqdn->getCommonName());
    }

    #[Test]
    public function getCommonNameReturnsRegularName(): void
    {
        $fqdn = new Fqdn('www.example.com');

        $this->assertEquals('www.example.com', $fqdn->getCommonName());
    }

    #[Test]
    public function getSslAuthReturnsDnsAuthRecordName(): void
    {
        $fqdn = new Fqdn('www.example.com');

        // Non-wildcard should have _dnsauth prefix
        $this->assertEquals('_dnsauth.www.example.com', $fqdn->getSslAuth());
    }

    #[Test]
    public function getSslAuthReturnsPlainNameForWildcard(): void
    {
        $fqdn = new Fqdn('*.example.com');

        // Wildcard should not have _dnsauth prefix
        $this->assertEquals('example.com', $fqdn->getSslAuth());
    }

    #[Test]
    #[DataProvider('tldProvider')]
    public function getTldExtractsCorrectTld(string $domain, string $expectedTld): void
    {
        $fqdn = new Fqdn($domain);

        $this->assertEquals($expectedTld, $fqdn->tld);
    }

    public static function tldProvider(): array
    {
        return [
            'com domain' => ['example.com', 'com'],
            'org domain' => ['example.org', 'org'],
            'net domain' => ['example.net', 'net'],
            'io domain' => ['example.io', 'io'],
            'de domain' => ['example.de', 'de'],
        ];
    }

    #[Test]
    #[DataProvider('subdomainProvider')]
    public function parsesVariousSubdomainLevels(string $domain, int $expectedTokenCount): void
    {
        $fqdn = new Fqdn($domain);

        $this->assertCount($expectedTokenCount, $fqdn->tokens);
    }

    public static function subdomainProvider(): array
    {
        return [
            'no subdomain' => ['example.com', 2],
            'one subdomain' => ['www.example.com', 3],
            'two subdomains' => ['api.www.example.com', 4],
            'three subdomains' => ['v1.api.www.example.com', 5],
        ];
    }

    #[Test]
    public function wildcardStripsAsteriskFromTokens(): void
    {
        $fqdn = new Fqdn('*.example.com');

        // Tokens should not contain asterisk
        $this->assertNotContains('*', $fqdn->tokens);
        $this->assertEquals(['example', 'com'], $fqdn->tokens);
    }

    #[Test]
    public function getDomainWorksForDeepSubdomains(): void
    {
        $fqdn = new Fqdn('mail.server.hosting.example.com');

        $domain = $fqdn->getDomain();
        // Should extract the registrable domain
        $this->assertStringContainsString('example.com', $domain);
    }

    #[Test]
    public function getCommonNamePreservesOriginalFormat(): void
    {
        // Test with wildcard
        $wildcardFqdn = new Fqdn('*.secure.example.com');
        $this->assertEquals('*.secure.example.com', $wildcardFqdn->getCommonName());

        // Test without wildcard
        $regularFqdn = new Fqdn('secure.example.com');
        $this->assertEquals('secure.example.com', $regularFqdn->getCommonName());
    }

    #[Test]
    public function handlesMixedCaseDomains(): void
    {
        // Domain names should be handled (typically case-insensitive in DNS)
        $fqdn = new Fqdn('WWW.Example.COM');

        // The class stores the name as provided
        $this->assertStringContainsString('WWW', $fqdn->getFqdn());
    }

    #[Test]
    public function tokensAreCorrectlyIndexed(): void
    {
        $fqdn = new Fqdn('www.example.com');

        $this->assertEquals('www', $fqdn->tokens[0]);
        $this->assertEquals('example', $fqdn->tokens[1]);
        $this->assertEquals('com', $fqdn->tokens[2]);
    }
}
