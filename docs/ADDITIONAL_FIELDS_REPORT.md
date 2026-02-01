# Ascio WHMCS Plugin - Additional Fields Report

## Executive Summary

This report analyzes the additional domain fields configuration across all 65 TLD plugins in the Ascio WHMCS module. It identifies:
- Which TLDs require additional fields
- What fields each TLD plugin expects
- Which TLDs have `additionaldomainfields.php` definitions
- Gaps where fields are expected but not defined
- Hardcoded values that should be configurable

## WHMCS Additional Fields Background

### How Additional Fields Work
WHMCS additional domain fields are defined in `/resources/domains/additionalfields.php` (or the legacy location `/includes/additionaldomainfields.php`). The Ascio plugin stores its own field definitions in `tlds/<tld>/additionaldomainfields.php` which must be copied to the WHMCS installation.

### Conditional Fields (WHMCS 7.0+)
WHMCS supports conditional requirements using the `Requires` array:
```php
$additionaldomainfields[".it"][] = array(
    "Name" => "Birth Country",
    "Type" => "dropdown",
    "Options" => "{Countries}",
    "Requires" => array(
        "Legal Type" => "Italian and foreign natural persons"
    )
);
```

### WHMCS 9.0 Changes
WHMCS 9.0 introduced the Nexus cart with dynamic form updates, but **no significant changes** to the additional domain fields system were made. The conditional fields feature from 7.0 remains the primary method for field dependencies.

---

## TLD Field Requirements Analysis

### TLDs with additionaldomainfields.php Defined (10)

| TLD | Fields Defined | Status |
|-----|----------------|--------|
| ca | Canadian Citizen, Trademark Number/Name/Country | Partial - missing Legal Type dropdown |
| com.sg | Registrant ID, Admin ID, Local Presence | Complete |
| dk | Registrant CVR nr., Administrator CVR nr. | Complete |
| edu.sg | (inherits from sg) | Complete |
| it | Birth country | Partial - missing Legal Type, Tax ID |
| nl | Organisation Number | Complete |
| org.sg | (inherits from sg) | Complete |
| sg | Registrant ID, Admin ID, Local Presence | Complete |
| tn.it | (inherits from it) | Partial |

### TLDs Missing additionaldomainfields.php (55)

These TLDs have plugins that expect additional fields but **no WHMCS field definitions exist**:

| TLD | Expected Fields | Priority |
|-----|-----------------|----------|
| **ee** | Registrant Type/Number, Admin Type/Number, Tech Type/Number | HIGH |
| **us** | Domain Purpose | HIGH |
| **hk** | Registrant Type, Registrant Number | HIGH |
| **fr** | City/Country/Date of birth, VAT (Company) | HIGH |
| **br** | VAT Number | MEDIUM |
| **az** | Registrant Type, VAT Number | MEDIUM |
| **moscow** | Registrant/Admin/Tech Type/Number/Details, VAT | MEDIUM |
| **pt** | Registrant/Admin/Tech Number, VAT | MEDIUM |
| **amsterdam** | Registrant Type/Number, Admin/Tech Type | MEDIUM |
| **ru** | Multiple passport/tax fields | MEDIUM |
| **asia** | Identity Form, Identity Number, Legal Type | MEDIUM |
| **aero** | Auth Code | LOW |
| **al** | Registrant Number | LOW |
| **ba** | Registrant Number | LOW |
| **by** | Registrant Number | LOW |
| **cn** | Registrant Number | LOW |
| **ec** | Registrant Number, VAT Number | LOW |
| **et** | VAT Number | LOW |
| **fi** | Identification Number, Legal Type | LOW |
| **fm** | Registrant Number | LOW |
| **hr** | VAT Number | LOW |
| **hu** | Registrant Number, Trademark Name, VAT | LOW |
| **ie** | Registrant Type/Number, Trademark Name | LOW |
| **is** | Registrant Number | LOW |
| **jobs** | Website, Business Nature, Company position | LOW |
| **kr** | Registrant Number | LOW |
| **lotto** | Registrant Number | LOW |
| **lv** | Registrant Number | LOW |
| **mk** | Registrant Number | LOW |
| **my** | Registrant Number | LOW |
| **nc** | Registrant Number | LOW |
| **no** | Registrant Number | LOW |
| **nu** | Identification Number, VAT | LOW |
| **nyc** | Domain Purpose | LOW |
| **pro** | Profession | LOW |
| **rio** | Registrant Number | LOW |
| **rs** | Registrant Type/Number, Admin Number | LOW |
| **se** | Identification Number, VAT | LOW |
| **si** | Registrant Number, VAT Number | LOW |
| **sk** | Registrant Number | LOW |
| **su** | Registrant Type/Number, VAT Number | LOW |
| **swiss** | Registrant Number | LOW |
| **cat** | Auth Code, Domain Purpose, Registrant Details | LOW |
| **tel** | Registrant Details | LOW |
| **travel** | Registrant Number | LOW |
| **xxx** | Member of sponsored community | LOW |
| **co.uk** | Legal Type, Company ID Number | LOW |
| **org.uk** | Legal Type, Company ID Number | LOW |
| **uk** | Legal Type, Company ID Number | LOW |
| **com.au** | Multiple eligibility fields | LOW |
| **de** | Tax ID | LOW |

---

## Hardcoded Values Analysis

### Critical: TLDs with Hardcoded Mappings

#### .IT (Italy)
**File:** `tlds/it/it.php`
```php
private $map = array(
    "Italian and foreign natural persons" => "1",
    "Companies/one man companies" => "2",
    // ... 7 types hardcoded
);
```
**Issue:** Legal Type values are hardcoded. The `additionaldomainfields.php` does not define the Legal Type dropdown.
**Impact:** Users see the WHMCS default dropdown which may have different labels.

#### .CA (Canada)
**File:** `tlds/ca/ca.php`
```php
$map = array(
    "Corporation" => "CCO",
    "Canadian Citizen" => "CCT",
    // ... 16 types hardcoded
);
```
**Issue:** Legal Type mapping is in code but the additionaldomainfields.php only has trademark fields (Legal Type dropdown is commented out).
**Impact:** Depends on WHMCS default .ca fields being present.

#### .NL (Netherlands)
**File:** `tlds/nl/nl.php`
```php
$contact["RegistrantType"] = "PERSOON";
if($contact["OrgName"] && $contact["CountryCode"] == "NL") {
    $contact["RegistrantType"] = "BV";
} else if($contact["OrgName"]) {
    $contact["RegistrantType"] = "BGG";
}
```
**Issue:** RegistrantType is auto-determined from country + company name, not user-selectable.
**Status:** This is intentional behavior - no field needed.

#### .FR (France)
**File:** `tlds/fr/fr.php`
```php
$tm["Name"]    = $params["City of birth (Individual)"];
$tm["Country"] = $params["Country of birth (Individual)"];
```
**Issue:** Accesses params directly (not through additionalfields), which only works if WHMCS includes has these fields defined globally.
**Impact:** Will fail if WHMCS default .fr fields are not present.

---

## Conditional Field Requirements

### TLDs Needing Conditional Logic

| TLD | Field | Condition | Type |
|-----|-------|-----------|------|
| .it | Birth Country | Legal Type = "Italian and foreign natural persons" | Country dropdown |
| .it | Tax ID | Always required | Text |
| .fr | Birth fields | companyname is empty | Text fields |
| .fr | VAT | companyname is not empty | Text |
| .ca | Trademark fields | Legal Type = "Trade-mark registered in Canada" | Text |
| .us | Nexus Country | Domain Purpose selected | Dropdown |
| .ru | Passport fields | Registrant Type = Individual | Text fields |
| .ru | Tax Numbers | Registrant Type = Organization | Text fields |

### State/Province Dependencies

Currently, no TLD plugins implement state/province lists that depend on country selection. This would require:
1. Custom JavaScript in WHMCS templates
2. AJAX endpoint to fetch states for selected country
3. Or: using the `{States}` placeholder (limited to US/CA)

**WHMCS does not provide built-in conditional state dropdowns for additional domain fields.**

---

## Recommendations

### Immediate Actions (HIGH Priority)

1. **Create additionaldomainfields.php for .ee, .us, .hk, .fr**
   - These are commonly registered TLDs missing field definitions
   - Users cannot currently provide required data

2. **Fix .it additionaldomainfields.php**
   - Add Legal Type dropdown with 7 options
   - Add Tax ID text field
   - Add conditional Birth Country (required when Legal Type = individuals)

3. **Fix .ca additionaldomainfields.php**
   - Uncomment the Legal Type dropdown
   - Add conditional logic for Trademark fields

### Medium Priority

4. **Create field definitions for European TLDs**
   - .br, .az, .pt, .moscow, .amsterdam, .ru
   - Many require VAT/Tax numbers

5. **Consolidate includes/additionalfields.php**
   - The file in `ascio/domains/includes/` duplicates definitions
   - Should either be removed or made the single source

### Long-term Improvements

6. **Version compatibility check**
   - Add WHMCS version detection in hooks.php
   - Load appropriate field definitions for 8.x vs 9.x

7. **Dynamic state/province support**
   - For .it (Italian provinces dependent on birth country)
   - Would require custom JavaScript hook

8. **Field validation enhancement**
   - Add client-side validation for formats (VAT, Tax ID, etc.)
   - Use WHMCS hooks to validate before submission

---

## File Inventory

### TLD Plugins with additionalfields references (50 TLDs)

| Category | TLDs |
|----------|------|
| Simple (1-2 fields) | al, ba, by, cn, de, fm, hr, is, kr, lotto, lv, mk, my, nc, no, rio, sk, swiss, travel |
| Medium (3-5 fields) | amsterdam, asia, az, br, cat, ec, ee, et, fi, hk, hu, ie, nu, nyc, pro, pt, rs, se, si, su, tel, us, xxx |
| Complex (6+ fields) | ca, com.au, com.sg, dk, edu.sg, fr, it, jobs, moscow, nl, org.sg, ru, sg, uk, co.uk, org.uk |

### Files to Create

```
tlds/ee/additionaldomainfields.php
tlds/us/additionaldomainfields.php
tlds/hk/additionaldomainfields.php
tlds/fr/additionaldomainfields.php
tlds/br/additionaldomainfields.php
tlds/az/additionaldomainfields.php
tlds/pt/additionaldomainfields.php
tlds/moscow/additionaldomainfields.php
tlds/amsterdam/additionaldomainfields.php
tlds/ru/additionaldomainfields.php
... (40+ more)
```

---

## Sources

- [WHMCS Custom Domain Fields Documentation](https://docs.whmcs.com/8-13/domains/pricing-and-configuration/custom-domain-fields/)
- [WHMCS Additional Domain Fields](https://docs.whmcs.com/Additional_Domain_Fields)
- [WHMCS 9.0 Release Notes](https://docs.whmcs.com/releases/9-0/9-0-release-notes/)
- [WHMCS Community - Conditional Fields](https://whmcs.community/topic/319955-custom-fields-conditional-logic-by-country)

---

*Report generated: 2026-02-01*
*Plugin version: ascio/domains with 65 TLD plugins*
