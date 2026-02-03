<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;

// Include the PriceImporter file classes without requiring init.php
// We need to define these classes manually for testing since the original file
// has a require_once for init.php which we can't load in tests

/**
 * Unit tests for PriceImporter classes (Product, Tld, PriceImporter)
 *
 * Tests the price import functionality for importing TLD prices
 * from Ascio TldKit API into WHMCS.
 *
 * @covers Product
 * @covers Tld
 * @covers PriceImporter
 */
class PriceImporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
    }

    // =========================================================================
    // Product::getEndcustomerPrice() - Price calculation with margin
    // =========================================================================

    #[Test]
    public function productCalculatesPriceWithZeroMargin(): void
    {
        $tld = $this->createTldWithMargin(0);
        $product = $this->createProduct(10.00, $tld);

        // With 0% margin: ceil(10.00) - 0.1 = 9.9
        $this->assertEquals(9.9, $product->getEndcustomerPrice());
    }

    #[Test]
    public function productCalculatesPriceWithTenPercentMargin(): void
    {
        $tld = $this->createTldWithMargin(10);
        $product = $this->createProduct(10.00, $tld);

        // With 10% margin: 10 + (10 * 0.1) = 11.00, ceil(11) - 0.1 = 10.9
        $this->assertEquals(10.9, $product->getEndcustomerPrice());
    }

    #[Test]
    public function productCalculatesPriceWithTwentyPercentMargin(): void
    {
        $tld = $this->createTldWithMargin(20);
        $product = $this->createProduct(10.00, $tld);

        // With 20% margin: 10 + (10 * 0.2) = 12.00, ceil(12) - 0.1 = 11.9
        $this->assertEquals(11.9, $product->getEndcustomerPrice());
    }

    #[Test]
    public function productCalculatesPriceWithFractionalPrice(): void
    {
        $tld = $this->createTldWithMargin(10);
        $product = $this->createProduct(9.50, $tld);

        // With 10% margin: 9.50 + (9.50 * 0.1) = 10.45, ceil(10.45) - 0.1 = 10.9
        $this->assertEquals(10.9, $product->getEndcustomerPrice());
    }

    #[Test]
    public function productCalculatesPriceWithHighMargin(): void
    {
        $tld = $this->createTldWithMargin(50);
        $product = $this->createProduct(20.00, $tld);

        // With 50% margin: 20 + (20 * 0.5) = 30.00, ceil(30) - 0.1 = 29.9
        $this->assertEquals(29.9, $product->getEndcustomerPrice());
    }

    #[Test]
    #[DataProvider('priceCalculationProvider')]
    public function productPriceCalculationWithDataProvider(float $basePrice, int $margin, float $expectedPrice): void
    {
        $tld = $this->createTldWithMargin($margin);
        $product = $this->createProduct($basePrice, $tld);

        $this->assertEquals($expectedPrice, $product->getEndcustomerPrice());
    }

    public static function priceCalculationProvider(): array
    {
        return [
            'base 10, margin 0%' => [10.00, 0, 9.9],
            'base 10, margin 10%' => [10.00, 10, 10.9],
            'base 10, margin 25%' => [10.00, 25, 12.9], // 10 + 2.5 = 12.5 -> ceil(12.5) - 0.1 = 12.9
            'base 15.50, margin 10%' => [15.50, 10, 17.9], // 15.5 + 1.55 = 17.05 -> ceil(17.05) - 0.1 = 17.9
            'base 99.99, margin 5%' => [99.99, 5, 104.9], // 99.99 + 4.9995 = 104.9895 -> ceil = 105 - 0.1 = 104.9
            'base 1.00, margin 100%' => [1.00, 100, 1.9], // 1 + 1 = 2 -> ceil(2) - 0.1 = 1.9
        ];
    }

    // =========================================================================
    // Product::getWhmcsPeriod() - Period mapping
    // =========================================================================

    #[Test]
    #[DataProvider('whmcsPeriodProvider')]
    public function productMapsPeriodsToWhmcsFormat(int $period, string $expectedWhmcsPeriod): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', 10.00, $period);
        $product = new \Product($productData, $tld);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($product);
        $method = $reflection->getMethod('getWhmcsPeriod');
        $method->setAccessible(true);

        $this->assertEquals($expectedWhmcsPeriod, $method->invoke($product, $period));
    }

    public static function whmcsPeriodProvider(): array
    {
        return [
            'period 0 (maps to 1 internally)' => [0, 'msetupfee'],
            'period 1 - msetupfee' => [1, 'msetupfee'],
            'period 2 - qsetupfee' => [2, 'qsetupfee'],
            'period 3 - ssetupfee' => [3, 'ssetupfee'],
            'period 4 - asetupfee' => [4, 'asetupfee'],
            'period 5 - bsetupfee' => [5, 'bsetupfee'],
            'period 6 - monthly' => [6, 'monthly'],
            'period 7 - quarterly' => [7, 'quarterly'],
            'period 8 - semiannually' => [8, 'semiannually'],
            'period 9 - annually' => [9, 'annually'],
            'period 10 - biennially' => [10, 'biennially'],
            'period 11 - triennially' => [11, 'triennially'],
        ];
    }

    // =========================================================================
    // Product::getWhmcsCommand() - Command mapping
    // =========================================================================

    #[Test]
    #[DataProvider('whmcsCommandProvider')]
    public function productMapsCommandsToWhmcsFormat(string $command, string $expectedWhmcsCommand): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData($command, 10.00, 1);
        $product = new \Product($productData, $tld);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($product);
        $method = $reflection->getMethod('getWhmcsCommand');
        $method->setAccessible(true);

        $this->assertEquals($expectedWhmcsCommand, $method->invoke($product));
    }

    public static function whmcsCommandProvider(): array
    {
        return [
            'REGISTER -> domainregister' => ['REGISTER', 'domainregister'],
            'RENEW -> domainrenew' => ['RENEW', 'domainrenew'],
            'TRANSFER -> domaintransfer' => ['TRANSFER', 'domaintransfer'],
        ];
    }

    // =========================================================================
    // Product::isUsed() - Product usage detection
    // =========================================================================

    #[Test]
    public function productIsUsedForRegisterCommand(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME');
        $product = new \Product($productData, $tld);

        $this->assertTrue($product->isUsed());
    }

    #[Test]
    public function productIsUsedForRenewCommand(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('RENEW', 10.00, 1, 'DOMAINNAME');
        $product = new \Product($productData, $tld);

        $this->assertTrue($product->isUsed());
    }

    #[Test]
    public function productIsUsedForTransferCommand(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('TRANSFER', 10.00, 1, 'DOMAINNAME');
        $product = new \Product($productData, $tld);

        $this->assertTrue($product->isUsed());
    }

    #[Test]
    public function productIsNotUsedForNonDomainObjectType(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', 10.00, 1, 'SSL');
        $product = new \Product($productData, $tld);

        $this->assertNull($product->isUsed());
    }

    #[Test]
    public function productIsNotUsedForDeleteCommand(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('DELETE', 10.00, 1, 'DOMAINNAME');
        $product = new \Product($productData, $tld);

        $this->assertNull($product->isUsed());
    }

    #[Test]
    public function productIsNotUsedWithZeroPrice(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', 0.00, 1, 'DOMAINNAME');
        $product = new \Product($productData, $tld);

        $this->assertNull($product->isUsed());
    }

    // =========================================================================
    // Product::hasPrice() - Price detection
    // =========================================================================

    #[Test]
    public function productHasPriceWhenPriceGreaterThanZero(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', 10.00, 1);
        $product = new \Product($productData, $tld);

        $this->assertTrue($product->hasPrice());
    }

    #[Test]
    public function productHasNoPriceWhenPriceIsZero(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', 0.00, 1);
        $product = new \Product($productData, $tld);

        $this->assertNull($product->hasPrice());
    }

    #[Test]
    public function productHasNoPriceWhenPriceIsNegative(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', -5.00, 1);
        $product = new \Product($productData, $tld);

        $this->assertNull($product->hasPrice());
    }

    // =========================================================================
    // Product::__construct() - Currency mapping
    // =========================================================================

    #[Test]
    public function productMapsCurrencyEURToTwo(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME', 'EUR');
        $product = new \Product($productData, $tld);

        $this->assertEquals(2, $product->currency);
    }

    #[Test]
    public function productMapsCurrencyUSDToOne(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME', 'USD');
        $product = new \Product($productData, $tld);

        $this->assertEquals(1, $product->currency);
    }

    #[Test]
    public function productMapsOtherCurrencyToOne(): void
    {
        $tld = $this->createTldWithMargin(0);
        $productData = $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME', 'GBP');
        $product = new \Product($productData, $tld);

        $this->assertEquals(1, $product->currency);
    }

    // =========================================================================
    // Tld::isActive() - TLD active state detection
    // =========================================================================

    #[Test]
    public function tldIsActiveWhenHasUsableProducts(): void
    {
        $tldData = $this->createTldData('com', [
            $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME'),
            $this->createProductData('RENEW', 12.00, 1, 'DOMAINNAME'),
        ]);

        $tld = new \Tld($tldData, 10);

        $this->assertTrue($tld->isActive());
    }

    #[Test]
    public function tldIsNotActiveWhenNoUsableProducts(): void
    {
        $tldData = $this->createTldData('com', [
            $this->createProductData('DELETE', 0.00, 1, 'DOMAINNAME'),
        ]);

        $tld = new \Tld($tldData, 10);

        $this->assertNull($tld->isActive());
    }

    #[Test]
    public function tldIsNotActiveWhenAllProductsHaveZeroPrice(): void
    {
        $tldData = $this->createTldData('com', [
            $this->createProductData('REGISTER', 0.00, 1, 'DOMAINNAME'),
            $this->createProductData('RENEW', 0.00, 1, 'DOMAINNAME'),
        ]);

        $tld = new \Tld($tldData, 10);

        $this->assertNull($tld->isActive());
    }

    #[Test]
    public function tldIsNotActiveWhenProductsAreNonDomain(): void
    {
        $tldData = $this->createTldData('com', [
            $this->createProductData('REGISTER', 10.00, 1, 'SSL'),
        ]);

        $tld = new \Tld($tldData, 10);

        $this->assertNull($tld->isActive());
    }

    // =========================================================================
    // Tld::__construct() - Constructor tests
    // =========================================================================

    #[Test]
    public function tldConstructorSetsName(): void
    {
        $tldData = $this->createTldData('info', []);
        $tld = new \Tld($tldData, 15);

        $this->assertEquals('info', $tld->name);
    }

    #[Test]
    public function tldConstructorSetsMargin(): void
    {
        $tldData = $this->createTldData('org', []);
        $tld = new \Tld($tldData, 25);

        $this->assertEquals(25, $tld->margin);
    }

    // =========================================================================
    // PriceImporter::getRequestContext() - API context creation
    // =========================================================================

    #[Test]
    public function priceImporterCreatesRequestContextWithBasicAuth(): void
    {
        $importer = new \PriceImporter('testaccount', 'testpassword');

        // Use reflection to access private method
        $reflection = new \ReflectionClass($importer);
        $method = $reflection->getMethod('getRequestContext');
        $method->setAccessible(true);

        $context = $method->invoke($importer);

        // Verify it's a valid stream context resource
        $this->assertIsResource($context);

        // Get the options from the context
        $options = stream_context_get_options($context);

        $this->assertArrayHasKey('http', $options);
        $this->assertEquals('GET', $options['http']['method']);
        $this->assertStringContainsString('Content-Type: application/json', $options['http']['header']);
        $this->assertStringContainsString(
            'Authorization: Basic ' . base64_encode('testaccount:testpassword'),
            $options['http']['header']
        );
        $this->assertEquals(60, $options['http']['timeout']);
    }

    #[Test]
    public function priceImporterSetMarginUpdatesMargin(): void
    {
        $importer = new \PriceImporter('account', 'password');
        $importer->setMargin(30);

        // Use reflection to access private property
        $reflection = new \ReflectionClass($importer);
        $property = $reflection->getProperty('margin');
        $property->setAccessible(true);

        $this->assertEquals(30, $property->getValue($importer));
    }

    #[Test]
    public function priceImporterConstructorSetsCredentials(): void
    {
        $importer = new \PriceImporter('myaccount', 'mypassword');

        $reflection = new \ReflectionClass($importer);

        $accountProperty = $reflection->getProperty('account');
        $accountProperty->setAccessible(true);
        $this->assertEquals('myaccount', $accountProperty->getValue($importer));

        $passwordProperty = $reflection->getProperty('password');
        $passwordProperty->setAccessible(true);
        $this->assertEquals('mypassword', $passwordProperty->getValue($importer));
    }

    // =========================================================================
    // Product::updateWhmcs() - Database update tests
    // =========================================================================

    #[Test]
    public function productUpdateWhmcsSkipsNonUsedProducts(): void
    {
        $tld = $this->createTldWithMargin(10);
        $tld->id = 1;
        $productData = $this->createProductData('DELETE', 10.00, 1, 'DOMAINNAME');
        $product = new \Product($productData, $tld);

        // Should return early without doing anything
        $result = $product->updateWhmcs();
        $this->assertNull($result);
    }

    #[Test]
    public function productUpdateWhmcsReturnsErrorOnMysqlError(): void
    {
        WhmcsFunctionsMock::setMysqlError('Database error');

        $tld = $this->createTldWithMargin(10);
        $tld->id = 1;
        $productData = $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME');
        $product = new \Product($productData, $tld);

        $result = $product->updateWhmcs();

        // Should return the error message
        $this->assertStringContainsString('Database error', $result);
    }

    #[Test]
    public function productUpdateWhmcsInsertsNewRecordWhenNotExists(): void
    {
        // No existing record
        WhmcsFunctionsMock::setQueryResults([]);
        WhmcsFunctionsMock::setMysqlError(null);

        $tld = $this->createTldWithMargin(10);
        $tld->id = 5;
        $productData = $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME', 'USD');
        $product = new \Product($productData, $tld);

        $result = $product->updateWhmcs();

        $this->assertTrue($result);
    }

    #[Test]
    public function productUpdateWhmcsUpdatesExistingRecord(): void
    {
        // Existing record found
        WhmcsFunctionsMock::setQueryResults([
            ['id' => 1, 'relid' => 5, 'type' => 'domainregister', 'currency' => 1]
        ]);
        WhmcsFunctionsMock::setMysqlError(null);

        $tld = $this->createTldWithMargin(10);
        $tld->id = 5;
        $productData = $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME', 'USD');
        $product = new \Product($productData, $tld);

        $result = $product->updateWhmcs();

        $this->assertTrue($result);
    }

    // =========================================================================
    // Tld::updateWhmcs() - TLD database update tests
    // =========================================================================

    #[Test]
    public function tldUpdateWhmcsReturnsFalseWhenNotActive(): void
    {
        $tldData = $this->createTldData('com', [
            $this->createProductData('DELETE', 0.00, 1, 'DOMAINNAME'),
        ]);

        $tld = new \Tld($tldData, 10);

        $this->assertFalse($tld->updateWhmcs());
    }

    #[Test]
    public function tldUpdateWhmcsCreatesNewTldWhenNotExists(): void
    {
        // First query: TLD lookup - no result
        // Product queries will follow
        WhmcsFunctionsMock::setQueryResults([]);
        WhmcsFunctionsMock::setMysqlInsertId(123);
        WhmcsFunctionsMock::setMysqlError(null);

        $tldData = $this->createTldData('newext', [
            $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME'),
        ]);

        $tld = new \Tld($tldData, 10);

        // Capture output
        ob_start();
        $result = $tld->updateWhmcs();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('create tld newext', $output);
        $this->assertEquals(123, $tld->id);
    }

    #[Test]
    public function tldUpdateWhmcsUsesExistingTldId(): void
    {
        // First query: TLD lookup - exists
        WhmcsFunctionsMock::setQueryResults([
            ['id' => 456]
        ]);
        WhmcsFunctionsMock::setMysqlError(null);

        $tldData = $this->createTldData('existingtld', [
            $this->createProductData('REGISTER', 10.00, 1, 'DOMAINNAME'),
        ]);

        $tld = new \Tld($tldData, 10);

        // Capture output
        ob_start();
        $result = $tld->updateWhmcs();
        ob_get_clean();

        $this->assertTrue($result);
        $this->assertEquals(456, $tld->id);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a TLD object with specified margin for testing Product
     */
    private function createTldWithMargin(int $margin): object
    {
        $tld = new \stdClass();
        $tld->margin = $margin;
        $tld->id = 1;
        return $tld;
    }

    /**
     * Create a Product instance with specified price
     */
    private function createProduct(float $price, object $tld): \Product
    {
        $data = new \stdClass();
        $data->Command = 'REGISTER';
        $data->Price = $price;
        $data->Period = 1;
        $data->Currency = 'USD';
        $data->ObjectType = 'DOMAINNAME';

        return new \Product($data, $tld);
    }

    /**
     * Create product data object
     */
    private function createProductData(
        string $command,
        float $price,
        int $period,
        string $objectType = 'DOMAINNAME',
        string $currency = 'USD'
    ): object {
        $data = new \stdClass();
        $data->Command = $command;
        $data->Price = $price;
        $data->Period = $period;
        $data->Currency = $currency;
        $data->ObjectType = $objectType;

        return $data;
    }

    /**
     * Create TLD data object with products
     *
     * Note: The Tld::getProducts() method has a bug where it checks
     * $this->data->products (lowercase) but then returns $this->products
     * if it's truthy. We set products to false to ensure getProducts()
     * actually processes the Products array.
     */
    private function createTldData(string $name, array $products): object
    {
        $data = new \stdClass();
        $data->Name = $name;
        $data->Products = $products;
        // Set lowercase products to false to force getProducts() to process the array
        $data->products = false;

        return $data;
    }
}
