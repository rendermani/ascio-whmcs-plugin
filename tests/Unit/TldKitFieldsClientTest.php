<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ascio\TldKitFieldsClient;

/**
 * Unit tests for TldKitFieldsClient (TLD Rules API client)
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
    public function constructorAcceptsLocalUrl(): void
    {
        $client = new TldKitFieldsClient('http://aws-local.ascio.loc');
        $this->assertInstanceOf(TldKitFieldsClient::class, $client);
        $this->assertEquals('http://aws-local.ascio.loc', $client->getBaseUrl());
    }

    #[Test]
    public function constructorAcceptsProductionUrl(): void
    {
        $client = new TldKitFieldsClient('https://aws.ascio.info');
        $this->assertInstanceOf(TldKitFieldsClient::class, $client);
        $this->assertEquals('https://aws.ascio.info', $client->getBaseUrl());
    }

    #[Test]
    public function constructorAcceptsCredentials(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info',
            'testuser',
            'testpass',
            false // production
        );
        $this->assertInstanceOf(TldKitFieldsClient::class, $client);
        $this->assertEquals('production', $client->getEnv());
    }

    #[Test]
    public function constructorAcceptsTestMode(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info',
            'testuser',
            'testpass',
            true // test mode
        );
        $this->assertInstanceOf(TldKitFieldsClient::class, $client);
        $this->assertEquals('testing', $client->getEnv());
    }

    #[Test]
    public function constructorTrimsTrailingSlash(): void
    {
        $client = new TldKitFieldsClient('https://aws.ascio.info/');
        $this->assertEquals('https://aws.ascio.info', $client->getBaseUrl());
    }

    // ========================================================================
    // Constants Tests
    // ========================================================================

    #[Test]
    public function hasLocalHostConstant(): void
    {
        $this->assertEquals('http://aws-local.ascio.loc', TldKitFieldsClient::HOST_LOCAL);
    }

    #[Test]
    public function hasProductionHostConstant(): void
    {
        $this->assertEquals('https://aws.ascio.info', TldKitFieldsClient::HOST_PRODUCTION);
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
        $client = new TldKitFieldsClient('http://127.0.0.1:99999', null, null, false, 500, 2);

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

    #[Test]
    public function fetchFieldsThrowsOnUnreachableUrl(): void
    {
        $client = new TldKitFieldsClient('http://127.0.0.1:99999', null, null, false, 500, 2);

        $this->expectException(\RuntimeException::class);
        $client->fetchFields();
    }

    #[Test]
    public function fetchConditionsThrowsOnUnreachableUrl(): void
    {
        $client = new TldKitFieldsClient('http://127.0.0.1:99999', null, null, false, 500, 2);

        $this->expectException(\RuntimeException::class);
        $client->fetchConditions();
    }

    // ========================================================================
    // URL Building Tests (via reflection to test private method)
    // ========================================================================

    #[Test]
    public function urlContainsAuthParamsWhenCredentialsProvided(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info',
            'myuser',
            'mypass',
            false // production
        );

        // Use reflection to access private buildUrl method
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, '/api/v1/tldkit/fields');

        $this->assertStringContainsString('/api/v1/tldkit/fields', $url);
        $this->assertStringContainsString('username=myuser', $url);
        $this->assertStringContainsString('password=mypass', $url);
        $this->assertStringContainsString('env=production', $url);
    }

    #[Test]
    public function urlContainsTestingEnvWhenTestModeEnabled(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info',
            'myuser',
            'mypass',
            true // test mode
        );

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, '/api/v1/tldkit/tlds');

        $this->assertStringContainsString('env=testing', $url);
    }

    #[Test]
    public function urlOmitsAuthParamsWhenNoCredentials(): void
    {
        $client = new TldKitFieldsClient('https://aws.ascio.info');

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, '/api/v1/tldkit/fields');

        $this->assertStringNotContainsString('username=', $url);
        $this->assertStringNotContainsString('password=', $url);
        $this->assertStringNotContainsString('env=', $url);
    }

    #[Test]
    public function urlEncodesSpecialCharactersInCredentials(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info',
            'user@domain.com',
            'pass&word=123',
            false
        );

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, '/api/v1/tldkit/tlds');

        // @ should be encoded as %40, & as %26, = as %3D
        $this->assertStringContainsString('username=user%40domain.com', $url);
        $this->assertStringContainsString('password=pass%26word%3D123', $url);
    }

    #[Test]
    public function urlBuildsCorrectEndpointPath(): void
    {
        $client = new TldKitFieldsClient('https://aws.ascio.info');

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, '/api/v1/tldkit/conditions');

        $this->assertStringStartsWith('https://aws.ascio.info/api/v1/tldkit/conditions', $url);
    }

    #[Test]
    public function urlIncludesExtraParams(): void
    {
        $client = new TldKitFieldsClient(
            'https://aws.ascio.info',
            'user',
            'pass',
            false
        );

        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $url = $method->invoke($client, '/api/v1/tldkit/fields', ['tld' => 'it,de']);

        $this->assertStringContainsString('tld=it%2Cde', $url);
    }
}
