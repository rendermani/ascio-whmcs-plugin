<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use ascio\FieldRegistry;

/**
 * Unit tests for FieldRegistry
 *
 * Verifies all API→WHMCS mappings exist and field names match TLD plugin expectations.
 */
class FieldRegistryTest extends TestCase
{
    private FieldRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new FieldRegistry();
    }

    // ========================================================================
    // Default Mapping Tests
    // ========================================================================

    #[Test]
    public function defaultMappingExistsForRegistrantType(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.Type', '.generic');
        $this->assertNotNull($def);
        $this->assertEquals('Registrant Type', $def['Name']);
        $this->assertEquals('dropdown', $def['Type']);
    }

    #[Test]
    public function defaultMappingExistsForVatNumber(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.VatNumber', '.generic');
        $this->assertNotNull($def);
        $this->assertEquals('VAT Number', $def['Name']);
        $this->assertEquals('text', $def['Type']);
    }

    #[Test]
    public function unknownApiFieldReturnsNull(): void
    {
        $def = $this->registry->getFieldDefinition('Unknown.Field', '.com');
        $this->assertNull($def);
    }

    // ========================================================================
    // TLD Override Tests - Field names must match what TLD plugins expect
    // ========================================================================

    #[Test]
    #[DataProvider('italyFieldProvider')]
    public function itTldFieldNamesMatchPluginExpectations(string $apiField, string $expectedName): void
    {
        $def = $this->registry->getFieldDefinition($apiField, '.it');
        $this->assertNotNull($def, "Missing definition for {$apiField} on .it");
        $this->assertEquals($expectedName, $def['Name']);
    }

    public static function italyFieldProvider(): array
    {
        return [
            'Legal Type' => ['Registrant.Type', 'Legal Type'],
            'Tax ID' => ['Registrant.OrganisationNumber', 'Tax ID'],
            'Birth Country' => ['Registrant.BirthCountry', 'Birth Country'],
        ];
    }

    #[Test]
    #[DataProvider('canadaFieldProvider')]
    public function caTldFieldNamesMatchPluginExpectations(string $apiField, string $expectedName): void
    {
        $def = $this->registry->getFieldDefinition($apiField, '.ca');
        $this->assertNotNull($def, "Missing definition for {$apiField} on .ca");
        $this->assertEquals($expectedName, $def['Name']);
    }

    public static function canadaFieldProvider(): array
    {
        return [
            'Legal Type' => ['Registrant.Type', 'Legal Type'],
            'Trademark Number' => ['Trademark.Number', 'Trademark Number'],
            'Trademark Name' => ['Trademark.Name', 'Trademark Name'],
            'Trademark Country' => ['Trademark.Country', 'Trademark Country'],
            'CIRA Agreement' => ['CIRA.Agreement', 'CIRA Agreement'],
            'Canadian Citizen' => ['Registrant.CanadianCitizen', 'Canadian Citizen'],
        ];
    }

    #[Test]
    public function ukTldUsesLegalType(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.Type', '.uk');
        $this->assertEquals('Legal Type', $def['Name']);
        $this->assertStringContainsString('IND', $def['Options']);
    }

    #[Test]
    public function ukTldUsesCompanyIdNumber(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.OrganisationNumber', '.uk');
        $this->assertEquals('Company ID Number', $def['Name']);
    }

    #[Test]
    public function frTldUsesContactType(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.Type', '.fr');
        $this->assertEquals('Contact Type', $def['Name']);
        $this->assertEquals('Individual,Company', $def['Options']);
    }

    #[Test]
    public function ruTldUsesRegistrantType(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.Type', '.ru');
        $this->assertEquals('Registrant Type', $def['Name']);
        $this->assertEquals('Individual,Organization', $def['Options']);
    }

    #[Test]
    public function usTldHasApplicationPurpose(): void
    {
        $def = $this->registry->getFieldDefinition('Domain.ApplicationPurpose', '.us');
        $this->assertEquals('Application Purpose', $def['Name']);
        $this->assertStringContainsString('P1', $def['Options']);
    }

    #[Test]
    public function sgTldUsesRegistrantId(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.OrganisationNumber', '.sg');
        $this->assertEquals('Registrant ID', $def['Name']);
        $this->assertTrue($def['Required']);
    }

    #[Test]
    public function nlTldUsesOrganisationNumber(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.OrganisationNumber', '.nl');
        $this->assertEquals('Organisation Number', $def['Name']);
    }

    #[Test]
    public function seTldUsesIdentificationNumber(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.OrganisationNumber', '.se');
        $this->assertEquals('Identification Number', $def['Name']);
    }

    // ========================================================================
    // TLD Variant Resolution Tests
    // ========================================================================

    #[Test]
    public function suVariantResolvesToRu(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.Type', '.su');
        $this->assertEquals('Registrant Type', $def['Name']);
    }

    #[Test]
    public function reVariantResolvesToFr(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.Type', '.re');
        $this->assertEquals('Contact Type', $def['Name']);
    }

    #[Test]
    public function coUkVariantResolvesToUk(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.Type', '.co.uk');
        $this->assertEquals('Legal Type', $def['Name']);
    }

    #[Test]
    public function comSgVariantResolvesToSg(): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.OrganisationNumber', '.com.sg');
        $this->assertEquals('Registrant ID', $def['Name']);
    }

    #[Test]
    #[DataProvider('frenchVariantProvider')]
    public function frenchVariantsResolveProperly(string $tld): void
    {
        $def = $this->registry->getFieldDefinition('Registrant.Type', $tld);
        $this->assertNotNull($def);
        $this->assertEquals('Contact Type', $def['Name']);
    }

    public static function frenchVariantProvider(): array
    {
        return [
            '.re' => ['.re'],
            '.pm' => ['.pm'],
            '.tf' => ['.tf'],
            '.wf' => ['.wf'],
            '.yt' => ['.yt'],
        ];
    }

    // ========================================================================
    // Remove Fields Tests
    // ========================================================================

    #[Test]
    public function caRemoveFieldsIncludeWhmcsDefaults(): void
    {
        $removes = $this->registry->getRemoveFields('.ca');
        $this->assertContains('Legal Type', $removes);
        $this->assertContains('CIRA Agreement', $removes);
        $this->assertContains('WHOIS Opt-out', $removes);
    }

    #[Test]
    public function usRemoveFieldsIncludeWhmcsDefaults(): void
    {
        $removes = $this->registry->getRemoveFields('.us');
        $this->assertContains('Nexus Category', $removes);
        $this->assertContains('Nexus Country', $removes);
        $this->assertContains('Application Purpose', $removes);
    }

    #[Test]
    public function ukRemoveFieldsIncludeWhmcsDefaults(): void
    {
        $removes = $this->registry->getRemoveFields('.uk');
        $this->assertContains('Legal Type', $removes);
        $this->assertContains('Company ID Number', $removes);
    }

    #[Test]
    public function tldWithNoRemovesReturnsEmptyArray(): void
    {
        $removes = $this->registry->getRemoveFields('.de');
        $this->assertEmpty($removes);
    }

    // ========================================================================
    // Context Mapping Tests
    // ========================================================================

    #[Test]
    public function itContextMappingsExist(): void
    {
        $mappings = $this->registry->getContextMappings('.it');
        $this->assertArrayHasKey('Registrant.Type', $mappings);
        $this->assertArrayHasKey('individual', $mappings['Registrant.Type']);
        $this->assertContains('Italian and foreign natural persons', $mappings['Registrant.Type']['individual']);
    }

    #[Test]
    public function ruContextMappingsExist(): void
    {
        $mappings = $this->registry->getContextMappings('.ru');
        $this->assertArrayHasKey('Registrant.Type', $mappings);
        $this->assertContains('Individual', $mappings['Registrant.Type']['individual']);
        $this->assertContains('Organization', $mappings['Registrant.Type']['organization']);
    }

    #[Test]
    public function frContextMappingsExist(): void
    {
        $mappings = $this->registry->getContextMappings('.fr');
        $this->assertArrayHasKey('Registrant.Type', $mappings);
        $this->assertContains('Individual', $mappings['Registrant.Type']['individual']);
        $this->assertContains('Company', $mappings['Registrant.Type']['organization']);
    }

    // ========================================================================
    // TLD Override Merge Tests
    // ========================================================================

    #[Test]
    public function tldOverrideMergesWithDefault(): void
    {
        // .asia CED Locality has override for LangVar but should still get Options from default
        $def = $this->registry->getFieldDefinition('CED.Locality', '.asia');
        $this->assertNotNull($def);
        $this->assertEquals('CED Locality', $def['Name']);
        $this->assertEquals('asiacedlocality', $def['LangVar']);
        // Options should come from the default mapping
        $this->assertStringContainsString('AF,AU', $def['Options']);
    }

    // ========================================================================
    // Utility Method Tests
    // ========================================================================

    #[Test]
    public function getAllApiFieldNamesReturnsNonEmptyArray(): void
    {
        $names = $this->registry->getAllApiFieldNames();
        $this->assertNotEmpty($names);
        $this->assertContains('Registrant.Type', $names);
        $this->assertContains('Registrant.VatNumber', $names);
    }

    #[Test]
    public function getTldsWithOverridesReturnsExpectedTlds(): void
    {
        $tlds = $this->registry->getTldsWithOverrides();
        $this->assertContains('.it', $tlds);
        $this->assertContains('.ca', $tlds);
        $this->assertContains('.uk', $tlds);
        $this->assertContains('.fr', $tlds);
        $this->assertContains('.ru', $tlds);
    }

    #[Test]
    public function hasTldOverridesReturnsTrueForKnownTld(): void
    {
        $this->assertTrue($this->registry->hasTldOverrides('.it'));
        $this->assertTrue($this->registry->hasTldOverrides('.su')); // resolves to .ru
    }

    #[Test]
    public function hasTldOverridesReturnsFalseForGenericTld(): void
    {
        $this->assertFalse($this->registry->hasTldOverrides('.com'));
    }
}
