<?php
/**
 * Debug SSL validation to see actual error details
 */

require_once __DIR__ . '/../v3/service/autoload.php';

use ascio\v3 as v3;

// Get credentials from environment
$account = getenv('ASCIO_TEST_ACCOUNT') ?: 'whmcsdemo';
$password = getenv('ASCIO_TEST_PASSWORD') ?: '';
$testMode = true;

$wsdlUrl = "https://aws.demo.ascio.com/v3/aws.wsdl";

echo "=== SSL Validation Debug ===\n";
echo "Account: $account\n";
echo "WSDL: $wsdlUrl\n\n";

// Create SOAP client
$header = new SoapHeader(
    "http://www.ascio.com/2013/02",
    "SecurityHeaderDetails",
    ['Account' => $account, 'Password' => $password],
    false
);

$client = new v3\AscioService(['trace' => true], $wsdlUrl);
$client->__setSoapHeaders($header);

// Generate test CSR
$domain = 'ssl-test-' . time() . '-' . substr(md5(uniqid()), 0, 6) . '.example.com';
echo "Test domain: $domain\n\n";

$dn = [
    'countryName' => 'DE',
    'stateOrProvinceName' => 'Bavaria',
    'localityName' => 'Munich',
    'organizationName' => 'Test Organization',
    'commonName' => $domain,
    'emailAddress' => 'admin@' . $domain,
];

$privkey = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
]);

$csr = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);
openssl_csr_export($csr, $csrOut);

echo "CSR generated successfully\n\n";

// Build certificate
$certificate = new v3\SslCertificate();
$certificate->setCommonName($domain);
$certificate->setProductCode('positivessl');
$certificate->setWebServerType(v3\WebServerType::ApacheSsl);
$certificate->setApproverEmail('admin@' . $domain);
$certificate->setCSR($csrOut);
$certificate->setValidationType(v3\SslDomainValidationType::Dns);

// Build owner (Registrant)
$owner = new v3\Registrant();
$owner->setFirstName('Test');
$owner->setLastName('User');
$owner->setOrgName('Test Organization GmbH');
$owner->setAddress1('Test Street 123');
$owner->setCity('Munich');
$owner->setState('Bavaria');
$owner->setPostalCode('80331');
$owner->setCountryCode('DE');
$owner->setPhone('+49.891234567');
$owner->setEmail('test@example.com');
$owner->setType('Organization');
$certificate->setOwner($owner);

// Build admin contact
$admin = new v3\Contact();
$admin->setFirstName('Admin');
$admin->setLastName('User');
$admin->setOrgName('Test Organization GmbH');
$admin->setAddress1('Test Street 123');
$admin->setCity('Munich');
$admin->setState('Bavaria');
$admin->setPostalCode('80331');
$admin->setCountryCode('DE');
$admin->setPhone('+49.891234567');
$admin->setEmail('admin@example.com');
$admin->setType('Organization');
$certificate->setAdmin($admin);

// Build tech contact
$tech = new v3\Contact();
$tech->setFirstName('Tech');
$tech->setLastName('User');
$tech->setOrgName('Test Organization GmbH');
$tech->setAddress1('Test Street 123');
$tech->setCity('Munich');
$tech->setState('Bavaria');
$tech->setPostalCode('80331');
$tech->setCountryCode('DE');
$tech->setPhone('+49.891234567');
$tech->setEmail('tech@example.com');
$tech->setType('Organization');
$certificate->setTech($tech);

// Build order request
$request = new v3\SslCertificateOrderRequest();
$request->setType(v3\OrderType::Register);
$request->setPeriod(1);
$request->setTransactionComment('Debug: SSL Validation Test');
$request->setSslCertificate($certificate);

echo "=== Sending ValidateOrder ===\n";

try {
    $validateOrder = new v3\ValidateOrder($request);
    $response = $client->ValidateOrder($validateOrder);

    $result = $response->ValidateOrderResult;

    echo "Result Code: " . $result->getResultCode() . "\n";
    echo "Result Message: " . $result->getResultMessage() . "\n\n";

    // Check for errors
    $errors = $result->getErrors();
    if ($errors) {
        echo "=== ERRORS ===\n";
        $errorList = $errors->getString();
        if (is_array($errorList)) {
            foreach ($errorList as $error) {
                echo "  - $error\n";
            }
        } else {
            echo "  - $errorList\n";
        }
    }

    // Check for values
    $values = $result->getValues();
    if ($values) {
        echo "\n=== VALUES ===\n";
        $valueList = $values->getKeyValueOfstringstring();
        if (is_array($valueList)) {
            foreach ($valueList as $kv) {
                echo "  " . $kv->getKey() . " = " . $kv->getValue() . "\n";
            }
        }
    }

} catch (SoapFault $e) {
    echo "SOAP Fault: " . $e->getMessage() . "\n";
    echo "Request:\n" . $client->__getLastRequest() . "\n";
    echo "Response:\n" . $client->__getLastResponse() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== SOAP Request ===\n";
echo $client->__getLastRequest() . "\n";

echo "\n=== SOAP Response ===\n";
echo $client->__getLastResponse() . "\n";
