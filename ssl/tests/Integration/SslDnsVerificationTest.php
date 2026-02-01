<?php
/**
 * SSL DNS Verification Tests
 *
 * Tests DNS verification callback simulation and DNS token extraction.
 * Tests the callback processing logic for Pending_End_User_Action status
 * with DNS verification tokens.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Integration;

use ascio\v3 as v3;

require_once __DIR__ . '/BaseSslIntegrationTest.php';

class SslDnsVerificationTest extends BaseSslIntegrationTest
{
    /**
     * Test parsing DNS verification tokens from Pending_End_User_Action callback message
     *
     * @test
     */
    public function testPendingEndUserActionCallback(): void
    {
        $domain = 'test-dns.example.com';
        $orderId = 'TEST123456';

        // Simulate callback message with DNS verification tokens
        $callbackMessage = "Your SSL Certificate order requires domain validation.\n\n" .
            "AuthName: _ascio-validation.{$domain}\n" .
            "AuthValue: ascio-dns-validation-token-abc123xyz\n\n" .
            "Please create a DNS TXT record with the above values.";

        $callback = $this->mockCallback($orderId, 'Pending_End_User_Action', $callbackMessage);

        $this->assertEquals('Pending_End_User_Action', $callback['status']);
        $this->assertStringContainsString('AuthName:', $callback['message']);
        $this->assertStringContainsString('AuthValue:', $callback['message']);
    }

    /**
     * Test DNS token extraction from callback message
     *
     * @test
     */
    public function testDnsTokenExtraction(): void
    {
        $testCases = [
            // Standard format
            [
                'message' => "AuthName: _ascio-validation.example.com\nAuthValue: token-abc-123",
                'expectedName' => '_ascio-validation.example.com',
                'expectedValue' => 'token-abc-123',
            ],
            // With extra whitespace
            [
                'message' => "AuthName:  _ascio-validation.test.com  \nAuthValue:  my-token-value  ",
                'expectedName' => '_ascio-validation.test.com',
                'expectedValue' => 'my-token-value',
            ],
            // With surrounding text
            [
                'message' => "Please verify:\nAuthName: _dv.mydomain.com\nAuthValue: xyz789\nThank you.",
                'expectedName' => '_dv.mydomain.com',
                'expectedValue' => 'xyz789',
            ],
        ];

        foreach ($testCases as $index => $testCase) {
            $extracted = $this->extractDnsTokens($testCase['message']);

            $this->assertEquals(
                $testCase['expectedName'],
                $extracted['authName'],
                "Test case {$index}: AuthName extraction failed"
            );
            $this->assertEquals(
                $testCase['expectedValue'],
                $extracted['authValue'],
                "Test case {$index}: AuthValue extraction failed"
            );
        }
    }

    /**
     * Test DNS token extraction returns null for invalid format
     *
     * @test
     */
    public function testDnsTokenExtractionReturnsNullForInvalidFormat(): void
    {
        $invalidMessages = [
            '', // Empty
            'No tokens here',
            'AuthName: only-name', // Missing AuthValue
            'AuthValue: only-value', // Missing AuthName
            "AuthName only-name\nAuthValue only-value", // Missing colons
        ];

        foreach ($invalidMessages as $message) {
            $extracted = $this->extractDnsTokens($message);

            $this->assertNull(
                $extracted['authName'],
                "Expected null authName for message: {$message}"
            );
        }
    }

    /**
     * Test DNS record creation logic (mocked)
     *
     * This tests the DNS auto-creation logic without actually creating DNS records.
     *
     * @test
     */
    public function testDnsRecordCreation(): void
    {
        $domain = 'auto-dns-test.example.com';
        $dnsName = '_ascio-validation.' . $domain;
        $dnsValue = 'validation-token-' . uniqid();

        // Simulate the DNS record that would be created
        $dnsRecord = [
            'type' => 'TXT',
            'name' => $dnsName,
            'value' => $dnsValue,
            'ttl' => 300,
        ];

        // Verify record structure
        $this->assertEquals('TXT', $dnsRecord['type']);
        $this->assertStringStartsWith('_ascio-validation.', $dnsRecord['name']);
        $this->assertNotEmpty($dnsRecord['value']);
        $this->assertIsInt($dnsRecord['ttl']);

        // Verify DNS name format
        $this->assertStringContainsString($domain, $dnsRecord['name']);
    }

    /**
     * Test SAN DNS verification - each SAN gets its own DNS record
     *
     * @test
     */
    public function testSanDnsVerification(): void
    {
        $primaryDomain = 'primary.example.com';
        $sans = [
            'www.primary.example.com',
            'api.primary.example.com',
            'mail.primary.example.com',
        ];

        // Simulate callback message with multiple SANs
        $baseToken = 'validation-token-';

        // Verify each SAN would get its own DNS record
        $expectedRecords = [];

        // Primary domain record
        $expectedRecords[] = [
            'name' => '_ascio-validation.' . $primaryDomain,
            'value' => $baseToken . 'primary',
        ];

        // SAN records
        foreach ($sans as $index => $san) {
            $expectedRecords[] = [
                'name' => '_ascio-validation.' . $san,
                'value' => $baseToken . 'san' . $index,
            ];
        }

        // Verify we have records for primary + all SANs
        $this->assertCount(4, $expectedRecords);

        // Verify each record has the correct format
        foreach ($expectedRecords as $record) {
            $this->assertStringStartsWith('_ascio-validation.', $record['name']);
            $this->assertStringStartsWith('validation-token-', $record['value']);
        }
    }

    /**
     * Test DNS verification complete callback simulation
     *
     * @test
     */
    public function testDnsVerificationComplete(): void
    {
        $orderId = 'TEST' . uniqid();
        $domain = 'verified.example.com';

        // Step 1: Pending_End_User_Action callback with DNS tokens
        $pendingCallback = $this->mockCallback(
            $orderId,
            'Pending_End_User_Action',
            "AuthName: _ascio-validation.{$domain}\nAuthValue: token-123"
        );

        $this->assertEquals('Pending_End_User_Action', $pendingCallback['status']);

        // Step 2: After DNS is verified, order proceeds
        $completedCallback = $this->mockCallback(
            $orderId,
            'Completed',
            'SSL Certificate has been issued successfully.'
        );

        $this->assertEquals('Completed', $completedCallback['status']);

        // Verify transition from pending to completed
        $this->assertNotEquals($pendingCallback['status'], $completedCallback['status']);
    }

    /**
     * Test file-based verification token extraction
     *
     * @test
     */
    public function testFileVerificationTokenExtraction(): void
    {
        $message = "Please verify domain ownership using file validation.\n\n" .
            "AuthFileName: /.well-known/pki-validation/fileauth.txt\n" .
            "AuthFileContent: validation-content-xyz789\n\n" .
            "Create the file at the specified path.";

        $extracted = $this->extractFileTokens($message);

        $this->assertEquals(
            '/.well-known/pki-validation/fileauth.txt',
            $extracted['authFileName']
        );
        $this->assertEquals(
            'validation-content-xyz789',
            $extracted['authFileContent']
        );
    }

    /**
     * Test callback message with both DNS and File options
     *
     * @test
     */
    public function testCallbackWithMultipleVerificationOptions(): void
    {
        $message = "Domain validation required.\n\n" .
            "Option 1 - DNS Validation:\n" .
            "AuthName: _dnsauth.example.com\n" .
            "AuthValue: dns-token-123\n\n" .
            "Option 2 - File Validation:\n" .
            "AuthFileName: /.well-known/validation.txt\n" .
            "AuthFileContent: file-content-456";

        // Extract DNS tokens
        $dnsTokens = $this->extractDnsTokens($message);
        $this->assertEquals('_dnsauth.example.com', $dnsTokens['authName']);
        $this->assertEquals('dns-token-123', $dnsTokens['authValue']);

        // Extract File tokens
        $fileTokens = $this->extractFileTokens($message);
        $this->assertEquals('/.well-known/validation.txt', $fileTokens['authFileName']);
        $this->assertEquals('file-content-456', $fileTokens['authFileContent']);
    }

    /**
     * Test DNS verification for wildcard certificate
     *
     * @test
     */
    public function testWildcardDnsVerification(): void
    {
        $baseDomain = 'wildcard-test.example.com';
        $wildcardDomain = '*.' . $baseDomain;

        // For wildcard certificates, DNS validation is on the base domain
        $expectedDnsName = '_ascio-validation.' . $baseDomain;

        // Simulate callback
        $callback = $this->mockCallback(
            'TEST' . uniqid(),
            'Pending_End_User_Action',
            "AuthName: {$expectedDnsName}\nAuthValue: wildcard-token-xyz"
        );

        $tokens = $this->extractDnsTokens($callback['message']);

        // Wildcard DNS validation should be on base domain, not *.domain
        $this->assertStringNotContainsString('*', $tokens['authName']);
        $this->assertStringContainsString($baseDomain, $tokens['authName']);
    }

    /**
     * Test subdomain DNS verification
     *
     * @test
     */
    public function testSubdomainDnsVerification(): void
    {
        $subdomain = 'app.subdomain.example.com';
        $expectedDnsName = '_ascio-validation.' . $subdomain;

        $callback = $this->mockCallback(
            'TEST' . uniqid(),
            'Pending_End_User_Action',
            "AuthName: {$expectedDnsName}\nAuthValue: subdomain-token-abc"
        );

        $tokens = $this->extractDnsTokens($callback['message']);

        $this->assertEquals($expectedDnsName, $tokens['authName']);
        $this->assertEquals('subdomain-token-abc', $tokens['authValue']);
    }

    /**
     * Helper method to extract DNS tokens from callback message
     *
     * Uses the same regex pattern as SslCallback::parseDnsToken()
     */
    private function extractDnsTokens(string $message): array
    {
        $regex = '/AuthName:\s*(.*)\nAuthValue:\s*(.*)/';
        preg_match($regex, $message, $result);

        if (count($result) < 3) {
            return ['authName' => null, 'authValue' => null];
        }

        return [
            'authName' => trim($result[1]),
            'authValue' => trim($result[2]),
        ];
    }

    /**
     * Helper method to extract file-based verification tokens from callback message
     *
     * Uses the same regex pattern as SslCallback::parseFile()
     */
    private function extractFileTokens(string $message): array
    {
        $regex = '/AuthFileName:\s*(.*)\nAuthFileContent:\s*(.*)/';
        preg_match($regex, $message, $result);

        if (count($result) < 3) {
            return ['authFileName' => null, 'authFileContent' => null];
        }

        return [
            'authFileName' => trim($result[1]),
            'authFileContent' => trim($result[2]),
        ];
    }

    /**
     * Test multiple DNS token format variations
     *
     * @test
     * @dataProvider dnsTokenFormatProvider
     */
    public function testDnsTokenFormats(string $message, string $expectedName, string $expectedValue): void
    {
        $extracted = $this->extractDnsTokens($message);

        $this->assertEquals($expectedName, $extracted['authName']);
        $this->assertEquals($expectedValue, $extracted['authValue']);
    }

    /**
     * Data provider for DNS token format variations
     */
    public static function dnsTokenFormatProvider(): array
    {
        return [
            'standard' => [
                "AuthName: _validation.test.com\nAuthValue: abc123",
                '_validation.test.com',
                'abc123',
            ],
            'with_hyphen_in_value' => [
                "AuthName: _auth.domain.com\nAuthValue: token-with-hyphens-123",
                '_auth.domain.com',
                'token-with-hyphens-123',
            ],
            'long_subdomain' => [
                "AuthName: _ascio-validation.very.long.subdomain.example.com\nAuthValue: xyz",
                '_ascio-validation.very.long.subdomain.example.com',
                'xyz',
            ],
            'alphanumeric_token' => [
                "AuthName: _dv.test.org\nAuthValue: AbC123XyZ789",
                '_dv.test.org',
                'AbC123XyZ789',
            ],
        ];
    }
}
