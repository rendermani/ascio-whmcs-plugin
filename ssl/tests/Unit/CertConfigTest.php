<?php
/**
 * Unit Tests for CertConfig class
 *
 * Tests certificate configuration properties, description generation,
 * and price management.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\ssl\CertConfig;
use ascio\ssl\Prices;

require_once __DIR__ . '/bootstrap.php';

class CertConfigTest extends TestCase
{
    private CertConfig $certConfig;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test CertConfig with known values
        $params = (object) [
            'id' => 'test_cert',
            'name' => 'Test Certificate',
            'type' => 'DV',
            'vendor' => 'Comodo',
            'wildcard' => false,
            'multiDomain' => true,
            'maxSans' => 10,
            'freeSans' => 2,
        ];

        $this->certConfig = new CertConfig($params);
    }

    #[Test]
    public function constructorSetsPropertiesFromParams(): void
    {
        $this->assertEquals('test_cert', $this->certConfig->id);
        $this->assertEquals('Test Certificate', $this->certConfig->name);
        $this->assertEquals('DV', $this->certConfig->type);
        $this->assertEquals('Comodo', $this->certConfig->vendor);
        $this->assertFalse($this->certConfig->wildcard);
        $this->assertTrue($this->certConfig->multiDomain);
        $this->assertEquals(10, $this->certConfig->maxSans);
        $this->assertEquals(2, $this->certConfig->freeSans);
    }

    #[Test]
    public function getDescriptionIncludesTypeDescription(): void
    {
        $description = $this->certConfig->getDescription();

        $this->assertStringContainsString('Domain Verification', $description);
    }

    #[Test]
    public function getDescriptionIncludesMultiDomainInfo(): void
    {
        $description = $this->certConfig->getDescription();

        $this->assertStringContainsString('Multi-Domain', $description);
    }

    #[Test]
    public function getDescriptionIncludesSansInfo(): void
    {
        $description = $this->certConfig->getDescription();

        $this->assertStringContainsString('Up to 10 SANs', $description);
        $this->assertStringContainsString('2 SANs included', $description);
    }

    #[Test]
    public function getTypeDescriptionReturnsCorrectTextForDV(): void
    {
        $typeDesc = $this->certConfig->getTypeDescription();

        $this->assertEquals('Domain Verification', $typeDesc);
    }

    #[Test]
    #[DataProvider('typeDescriptionProvider')]
    public function getTypeDescriptionReturnsCorrectText(string $type, string $expectedDescription): void
    {
        $params = (object) [
            'id' => 'test',
            'name' => 'Test',
            'type' => $type,
        ];

        $config = new CertConfig($params);
        $this->assertEquals($expectedDescription, $config->getTypeDescription());
    }

    public static function typeDescriptionProvider(): array
    {
        return [
            'DV type' => ['DV', 'Domain Verification'],
            'OV type' => ['OV', 'Organisation Verification'],
            'EV type' => ['EV', 'Extended Verification'],
        ];
    }

    #[Test]
    public function getPricesReturnsEmptyPricesObject(): void
    {
        $prices = $this->certConfig->getPrices();

        $this->assertInstanceOf(Prices::class, $prices);
    }

    #[Test]
    public function addPriceStoresPriceCorrectly(): void
    {
        $this->certConfig->addPrice(1, 49.99);
        $this->certConfig->addPrice(2, 89.99);
        $this->certConfig->addPrice(3, 129.99);

        $prices = $this->certConfig->getPrices();
        $priceArray = $prices->get();

        $this->assertEquals(49.99, $priceArray['annually']);
        $this->assertEquals(89.99, $priceArray['biennially']);
        $this->assertEquals(129.99, $priceArray['triennially']);
    }

    #[Test]
    public function cloneCreatesNewPricesObject(): void
    {
        $this->certConfig->addPrice(1, 99.99);

        $cloned = clone $this->certConfig;
        $cloned->addPrice(1, 199.99);

        // Original should still have original price
        $originalPrices = $this->certConfig->getPrices()->get();
        $this->assertEquals(99.99, $originalPrices['annually']);

        // Cloned should have new price
        $clonedPrices = $cloned->getPrices()->get();
        $this->assertEquals(199.99, $clonedPrices['annually']);
    }
}
