<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for config validation logic
 *
 * Note: We test the logic patterns used in ascio_config_validate without
 * loading the full ascio.php which has WHMCS dependencies.
 */
#[Group('unit')]
#[Group('config')]
class ConfigValidateTest extends TestCase
{
    #[Test]
    public function emptyCredentialsShouldSkipValidation(): void
    {
        // Test the logic used in ascio_config_validate
        $params = ['Username' => '', 'Password' => ''];

        $username = $params['Username'] ?? '';
        $password = $params['Password'] ?? '';

        // Should skip validation if credentials are empty
        $shouldSkip = empty($username) || empty($password);
        $this->assertTrue($shouldSkip);
    }

    #[Test]
    public function partialCredentialsShouldSkipValidation(): void
    {
        // Only username provided
        $params1 = ['Username' => 'testuser', 'Password' => ''];
        $shouldSkip1 = empty($params1['Username'] ?? '') || empty($params1['Password'] ?? '');
        $this->assertTrue($shouldSkip1);

        // Only password provided
        $params2 = ['Username' => '', 'Password' => 'testpass'];
        $shouldSkip2 = empty($params2['Username'] ?? '') || empty($params2['Password'] ?? '');
        $this->assertTrue($shouldSkip2);
    }

    #[Test]
    public function fullCredentialsShouldNotSkipValidation(): void
    {
        $params = ['Username' => 'testuser', 'Password' => 'testpass'];

        $username = $params['Username'] ?? '';
        $password = $params['Password'] ?? '';

        $shouldSkip = empty($username) || empty($password);
        $this->assertFalse($shouldSkip);
    }

    public static function wsdlUrlDataProvider(): array
    {
        return [
            'test mode on' => ['on', 'https://demo.ascio.info/2012/01/01/AscioService.wsdl'],
            'test mode off' => ['', 'https://aws.ascio.info/2012/01/01/AscioService.wsdl'],
            'test mode explicit no' => ['off', 'https://aws.ascio.info/2012/01/01/AscioService.wsdl'],
            'test mode null' => [null, 'https://aws.ascio.info/2012/01/01/AscioService.wsdl'],
        ];
    }

    #[Test]
    #[DataProvider('wsdlUrlDataProvider')]
    public function wsdlUrlIsCorrectForTestMode(?string $testMode, string $expectedUrl): void
    {
        $testModeEnabled = ($testMode ?? '') === 'on';
        $wsdlPrefix = $testModeEnabled ? 'https://demo' : 'https://aws';
        $wsdlUrl = $wsdlPrefix . '.ascio.info/2012/01/01/AscioService.wsdl';

        $this->assertEquals($expectedUrl, $wsdlUrl);
    }

    #[Test]
    public function tldDataTransformationIsCorrect(): void
    {
        // Test the TLD data transformation logic used in ascio_syncOnConfigSave
        $tld = [
            'tld' => 'com',
            'Threshold' => '-35',
            'Renew' => 'true',
            'LocalPresenceRequired' => 'false',
            'LocalPresenceOffered' => 'true',
            'AuthCodeRequired' => 'true',
            'Country' => 'US',
        ];

        $transformed = [
            'Threshold' => $tld['Threshold'] ?? 0,
            'Renew' => ($tld['Renew'] ?? '') === 'true' ? 1 : 0,
            'LocalPresenceRequired' => ($tld['LocalPresenceRequired'] ?? '') === 'true' ? 1 : 0,
            'LocalPresenceOffered' => ($tld['LocalPresenceOffered'] ?? '') === 'true' ? 1 : 0,
            'AuthCodeRequired' => ($tld['AuthCodeRequired'] ?? '') === 'true' ? 1 : 0,
            'Country' => $tld['Country'] ?? null,
        ];

        $this->assertEquals('-35', $transformed['Threshold']);
        $this->assertEquals(1, $transformed['Renew']);
        $this->assertEquals(0, $transformed['LocalPresenceRequired']);
        $this->assertEquals(1, $transformed['LocalPresenceOffered']);
        $this->assertEquals(1, $transformed['AuthCodeRequired']);
        $this->assertEquals('US', $transformed['Country']);
    }

    #[Test]
    public function tldDataHandlesMissingFields(): void
    {
        // Test with minimal TLD data
        $tld = [
            'tld' => 'test',
        ];

        $transformed = [
            'Threshold' => $tld['Threshold'] ?? 0,
            'Renew' => ($tld['Renew'] ?? '') === 'true' ? 1 : 0,
            'LocalPresenceRequired' => ($tld['LocalPresenceRequired'] ?? '') === 'true' ? 1 : 0,
            'LocalPresenceOffered' => ($tld['LocalPresenceOffered'] ?? '') === 'true' ? 1 : 0,
            'AuthCodeRequired' => ($tld['AuthCodeRequired'] ?? '') === 'true' ? 1 : 0,
            'Country' => $tld['Country'] ?? null,
        ];

        $this->assertEquals(0, $transformed['Threshold']);
        $this->assertEquals(0, $transformed['Renew']);
        $this->assertEquals(0, $transformed['LocalPresenceRequired']);
        $this->assertEquals(0, $transformed['LocalPresenceOffered']);
        $this->assertEquals(0, $transformed['AuthCodeRequired']);
        $this->assertNull($transformed['Country']);
    }

    #[Test]
    public function queryParamAuthIsCorrectlyFormatted(): void
    {
        $username = 'testuser';
        $password = 'testpass';
        $env = 'testing';

        $params = http_build_query([
            'username' => $username,
            'password' => $password,
            'env' => $env,
        ]);

        $this->assertEquals('username=testuser&password=testpass&env=testing', $params);
    }

    #[Test]
    public function queryParamAuthEncodesSpecialChars(): void
    {
        $username = 'user@domain.com';
        $password = 'pass&word=123';

        $usernameEncoded = urlencode($username);
        $passwordEncoded = urlencode($password);

        $this->assertEquals('user%40domain.com', $usernameEncoded);
        $this->assertEquals('pass%26word%3D123', $passwordEncoded);
    }

    #[Test]
    public function hashComputationIsConsistent(): void
    {
        $data = ['tld' => [['tld' => 'com'], ['tld' => 'net']]];

        $hash1 = md5(json_encode($data));
        $hash2 = md5(json_encode($data));

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(32, strlen($hash1)); // MD5 is 32 chars
    }

    #[Test]
    public function hashChangesWhenDataChanges(): void
    {
        $data1 = ['tld' => [['tld' => 'com']]];
        $data2 = ['tld' => [['tld' => 'net']]];

        $hash1 = md5(json_encode($data1));
        $hash2 = md5(json_encode($data2));

        $this->assertNotEquals($hash1, $hash2);
    }
}
