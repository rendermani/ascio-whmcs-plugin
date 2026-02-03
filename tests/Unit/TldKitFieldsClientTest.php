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

    #[Test]
    public function constructorAcceptsCredentials(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info/tldkit.xq',
            'testuser',
            'testpass',
            false // production
        );
        $this->assertInstanceOf(TldKitFieldsClient::class, $client);
    }

    #[Test]
    public function constructorAcceptsTestMode(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info/tldkit.xq',
            'testuser',
            'testpass',
            true // test mode
        );
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
        $client = new TldKitFieldsClient('http://127.0.0.1:99999/nonexistent', null, null, false, 500, 2);

        $this->expectException(\RuntimeException::class);
        $client->fetchAll();
    }

    #[Test]
    public function fetchAllThrowsOnInvalidUrl(): void
    {
        $client = new TldKitFieldsClient('not-a-valid-url', null, null, false, 500, 2);

        $this->expectException(\RuntimeException::class);
        $client->fetchAll();
    }

    // ========================================================================
    // URL Building Tests (via reflection to test private method)
    // ========================================================================

    #[Test]
    public function urlContainsAuthParamsWhenCredentialsProvided(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info/tldkit.xq',
            'myuser',
            'mypass',
            false // production
        );

        // Use reflection to access private buildUrl method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, 1);

        $this->assertStringContainsString('username=myuser', $url);
        $this->assertStringContainsString('password=mypass', $url);
        $this->assertStringContainsString('env=production', $url);
        $this->assertStringContainsString('export=all', $url);
    }

    #[Test]
    public function urlContainsTestingEnvWhenTestModeEnabled(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info/tldkit.xq',
            'myuser',
            'mypass',
            true // test mode
        );

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, 1);

        $this->assertStringContainsString('env=testing', $url);
    }

    #[Test]
    public function urlOmitsAuthParamsWhenNoCredentials(): void
    {
        $client = new TldKitFieldsClient('https://aws.ascio.info/tldkit.xq');

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, 1);

        $this->assertStringNotContainsString('username=', $url);
        $this->assertStringNotContainsString('password=', $url);
        $this->assertStringNotContainsString('env=', $url);
        $this->assertStringContainsString('export=all', $url);
    }

    #[Test]
    public function urlEncodesSpecialCharactersInCredentials(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info/tldkit.xq',
            'user@domain.com',
            'pass&word=123',
            false
        );

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, 1);

        // @ should be encoded as %40, & as %26, = as %3D
        $this->assertStringContainsString('username=user%40domain.com', $url);
        $this->assertStringContainsString('password=pass%26word%3D123', $url);
    }
}
