/**
 * Ascio WHMCS Registrar Module - Conditional Domain Fields
 *
 * This script handles dynamic showing/hiding of additional domain fields
 * based on other field values (e.g., Legal Type, Registrant Type).
 *
 * Works in both client area (cart/domain config) and admin area.
 */
(function() {
    'use strict';

    // Conditional field rules - which fields to show based on other field values
    var conditionalRules = {
        '.it': {
            'Birth Country': {
                dependsOn: 'Legal Type',
                showWhen: ['Italian and foreign natural persons']
            }
        },
        '.ca': {
            'Trademark Number': {
                dependsOn: 'Legal Type',
                showWhen: ['Trademark']
            },
            'Trademark Name': {
                dependsOn: 'Legal Type',
                showWhen: ['Trademark']
            },
            'Trademark Country': {
                dependsOn: 'Legal Type',
                showWhen: ['Trademark']
            }
        },
        '.us': {
            'Nexus Country': {
                dependsOn: 'Nexus Category',
                showWhen: ['C31', 'C32']
            }
        },
        '.ru': {
            'Passport Number': {
                dependsOn: 'Registrant Type',
                showWhen: ['Individual']
            },
            'Passport Issue Date': {
                dependsOn: 'Registrant Type',
                showWhen: ['Individual']
            },
            'Passport Issuer': {
                dependsOn: 'Registrant Type',
                showWhen: ['Individual']
            },
            'Birth Date': {
                dependsOn: 'Registrant Type',
                showWhen: ['Individual']
            },
            'TIN': {
                dependsOn: 'Registrant Type',
                showWhen: ['Organization']
            }
        },
        '.su': {
            'Passport Number': {
                dependsOn: 'Registrant Type',
                showWhen: ['Individual']
            },
            'Passport Issue Date': {
                dependsOn: 'Registrant Type',
                showWhen: ['Individual']
            },
            'Passport Issuer': {
                dependsOn: 'Registrant Type',
                showWhen: ['Individual']
            },
            'Birth Date': {
                dependsOn: 'Registrant Type',
                showWhen: ['Individual']
            },
            'TIN': {
                dependsOn: 'Registrant Type',
                showWhen: ['Organization']
            }
        },
        '.fr': {
            'Birth Date': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'Birth Place': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'SIREN/SIRET': {
                dependsOn: 'Contact Type',
                showWhen: ['Company']
            }
        },
        '.re': {
            'Birth Date': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'Birth Place': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'SIREN/SIRET': {
                dependsOn: 'Contact Type',
                showWhen: ['Company']
            }
        },
        '.pm': {
            'Birth Date': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'Birth Place': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'SIREN/SIRET': {
                dependsOn: 'Contact Type',
                showWhen: ['Company']
            }
        },
        '.tf': {
            'Birth Date': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'Birth Place': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'SIREN/SIRET': {
                dependsOn: 'Contact Type',
                showWhen: ['Company']
            }
        },
        '.wf': {
            'Birth Date': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'Birth Place': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'SIREN/SIRET': {
                dependsOn: 'Contact Type',
                showWhen: ['Company']
            }
        },
        '.yt': {
            'Birth Date': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'Birth Place': {
                dependsOn: 'Contact Type',
                showWhen: ['Individual']
            },
            'SIREN/SIRET': {
                dependsOn: 'Contact Type',
                showWhen: ['Company']
            }
        },
        '.xxx': {
            'ICM Member ID': {
                dependsOn: 'ICM Membership',
                showWhen: ['Yes']
            }
        }
    };

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
        var tldMatch = pageTitle.match(/\.([a-z]{2,})/i);
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
            '#' + fieldName.replace(/\s+/g, ''),
            '[data-fieldname="' + fieldName + '"]',
            // Label-based lookup
            'label:contains("' + fieldName + '")'
        ];

        for (var i = 0; i < selectors.length; i++) {
            try {
                var el = document.querySelector(selectors[i]);
                if (el) return el;
            } catch (e) {
                // Ignore selector errors (e.g., :contains is not standard)
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
            return fieldElement.options[fieldElement.selectedIndex]?.text || fieldElement.value;
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
