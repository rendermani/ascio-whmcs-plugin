<?php

namespace ascio;

/**
 * Field Generator
 *
 * Combines TLDKit API data with FieldRegistry mappings to generate:
 * - resources/domains/additionalfields.php (WHMCS additional domain fields)
 * - assets/js/ascio-fields.js (client-side conditional field logic)
 * - resources/domains/conditional-rules.json (machine-readable cache)
 */
class FieldGenerator
{
    private $registry;
    private $conditionalMapper;

    public function __construct(FieldRegistry $registry, ConditionalFieldMapper $conditionalMapper)
    {
        $this->registry = $registry;
        $this->conditionalMapper = $conditionalMapper;
    }

    /**
     * Generate the complete additionalfields.php content.
     *
     * @param array $apiData TLD entries from TLDKit API
     * @return string PHP source code
     */
    public function generateAdditionalFields(array $apiData): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $output = "<?php\n";
        $output .= "/**\n";
        $output .= " * Ascio WHMCS Registrar Module - Additional Domain Fields\n";
        $output .= " *\n";
        $output .= " * AUTO-GENERATED from TLDKit API on {$timestamp}\n";
        $output .= " * Do not edit manually - changes will be overwritten by the next generation.\n";
        $output .= " * Edit FieldRegistry.php to change field definitions.\n";
        $output .= " *\n";
        $output .= " * @see https://docs.whmcs.com/Additional_Domain_Fields\n";
        $output .= " */\n\n";

        $processedTlds = [];
        $conditionalRulesAll = [];
        $variantTargets = [];

        // Collect which TLDs are variant targets (will be handled via copy)
        $variants = $this->registry->getTldVariants();
        foreach ($variants as $variant => $parent) {
            $variantTargets[$variant] = $parent;
        }

        // Process each TLD from API data
        foreach ($apiData as $tldEntry) {
            $tld = $this->normalizeTld($tldEntry['tld'] ?? $tldEntry['name'] ?? '');
            if (empty($tld) || isset($variantTargets[$tld])) {
                continue; // Skip variants - they'll be copied from parent
            }

            $requiredFields = $tldEntry['required_fields'] ?? [];
            $conditionalFields = $tldEntry['conditional_fields'] ?? [];

            $fields = $this->buildFieldsForTld($tld, $requiredFields, $conditionalFields);
            if (empty($fields) && empty($this->registry->getRemoveFields($tld))) {
                continue;
            }

            $output .= $this->generateTldSection($tld, $fields);
            $processedTlds[] = $tld;

            // Build conditional rules
            if (!empty($conditionalFields)) {
                $rules = $this->conditionalMapper->mapConditionalFields($tld, $conditionalFields);
                if (!empty($rules)) {
                    $conditionalRulesAll[$tld] = $rules;
                }
            }
        }

        // Generate variant TLD copies
        $output .= $this->generateVariantCopies($processedTlds);

        // Copy conditional rules to variants
        foreach ($variants as $variant => $parent) {
            if (isset($conditionalRulesAll[$parent])) {
                $conditionalRulesAll[$variant] = $conditionalRulesAll[$parent];
            }
        }

        // Generate conditional fields PHP section
        $output .= $this->generateConditionalFieldsPhp($conditionalRulesAll);

        return $output;
    }

    /**
     * Generate the ascio-fields.js content.
     *
     * @param array $apiData TLD entries from TLDKit API
     * @return string JavaScript source code
     */
    public function generateConditionalJs(array $apiData): string
    {
        $conditionalRulesAll = [];
        $variants = $this->registry->getTldVariants();

        foreach ($apiData as $tldEntry) {
            $tld = $this->normalizeTld($tldEntry['tld'] ?? $tldEntry['name'] ?? '');
            if (empty($tld)) {
                continue;
            }

            $conditionalFields = $tldEntry['conditional_fields'] ?? [];
            if (!empty($conditionalFields)) {
                $rules = $this->conditionalMapper->mapConditionalFields($tld, $conditionalFields);
                if (!empty($rules)) {
                    $conditionalRulesAll[$tld] = $rules;
                }
            }
        }

        // Copy rules to variants (expanded, not referenced)
        foreach ($variants as $variant => $parent) {
            if (isset($conditionalRulesAll[$parent])) {
                $conditionalRulesAll[$variant] = $conditionalRulesAll[$parent];
            }
        }

        return $this->renderJsFile($conditionalRulesAll);
    }

    /**
     * Generate the conditional-rules.json content.
     *
     * @param array $apiData TLD entries from TLDKit API
     * @return string JSON string
     */
    public function generateConditionalJson(array $apiData): string
    {
        $conditionalRulesAll = [];
        $variants = $this->registry->getTldVariants();

        foreach ($apiData as $tldEntry) {
            $tld = $this->normalizeTld($tldEntry['tld'] ?? $tldEntry['name'] ?? '');
            if (empty($tld)) {
                continue;
            }

            $conditionalFields = $tldEntry['conditional_fields'] ?? [];
            if (!empty($conditionalFields)) {
                $rules = $this->conditionalMapper->mapConditionalFields($tld, $conditionalFields);
                if (!empty($rules)) {
                    $conditionalRulesAll[$tld] = $rules;
                }
            }
        }

        foreach ($variants as $variant => $parent) {
            if (isset($conditionalRulesAll[$parent])) {
                $conditionalRulesAll[$variant] = $conditionalRulesAll[$parent];
            }
        }

        return json_encode($conditionalRulesAll, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Write all generated files to disk.
     *
     * @param array $apiData TLD entries from TLDKit API
     * @param string $basePath Base path of the ascio module directory
     * @return array Files written with their paths
     */
    public function writeAll(array $apiData, string $basePath): array
    {
        $basePath = rtrim($basePath, '/');
        $files = [];

        $phpContent = $this->generateAdditionalFields($apiData);
        $phpPath = $basePath . '/resources/domains/additionalfields.php';
        $this->writeFile($phpPath, $phpContent);
        $files['additionalfields.php'] = $phpPath;

        $jsContent = $this->generateConditionalJs($apiData);
        $jsPath = $basePath . '/assets/js/ascio-fields.js';
        $this->writeFile($jsPath, $jsContent);
        $files['ascio-fields.js'] = $jsPath;

        $jsonContent = $this->generateConditionalJson($apiData);
        $jsonPath = $basePath . '/resources/domains/conditional-rules.json';
        $this->writeFile($jsonPath, $jsonContent);
        $files['conditional-rules.json'] = $jsonPath;

        return $files;
    }

    /**
     * Build WHMCS field definitions for a TLD from API data.
     *
     * @param string $tld TLD with dot prefix
     * @param array $requiredFields API required field names
     * @param array $conditionalFields API conditional field definitions
     * @return array WHMCS field definitions
     */
    private function buildFieldsForTld(string $tld, array $requiredFields, array $conditionalFields): array
    {
        $fields = [];
        $addedFields = [];

        // Add required fields
        foreach ($requiredFields as $apiFieldName) {
            $def = $this->registry->getFieldDefinition($apiFieldName, $tld);
            if ($def && !isset($addedFields[$def['Name']])) {
                $fields[] = $def;
                $addedFields[$def['Name']] = true;
            }
        }

        // Add conditional fields (they're defined but may be hidden initially)
        foreach ($conditionalFields as $condition) {
            $conditions = $condition['conditions'] ?? [];
            foreach ($conditions as $ctx) {
                $contextFields = $ctx['fields'] ?? [];
                foreach ($contextFields as $apiFieldName) {
                    $def = $this->registry->getFieldDefinition($apiFieldName, $tld);
                    if ($def && !isset($addedFields[$def['Name']])) {
                        $fields[] = $def;
                        $addedFields[$def['Name']] = true;
                    }
                }
            }

            // Also ensure the dependency field itself is included
            $depField = $condition['field'] ?? null;
            if ($depField) {
                $def = $this->registry->getFieldDefinition($depField, $tld);
                if ($def && !isset($addedFields[$def['Name']])) {
                    // Insert at beginning since it's a dependency
                    array_unshift($fields, $def);
                    $addedFields[$def['Name']] = true;
                }
            }
        }

        return $fields;
    }

    /**
     * Generate PHP code for a single TLD section.
     */
    private function generateTldSection(string $tld, array $fields): string
    {
        $tldLabel = strtoupper(ltrim($tld, '.'));
        $output = "// " . str_repeat('=', 76) . "\n";
        $output .= "// {$tldLabel}\n";
        $output .= "// " . str_repeat('=', 76) . "\n";

        // Remove directives first
        $removeFields = $this->registry->getRemoveFields($tld);
        foreach ($removeFields as $fieldName) {
            $output .= "\$additionaldomainfields[\"{$tld}\"][] = array(\"Name\" => " . var_export($fieldName, true) . ", \"Remove\" => true);\n";
        }
        if (!empty($removeFields)) {
            $output .= "\n";
        }

        // Field definitions
        foreach ($fields as $field) {
            $output .= $this->renderFieldDefinition($tld, $field);
        }

        $output .= "\n";
        return $output;
    }

    /**
     * Render a single WHMCS field definition as PHP code.
     */
    private function renderFieldDefinition(string $tld, array $field): string
    {
        $parts = [];
        $parts[] = "\"Name\" => " . var_export($field['Name'], true);

        if (isset($field['LangVar'])) {
            $parts[] = "\"LangVar\" => " . var_export($field['LangVar'], true);
        }

        $parts[] = "\"Type\" => " . var_export($field['Type'], true);

        if ($field['Type'] === 'dropdown' && isset($field['Options'])) {
            $parts[] = "\"Options\" => " . var_export($field['Options'], true);
        }

        if ($field['Type'] === 'text' && isset($field['Size'])) {
            $parts[] = "\"Size\" => " . var_export($field['Size'], true);
        }

        if (isset($field['Default'])) {
            $parts[] = "\"Default\" => " . var_export($field['Default'], true);
        }

        $required = isset($field['Required']) ? ($field['Required'] ? 'true' : 'false') : 'false';
        $parts[] = "\"Required\" => {$required}";

        if (isset($field['Description'])) {
            $parts[] = "\"Description\" => " . var_export($field['Description'], true);
        }

        $inner = implode(",\n    ", $parts);
        return "\$additionaldomainfields[\"{$tld}\"][] = array(\n    {$inner}\n);\n";
    }

    /**
     * Generate PHP code for variant TLD copies.
     */
    private function generateVariantCopies(array $processedTlds): string
    {
        $output = '';
        $variants = $this->registry->getTldVariants();

        // Group variants by parent
        $grouped = [];
        foreach ($variants as $variant => $parent) {
            $grouped[$parent][] = $variant;
        }

        foreach ($grouped as $parent => $variantList) {
            if (!in_array($parent, $processedTlds)) {
                continue;
            }
            $output .= "// Copy {$parent} fields to variants\n";
            foreach ($variantList as $variant) {
                $output .= "\$additionaldomainfields[\"{$variant}\"] = \$additionaldomainfields[\"{$parent}\"];\n";
            }
            $output .= "\n";
        }

        return $output;
    }

    /**
     * Generate the conditional fields PHP section (stored in $GLOBALS).
     */
    private function generateConditionalFieldsPhp(array $conditionalRules): string
    {
        if (empty($conditionalRules)) {
            return '';
        }

        $output = "// " . str_repeat('=', 76) . "\n";
        $output .= "// Conditional Fields Configuration for JavaScript\n";
        $output .= "// " . str_repeat('=', 76) . "\n";
        $output .= "if (!isset(\$GLOBALS['ascio_conditional_fields'])) {\n";
        $output .= "    \$GLOBALS['ascio_conditional_fields'] = array(\n";

        $variants = $this->registry->getTldVariants();
        $variantTlds = array_keys($variants);

        // Write non-variant rules first
        foreach ($conditionalRules as $tld => $rules) {
            if (in_array($tld, $variantTlds)) {
                continue;
            }
            $output .= $this->renderConditionalRulesPhp($tld, $rules, '        ');
        }

        $output .= "    );\n\n";

        // Copy rules to variants
        $grouped = [];
        foreach ($variants as $variant => $parent) {
            $grouped[$parent][] = $variant;
        }

        foreach ($grouped as $parent => $variantList) {
            if (!isset($conditionalRules[$parent])) {
                continue;
            }
            $output .= "    // Copy conditional rules to {$parent} variants\n";
            foreach ($variantList as $variant) {
                $output .= "    \$GLOBALS['ascio_conditional_fields']['{$variant}'] = \$GLOBALS['ascio_conditional_fields']['{$parent}'];\n";
            }
        }

        $output .= "}\n";
        return $output;
    }

    /**
     * Render conditional rules for a single TLD as PHP array entries.
     */
    private function renderConditionalRulesPhp(string $tld, array $rules, string $indent): string
    {
        $output = "{$indent}'{$tld}' => array(\n";

        foreach ($rules as $fieldName => $rule) {
            $dependsOn = var_export($rule['depends_on'], true);
            $showWhenItems = array_map(function ($v) { return var_export($v, true); }, $rule['show_when']);
            $showWhen = implode(', ', $showWhenItems);

            $output .= "{$indent}    '{$fieldName}' => array(\n";
            $output .= "{$indent}        'depends_on' => {$dependsOn},\n";
            $output .= "{$indent}        'show_when' => array({$showWhen})\n";
            $output .= "{$indent}    ),\n";
        }

        $output .= "{$indent}),\n";
        return $output;
    }

    /**
     * Render the complete ascio-fields.js file.
     */
    private function renderJsFile(array $conditionalRules): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $rulesJson = json_encode($conditionalRules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Indent the JSON by 4 spaces to fit inside the IIFE
        $rulesJsonIndented = preg_replace('/^/m', '    ', $rulesJson);

        return <<<JS
/**
 * Ascio WHMCS Registrar Module - Conditional Domain Fields
 *
 * AUTO-GENERATED from TLDKit API on {$timestamp}
 * Do not edit manually - changes will be overwritten by the next generation.
 * Edit FieldRegistry.php to change field definitions.
 *
 * This script handles dynamic showing/hiding of additional domain fields
 * based on other field values (e.g., Legal Type, Registrant Type).
 *
 * Works in both client area (cart/domain config) and admin area.
 */
(function() {
    'use strict';

    // Conditional field rules - which fields to show based on other field values
    var conditionalRules = (function() {
        var raw = {$rulesJsonIndented};
        // Convert from JSON format to JS format
        var rules = {};
        for (var tld in raw) {
            if (raw.hasOwnProperty(tld)) {
                rules[tld] = {};
                for (var field in raw[tld]) {
                    if (raw[tld].hasOwnProperty(field)) {
                        rules[tld][field] = {
                            dependsOn: raw[tld][field].depends_on,
                            showWhen: raw[tld][field].show_when
                        };
                    }
                }
            }
        }
        return rules;
    })();

    /**
     * Get TLD from domain name or page context
     */
    function getCurrentTld() {
        // Try to get from domain name input
        var domainInput = document.querySelector('input[name="domain"]') ||
                          document.querySelector('input[name="domainname"]') ||
                          document.querySelector('#inputDomain');

        if (domainInput && domainInput.value) {
            var domain = domainInput.value;
            var parts = domain.split('.');
            if (parts.length >= 2) {
                // Handle multi-level TLDs like co.uk, com.sg
                if (parts.length >= 3 && ['co', 'com', 'org', 'edu', 'net', 'per'].indexOf(parts[parts.length - 2]) !== -1) {
                    return '.' + parts[parts.length - 2] + '.' + parts[parts.length - 1];
                }
                return '.' + parts[parts.length - 1];
            }
        }

        // Try to get from TLD select dropdown (cart)
        var tldSelect = document.querySelector('select[name="tld"]');
        if (tldSelect && tldSelect.value) {
            return tldSelect.value.indexOf('.') === 0 ? tldSelect.value : '.' + tldSelect.value;
        }

        // Try to get from page URL or hidden fields
        var hiddenTld = document.querySelector('input[name="tld"]');
        if (hiddenTld && hiddenTld.value) {
            return hiddenTld.value.indexOf('.') === 0 ? hiddenTld.value : '.' + hiddenTld.value;
        }

        // Try to extract from page title or breadcrumb
        var pageTitle = document.title || '';
        var tldMatch = pageTitle.match(/\\.([a-z]{2,})/i);
        if (tldMatch) {
            return '.' + tldMatch[1].toLowerCase();
        }

        return null;
    }

    /**
     * Find field element by name (works in both client and admin areas)
     */
    function findFieldElement(fieldName) {
        // Common selectors for additional domain fields
        var selectors = [
            // WHMCS 8+ client area
            'input[name="domainfield[' + fieldName + ']"]',
            'select[name="domainfield[' + fieldName + ']"]',
            'textarea[name="domainfield[' + fieldName + ']"]',
            // Admin area
            'input[name="additionalfields[' + fieldName + ']"]',
            'select[name="additionalfields[' + fieldName + ']"]',
            'textarea[name="additionalfields[' + fieldName + ']"]',
            // Legacy selectors
            '#' + fieldName.replace(/\\s+/g, ''),
            '[data-fieldname="' + fieldName + '"]'
        ];

        for (var i = 0; i < selectors.length; i++) {
            try {
                var el = document.querySelector(selectors[i]);
                if (el) return el;
            } catch (e) {
                // Ignore selector errors
            }
        }

        // Fallback: search by label text
        var labels = document.querySelectorAll('label');
        for (var j = 0; j < labels.length; j++) {
            var labelText = labels[j].textContent || labels[j].innerText;
            if (labelText.trim() === fieldName || labelText.indexOf(fieldName) !== -1) {
                // Get the associated input
                var forAttr = labels[j].getAttribute('for');
                if (forAttr) {
                    return document.getElementById(forAttr);
                }
                // Check for input inside label
                var input = labels[j].querySelector('input, select, textarea');
                if (input) return input;
                // Check next sibling
                var next = labels[j].nextElementSibling;
                if (next && (next.tagName === 'INPUT' || next.tagName === 'SELECT' || next.tagName === 'TEXTAREA')) {
                    return next;
                }
            }
        }

        return null;
    }

    /**
     * Get the row/container element for a field (for hiding/showing)
     */
    function getFieldRow(fieldElement) {
        if (!fieldElement) return null;

        // Walk up the DOM to find the row container
        var parent = fieldElement.parentElement;
        var maxDepth = 5;
        var depth = 0;

        while (parent && depth < maxDepth) {
            // Check for common row classes
            if (parent.classList.contains('form-group') ||
                parent.classList.contains('row') ||
                parent.classList.contains('field-row') ||
                parent.classList.contains('domain-field') ||
                parent.tagName === 'TR') {
                return parent;
            }
            parent = parent.parentElement;
            depth++;
        }

        // Fallback: return the parent of the input
        return fieldElement.parentElement;
    }

    /**
     * Show or hide a field row
     */
    function setFieldVisibility(fieldName, visible) {
        var fieldElement = findFieldElement(fieldName);
        if (!fieldElement) {
            return;
        }

        var row = getFieldRow(fieldElement);
        if (row) {
            row.style.display = visible ? '' : 'none';

            // Also update required attribute if hiding
            if (!visible) {
                fieldElement.removeAttribute('required');
                fieldElement.classList.remove('required');
            }
        }
    }

    /**
     * Get the current value of a field
     */
    function getFieldValue(fieldName) {
        var fieldElement = findFieldElement(fieldName);
        if (!fieldElement) return null;

        if (fieldElement.tagName === 'SELECT') {
            return fieldElement.options[fieldElement.selectedIndex] ?
                   fieldElement.options[fieldElement.selectedIndex].text :
                   fieldElement.value;
        }
        if (fieldElement.type === 'checkbox') {
            return fieldElement.checked ? 'Yes' : 'No';
        }
        return fieldElement.value;
    }

    /**
     * Apply conditional rules for a TLD
     */
    function applyConditionalRules(tld) {
        var rules = conditionalRules[tld];
        if (!rules) return;

        for (var fieldName in rules) {
            if (rules.hasOwnProperty(fieldName)) {
                var rule = rules[fieldName];
                var dependsOnValue = getFieldValue(rule.dependsOn);
                var shouldShow = rule.showWhen.indexOf(dependsOnValue) !== -1;
                setFieldVisibility(fieldName, shouldShow);
            }
        }
    }

    /**
     * Set up event listeners for dependency fields
     */
    function setupEventListeners(tld) {
        var rules = conditionalRules[tld];
        if (!rules) return;

        // Collect unique dependency field names
        var dependencyFields = {};
        for (var fieldName in rules) {
            if (rules.hasOwnProperty(fieldName)) {
                var dependsOn = rules[fieldName].dependsOn;
                dependencyFields[dependsOn] = true;
            }
        }

        // Add change listeners to dependency fields
        for (var depFieldName in dependencyFields) {
            if (dependencyFields.hasOwnProperty(depFieldName)) {
                var depElement = findFieldElement(depFieldName);
                if (depElement) {
                    (function(currentTld) {
                        depElement.addEventListener('change', function() {
                            applyConditionalRules(currentTld);
                        });
                    })(tld);
                }
            }
        }
    }

    /**
     * Initialize conditional fields for the current page
     */
    function init() {
        var tld = getCurrentTld();
        if (!tld) {
            // If no TLD detected, set up observer to wait for domain input
            var observer = new MutationObserver(function(mutations) {
                var newTld = getCurrentTld();
                if (newTld) {
                    observer.disconnect();
                    initForTld(newTld);
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
            return;
        }
        initForTld(tld);
    }

    /**
     * Initialize for a specific TLD
     */
    function initForTld(tld) {
        // Check if we have rules for this TLD
        if (!conditionalRules[tld]) {
            return;
        }

        // Apply initial state
        applyConditionalRules(tld);

        // Set up event listeners
        setupEventListeners(tld);

        // Also listen for TLD changes (in cart)
        var tldSelect = document.querySelector('select[name="tld"]');
        if (tldSelect) {
            tldSelect.addEventListener('change', function() {
                var newTld = this.value.indexOf('.') === 0 ? this.value : '.' + this.value;
                applyConditionalRules(newTld);
                setupEventListeners(newTld);
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also initialize after a short delay (for dynamically loaded content)
    setTimeout(init, 500);
    setTimeout(init, 1500);

    // Expose for debugging
    window.AscioFields = {
        conditionalRules: conditionalRules,
        getCurrentTld: getCurrentTld,
        applyConditionalRules: applyConditionalRules,
        init: init
    };

})();
JS;
    }

    /**
     * Normalize a TLD to have a dot prefix.
     */
    private function normalizeTld(string $tld): string
    {
        $tld = strtolower(trim($tld));
        if (empty($tld)) {
            return '';
        }
        if ($tld[0] !== '.') {
            $tld = '.' . $tld;
        }
        return $tld;
    }

    /**
     * Write content to a file, creating directories if needed.
     *
     * @throws \RuntimeException If the file cannot be written
     */
    private function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new \RuntimeException("Cannot create directory: {$dir}");
            }
        }

        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException("Cannot write file: {$path}");
        }
    }
}
