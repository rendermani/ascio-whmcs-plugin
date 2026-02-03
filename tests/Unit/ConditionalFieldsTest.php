<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for conditional domain fields configuration
 *
 * Validates the PHP conditional field rules in additionalfields.php
 * that drive the ascio-fields.js dynamic field visibility.
 *
 * @covers additionalfields.php conditional rules
 */
class ConditionalFieldsTest extends TestCase
{
    private array $conditionalFields;
    private array $additionalDomainFields;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset globals
        unset($GLOBALS['ascio_conditional_fields']);
        $additionaldomainfields = [];

        // Load the additional fields configuration
        require __DIR__ . '/../../resources/domains/additionalfields.php';

        $this->conditionalFields = $GLOBALS['ascio_conditional_fields'];
        $this->additionalDomainFields = $additionaldomainfields;
    }

    // ========================================================================
    // Structure Tests - Validate conditional rules are properly defined
    // ========================================================================

    #[Test]
    public function conditionalFieldsGlobalIsPopulated(): void
    {
        $this->assertNotEmpty($this->conditionalFields);
        $this->assertIsArray($this->conditionalFields);
    }

    #[Test]
    public function allExpectedTldsHaveConditionalRules(): void
    {
        $expectedTlds = ['.it', '.ca', '.us', '.ru', '.su', '.fr', '.re', '.pm', '.tf', '.wf', '.yt', '.xxx'];

        foreach ($expectedTlds as $tld) {
            $this->assertArrayHasKey($tld, $this->conditionalFields, "Missing conditional rules for $tld");
        }
    }

    #[Test]
    public function eachRuleHasRequiredKeys(): void
    {
        foreach ($this->conditionalFields as $tld => $fields) {
            foreach ($fields as $fieldName => $rule) {
                $this->assertArrayHasKey('depends_on', $rule, "Missing 'depends_on' for $tld/$fieldName");
                $this->assertArrayHasKey('show_when', $rule, "Missing 'show_when' for $tld/$fieldName");
                $this->assertIsArray($rule['show_when'], "'show_when' must be array for $tld/$fieldName");
                $this->assertNotEmpty($rule['show_when'], "'show_when' must not be empty for $tld/$fieldName");
                $this->assertIsString($rule['depends_on'], "'depends_on' must be string for $tld/$fieldName");
            }
        }
    }

    // ========================================================================
    // .IT Conditional Fields
    // ========================================================================

    #[Test]
    public function itTldHasBirthCountryConditionalField(): void
    {
        $itRules = $this->conditionalFields['.it'];

        $this->assertArrayHasKey('Birth Country', $itRules);
        $this->assertEquals('Legal Type', $itRules['Birth Country']['depends_on']);
        $this->assertContains('Italian and foreign natural persons', $itRules['Birth Country']['show_when']);
    }

    #[Test]
    public function itBirthCountryOnlyShownForNaturalPersons(): void
    {
        $rule = $this->conditionalFields['.it']['Birth Country'];

        // Should show for natural persons
        $this->assertTrue(in_array('Italian and foreign natural persons', $rule['show_when']));

        // Should NOT show for companies (not in show_when list)
        $this->assertNotContains('Companies/one man companies', $rule['show_when']);
    }

    // ========================================================================
    // .CA Conditional Fields
    // ========================================================================

    #[Test]
    public function caTldHasThreeTrademarkConditionalFields(): void
    {
        $caRules = $this->conditionalFields['.ca'];

        $this->assertArrayHasKey('Trademark Number', $caRules);
        $this->assertArrayHasKey('Trademark Name', $caRules);
        $this->assertArrayHasKey('Trademark Country', $caRules);
        $this->assertCount(3, $caRules);
    }

    #[Test]
    public function caTrademarkFieldsAllDependOnLegalType(): void
    {
        $caRules = $this->conditionalFields['.ca'];

        foreach (['Trademark Number', 'Trademark Name', 'Trademark Country'] as $field) {
            $this->assertEquals('Legal Type', $caRules[$field]['depends_on']);
            $this->assertEquals(['Trademark'], $caRules[$field]['show_when']);
        }
    }

    // ========================================================================
    // .US Conditional Fields
    // ========================================================================

    #[Test]
    public function usTldHasNexusCountryConditionalField(): void
    {
        $usRules = $this->conditionalFields['.us'];

        $this->assertArrayHasKey('Nexus Country', $usRules);
        $this->assertEquals('Nexus Category', $usRules['Nexus Country']['depends_on']);
    }

    #[Test]
    public function usNexusCountryShownForC31AndC32Only(): void
    {
        $showWhen = $this->conditionalFields['.us']['Nexus Country']['show_when'];

        $this->assertContains('C31', $showWhen);
        $this->assertContains('C32', $showWhen);
        $this->assertCount(2, $showWhen);
    }

    // ========================================================================
    // .RU Conditional Fields
    // ========================================================================

    #[Test]
    public function ruTldHasIndividualAndOrganizationFields(): void
    {
        $ruRules = $this->conditionalFields['.ru'];

        // Individual fields
        $individualFields = ['Passport Number', 'Passport Issue Date', 'Passport Issuer', 'Birth Date'];
        foreach ($individualFields as $field) {
            $this->assertArrayHasKey($field, $ruRules, "Missing $field for .ru");
            $this->assertEquals('Registrant Type', $ruRules[$field]['depends_on']);
            $this->assertEquals(['Individual'], $ruRules[$field]['show_when']);
        }

        // Organization field
        $this->assertArrayHasKey('TIN', $ruRules);
        $this->assertEquals('Registrant Type', $ruRules['TIN']['depends_on']);
        $this->assertEquals(['Organization'], $ruRules['TIN']['show_when']);
    }

    #[Test]
    public function ruTldHasFiveConditionalFields(): void
    {
        $this->assertCount(5, $this->conditionalFields['.ru']);
    }

    // ========================================================================
    // .FR Conditional Fields
    // ========================================================================

    #[Test]
    public function frTldHasIndividualAndCompanyFields(): void
    {
        $frRules = $this->conditionalFields['.fr'];

        // Individual fields
        $this->assertEquals('Contact Type', $frRules['Birth Date']['depends_on']);
        $this->assertEquals(['Individual'], $frRules['Birth Date']['show_when']);
        $this->assertEquals('Contact Type', $frRules['Birth Place']['depends_on']);
        $this->assertEquals(['Individual'], $frRules['Birth Place']['show_when']);

        // Company field
        $this->assertEquals('Contact Type', $frRules['SIREN/SIRET']['depends_on']);
        $this->assertEquals(['Company'], $frRules['SIREN/SIRET']['show_when']);
    }

    #[Test]
    public function frTldHasThreeConditionalFields(): void
    {
        $this->assertCount(3, $this->conditionalFields['.fr']);
    }

    // ========================================================================
    // .XXX Conditional Fields
    // ========================================================================

    #[Test]
    public function xxxTldHasIcmMemberIdConditionalField(): void
    {
        $xxxRules = $this->conditionalFields['.xxx'];

        $this->assertArrayHasKey('ICM Member ID', $xxxRules);
        $this->assertEquals('ICM Membership', $xxxRules['ICM Member ID']['depends_on']);
        $this->assertEquals(['Yes'], $xxxRules['ICM Member ID']['show_when']);
    }

    // ========================================================================
    // Variant TLD Inheritance Tests
    // ========================================================================

    #[Test]
    #[DataProvider('ruVariantTldsProvider')]
    public function ruVariantTldsInheritRules(string $tld): void
    {
        $this->assertEquals(
            $this->conditionalFields['.ru'],
            $this->conditionalFields[$tld],
            "$tld should have identical rules to .ru"
        );
    }

    public static function ruVariantTldsProvider(): array
    {
        return [
            '.su' => ['.su'],
        ];
    }

    #[Test]
    #[DataProvider('frVariantTldsProvider')]
    public function frVariantTldsInheritRules(string $tld): void
    {
        $this->assertEquals(
            $this->conditionalFields['.fr'],
            $this->conditionalFields[$tld],
            "$tld should have identical rules to .fr"
        );
    }

    public static function frVariantTldsProvider(): array
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
    // Consistency Tests - Conditional fields match additional fields definitions
    // ========================================================================

    #[Test]
    #[DataProvider('conditionalFieldDefinitionsProvider')]
    public function conditionalFieldExistsInAdditionalFields(string $tld, string $fieldName): void
    {
        $this->assertArrayHasKey(
            $tld,
            $this->additionalDomainFields,
            "TLD $tld not found in additionaldomainfields"
        );

        $fieldNames = array_column($this->additionalDomainFields[$tld], 'Name');
        $this->assertContains(
            $fieldName,
            $fieldNames,
            "Conditional field '$fieldName' not defined in additional fields for $tld"
        );
    }

    public static function conditionalFieldDefinitionsProvider(): array
    {
        return [
            '.it Birth Country' => ['.it', 'Birth Country'],
            '.ca Trademark Number' => ['.ca', 'Trademark Number'],
            '.ca Trademark Name' => ['.ca', 'Trademark Name'],
            '.ca Trademark Country' => ['.ca', 'Trademark Country'],
            '.us Nexus Country' => ['.us', 'Nexus Country'],
            '.xxx ICM Member ID' => ['.xxx', 'ICM Member ID'],
        ];
    }

    #[Test]
    #[DataProvider('dependencyFieldDefinitionsProvider')]
    public function dependencyFieldExistsInAdditionalFields(string $tld, string $dependsOnField): void
    {
        $this->assertArrayHasKey(
            $tld,
            $this->additionalDomainFields,
            "TLD $tld not found in additionaldomainfields"
        );

        $fieldNames = array_column($this->additionalDomainFields[$tld], 'Name');
        $this->assertContains(
            $dependsOnField,
            $fieldNames,
            "Dependency field '$dependsOnField' not defined in additional fields for $tld"
        );
    }

    public static function dependencyFieldDefinitionsProvider(): array
    {
        return [
            '.it Legal Type' => ['.it', 'Legal Type'],
            '.ca Legal Type' => ['.ca', 'Legal Type'],
            '.us Nexus Category' => ['.us', 'Nexus Category'],
            '.xxx ICM Membership' => ['.xxx', 'ICM Membership'],
        ];
    }

    // ========================================================================
    // Visibility Logic Tests - Simulate field show/hide decisions
    // ========================================================================

    #[Test]
    #[DataProvider('visibilityDecisionProvider')]
    public function fieldVisibilityMatchesExpectation(
        string $tld,
        string $conditionalField,
        string $dependencyValue,
        bool $expectedVisible
    ): void {
        $rule = $this->conditionalFields[$tld][$conditionalField];
        $isVisible = in_array($dependencyValue, $rule['show_when']);

        $this->assertEquals(
            $expectedVisible,
            $isVisible,
            "Field '$conditionalField' on $tld should be " .
            ($expectedVisible ? 'visible' : 'hidden') .
            " when '{$rule['depends_on']}' = '$dependencyValue'"
        );
    }

    public static function visibilityDecisionProvider(): array
    {
        return [
            // .IT - Birth Country visibility
            'IT: natural person shows birth country' => ['.it', 'Birth Country', 'Italian and foreign natural persons', true],
            'IT: company hides birth country' => ['.it', 'Birth Country', 'Companies/one man companies', false],
            'IT: freelance hides birth country' => ['.it', 'Birth Country', 'Freelance workers', false],

            // .CA - Trademark fields visibility
            'CA: trademark shows trademark number' => ['.ca', 'Trademark Number', 'Trademark', true],
            'CA: corporation hides trademark number' => ['.ca', 'Trademark Number', 'Corporation', false],
            'CA: canadian citizen hides trademark number' => ['.ca', 'Trademark Number', 'Canadian Citizen', false],

            // .US - Nexus Country visibility
            'US: C31 shows nexus country' => ['.us', 'Nexus Country', 'C31', true],
            'US: C32 shows nexus country' => ['.us', 'Nexus Country', 'C32', true],
            'US: C11 hides nexus country' => ['.us', 'Nexus Country', 'C11', false],
            'US: C12 hides nexus country' => ['.us', 'Nexus Country', 'C12', false],

            // .RU - Individual vs Organization fields
            'RU: individual shows passport' => ['.ru', 'Passport Number', 'Individual', true],
            'RU: organization hides passport' => ['.ru', 'Passport Number', 'Organization', false],
            'RU: organization shows TIN' => ['.ru', 'TIN', 'Organization', true],
            'RU: individual hides TIN' => ['.ru', 'TIN', 'Individual', false],

            // .FR - Individual vs Company fields
            'FR: individual shows birth date' => ['.fr', 'Birth Date', 'Individual', true],
            'FR: company hides birth date' => ['.fr', 'Birth Date', 'Company', false],
            'FR: company shows SIREN' => ['.fr', 'SIREN/SIRET', 'Company', true],
            'FR: individual hides SIREN' => ['.fr', 'SIREN/SIRET', 'Individual', false],

            // .XXX - ICM Member ID
            'XXX: yes shows member ID' => ['.xxx', 'ICM Member ID', 'Yes', true],
            'XXX: no hides member ID' => ['.xxx', 'ICM Member ID', 'No', false],
        ];
    }

    // ========================================================================
    // JavaScript File Tests - Ensure JS file exists and is loadable
    // ========================================================================

    #[Test]
    public function ascioFieldsJsFileExists(): void
    {
        $jsPath = __DIR__ . '/../../assets/js/ascio-fields.js';
        $this->assertFileExists($jsPath);
    }

    #[Test]
    public function ascioFieldsJsContainsAllConditionalTlds(): void
    {
        $jsContent = file_get_contents(__DIR__ . '/../../assets/js/ascio-fields.js');

        foreach (['.it', '.ca', '.us', '.ru', '.su', '.fr', '.re', '.xxx'] as $tld) {
            $escapedTld = preg_quote($tld, '/');
            $pattern = '/[\'"]' . $escapedTld . '[\'"]/' ;
            $this->assertMatchesRegularExpression(
                $pattern,
                $jsContent,
                "ascio-fields.js missing rules for $tld"
            );
        }
    }

    #[Test]
    public function ascioFieldsJsExposesInitFunction(): void
    {
        $jsContent = file_get_contents(__DIR__ . '/../../assets/js/ascio-fields.js');

        $this->assertStringContainsString('AscioFields', $jsContent);
        $this->assertStringContainsString('init', $jsContent);
    }

    #[Test]
    public function jsAndPhpRulesAreConsistent(): void
    {
        $jsContent = file_get_contents(__DIR__ . '/../../assets/js/ascio-fields.js');

        // Verify key field names appear in both PHP and JS
        $phpFieldNames = [];
        foreach ($this->conditionalFields as $tld => $fields) {
            foreach ($fields as $fieldName => $rule) {
                $phpFieldNames[] = $fieldName;
                $phpFieldNames[] = $rule['depends_on'];
            }
        }
        $phpFieldNames = array_unique($phpFieldNames);

        foreach ($phpFieldNames as $fieldName) {
            $this->assertStringContainsString(
                $fieldName,
                $jsContent,
                "Field '$fieldName' exists in PHP rules but not in ascio-fields.js"
            );
        }
    }
}
