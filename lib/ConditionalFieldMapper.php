<?php

namespace ascio;

/**
 * Conditional Field Mapper
 *
 * Maps API conditional field contexts to WHMCS depends_on/show_when format.
 *
 * API format:
 *   {"field": "Registrant.Type", "conditions": [{"context": "individual", "fields": ["Registrant.BirthCountry"]}]}
 *
 * WHMCS format:
 *   ["depends_on" => "Legal Type", "show_when" => ["Italian and foreign natural persons"]]
 */
class ConditionalFieldMapper
{
    private $registry;

    public function __construct(FieldRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Map API conditional fields to WHMCS format for a specific TLD.
     *
     * @param string $tld TLD with dot prefix
     * @param array $conditionalFields API conditional field definitions
     * @return array<string, array{depends_on: string, show_when: string[]}> WHMCS conditional rules
     */
    public function mapConditionalFields(string $tld, array $conditionalFields): array
    {
        $rules = [];

        foreach ($conditionalFields as $condition) {
            $dependsOnApiField = $condition['field'] ?? null;
            if (!$dependsOnApiField) {
                continue;
            }

            // Resolve the API field name to the WHMCS field name for this TLD
            $dependsOnDef = $this->registry->getFieldDefinition($dependsOnApiField, $tld);
            if (!$dependsOnDef) {
                continue;
            }
            $dependsOnWhmcsName = $dependsOnDef['Name'];

            $conditions = $condition['conditions'] ?? [];
            foreach ($conditions as $ctx) {
                $contextValue = $ctx['context'] ?? null;
                $contextFields = $ctx['fields'] ?? [];

                if (!$contextValue || empty($contextFields)) {
                    continue;
                }

                // Map the context value to WHMCS dropdown option labels
                $showWhen = $this->resolveContextToOptions($tld, $dependsOnApiField, $contextValue);
                if (empty($showWhen)) {
                    // If no mapping found, use the raw context value
                    $showWhen = [$contextValue];
                }

                // Create rules for each conditional field
                foreach ($contextFields as $conditionalApiField) {
                    $fieldDef = $this->registry->getFieldDefinition($conditionalApiField, $tld);
                    if (!$fieldDef) {
                        continue;
                    }

                    $fieldName = $fieldDef['Name'];

                    if (isset($rules[$fieldName])) {
                        // Merge show_when values if rule already exists for this field
                        $rules[$fieldName]['show_when'] = array_unique(
                            array_merge($rules[$fieldName]['show_when'], $showWhen)
                        );
                    } else {
                        $rules[$fieldName] = [
                            'depends_on' => $dependsOnWhmcsName,
                            'show_when' => $showWhen,
                        ];
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Resolve an API context value to WHMCS dropdown option labels.
     *
     * Uses the FieldRegistry context mappings to translate API values
     * like "individual" to option labels like "Italian and foreign natural persons".
     *
     * @param string $tld TLD with dot prefix
     * @param string $apiFieldName API field name
     * @param string $contextValue API context value
     * @return string[] Matching WHMCS option labels
     */
    private function resolveContextToOptions(string $tld, string $apiFieldName, string $contextValue): array
    {
        $contextMappings = $this->registry->getContextMappings($tld);

        if (isset($contextMappings[$apiFieldName][$contextValue])) {
            return $contextMappings[$apiFieldName][$contextValue];
        }

        return [];
    }
}
