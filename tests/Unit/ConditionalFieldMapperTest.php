<?php

namespace Ascio\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ascio\FieldRegistry;
use ascio\ConditionalFieldMapper;

/**
 * Unit tests for ConditionalFieldMapper
 *
 * Verifies API context → WHMCS show_when mapping.
 */
class ConditionalFieldMapperTest extends TestCase
{
    private ConditionalFieldMapper $mapper;
    private FieldRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new FieldRegistry();
        $this->mapper = new ConditionalFieldMapper($this->registry);
    }

    // ========================================================================
    // Italian (.it) conditional fields
    // ========================================================================

    #[Test]
    public function itBirthCountryDependsOnLegalType(): void
    {
        $conditionalFields = [
            [
                'field' => 'Registrant.Type',
                'conditions' => [
                    [
                        'context' => 'individual',
                        'fields' => ['Registrant.BirthCountry'],
                    ],
                ],
            ],
        ];

        $rules = $this->mapper->mapConditionalFields('.it', $conditionalFields);

        $this->assertArrayHasKey('Birth Country', $rules);
        $this->assertEquals('Legal Type', $rules['Birth Country']['depends_on']);
        $this->assertContains('Italian and foreign natural persons', $rules['Birth Country']['show_when']);
    }

    // ========================================================================
    // Canadian (.ca) conditional fields
    // ========================================================================

    #[Test]
    public function caTrademarkFieldsDependOnLegalType(): void
    {
        $conditionalFields = [
            [
                'field' => 'Registrant.Type',
                'conditions' => [
                    [
                        'context' => 'trademark',
                        'fields' => ['Trademark.Number', 'Trademark.Name', 'Trademark.Country'],
                    ],
                ],
            ],
        ];

        $rules = $this->mapper->mapConditionalFields('.ca', $conditionalFields);

        $this->assertArrayHasKey('Trademark Number', $rules);
        $this->assertArrayHasKey('Trademark Name', $rules);
        $this->assertArrayHasKey('Trademark Country', $rules);
        $this->assertEquals('Legal Type', $rules['Trademark Number']['depends_on']);
        $this->assertContains('Trademark', $rules['Trademark Number']['show_when']);
    }

    // ========================================================================
    // US (.us) conditional fields
    // ========================================================================

    #[Test]
    public function usNexusCountryDependsOnNexusCategory(): void
    {
        $conditionalFields = [
            [
                'field' => 'Domain.NexusCategory',
                'conditions' => [
                    [
                        'context' => 'foreign',
                        'fields' => ['Domain.NexusCountry'],
                    ],
                ],
            ],
        ];

        $rules = $this->mapper->mapConditionalFields('.us', $conditionalFields);

        $this->assertArrayHasKey('Nexus Country', $rules);
        $this->assertEquals('Nexus Category', $rules['Nexus Country']['depends_on']);
        $this->assertContains('C31', $rules['Nexus Country']['show_when']);
        $this->assertContains('C32', $rules['Nexus Country']['show_when']);
    }

    // ========================================================================
    // Russian (.ru) conditional fields
    // ========================================================================

    #[Test]
    public function ruPassportFieldsDependOnRegistrantType(): void
    {
        $conditionalFields = [
            [
                'field' => 'Registrant.Type',
                'conditions' => [
                    [
                        'context' => 'individual',
                        'fields' => ['Registrant.PassportNumber', 'Registrant.PassportIssueDate', 'Registrant.PassportIssuer', 'Registrant.BirthDate'],
                    ],
                    [
                        'context' => 'organization',
                        'fields' => ['Registrant.TIN'],
                    ],
                ],
            ],
        ];

        $rules = $this->mapper->mapConditionalFields('.ru', $conditionalFields);

        $this->assertArrayHasKey('Passport Number', $rules);
        $this->assertEquals('Registrant Type', $rules['Passport Number']['depends_on']);
        $this->assertContains('Individual', $rules['Passport Number']['show_when']);

        $this->assertArrayHasKey('TIN', $rules);
        $this->assertEquals('Registrant Type', $rules['TIN']['depends_on']);
        $this->assertContains('Organization', $rules['TIN']['show_when']);
    }

    // ========================================================================
    // French (.fr) conditional fields
    // ========================================================================

    #[Test]
    public function frBirthFieldsDependOnContactType(): void
    {
        $conditionalFields = [
            [
                'field' => 'Registrant.Type',
                'conditions' => [
                    [
                        'context' => 'individual',
                        'fields' => ['Registrant.BirthDate', 'Registrant.BirthPlace'],
                    ],
                    [
                        'context' => 'organization',
                        'fields' => ['Registrant.SIREN'],
                    ],
                ],
            ],
        ];

        $rules = $this->mapper->mapConditionalFields('.fr', $conditionalFields);

        $this->assertArrayHasKey('Birth Date', $rules);
        $this->assertEquals('Contact Type', $rules['Birth Date']['depends_on']);
        $this->assertContains('Individual', $rules['Birth Date']['show_when']);

        $this->assertArrayHasKey('SIREN/SIRET', $rules);
        $this->assertEquals('Contact Type', $rules['SIREN/SIRET']['depends_on']);
        $this->assertContains('Company', $rules['SIREN/SIRET']['show_when']);
    }

    // ========================================================================
    // Variant TLD resolution
    // ========================================================================

    #[Test]
    public function suVariantUsesRuMappings(): void
    {
        $conditionalFields = [
            [
                'field' => 'Registrant.Type',
                'conditions' => [
                    [
                        'context' => 'individual',
                        'fields' => ['Registrant.PassportNumber'],
                    ],
                ],
            ],
        ];

        $rules = $this->mapper->mapConditionalFields('.su', $conditionalFields);

        $this->assertArrayHasKey('Passport Number', $rules);
        $this->assertEquals('Registrant Type', $rules['Passport Number']['depends_on']);
    }

    // ========================================================================
    // Edge Cases
    // ========================================================================

    #[Test]
    public function emptyConditionalFieldsReturnsEmptyArray(): void
    {
        $rules = $this->mapper->mapConditionalFields('.it', []);
        $this->assertEmpty($rules);
    }

    #[Test]
    public function unknownApiFieldIsSkipped(): void
    {
        $conditionalFields = [
            [
                'field' => 'Unknown.Field',
                'conditions' => [
                    ['context' => 'test', 'fields' => ['Also.Unknown']],
                ],
            ],
        ];

        $rules = $this->mapper->mapConditionalFields('.it', $conditionalFields);
        $this->assertEmpty($rules);
    }

    #[Test]
    public function missingContextValueUsesRawValue(): void
    {
        $conditionalFields = [
            [
                'field' => 'Registrant.Type',
                'conditions' => [
                    [
                        'context' => 'unmapped_context_value',
                        'fields' => ['Registrant.BirthCountry'],
                    ],
                ],
            ],
        ];

        $rules = $this->mapper->mapConditionalFields('.it', $conditionalFields);

        $this->assertArrayHasKey('Birth Country', $rules);
        $this->assertContains('unmapped_context_value', $rules['Birth Country']['show_when']);
    }

    #[Test]
    public function multipleConditionsForSameFieldMergeShowWhen(): void
    {
        $conditionalFields = [
            [
                'field' => 'Registrant.Type',
                'conditions' => [
                    [
                        'context' => 'individual',
                        'fields' => ['Registrant.BirthDate'],
                    ],
                ],
            ],
            [
                'field' => 'Registrant.Type',
                'conditions' => [
                    [
                        'context' => 'organization',
                        'fields' => ['Registrant.BirthDate'],
                    ],
                ],
            ],
        ];

        $rules = $this->mapper->mapConditionalFields('.ru', $conditionalFields);

        $this->assertArrayHasKey('Birth Date', $rules);
        $this->assertContains('Individual', $rules['Birth Date']['show_when']);
        $this->assertContains('Organization', $rules['Birth Date']['show_when']);
    }
}
