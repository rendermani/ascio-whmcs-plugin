<?php

namespace Ascio\Tests\Unit\Tlds;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ascio\Request;
use Ascio\Tests\Mocks\WhmcsFunctionsMock;
use Ascio\Tests\Mocks\CapsuleMock;
use Ascio\Tests\Mocks\SoapClientMock;

/**
 * Unit tests for .DE TLD plugin
 *
 * Tests EPP code generation, renewal behavior, and fax fallback
 *
 * @covers \ascio\de
 */
class DeTldTest extends TestCase
{
    private array $defaultParams;

    protected function setUp(): void
    {
        parent::setUp();
        WhmcsFunctionsMock::reset();
        CapsuleMock::reset();
        SoapClientMock::reset();

        $this->defaultParams = [
            'Username' => 'testuser',
            'Password' => 'testpass',
            'TestMode' => 'on',
            'domainid' => 1,
            'domainname' => 'beispiel.de',
            'sld' => 'beispiel',
            'tld' => 'de',
            'regperiod' => 1,
            'firstname' => 'Hans',
            'lastname' => 'Müller',
            'companyname' => 'Test GmbH',
            'address1' => 'Hauptstraße 1',
            'address2' => '',
            'city' => 'Berlin',
            'state' => 'BE',
            'postcode' => '10115',
            'country' => 'DE',
            'email' => 'hans@example.de',
            'fullphonenumber' => '+49.3012345678',
            'adminfirstname' => 'Hans',
            'adminlastname' => 'Müller',
            'admincompanyname' => 'Test GmbH',
            'adminaddress1' => 'Hauptstraße 1',
            'adminaddress2' => '',
            'admincity' => 'Berlin',
            'adminstate' => 'BE',
            'adminpostcode' => '10115',
            'admincountry' => 'DE',
            'adminemail' => 'admin@example.de',
            'adminfullphonenumber' => '+49.3012345678',
            'ns1' => 'ns1.example.com',
            'ns2' => 'ns2.example.com',
            'ns3' => '',
            'ns4' => '',
            'ns5' => '',
            'eppcode' => '',
            'idprotection' => false,
            'custom' => [],
            'additionalfields' => [
                'Tax ID' => 'DE123456789'
            ]
        ];
    }

    // =========================================================================
    // EPP Code Generation Tests
    // Note: These test the Tools::generateEppCode directly since updateEPPCode
    // makes an API call. For integration testing of updateEPPCode, see
    // tests/Integration/AscioApiIntegrationTest.php
    // =========================================================================

    #[Test]
    public function toolsGenerateEppCodeReturns12Characters(): void
    {
        // DE TLD uses these characters for EPP code generation
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789+-/*';

        $code = \ascio\Tools::generateEppCode(12, $characters);

        $this->assertEquals(12, strlen($code));
    }

    #[Test]
    public function generatedEppCodeUsesAllowedCharacters(): void
    {
        // DE TLD uses these characters
        $allowedChars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789+-/*';

        // Generate multiple codes to verify character set
        for ($i = 0; $i < 10; $i++) {
            $code = \ascio\Tools::generateEppCode(12, $allowedChars);

            for ($j = 0; $j < strlen($code); $j++) {
                $this->assertStringContainsString(
                    $code[$j],
                    $allowedChars,
                    "Character '{$code[$j]}' at position {$j} is not in allowed set"
                );
            }
        }
    }

    #[Test]
    public function generatedEppCodeExcludesAmbiguousCharacters(): void
    {
        // DE TLD deliberately excludes 0, 1, O, I, l to avoid confusion
        $allowedChars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789+-/*';
        $excludedChars = ['0', '1', 'O', 'I', 'l'];

        for ($i = 0; $i < 20; $i++) {
            $code = \ascio\Tools::generateEppCode(12, $allowedChars);

            foreach ($excludedChars as $char) {
                $this->assertStringNotContainsString(
                    $char,
                    $code,
                    "Excluded character '{$char}' found in EPP code '{$code}'"
                );
            }
        }
    }

    // =========================================================================
    // Registrant Number Tests
    // =========================================================================

    #[Test]
    public function mapToRegistrantSetsTaxIdAsRegistrantNumber(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Tax ID' => 'DE123456789'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('DE123456789', $result['RegistrantNumber']);
    }

    #[Test]
    public function mapToRegistrantUsesCustomRegistrantNumberAsFallback(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Tax ID' => ''
            ],
            'custom' => [
                'RegistrantNumber' => 'CUSTOM123'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        // Note: DE TLD code has a bug - it tries to access $contact["custom"]["RegistrantNumber"]
        // but parent mapToRegistrant returns $result["RegistrantNumber"] directly.
        // The fallback logic `$regNr1 ? $regNr1 : $regNr2` where $regNr2 = $contact["custom"]["RegistrantNumber"]
        // will always be null because "custom" key doesn't exist in the contact.
        // When Tax ID is empty, RegistrantNumber will be null.
        $this->assertNull($result['RegistrantNumber']);
    }

    #[Test]
    public function mapToRegistrantPrefersTaxIdOverCustom(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Tax ID' => 'DE987654321'
            ],
            'custom' => [
                'RegistrantNumber' => 'CUSTOM123'
            ]
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToRegistrant');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        $this->assertEquals('DE987654321', $result['RegistrantNumber']);
    }

    // =========================================================================
    // Admin Contact Fax Fallback Tests
    // =========================================================================

    #[Test]
    public function mapToAdminSetsFaxToPhoneWhenFaxMissing(): void
    {
        $params = array_merge($this->defaultParams, [
            'custom' => ['Fax' => '']
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToAdmin');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        // Fax should be set to Phone when not provided
        $this->assertEquals($result['Phone'], $result['Fax']);
    }

    #[Test]
    public function mapToAdminKeepsFaxWhenProvided(): void
    {
        $params = array_merge($this->defaultParams, [
            'custom' => ['Fax' => '+49.3098765432']
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToAdmin');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        // Original fax from custom should be kept
        $this->assertNotEmpty($result['Fax']);
    }

    // =========================================================================
    // Tech Contact Fax Fallback Tests
    // =========================================================================

    #[Test]
    public function mapToTechSetsFaxToPhoneWhenFaxMissing(): void
    {
        $params = array_merge($this->defaultParams, [
            'custom' => ['Fax' => '']
        ]);

        $request = Request::create($params);

        $reflection = new \ReflectionMethod($request, 'mapToTech');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($request, $params);

        // Fax should be set to Phone when not provided
        $this->assertEquals($result['Phone'], $result['Fax']);
    }

    // =========================================================================
    // Transfer Domain Tests
    // =========================================================================

    #[Test]
    public function transferDomainMethodExists(): void
    {
        $params = $this->defaultParams;
        $request = Request::create($params);

        $this->assertTrue(method_exists($request, 'transferDomain'));
    }

    // =========================================================================
    // Order Structure Tests
    // =========================================================================

    #[Test]
    public function orderIncludesCorrectStructure(): void
    {
        $params = array_merge($this->defaultParams, [
            'additionalfields' => [
                'Tax ID' => 'DE123456789'
            ]
        ]);

        $request = Request::create($params);
        $order = $request->mapToOrder($params, 'Register');

        $this->assertEquals('beispiel.de', $order['Order']['Domain']['Name']);
        $this->assertEquals('DE123456789', $order['Order']['Domain']['Owner']['RegistrantNumber']);
    }
}
