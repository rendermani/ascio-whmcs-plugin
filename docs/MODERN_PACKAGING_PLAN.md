# Ascio WHMCS Plugin - Modern Packaging & Installation Plan

## Executive Summary

This plan modernizes the Ascio WHMCS plugin to use current best practices:
1. **Companion Addon Module** for automatic installation/database setup
2. **Enhanced hooks.php** for JavaScript injection (conditional fields)
3. **Consolidated additional fields** with dynamic loading
4. **Single ZIP distribution** with all components

## Current State Problems

### Installation Issues
1. `install.php` uses deprecated `mysql_*` functions (removed in PHP 7.0)
2. Manual file copying required to multiple locations
3. Additional fields must be manually merged into WHMCS configuration
4. No automatic database migrations
5. No version management

### Additional Fields Issues
1. Fields scattered across `tlds/*/additionaldomainfields.php`
2. Must be manually copied to `/resources/domains/additionalfields.php`
3. No JavaScript for conditional field logic (e.g., .it birth country)
4. No dynamic state/province dropdowns

---

## Modern Architecture

### Directory Structure

```
ascio/
├── domains/                          # Registrar module
│   ├── ascio.php                     # Main module
│   ├── hooks.php                     # Enhanced with JS injection
│   ├── lib/
│   │   ├── Request.php
│   │   ├── AdditionalFields.php      # NEW: Centralized field definitions
│   │   └── ...
│   ├── assets/                       # NEW: Frontend assets
│   │   ├── js/
│   │   │   └── ascio-fields.js       # Conditional field logic
│   │   └── css/
│   │       └── ascio-fields.css
│   └── tlds/
│       └── ...
│
├── addon/                            # NEW: Companion addon module
│   └── ascio_tools/
│       ├── ascio_tools.php           # Addon with _activate/_deactivate
│       ├── hooks.php                 # Addon hooks
│       └── lib/
│           ├── Installer.php         # Database migrations
│           └── FieldsManager.php     # Additional fields deployment
│
└── installer/                        # NEW: Unified installer
    ├── install.php                   # Modern installer script
    └── migrations/
        ├── 001_create_tables.php
        ├── 002_add_domain_column.php
        └── ...
```

### Components

#### 1. Companion Addon Module (`ascio_tools`)

```php
<?php
// modules/addons/ascio_tools/ascio_tools.php

use Illuminate\Database\Capsule\Manager as Capsule;

function ascio_tools_config() {
    return [
        'name' => 'Ascio Tools',
        'description' => 'Companion module for Ascio Domain Registrar',
        'version' => '2.0.0',
        'author' => 'Tucows Inc.',
        'fields' => [
            'auto_deploy_fields' => [
                'FriendlyName' => 'Auto-deploy Additional Fields',
                'Type' => 'yesno',
                'Description' => 'Automatically deploy additional domain fields on activation',
                'Default' => 'yes',
            ],
        ],
    ];
}

function ascio_tools_activate() {
    try {
        // Create tables using Capsule (Laravel Schema Builder)
        if (!Capsule::schema()->hasTable('tblasciotlds')) {
            Capsule::schema()->create('tblasciotlds', function($table) {
                $table->string('Tld', 255)->unique();
                $table->integer('Threshold')->nullable();
                $table->boolean('Renew')->default(false);
                $table->boolean('LocalPresenceRequired')->default(false);
                $table->boolean('LocalPresenceOffered')->default(false);
                $table->boolean('AuthCodeRequired')->default(true);
                $table->string('Country', 255)->nullable();
                $table->timestamp('LastUpdated')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('tblasciojobs')) {
            Capsule::schema()->create('tblasciojobs', function($table) {
                $table->increments('id');
                $table->integer('last_id')->index();
                $table->string('order_id', 255)->index();
                $table->string('method', 255);
                $table->text('request');
                $table->text('response');
                $table->timestamp('date')->useCurrent();
            });
        }

        if (!Capsule::schema()->hasTable('tblasciohandles')) {
            Capsule::schema()->create('tblasciohandles', function($table) {
                $table->string('type', 256);
                $table->integer('whmcs_id')->index();
                $table->string('ascio_id', 256)->index();
                $table->string('domain', 255)->index();
            });
        }

        if (!Capsule::schema()->hasTable('mod_asciosession')) {
            Capsule::schema()->create('mod_asciosession', function($table) {
                $table->string('account', 255)->unique();
                $table->string('sessionId', 255);
                $table->timestamp('timestamp')->useCurrent();
                $table->index('timestamp', 'date');
            });
        }

        // Deploy additional fields
        ascio_tools_deploy_additional_fields();

        // Sync TLD data
        ascio_tools_sync_tlds();

        return [
            'status' => 'success',
            'description' => 'Ascio Tools activated. Database tables created and additional fields deployed.',
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Activation failed: ' . $e->getMessage(),
        ];
    }
}

function ascio_tools_deactivate() {
    // Note: We don't drop tables on deactivation to preserve data
    return [
        'status' => 'success',
        'description' => 'Ascio Tools deactivated. Database tables preserved.',
    ];
}

function ascio_tools_deploy_additional_fields() {
    $whmcsRoot = realpath(__DIR__ . '/../../../');
    $fieldsSource = __DIR__ . '/../../registrars/ascio/lib/AdditionalFields.php';
    $fieldsTarget = $whmcsRoot . '/resources/domains/additionalfields.php';

    // Backup existing if present
    if (file_exists($fieldsTarget)) {
        copy($fieldsTarget, $fieldsTarget . '.backup.' . date('YmdHis'));
    }

    // Include the Ascio additional fields
    $content = "<?php\n// Ascio Additional Domain Fields - Auto-deployed\n";
    $content .= "// Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $content .= "require_once __DIR__ . '/../../modules/registrars/ascio/lib/AdditionalFields.php';\n";

    file_put_contents($fieldsTarget, $content);
}

function ascio_tools_sync_tlds() {
    // Sync TLD data from TLDKit API
    $url = 'https://aws.ascio.info/tldkit.xq';
    $response = file_get_contents($url);
    if ($response) {
        $tlds = json_decode($response);
        foreach ($tlds->tld as $tld) {
            Capsule::table('tblasciotlds')->updateOrInsert(
                ['Tld' => $tld->tld],
                [
                    'Threshold' => $tld->Threshold,
                    'Renew' => $tld->Renew === 'true' ? 1 : 0,
                    'LocalPresenceRequired' => $tld->LocalPresenceRequired === 'true' ? 1 : 0,
                    'LocalPresenceOffered' => $tld->LocalPresenceOffered === 'true' ? 1 : 0,
                    'AuthCodeRequired' => $tld->AuthCodeRequired === 'true' ? 1 : 0,
                    'Country' => $tld->Country,
                    'LastUpdated' => date('Y-m-d H:i:s'),
                ]
            );
        }
    }
}
```

#### 2. Enhanced hooks.php (JavaScript Injection)

```php
<?php
// modules/registrars/ascio/hooks.php

use ascio\v2\domains\Request as Request;

require_once(__DIR__ . "/lib/Request.php");

// Existing hook for domain status
function hook_set_domain_status($vars) {
    if (strpos($vars["params"]["registrar"], "ascio") === false) return;
    $request = new Request([
        'Account' => $vars["params"]["Username"],
        'Password' => $vars["params"]["Password"]
    ]);
    $domain = $vars["params"]["sld"] . "." . $vars["params"]["tld"];
    logActivity("Calling hook for domain " . $domain);
    $type = $vars["params"]["regtype"] == "Transfer" ? "Transfer_Domain" : false;
    $domainObj = (Object) ["DomainName" => $domain];
    $request->setStatus($domainObj, "Pending", $type);
}

add_hook("AfterRegistrarRegistration", 1, "hook_set_domain_status");
add_hook("AfterRegistrarTransfer", 1, "hook_set_domain_status");

// NEW: Inject JavaScript for conditional additional fields
function ascio_inject_conditional_fields_js($vars) {
    // Only on domain configuration pages
    $validPages = ['cart', 'configuredomains', 'clientareadomaindetails'];
    if (!in_array($vars['filename'] ?? '', $validPages)) {
        return '';
    }

    $jsPath = '../modules/registrars/ascio/assets/js/ascio-fields.js';
    $cssPath = '../modules/registrars/ascio/assets/css/ascio-fields.css';

    return <<<HTML
<link rel="stylesheet" href="{$cssPath}" type="text/css">
<script src="{$jsPath}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AscioFields !== 'undefined') {
        AscioFields.init();
    }
});
</script>
HTML;
}

add_hook("ClientAreaHeadOutput", 1, "ascio_inject_conditional_fields_js");

// NEW: Admin area JavaScript injection
function ascio_admin_inject_conditional_fields_js($vars) {
    $validPages = ['clientsdomains', 'domains'];
    if (!in_array($vars['filename'] ?? '', $validPages)) {
        return '';
    }

    $jsPath = '../modules/registrars/ascio/assets/js/ascio-fields.js';

    return <<<HTML
<script src="{$jsPath}"></script>
<script>
$(document).ready(function() {
    if (typeof AscioFields !== 'undefined') {
        AscioFields.init();
    }
});
</script>
HTML;
}

add_hook("AdminAreaHeadOutput", 1, "ascio_admin_inject_conditional_fields_js");
```

#### 3. JavaScript for Conditional Fields

```javascript
// modules/registrars/ascio/assets/js/ascio-fields.js

var AscioFields = {
    config: {
        // .IT conditional fields
        it: {
            conditionalFields: {
                'Birth country': {
                    showWhen: {
                        field: 'Legal Type',
                        values: ['Italian and foreign natural persons']
                    }
                }
            },
            statesByCountry: {
                'IT': [
                    'Agrigento', 'Alessandria', 'Ancona', 'Aosta', 'Arezzo',
                    // ... all Italian provinces
                ],
                // Other countries don't need state for .it
            }
        },
        // .US conditional fields
        us: {
            conditionalFields: {
                'Nexus Country': {
                    showWhen: {
                        field: 'Application Purpose',
                        values: ['P2', 'P3']
                    }
                }
            }
        },
        // .CA conditional fields
        ca: {
            conditionalFields: {
                'Trademark Number': {
                    showWhen: {
                        field: 'Legal Type',
                        values: ['Trade-mark registered in Canada']
                    }
                },
                'Trademark Name': {
                    showWhen: {
                        field: 'Legal Type',
                        values: ['Trade-mark registered in Canada']
                    }
                },
                'Trademark Country': {
                    showWhen: {
                        field: 'Legal Type',
                        values: ['Trade-mark registered in Canada']
                    }
                }
            }
        },
        // .RU conditional fields
        ru: {
            conditionalFields: {
                'Individuals Birthday': {
                    showWhen: {
                        field: 'Registrant Type',
                        values: ['Individual']
                    }
                },
                'Russian Organizations Taxpayer Number 1': {
                    showWhen: {
                        field: 'Registrant Type',
                        values: ['Organization']
                    }
                }
            }
        }
    },

    init: function() {
        this.detectTld();
        this.bindEvents();
        this.applyInitialState();
    },

    detectTld: function() {
        // Detect TLD from domain name field or page context
        var domainField = document.querySelector('[name="domain"]') ||
                         document.querySelector('[name="domainname"]');
        if (domainField) {
            var domain = domainField.value;
            var parts = domain.split('.');
            this.currentTld = parts.length > 1 ? parts.slice(1).join('.') : '';
        }

        // Also check URL for TLD context
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('tld')) {
            this.currentTld = urlParams.get('tld').replace(/^\./, '');
        }
    },

    bindEvents: function() {
        var self = this;

        // Bind to all select fields that might trigger conditional logic
        document.querySelectorAll('select[name^="domainfield"]').forEach(function(select) {
            select.addEventListener('change', function() {
                self.handleFieldChange(this);
            });
        });

        // Bind to country dropdowns for state/province updates
        document.querySelectorAll('select[name*="country"], select[name*="Country"]').forEach(function(select) {
            select.addEventListener('change', function() {
                self.handleCountryChange(this);
            });
        });
    },

    handleFieldChange: function(field) {
        var tldConfig = this.config[this.currentTld];
        if (!tldConfig || !tldConfig.conditionalFields) return;

        var fieldName = this.getFieldName(field);

        Object.keys(tldConfig.conditionalFields).forEach(function(targetField) {
            var condition = tldConfig.conditionalFields[targetField];
            if (condition.showWhen.field === fieldName) {
                var targetElement = this.findFieldByName(targetField);
                if (targetElement) {
                    var shouldShow = condition.showWhen.values.includes(field.value);
                    this.toggleField(targetElement, shouldShow);
                }
            }
        }.bind(this));
    },

    handleCountryChange: function(countryField) {
        var tldConfig = this.config[this.currentTld];
        if (!tldConfig || !tldConfig.statesByCountry) return;

        var country = countryField.value;
        var states = tldConfig.statesByCountry[country] || [];

        // Find associated state field
        var stateField = this.findAssociatedStateField(countryField);
        if (stateField && states.length > 0) {
            this.populateStateDropdown(stateField, states);
        }
    },

    toggleField: function(element, show) {
        var container = element.closest('.form-group') || element.closest('tr') || element.parentElement;
        if (container) {
            container.style.display = show ? '' : 'none';

            // Update required attribute
            var input = container.querySelector('input, select, textarea');
            if (input) {
                if (show && input.dataset.wasRequired === 'true') {
                    input.required = true;
                } else {
                    input.dataset.wasRequired = input.required ? 'true' : 'false';
                    input.required = false;
                }
            }
        }
    },

    findFieldByName: function(name) {
        return document.querySelector('[name*="' + name + '"]') ||
               document.querySelector('[data-field-name="' + name + '"]');
    },

    getFieldName: function(field) {
        // Extract field name from various naming conventions
        var name = field.name || '';
        var match = name.match(/\[([^\]]+)\]$/);
        return match ? match[1] : name;
    },

    findAssociatedStateField: function(countryField) {
        // Find state field in same form group or row
        var container = countryField.closest('form') || document;
        return container.querySelector('select[name*="state"], select[name*="State"]');
    },

    populateStateDropdown: function(stateField, states) {
        var currentValue = stateField.value;
        stateField.innerHTML = '<option value="">Select State/Province</option>';

        states.forEach(function(state) {
            var option = document.createElement('option');
            option.value = state;
            option.textContent = state;
            if (state === currentValue) {
                option.selected = true;
            }
            stateField.appendChild(option);
        });
    },

    applyInitialState: function() {
        // Apply initial visibility based on current field values
        var tldConfig = this.config[this.currentTld];
        if (!tldConfig || !tldConfig.conditionalFields) return;

        Object.keys(tldConfig.conditionalFields).forEach(function(targetField) {
            var condition = tldConfig.conditionalFields[targetField];
            var sourceField = this.findFieldByName(condition.showWhen.field);
            if (sourceField) {
                this.handleFieldChange(sourceField);
            }
        }.bind(this));
    }
};
```

#### 4. Centralized Additional Fields Definition

```php
<?php
// modules/registrars/ascio/lib/AdditionalFields.php

/**
 * Ascio Domain Additional Fields
 *
 * This file consolidates all additional domain field definitions.
 * It is automatically included in WHMCS via the ascio_tools addon.
 */

// .IT - Italy
$additionaldomainfields[".it"] = [];
$additionaldomainfields[".it"][] = [
    "Name" => "Legal Type",
    "LangVar" => "itlegaltype",
    "Type" => "dropdown",
    "Options" => implode(',', [
        "Italian and foreign natural persons",
        "Companies/one man companies",
        "Freelance workers/professionals",
        "non-profit organizations",
        "public organizations",
        "other subjects",
        "non natural foreigners"
    ]),
    "Required" => true,
    "Description" => "Legal type of the registrant",
];
$additionaldomainfields[".it"][] = [
    "Name" => "Tax ID",
    "LangVar" => "ittaxid",
    "Type" => "text",
    "Size" => "20",
    "Required" => true,
    "Description" => "Tax identification number (Codice Fiscale for individuals, VAT for companies)",
];
$additionaldomainfields[".it"][] = [
    "Name" => "Birth country",
    "LangVar" => "itbirthcountry",
    "Type" => "dropdown",
    "Options" => "{Countries}",
    "Required" => false,
    "Description" => "Country of birth (required for natural persons)",
    "Requires" => [
        "Legal Type" => "Italian and foreign natural persons"
    ],
];

// .CA - Canada
$additionaldomainfields[".ca"] = [];
$additionaldomainfields[".ca"][] = [
    "Name" => "Legal Type",
    "LangVar" => "calegaltype",
    "Type" => "dropdown",
    "Options" => implode(',', [
        "Corporation",
        "Canadian Citizen",
        "Permanent Resident of Canada",
        "Government",
        "Canadian Educational Institution",
        "Canadian Unincorporated Association",
        "Canadian Hospital",
        "Partnership Registered in Canada",
        "Trade-mark registered in Canada",
        "Canadian Trade Union",
        "Canadian Political Party",
        "Canadian Library Archive or Museum",
        "Trust established in Canada",
        "Aboriginal Peoples",
        "Legal Representative of a Canadian Citizen",
        "Official mark registered in Canada"
    ]),
    "Required" => true,
    "Description" => "Legal type of registrant contact",
];
$additionaldomainfields[".ca"][] = [
    "Name" => "Canadian Citizen",
    "LangVar" => "cacitizen",
    "Type" => "tickbox",
    "Description" => "Check if registrant is a Canadian citizen not residing in Canada",
];
$additionaldomainfields[".ca"][] = [
    "Name" => "Trademark Number",
    "LangVar" => "catrademarknumber",
    "Type" => "text",
    "Description" => "Trademark registration number (only for trademark-based registrations)",
    "Requires" => [
        "Legal Type" => "Trade-mark registered in Canada"
    ],
];
$additionaldomainfields[".ca"][] = [
    "Name" => "Trademark Name",
    "LangVar" => "catrademarkname",
    "Type" => "text",
    "Description" => "Registered trademark name",
    "Requires" => [
        "Legal Type" => "Trade-mark registered in Canada"
    ],
];
$additionaldomainfields[".ca"][] = [
    "Name" => "Trademark Country",
    "LangVar" => "catrademarkcountry",
    "Type" => "dropdown",
    "Options" => "{Countries}",
    "Description" => "Country where trademark is registered",
    "Requires" => [
        "Legal Type" => "Trade-mark registered in Canada"
    ],
];

// .US - United States
$additionaldomainfields[".us"] = [];
$additionaldomainfields[".us"][] = [
    "Name" => "Domain Purpose",
    "LangVar" => "uspurpose",
    "Type" => "dropdown",
    "Options" => "P1|Business use for profit,P2|Non-profit business,P3|Personal use",
    "Required" => true,
    "Description" => "Purpose of the domain registration",
];

// .EE - Estonia
$additionaldomainfields[".ee"] = [];
$additionaldomainfields[".ee"][] = [
    "Name" => "Registrant Type",
    "LangVar" => "eeregistranttype",
    "Type" => "dropdown",
    "Options" => "org|Organization,priv|Private Person",
    "Required" => true,
];
$additionaldomainfields[".ee"][] = [
    "Name" => "Registrant Number",
    "LangVar" => "eeregistrantnumber",
    "Type" => "text",
    "Required" => true,
    "Description" => "ID code (for persons) or registry code (for organizations)",
];
$additionaldomainfields[".ee"][] = [
    "Name" => "Admin Type",
    "LangVar" => "eeadmintype",
    "Type" => "dropdown",
    "Options" => "org|Organization,priv|Private Person",
];
$additionaldomainfields[".ee"][] = [
    "Name" => "Admin Number",
    "LangVar" => "eeadminnumber",
    "Type" => "text",
];
$additionaldomainfields[".ee"][] = [
    "Name" => "Tech Type",
    "LangVar" => "eetechtype",
    "Type" => "dropdown",
    "Options" => "org|Organization,priv|Private Person",
];
$additionaldomainfields[".ee"][] = [
    "Name" => "Tech Number",
    "LangVar" => "eetechnumber",
    "Type" => "text",
];

// .FR - France (AFNIC)
$additionaldomainfields[".fr"] = [];
$additionaldomainfields[".fr"][] = [
    "Name" => "VAT (Company)",
    "LangVar" => "frvat",
    "Type" => "text",
    "Size" => "20",
    "Description" => "VAT number (required for companies)",
];
$additionaldomainfields[".fr"][] = [
    "Name" => "City of birth (Individual)",
    "LangVar" => "frbirthcity",
    "Type" => "text",
    "Size" => "40",
    "Description" => "City of birth (required for individuals)",
];
$additionaldomainfields[".fr"][] = [
    "Name" => "Country of birth (Individual)",
    "LangVar" => "frbirthcountry",
    "Type" => "dropdown",
    "Options" => "{Countries}",
    "Description" => "Country of birth (required for individuals)",
];
$additionaldomainfields[".fr"][] = [
    "Name" => "Date of birth (Individual)",
    "LangVar" => "frbirthdate",
    "Type" => "text",
    "Size" => "10",
    "Description" => "Date of birth YYYY-MM-DD (required for individuals)",
];
$additionaldomainfields[".fr"][] = [
    "Name" => "Postal code of city of birth (Individual)",
    "LangVar" => "frbirthpostal",
    "Type" => "text",
    "Size" => "10",
    "Description" => "Postal code of birth city (required for individuals)",
];

// AFNIC TLDs inherit from .fr
$additionaldomainfields[".pm"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".re"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".tf"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".wf"] = $additionaldomainfields[".fr"];
$additionaldomainfields[".yt"] = $additionaldomainfields[".fr"];

// .NL - Netherlands
$additionaldomainfields[".nl"] = [];
$additionaldomainfields[".nl"][] = [
    "Name" => "Organisation Number",
    "LangVar" => "nlregistrantnumber",
    "Type" => "text",
    "Required" => false,
    "Description" => "Organization number (KvK number, required for companies)",
];

// .HK - Hong Kong
$additionaldomainfields[".hk"] = [];
$additionaldomainfields[".hk"][] = [
    "Name" => "Registrant Type",
    "LangVar" => "hkregistranttype",
    "Type" => "dropdown",
    "Options" => "ind|Individual,org|Organisation",
    "Required" => true,
];
$additionaldomainfields[".hk"][] = [
    "Name" => "Registrant Number",
    "LangVar" => "hkregistrantnumber",
    "Type" => "text",
    "Required" => true,
    "Description" => "HKID (individuals) or BR/CR number (organizations)",
];

// .BR - Brazil
$additionaldomainfields[".br"] = [];
$additionaldomainfields[".br"][] = [
    "Name" => "VAT Number",
    "LangVar" => "brvatnumber",
    "Type" => "text",
    "Required" => true,
    "Description" => "CPF (individuals) or CNPJ (companies)",
];

// Continue for all other TLDs...
// See ADDITIONAL_FIELDS_REPORT.md for complete list
```

---

## Installation Flow

### For New Customers (ZIP Distribution)

```
ascio-whmcs-2.0.0.zip
├── modules/
│   ├── registrars/
│   │   └── ascio/           # Complete registrar module
│   └── addons/
│       └── ascio_tools/     # Companion addon
├── includes/
│   └── hooks/
│       └── ascio.php        # Global hooks (optional)
└── INSTALL.md
```

**Installation Steps:**
1. Extract ZIP to WHMCS root directory
2. Navigate to **Addons > Addon Modules**
3. Find "Ascio Tools" and click **Activate**
   - Database tables created automatically
   - Additional fields deployed automatically
   - TLD data synced from TLDKit API
4. Navigate to **Configuration > Domain Registrars**
5. Activate "Ascio" and enter API credentials
6. Done!

### For Existing Customers (Upgrade)

The addon activation will:
1. Detect existing tables and preserve data
2. Run migrations for schema updates
3. Merge additional fields (backup existing first)
4. Offer to resync TLD data

---

## Benefits of This Approach

### For Customers
- **One-click installation** via addon activation
- **No manual file copying** for additional fields
- **Automatic updates** to field definitions
- **Conditional fields work** without template edits

### For Development
- **Single source of truth** for additional fields
- **Testable** JavaScript with unit tests
- **Version management** via addon config
- **Easy rollback** via addon deactivation

### WHMCS Compliance
- Follows [WHMCS Developer Documentation](https://developers.whmcs.com/)
- Uses [Capsule ORM](https://developers.whmcs.com/addon-modules/installation-uninstallation/) for database
- Hooks follow [WHMCS Hook Reference](https://developers.whmcs.com/hooks-reference/output/)
- Compatible with WHMCS 8.x and 9.x

---

## Migration Plan

### Phase 1: Create Addon Module
1. Create `modules/addons/ascio_tools/` directory structure
2. Implement `_activate()` with database creation
3. Implement additional fields deployment
4. Test activation/deactivation cycle

### Phase 2: Enhance Hooks
1. Update `hooks.php` with JavaScript injection
2. Create `assets/js/ascio-fields.js`
3. Test conditional fields for .it, .ca, .us, .fr

### Phase 3: Consolidate Additional Fields
1. Create `lib/AdditionalFields.php`
2. Migrate all TLD definitions
3. Remove scattered `additionaldomainfields.php` files
4. Update TLD plugins to use new field names

### Phase 4: Testing & Documentation
1. Unit tests for addon functions
2. Integration tests for field deployment
3. E2E tests for conditional fields
4. Update installation documentation

### Phase 5: Release
1. Create ZIP package
2. Update aws.ascio.info documentation
3. Notify existing customers of upgrade path

---

## Sources

- [WHMCS Sample Registrar Module](https://github.com/WHMCS/sample-registrar-module)
- [WHMCS Addon Modules Installation](https://developers.whmcs.com/addon-modules/installation-uninstallation/)
- [WHMCS Hooks Reference - Output](https://developers.whmcs.com/hooks-reference/output/)
- [WHMCS Custom Domain Fields](https://docs.whmcs.com/8-13/domains/pricing-and-configuration/custom-domain-fields/)
- [Openprovider WHMCS Module](https://github.com/openprovider/op-whmcs) - Reference implementation

---

*Plan created: 2026-02-01*
