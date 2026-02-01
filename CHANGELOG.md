# Changelog

All notable changes to the Ascio WHMCS Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
