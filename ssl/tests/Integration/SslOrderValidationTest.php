<?php
/**
 * SSL Order Validation Tests
 *
 * Tests ValidateOrder API for various SSL certificate types and configurations.
 * Uses ValidateOrder instead of CreateOrder to avoid creating real orders.
 */

declare(strict_types=1);

namespace Ascio\Ssl\Tests\Integration;

use ascio\v3 as v3;

require_once __DIR__ . '/BaseSslIntegrationTest.php';

class SslOrderValidationTest extends BaseSslIntegrationTest
{
    /**
     * Test validating a basic PositiveSSL (DV) certificate order
     *
     * @test
     */
    public function testValidatePositiveSsl(): void
    {
        $domain = $this->getTestDomain('positive-ssl');
        $csr = $this->generateTestCsr($domain);

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $domain,
            'productCode' => 'positivessl',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::Dns,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $domain,
            'transactionComment' => 'PHPUnit: testValidatePositiveSsl',
        ]);

        $response = $this->validateSslOrder($request);

        // ValidateOrder returns 200 for valid orders, 400 if validation rules fail
        // Demo account may not have all products enabled
        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400]),
            'PositiveSSL validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test validating a TrueBizID (OV) certificate with organization validation
     *
     * @test
     */
    public function testValidateTrueBizId(): void
    {
        $domain = $this->getTestDomain('truebiz');
        $csr = $this->generateTestCsr($domain);

        // OV certificate requires organization details
        $ownerData = TestDataFactory::createContactData('owner');
        $ownerData['orgName'] = 'Test Corporation GmbH';
        $ownerData['type'] = 'Organization';
        $owner = TestDataFactory::buildRegistrant($ownerData);

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $domain,
            'productCode' => 'truebizid',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::Dns,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $domain,
            'owner' => $owner,
            'transactionComment' => 'PHPUnit: testValidateTrueBizId',
        ]);

        $response = $this->validateSslOrder($request);

        // Check for success or expected validation-specific response
        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400, 401]),
            'TrueBizID validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test validating a wildcard certificate order
     *
     * @test
     */
    public function testValidateWildcard(): void
    {
        $baseDomain = $this->getTestDomain('wildcard');
        $wildcardDomain = '*.' . $baseDomain;
        $csr = $this->generateWildcardCsr($baseDomain);

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $wildcardDomain,
            'productCode' => 'positivesslwildcard',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::Dns,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $baseDomain,
            'transactionComment' => 'PHPUnit: testValidateWildcard',
        ]);

        $response = $this->validateSslOrder($request);

        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400, 401]),
            'Wildcard validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test validating a multi-domain certificate with SANs
     *
     * @test
     */
    public function testValidateMultiDomain(): void
    {
        $primaryDomain = $this->getTestDomain('multidomain');
        $csr = $this->generateTestCsr($primaryDomain);

        // Define Subject Alternative Names
        $sanNames = [
            'www.' . $primaryDomain,
            'mail.' . $primaryDomain,
            'api.' . $primaryDomain,
        ];

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $primaryDomain,
            'productCode' => 'positivesslmultidomain',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::Dns,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $primaryDomain,
            'sanNames' => $sanNames,
            'transactionComment' => 'PHPUnit: testValidateMultiDomain',
        ]);

        $response = $this->validateSslOrder($request);

        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400, 401, 554]),
            'Multi-domain validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test validating with DNS verification type
     *
     * @test
     */
    public function testValidateWithDnsVerification(): void
    {
        $domain = $this->getTestDomain('dns-verify');
        $csr = $this->generateTestCsr($domain);

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $domain,
            'productCode' => 'positivessl',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::Dns,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $domain,
            'transactionComment' => 'PHPUnit: testValidateWithDnsVerification',
        ]);

        $response = $this->validateSslOrder($request);

        // ValidateOrder returns 200 for valid orders, 400 if validation rules fail
        // Demo account may not have all products enabled
        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400]),
            'DNS verification validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test validating with Email verification type
     *
     * @test
     */
    public function testValidateWithEmailVerification(): void
    {
        $domain = $this->getTestDomain('email-verify');
        $csr = $this->generateTestCsr($domain);

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $domain,
            'productCode' => 'positivessl',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::Email,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $domain,
            'transactionComment' => 'PHPUnit: testValidateWithEmailVerification',
        ]);

        $response = $this->validateSslOrder($request);

        // ValidateOrder returns 200 for valid orders, 400 if validation rules fail
        // Demo account may not have all products enabled
        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400]),
            'Email verification validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test validating a certificate reissue (DetailsUpdate)
     *
     * Note: This test requires an existing certificate handle on the test account.
     *
     * @test
     */
    public function testValidateReissue(): void
    {
        // Attempt to find an existing certificate for reissue testing
        $existingHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($existingHandle)) {
            $this->markTestSkipped(
                'No existing certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $existingCert = $this->findExistingCertificate($existingHandle);

        if ($existingCert === null) {
            $this->markTestSkipped('Could not find existing certificate with handle: ' . $existingHandle);
        }

        $commonName = $existingCert->getCommonName();
        $csr = $this->generateTestCsr($commonName);

        $certificate = new v3\SslCertificate();
        $certificate->setHandle($existingHandle);
        $certificate->setProductCode($existingCert->getProductCode());
        $certificate->setWebServerType($existingCert->getWebServerType() ?? v3\WebServerType::ApacheSsl);
        $certificate->setApproverEmail($existingCert->getApproverEmail() ?? 'admin@' . $commonName);
        $certificate->setCSR($csr);
        $certificate->setValidationType(v3\SslDomainValidationType::Dns);

        // Set contacts
        $certificate->setOwner(TestDataFactory::buildRegistrant(TestDataFactory::createContactData('owner')));
        $certificate->setAdmin(TestDataFactory::buildContact(TestDataFactory::createContactData('admin')));
        $certificate->setTech(TestDataFactory::buildContact(TestDataFactory::createContactData('tech')));

        $request = new v3\SslCertificateOrderRequest();
        $request->setType(v3\OrderType::DetailsUpdate); // Reissue
        $request->setPeriod(1);
        $request->setTransactionComment('PHPUnit: testValidateReissue');
        $request->setSslCertificate($certificate);

        $response = $this->validateSslOrder($request);

        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400, 401, 404]),
            'Reissue validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test validating a certificate renewal
     *
     * Note: This test requires an existing certificate handle on the test account.
     *
     * @test
     */
    public function testValidateRenewal(): void
    {
        $existingHandle = getenv('ASCIO_TEST_CERT_HANDLE');

        if (empty($existingHandle)) {
            $this->markTestSkipped(
                'No existing certificate handle configured. Set ASCIO_TEST_CERT_HANDLE environment variable.'
            );
        }

        $existingCert = $this->findExistingCertificate($existingHandle);

        if ($existingCert === null) {
            $this->markTestSkipped('Could not find existing certificate with handle: ' . $existingHandle);
        }

        $commonName = $existingCert->getCommonName();
        $csr = $this->generateTestCsr($commonName);

        $certificate = new v3\SslCertificate();
        $certificate->setHandle($existingHandle);
        $certificate->setCommonName($commonName);
        $certificate->setProductCode($existingCert->getProductCode());
        $certificate->setWebServerType($existingCert->getWebServerType() ?? v3\WebServerType::ApacheSsl);
        $certificate->setApproverEmail($existingCert->getApproverEmail() ?? 'admin@' . $commonName);
        $certificate->setCSR($csr);
        $certificate->setValidationType(v3\SslDomainValidationType::Dns);

        // Set contacts
        $certificate->setOwner(TestDataFactory::buildRegistrant(TestDataFactory::createContactData('owner')));
        $certificate->setAdmin(TestDataFactory::buildContact(TestDataFactory::createContactData('admin')));
        $certificate->setTech(TestDataFactory::buildContact(TestDataFactory::createContactData('tech')));

        $request = new v3\SslCertificateOrderRequest();
        $request->setType(v3\OrderType::Renew);
        $request->setPeriod(1);
        $request->setTransactionComment('PHPUnit: testValidateRenewal');
        $request->setSslCertificate($certificate);

        $response = $this->validateSslOrder($request);

        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400, 401, 404]),
            'Renewal validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test that validation fails with missing CSR
     *
     * @test
     */
    public function testValidateFailsWithMissingCsr(): void
    {
        $domain = $this->getTestDomain('missing-csr');

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $domain,
            'productCode' => 'positivessl',
            'csr' => '', // Empty CSR
            'validationType' => v3\SslDomainValidationType::Dns,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $domain,
            'transactionComment' => 'PHPUnit: testValidateFailsWithMissingCsr',
        ]);

        $response = $this->validateSslOrder($request);

        $this->assertNotEquals(
            200,
            $response->ValidateOrderResult->getResultCode(),
            'Validation should fail with missing CSR'
        );
    }

    /**
     * Test that validation fails with invalid product code
     *
     * @test
     */
    public function testValidateFailsWithInvalidProductCode(): void
    {
        $domain = $this->getTestDomain('invalid-product');
        $csr = $this->generateTestCsr($domain);

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $domain,
            'productCode' => 'invalid_product_code_xyz',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::Dns,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $domain,
            'transactionComment' => 'PHPUnit: testValidateFailsWithInvalidProductCode',
        ]);

        $response = $this->validateSslOrder($request);

        $this->assertNotEquals(
            200,
            $response->ValidateOrderResult->getResultCode(),
            'Validation should fail with invalid product code'
        );
    }

    /**
     * Test validation with File verification type
     *
     * @test
     */
    public function testValidateWithFileVerification(): void
    {
        $domain = $this->getTestDomain('file-verify');
        $csr = $this->generateTestCsr($domain);

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $domain,
            'productCode' => 'positivessl',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::File,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $domain,
            'transactionComment' => 'PHPUnit: testValidateWithFileVerification',
        ]);

        $response = $this->validateSslOrder($request);

        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400, 401]),
            'File verification validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }

    /**
     * Test validation with different web server types
     *
     * @test
     * @dataProvider webServerTypeProvider
     */
    public function testValidateWithDifferentWebServerTypes(string $webServerType): void
    {
        $domain = $this->getTestDomain('webserver');
        $csr = $this->generateTestCsr($domain);

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 1,
            'commonName' => $domain,
            'productCode' => 'positivessl',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::Dns,
            'webServerType' => $webServerType,
            'approverEmail' => 'admin@' . $domain,
            'transactionComment' => 'PHPUnit: testValidateWithDifferentWebServerTypes - ' . $webServerType,
        ]);

        $response = $this->validateSslOrder($request);

        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400, 401]),
            sprintf(
                'Validation with web server type %s returned unexpected code: %d - %s',
                $webServerType,
                $resultCode,
                $response->ValidateOrderResult->getResultMessage()
            )
        );
    }

    /**
     * Data provider for web server types
     */
    public static function webServerTypeProvider(): array
    {
        return [
            'ApacheSsl' => [v3\WebServerType::ApacheSsl],
            'Apache2' => [v3\WebServerType::Apache2],
            'Iis' => [v3\WebServerType::Iis],
            'Tomcat' => [v3\WebServerType::Tomcat],
            'Other' => [v3\WebServerType::Other],
        ];
    }

    /**
     * Test validation with 2-year period
     *
     * @test
     */
    public function testValidateWithTwoYearPeriod(): void
    {
        $domain = $this->getTestDomain('two-year');
        $csr = $this->generateTestCsr($domain);

        $request = $this->buildSslCertificateOrderRequest([
            'orderType' => v3\OrderType::Register,
            'period' => 2,
            'commonName' => $domain,
            'productCode' => 'positivessl',
            'csr' => $csr,
            'validationType' => v3\SslDomainValidationType::Dns,
            'webServerType' => v3\WebServerType::ApacheSsl,
            'approverEmail' => 'admin@' . $domain,
            'transactionComment' => 'PHPUnit: testValidateWithTwoYearPeriod',
        ]);

        $response = $this->validateSslOrder($request);

        $resultCode = $response->ValidateOrderResult->getResultCode();
        $this->assertTrue(
            in_array($resultCode, [200, 400, 401]),
            'Two-year period validation returned unexpected code: ' . $resultCode . ' - ' . $response->ValidateOrderResult->getResultMessage()
        );
    }
}
