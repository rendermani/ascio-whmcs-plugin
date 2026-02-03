<?php
/**
 * Unit Tests for Prices class
 *
 * Tests pricing calculations, margin application,
 * and price rounding.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ascio\ssl\Prices;

require_once __DIR__ . '/bootstrap.php';

class PricesTest extends TestCase
{
    private Prices $prices;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prices = new Prices();
    }

    #[Test]
    public function addStoresAnnualPrice(): void
    {
        $this->prices->add(1, 49.99);

        $priceArray = $this->prices->get();
        $this->assertEquals(49.99, $priceArray['annually']);
    }

    #[Test]
    public function addStoresBiennialPrice(): void
    {
        $this->prices->add(2, 89.99);

        $priceArray = $this->prices->get();
        $this->assertEquals(89.99, $priceArray['biennially']);
    }

    #[Test]
    public function addStoresTriennialPrice(): void
    {
        $this->prices->add(3, 129.99);

        $priceArray = $this->prices->get();
        $this->assertEquals(129.99, $priceArray['triennially']);
    }

    #[Test]
    public function getReturnsMinusOneForUnavailablePeriods(): void
    {
        // Only set annual price
        $this->prices->add(1, 49.99);

        $priceArray = $this->prices->get();

        $this->assertEquals(-1, $priceArray['monthly']);
        $this->assertEquals(-1, $priceArray['quarterly']);
        $this->assertEquals(-1, $priceArray['semiannually']);
    }

    #[Test]
    public function getConvertsZeroPriceToMinusOne(): void
    {
        $this->prices->add(1, 0);

        $priceArray = $this->prices->get();

        $this->assertEquals(-1, $priceArray['annually']);
    }

    #[Test]
    public function calculateAppliesMarginCorrectly(): void
    {
        $this->prices->add(1, 100);

        // Apply 20% margin
        $this->prices->calculate(20, 1);

        $priceArray = $this->prices->get();

        // 100 + 20% = 120
        $this->assertEquals(120, $priceArray['annually']);
    }

    #[Test]
    public function calculateAppliesRoundingCorrectly(): void
    {
        $this->prices->add(1, 100);

        // Apply 15% margin with rounding to nearest 10
        $this->prices->calculate(15, 10);

        $priceArray = $this->prices->get();

        // 100 + 15% = 115, rounded up to nearest 10 = 120
        $this->assertEquals(120, $priceArray['annually']);
    }

    #[Test]
    public function calculatePreservesMinusOneForUnavailablePrices(): void
    {
        // Only set annual price
        $this->prices->add(1, 100);

        $this->prices->calculate(20, 1);

        $priceArray = $this->prices->get();

        // Unset prices should remain -1
        $this->assertEquals(-1, $priceArray['biennially']);
        $this->assertEquals(-1, $priceArray['triennially']);
    }

    #[Test]
    public function calculateWithZeroMarginReturnsOriginalPrice(): void
    {
        $this->prices->add(1, 50);

        $this->prices->calculate(0, 1);

        $priceArray = $this->prices->get();
        $this->assertEquals(50, $priceArray['annually']);
    }

    #[Test]
    public function calculateWithNullValuesUsesDefaults(): void
    {
        $this->prices->add(1, 100);

        // Both null should use defaults (0 margin, 1 rounding)
        $this->prices->calculate(null, null);

        $priceArray = $this->prices->get();
        $this->assertEquals(100, $priceArray['annually']);
    }

    #[Test]
    public function addStoresOldPriceForRecalculation(): void
    {
        $this->prices->add(1, 100);

        // First calculation
        $this->prices->calculate(50, 1);
        $firstCalc = $this->prices->get()['annually'];

        // Second calculation should use original price, not calculated price
        $this->prices->calculate(100, 1);
        $secondCalc = $this->prices->get()['annually'];

        // 100 + 50% = 150
        $this->assertEquals(150, $firstCalc);

        // 100 + 100% = 200 (not 150 + 100% = 300)
        $this->assertEquals(200, $secondCalc);
    }
}
