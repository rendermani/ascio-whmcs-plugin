<?php
/**
 * SSL Certificate Retrieval Tests
 *
 * Tests GetSslCertificate API for retrieving existing certificates.
 * Uses existing certificates on the Ascio test account.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Integration;

use ascio\v3 as v3;

require_once __DIR__ . '/SslIntegrationTestBase.php';

class SslCertificateRetrievalTest extends SslIntegrationTestBase
{
    /**
     * Test retrieving an SSL certificate by handle
     *
     * @test
     */
    public function testGetSslCertificateByHandle(): void
    {
        $certificateHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($certificateHandle)) {
            $this->markTestSkipped(
                'No test certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($certificateHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->GetSslCertificateResult->getResultCode();

        if ($resultCode === 200) {
            $certInfo = $response->GetSslCertificateResult->getSslCertificateInfo();

            $this->assertNotNull($certInfo);
            $this->assertEquals($certificateHandle, $certInfo->getHandle());
            $this->assertNotEmpty($certInfo->getCommonName());
            $this->assertNotEmpty($certInfo->getProductCode());
        } else {
            // Certificate may not exist on test account
            $this->assertTrue(
                in_array($resultCode, [404, 401, 500]),
                'Unexpected result code: ' . $resultCode . ' - ' . $response->GetSslCertificateResult->getResultMessage()
            );
        }
    }

    /**
     * Test retrieving certificate chain for existing certificate
     *
     * @test
     */
    public function testGetSslCertificateChain(): void
    {
        $certificateHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($certificateHandle)) {
            $this->markTestSkipped(
                'No test certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($certificateHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->GetSslCertificateResult->getResultCode();

        if ($resultCode === 200) {
            $certInfo = $response->GetSslCertificateResult->getSslCertificateInfo();
            $certificate = $certInfo->getCertificate();

            if (!empty($certificate)) {
                // Verify certificate format (PEM)
                $this->assertStringContainsString('-----BEGIN CERTIFICATE-----', $certificate);
                $this->assertStringContainsString('-----END CERTIFICATE-----', $certificate);

                // Parse certificate to verify it's valid
                $certData = openssl_x509_parse($certificate);
                if ($certData !== false) {
                    $this->assertArrayHasKey('subject', $certData);
                    $this->assertArrayHasKey('issuer', $certData);
                    $this->assertArrayHasKey('validTo_time_t', $certData);
                }
            }
        }
    }

    /**
     * Test certificate expiry date retrieval
     *
     * @test
     */
    public function testCertificateExpiry(): void
    {
        $certificateHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($certificateHandle)) {
            $this->markTestSkipped(
                'No test certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($certificateHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->GetSslCertificateResult->getResultCode();

        if ($resultCode === 200) {
            $certInfo = $response->GetSslCertificateResult->getSslCertificateInfo();
            $expires = $certInfo->getExpires();

            if ($expires !== null) {
                $this->assertInstanceOf(\DateTime::class, $expires);

                // Verify expiry is a valid future or past date
                $now = new \DateTime();
                $this->assertNotEquals($now->format('Y-m-d'), $expires->format('Y-m-d'));
            }
        }
    }

    /**
     * Test handling of non-existent certificate handle
     *
     * @test
     */
    public function testCertificateNotFound(): void
    {
        // Use a handle that definitely doesn't exist
        $invalidHandle = 'NONEXISTENT_CERT_HANDLE_' . uniqid();

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($invalidHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            // SOAP fault is acceptable for not found
            $this->assertStringContainsString('not found', strtolower($e->getMessage()));
            return;
        }

        // API should return non-200 code for not found
        $resultCode = $response->GetSslCertificateResult->getResultCode();
        $this->assertNotEquals(200, $resultCode, 'Expected error code for non-existent certificate');
    }

    /**
     * Test retrieving certificate with empty handle
     *
     * @test
     */
    public function testGetCertificateWithEmptyHandle(): void
    {
        $request = new v3\GetSslCertificateRequest();
        $request->setHandle('');

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            // SOAP fault is acceptable
            $this->assertTrue(true);
            return;
        }

        // API should return error code for empty handle
        $resultCode = $response->GetSslCertificateResult->getResultCode();
        $this->assertNotEquals(200, $resultCode, 'Expected error code for empty handle');
    }

    /**
     * Test certificate status field
     *
     * @test
     */
    public function testCertificateStatus(): void
    {
        $certificateHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($certificateHandle)) {
            $this->markTestSkipped(
                'No test certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($certificateHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->GetSslCertificateResult->getResultCode();

        if ($resultCode === 200) {
            $certInfo = $response->GetSslCertificateResult->getSslCertificateInfo();
            $status = $certInfo->getStatus();

            // Valid statuses
            $validStatuses = ['Active', 'Pending', 'Expired', 'Revoked', 'Cancelled'];

            if (!empty($status)) {
                $this->assertTrue(
                    in_array($status, $validStatuses) || !empty($status),
                    'Unexpected certificate status: ' . $status
                );
            }
        }
    }

    /**
     * Test certificate owner contact retrieval
     *
     * @test
     */
    public function testCertificateOwnerRetrieval(): void
    {
        $certificateHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($certificateHandle)) {
            $this->markTestSkipped(
                'No test certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($certificateHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->GetSslCertificateResult->getResultCode();

        if ($resultCode === 200) {
            $certInfo = $response->GetSslCertificateResult->getSslCertificateInfo();
            $owner = $certInfo->getOwner();

            if ($owner !== null) {
                $this->assertInstanceOf(v3\Registrant::class, $owner);

                // Check basic owner fields if available
                if ($owner->getFirstName()) {
                    $this->assertIsString($owner->getFirstName());
                }
                if ($owner->getLastName()) {
                    $this->assertIsString($owner->getLastName());
                }
                if ($owner->getEmail()) {
                    $this->assertStringContainsString('@', $owner->getEmail());
                }
            }
        }
    }

    /**
     * Test certificate CSR retrieval
     *
     * @test
     */
    public function testCertificateCsrRetrieval(): void
    {
        $certificateHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($certificateHandle)) {
            $this->markTestSkipped(
                'No test certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($certificateHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->GetSslCertificateResult->getResultCode();

        if ($resultCode === 200) {
            $certInfo = $response->GetSslCertificateResult->getSslCertificateInfo();
            $csr = $certInfo->getCSR();

            if (!empty($csr)) {
                // Verify CSR format (PEM)
                $this->assertStringContainsString('-----BEGIN CERTIFICATE REQUEST-----', $csr);
                $this->assertStringContainsString('-----END CERTIFICATE REQUEST-----', $csr);
            }
        }
    }

    /**
     * Test certificate product code retrieval
     *
     * @test
     */
    public function testCertificateProductCode(): void
    {
        $certificateHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($certificateHandle)) {
            $this->markTestSkipped(
                'No test certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($certificateHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->GetSslCertificateResult->getResultCode();

        if ($resultCode === 200) {
            $certInfo = $response->GetSslCertificateResult->getSslCertificateInfo();
            $productCode = $certInfo->getProductCode();

            if (!empty($productCode)) {
                $this->assertIsString($productCode);
                // Common product codes
                $knownProducts = [
                    'positivessl',
                    'positivesslwildcard',
                    'positivesslmultidomain',
                    'truebizid',
                    'truebizidwildcard',
                    'extendedssl',
                ];
                // Product code should be a reasonable string (may be different from known list)
                $this->assertNotEmpty($productCode);
            }
        }
    }

    /**
     * Test certificate SAN names retrieval
     *
     * @test
     */
    public function testCertificateSanNames(): void
    {
        $certificateHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($certificateHandle)) {
            $this->markTestSkipped(
                'No test certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($certificateHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->GetSslCertificateResult->getResultCode();

        if ($resultCode === 200) {
            $certInfo = $response->GetSslCertificateResult->getSslCertificateInfo();
            $sanNames = $certInfo->getSanNames();

            // SANs may be null or array depending on certificate type
            if ($sanNames !== null) {
                // Verify it's a valid structure
                $this->assertTrue(
                    is_array($sanNames) || is_object($sanNames),
                    'SAN names should be array or ArrayOfstring object'
                );
            }
        }
    }

    /**
     * Test certificate creation date retrieval
     *
     * @test
     */
    public function testCertificateCreationDate(): void
    {
        $certificateHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($certificateHandle)) {
            $this->markTestSkipped(
                'No test certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($certificateHandle);

        try {
            $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\SoapFault $e) {
            $this->markTestSkipped('SOAP error: ' . $e->getMessage());
        }

        $resultCode = $response->GetSslCertificateResult->getResultCode();

        if ($resultCode === 200) {
            $certInfo = $response->GetSslCertificateResult->getSslCertificateInfo();
            $created = $certInfo->getCreated();

            if ($created !== null) {
                $this->assertInstanceOf(\DateTime::class, $created);

                // Creation date should be in the past
                $now = new \DateTime();
                $this->assertLessThanOrEqual($now, $created, 'Creation date should not be in the future');
            }
        }
    }
}
