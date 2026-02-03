<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ascio\TldKitFieldsClient;

/**
 * Unit tests for TldKitFieldsClient
 *
 * Tests URL building, hash computation, and error handling.
 * Network tests are skipped if the API is not reachable.
 */
class TldKitFieldsClientTest extends TestCase
{
    // ========================================================================
    // Constructor / Configuration Tests
    // ========================================================================

    #[Test]
    public function constructorAcceptsBaseUrl(): void
    {
        $client = new TldKitFieldsClient('http://localhost:8021/exist/apps/aws/tldkit.xq');
        $this->assertInstanceOf(TldKitFieldsClient::class, $client);
    }

    #[Test]
    public function constructorAcceptsProductionUrl(): void
    {
        $client = new TldKitFieldsClient('https://aws.ascio.info');
        $this->assertInstanceOf(TldKitFieldsClient::class, $client);
    }

    // ========================================================================
    // Hash Computation Tests
    // ========================================================================

    #[Test]
    public function computeHashReturnsMd5String(): void
    {
        $client = new TldKitFieldsClient('http://example.com');
        $hash = $client->computeHash(['tld' => '.it', 'fields' => ['test']]);

        $this->assertNotEmpty($hash);
        $this->assertEquals(32, strlen($hash)); // MD5 hash is 32 hex chars
    }

    #[Test]
    public function computeHashIsDeterministic(): void
    {
        $client = new TldKitFieldsClient('http://example.com');
        $data = ['tld' => '.it', 'fields' => ['Registrant.Type']];

        $hash1 = $client->computeHash($data);
        $hash2 = $client->computeHash($data);

        $this->assertEquals($hash1, $hash2);
    }

    #[Test]
    public function computeHashDiffersForDifferentData(): void
    {
        $client = new TldKitFieldsClient('http://example.com');

        $hash1 = $client->computeHash(['tld' => '.it']);
        $hash2 = $client->computeHash(['tld' => '.ca']);

        $this->assertNotEquals($hash1, $hash2);
    }

    // ========================================================================
    // Error Handling Tests
    // ========================================================================

    #[Test]
    public function fetchAllThrowsOnUnreachableUrl(): void
    {
        $client = new TldKitFieldsClient('http://127.0.0.1:99999/nonexistent', 500, 2);

        $this->expectException(\RuntimeException::class);
        $client->fetchAll();
    }

    #[Test]
    public function fetchAllThrowsOnInvalidUrl(): void
    {
        $client = new TldKitFieldsClient('not-a-valid-url', 500, 2);

        $this->expectException(\RuntimeException::class);
        $client->fetchAll();
    }
}
