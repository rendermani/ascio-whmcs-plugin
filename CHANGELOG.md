# Changelog

All notable changes to the Ascio WHMCS Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-02-03

### Added
- **Dynamic Additional Fields Generation** - Auto-generate `additionalfields.php` from TLDKit API
  - `lib/FieldRegistry.php` - Single source of truth for API→WHMCS field mappings
  - `lib/TldKitFieldsClient.php` - HTTP client for TLDKit API with pagination
  - `lib/ConditionalFieldMapper.php` - Maps API contexts to WHMCS depends_on/show_when
  - `lib/FieldGenerator.php` - Generates PHP, JS, and JSON output files
- **76 new unit tests** for field generation system
- **Comprehensive documentation**
  - `docs/domains.md` - Domain module documentation
  - `docs/ssl.md` - SSL module documentation
  - Updated README.md with documentation links

### Changed
- Field generation integrated into `install.php` and `TldSync.php`
- Hash-based change detection to avoid unnecessary file regeneration

### Fixed
- PHP 8+ warnings in test fixtures (undefined array keys)
- `.ru` TLD plugin bug accessing non-existent `$contact["Name"]` key
- Singapore TLD tests missing `Local Presence` field
- Italy TLD test asserting null on unset key (changed to assertArrayNotHasKey)
- ZoneTest missing `UserName` key in test parameters

## [2.0.0] - 2025-02-01

### Added
- **Monorepo consolidation**: All Ascio WHMCS modules now in single repository
  - SSL certificates (`ssl/`)
  - Domain monitoring/NameWatch (`monitoring/`)
  - Defensive/DPML registrations (`defensive/`)
  - Trademark Clearinghouse (`tmch/`)
  - Admin tools addon (`tools/`)
  - Shared v3 API components (`core/`)
- **Unified test suite**: PHPUnit tests for all modules with Makefile runner
- **E2E lifecycle tests**: Full order flow tests against Ascio demo API
- **Standard WHMCS installation**: Follows official WHMCS module patterns
  - Server modules use lazy table creation (`_EnsureTable()`)
  - Addon module uses `_activate()`/`_deactivate()` for setup
  - No separate install.php files required

### Changed
- **Installation process**: Now standard WHMCS pattern
  - Copy modules to appropriate directories
  - Activate in WHMCS Admin
  - Tables created automatically on first use
- **Tools addon**: Rewritten with proper `_activate()` function
  - Creates shared settings table on activation
  - Migrates settings from old SSL module if present
- **SSL module**: Modernized from deprecated `mysql_*` to Capsule ORM
- **Security**: All modules now have WHMCS context protection

### Removed
- Standalone `install.php` files (functionality moved into modules)
- `install-all.php` unified installer (not needed with standard pattern)

### Security
- All module files now check for WHMCS context before execution
- Removed direct database access via deprecated mysql_* functions

## [1.x] - Previous Versions

See git history for changes prior to monorepo consolidation.
