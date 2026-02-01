# Ascio WHMCS Plugin - Consolidation & Modernization Plan

## Executive Summary

This plan consolidates three separate Ascio WHMCS repositories into a single monorepo with modern architecture, improved installation, and conditional field support.

### Current Repositories

| Repository | Type | Location | Last Updated | Issues |
|------------|------|----------|--------------|--------|
| `rendermani/ascio-whmcs-plugin` | Registrar Module | `ascio/domains/` | Active (Feb 2026) | Deprecated `mysql_*` in install.php, no conditional fields |
| `rendermani/ascio-ssl-whmcs-plugin` | Provisioning Module | `ascio-ssl-whmcs-plugin/` | Stale (2022) | AutoInstallSSL outdated, deprecated `mysql_*`, mixed WHMCS SDK patterns |
| `rendermani/whmcs-ascio-tools` | Addon Module | `whmcs-ascio-tools/` | Stale (2022) | Only SSL installer, no domains support, demo code left in |

### Target Architecture

**Single Monorepo: `rendermani/ascio-whmcs`**

```
ascio-whmcs/
├── modules/
│   ├── registrars/
│   │   └── ascio/                    # Domain registrar
│   ├── servers/
│   │   └── asciossl/                 # SSL provisioning
│   └── addons/
│       └── ascio_tools/              # Unified installer/tools
├── includes/
│   └── hooks/
│       └── ascio.php                 # Global hooks
├── shared/                           # Shared libraries
│   ├── AscioClient.php               # Unified API client
│   ├── AdditionalFields.php          # Conditional fields
│   └── assets/
│       ├── js/ascio-fields.js        # Field logic JS
│       └── css/ascio-fields.css
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── E2E/
├── installer/
│   ├── install.sh                    # Unified installer
│   └── migrations/
├── docs/
├── composer.json
├── phpunit.xml
└── README.md
```

---

## Recommendation: Monorepo

### Why Monorepo for Ascio WHMCS?

| Factor | Monorepo | Multi-repo |
|--------|----------|------------|
| Shared API client | ✅ Single source | ❌ Duplicate code |
| Database tables | ✅ Unified migrations | ❌ Conflicting schemas |
| Additional fields | ✅ Centralized | ❌ Fragmented |
| Testing | ✅ Cross-module tests | ❌ Complex integration |
| Installation | ✅ Single ZIP/installer | ❌ Multiple downloads |
| Versioning | ✅ Synchronized releases | ❌ Dependency hell |
| Customer experience | ✅ One-click setup | ❌ Manual coordination |

**Decision: Use monorepo** because:
1. All modules share Ascio API credentials
2. Database tables are related (`mod_asciosession`, etc.)
3. Customers need both domains + SSL
4. Installation should be unified

---

## Component Analysis

### 1. SSL Module Issues

**File:** `ascio-ssl-whmcs-plugin/asciossl.php`

#### Critical Issues

1. **AutoInstallSSL Feature** (Lines 169-243, 260-323)
   - Uses deprecated `mysql_*` functions
   - SSLStore-specific auto-install features not relevant
   - Mixed PHP patterns (procedural + OOP)

   ```php
   // DEPRECATED CODE:
   $result = mysql_query("select id,remoteid,status from tblsslorders where serviceid='".$params["serviceid"]."'");
   $sslOrderData = mysql_fetch_assoc($result);
   ```

2. **Hardcoded Certificate Types** (Lines 96-168)
   - 60+ certificate types hardcoded
   - No dynamic loading from API
   - Includes obsolete certificates (Symantec, Comodo rebrands)

3. **Incomplete Functions**
   - `asciossl_Renew()` - just `var_dump("test renew")`
   - `asciossl_TestConnection()` - empty try block
   - `asciossl_buttonOneFunction()` - empty

4. **install.php** (Lines 1-31)
   - Uses `mysql_query()` - removed in PHP 7.0+
   - No migration support
   - No rollback capability

#### SSL Module Recommendations

1. Remove AutoInstallSSL feature (SSLStore-specific)
2. Migrate to Capsule ORM
3. Dynamic certificate type loading from Ascio API
4. Complete all stub functions
5. Add proper error handling

### 2. Tools Module Issues

**File:** `whmcs-ascio-tools/asciotools.php`

#### Critical Issues

1. **Demo Code Left In** (Lines 84-113)
   ```php
   // Still using demo table:
   $query = "CREATE TABLE `mod_asciotools` (`id` INT( 1 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`demo` TEXT NOT NULL )";
   ```

2. **Only SSL Installer** - No domains module support

3. **Sidebar Only Shows SSL** (Lines 197-207)
   ```php
   $sidebar = '<span class="header">SSL</span>';
   $items = '
       <li><a href="'.$modulelink.'&action=install">Install/Update</a></li>
       <li><a href="'.$modulelink.'&action=settings">Settings</a></li>
   ';
   ```

4. **Installer Architecture** (`ssl/Installer/Installer.php`)
   - Good version management concept
   - Downloads from GitHub raw
   - But only handles SSL module

#### Tools Module Recommendations

1. Rename to `ascio_tools` (underscore convention)
2. Add domains module installation
3. Add additional fields deployment
4. Add TLD sync functionality
5. Remove demo code
6. Unified settings page for all modules

### 3. Domains Module (Current Work)

**Status:** Mostly updated, needs completion

#### Remaining Issues

1. **install.php** - Uses deprecated `mysql_*`
2. **hooks.php** - Minimal, needs JS injection
3. **Additional fields** - Scattered across TLD directories
4. **No conditional field JS**

---

## Jira Epic & Tickets Structure

### Epic: Ascio WHMCS Plugin Modernization

**Epic Key:** PS-XXX
**Summary:** Consolidate and modernize Ascio WHMCS plugins (domains, SSL, tools)

---

### Story 1: Conditional Additional Fields

**Key:** PS-XXX
**Summary:** Implement JavaScript-based conditional additional fields for domain registration
**Story Points:** 8
**Labels:** domains, whmcs, javascript, ux

**Description:**
```
Implement conditional visibility for additional domain fields using JavaScript hooks.

Background:
- Current implementation has hardcoded field mappings in PHP
- Fields like .IT "Birth Country" should only show when "Legal Type" = "Individual"
- WHMCS 7.0+ supports `Requires` array but doesn't hide fields, only validates
- Need JavaScript to dynamically show/hide fields

Acceptance Criteria:
- [ ] Create ascio-fields.js with conditional logic
- [ ] Inject JS via ClientAreaHeadOutput hook
- [ ] Inject JS via AdminAreaHeadOutput hook
- [ ] Support .IT: Birth Country conditional on Legal Type
- [ ] Support .CA: Trademark fields conditional on Legal Type
- [ ] Support .RU: Passport fields conditional on Registrant Type
- [ ] Support .US: Nexus Country conditional on Purpose
- [ ] No WHMCS template modifications required
- [ ] Works in both client and admin areas
```

**Subtasks:**

1. **PS-XXX-1:** Create centralized AdditionalFields.php
   - Move all TLD field definitions to single file
   - Use WHMCS `Requires` syntax where applicable
   - Add missing TLD definitions (55 TLDs)

2. **PS-XXX-2:** Create ascio-fields.js
   - TLD-specific configuration object
   - Field visibility toggle functions
   - Country-dependent state/province support
   - Initial state application on page load

3. **PS-XXX-3:** Update hooks.php for JS injection
   - ClientAreaHeadOutput hook
   - AdminAreaHeadOutput hook
   - Page detection (cart, domain config, admin domain)

4. **PS-XXX-4:** Unit tests for conditional fields
   - Test field definitions
   - Test JS configuration generation

---

### Story 2: Tools Module Integration

**Key:** PS-XXX
**Summary:** Modernize ascio_tools addon to handle unified installation for domains and SSL
**Story Points:** 13
**Labels:** tools, whmcs, installation, addon

**Description:**
```
Upgrade the ascio_tools addon module to serve as the unified installation
and management interface for both domains and SSL modules.

Current State:
- Only handles SSL module installation
- Demo code still present
- No domains module support
- No additional fields deployment

Target State:
- One-click installation for domains + SSL
- Automatic database table creation (Capsule ORM)
- Automatic additional fields deployment
- TLD sync from TLDKit API
- Version management with migrations

Acceptance Criteria:
- [ ] Rename module to ascio_tools (underscore convention)
- [ ] _activate() creates all required tables
- [ ] _deactivate() preserves data
- [ ] Sidebar shows both Domains and SSL sections
- [ ] Install action handles both modules
- [ ] Settings page for unified configuration
- [ ] Remove all demo/sample code
```

**Subtasks:**

1. **PS-XXX-1:** Database migration to Capsule ORM
   - Create all tables in _activate()
   - Migration versioning system
   - Handle existing installations

2. **PS-XXX-2:** Domains module installer
   - Deploy additional fields to WHMCS
   - Sync TLD data from TLDKit API
   - Verify registrar activation

3. **PS-XXX-3:** SSL module installer
   - Update existing installer
   - Remove AutoInstallSSL deployment
   - Add certificate type sync

4. **PS-XXX-4:** Unified admin interface
   - Combined sidebar (Domains + SSL)
   - Unified settings page
   - Status dashboard

5. **PS-XXX-5:** Version management
   - Read version from module.json
   - Database version tracking
   - Filesystem version tracking
   - Upgrade path handling

---

### Story 3: SSL Module Improvements

**Key:** PS-XXX
**Summary:** Modernize SSL module, remove deprecated features, complete implementation
**Story Points:** 8
**Labels:** ssl, whmcs, provisioning

**Description:**
```
Refactor the SSL provisioning module to modern WHMCS standards,
remove obsolete SSLStore features, and complete stub implementations.

Current Issues:
- AutoInstallSSL feature is SSLStore-specific
- Uses deprecated mysql_* functions
- 60+ hardcoded certificate types
- Stub functions incomplete (Renew, TestConnection)
- Mixed coding patterns

Acceptance Criteria:
- [ ] Remove AutoInstallSSL feature
- [ ] Migrate all DB queries to Capsule ORM
- [ ] Dynamic certificate loading from API
- [ ] Complete asciossl_Renew() implementation
- [ ] Complete asciossl_TestConnection() implementation
- [ ] Remove unused button functions
- [ ] Update hooks.php (remove sample code)
- [ ] Unit tests for all functions
```

**Subtasks:**

1. **PS-XXX-1:** Remove AutoInstallSSL
   - Delete related code in asciossl.php
   - Remove custom fields for AutoInstallSSL
   - Update documentation

2. **PS-XXX-2:** Database migration to Capsule
   - Replace mysql_* with Capsule
   - Update install.php or move to addon
   - Handle existing data

3. **PS-XXX-3:** Dynamic certificate types
   - Fetch from Ascio API
   - Cache in database
   - Update ConfigOptions()

4. **PS-XXX-4:** Complete stub functions
   - asciossl_Renew()
   - asciossl_Reissue()
   - asciossl_TestConnection()
   - Remove unused functions

5. **PS-XXX-5:** Clean up hooks.php
   - Remove sample code comments
   - Add meaningful hooks
   - Add JS injection if needed

---

### Story 4: Repository Consolidation

**Key:** PS-XXX
**Summary:** Merge three repositories into single ascio-whmcs monorepo
**Story Points:** 5
**Labels:** infrastructure, git, devops

**Description:**
```
Consolidate the three separate Ascio WHMCS repositories into a single
monorepo with proper directory structure.

Current Repos:
- rendermani/ascio-whmcs-plugin (domains)
- rendermani/ascio-ssl-whmcs-plugin (ssl)
- rendermani/whmcs-ascio-tools (tools)

Target Repo:
- rendermani/ascio-whmcs (unified)

Acceptance Criteria:
- [ ] Create new repo structure
- [ ] Preserve git history for all modules
- [ ] Update composer.json for monorepo
- [ ] Unified phpunit.xml
- [ ] GitHub Actions CI/CD
- [ ] ZIP release packaging
- [ ] Update documentation
- [ ] Archive old repositories
```

**Subtasks:**

1. **PS-XXX-1:** Create monorepo structure
   - Directory layout per plan
   - Shared libraries location
   - Assets organization

2. **PS-XXX-2:** Migrate git history
   - Use git filter-repo
   - Preserve commit history
   - Maintain blame/authorship

3. **PS-XXX-3:** CI/CD setup
   - GitHub Actions workflow
   - Unit tests on PR
   - Integration tests on merge
   - Release packaging

4. **PS-XXX-4:** Documentation
   - Unified README
   - Installation guide
   - Migration guide for existing users

---

## Implementation Timeline

### Phase 1: Conditional Fields (Week 1-2)
- Create AdditionalFields.php
- Create ascio-fields.js
- Update hooks.php
- Test with .IT, .CA, .US, .RU

### Phase 2: Tools Integration (Week 3-4)
- Migrate tools module
- Add domains installer
- Database migrations
- Unified settings

### Phase 3: SSL Improvements (Week 5-6)
- Remove AutoInstallSSL
- Complete implementations
- Dynamic certificate types
- Testing

### Phase 4: Consolidation (Week 7)
- Create monorepo
- Migrate history
- CI/CD setup
- Release v2.0.0

---

## Verification Checklist

### Installation
- [ ] Fresh install via ascio_tools activation
- [ ] Upgrade from existing installation
- [ ] All database tables created
- [ ] Additional fields deployed
- [ ] TLD data synced

### Domains Module
- [ ] Domain registration with conditional fields
- [ ] Transfer with EPP code
- [ ] Renewal
- [ ] Nameserver management
- [ ] Contact updates
- [ ] All 65 TLDs working

### SSL Module
- [ ] Certificate purchase
- [ ] Certificate renewal
- [ ] Certificate reissue
- [ ] Status checking
- [ ] Certificate download

### Tools Module
- [ ] Both modules show in sidebar
- [ ] Settings save correctly
- [ ] Installer updates both modules
- [ ] Failed orders display

---

## Sources

- [WHMCS Addon Module Installation](https://developers.whmcs.com/addon-modules/installation-uninstallation/)
- [WHMCS Hooks Reference](https://developers.whmcs.com/hooks-reference/output/)
- [WHMCS Sample Registrar Module](https://github.com/WHMCS/sample-registrar-module)
- [WHMCS Sample Provisioning Module](https://github.com/WHMCS/sample-provisioning-module)
- [Monorepo Best Practices](https://www.thoughtworks.com/en-us/insights/blog/agile-engineering-practices/monorepo-vs-multirepo)

---

*Plan created: 2026-02-01*
