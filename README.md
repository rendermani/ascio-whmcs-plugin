# Ascio WHMCS Plugin

WHMCS modules for Ascio domain registration, SSL certificates, and related services.

## Modules Included

| Module | Type | Directory | WHMCS Location |
|--------|------|-----------|----------------|
| **Ascio Domains** | Registrar | `./` (root) | `/modules/registrars/ascio/` |
| **Ascio SSL** | Server | `ssl/` | `/modules/servers/asciossl/` |
| **Ascio Monitoring** | Server | `monitoring/` | `/modules/servers/asciomonitoring/` |
| **Ascio Defensive** | Server | `defensive/` | `/modules/servers/asciodefensive/` |
| **Ascio TMCH** | Server | `tmch/` | `/modules/servers/asciotmch/` |
| **Ascio Tools** | Addon | `tools/` | `/modules/addons/asciotools/` |

## Requirements

- PHP 8.0+
- PHP SOAP extension
- WHMCS 8.0+

## Installation

### 1. Clone the Repository

```bash
cd /path/to/whmcs
git clone https://github.com/tucowsinc/ascio-whmcs-plugin.git /tmp/ascio-whmcs-plugin
```

### 2. Copy Modules to WHMCS

```bash
# Domain Registrar Module
cp -r /tmp/ascio-whmcs-plugin/{ascio.php,lib,tlds,callbacks.php,polling.php,hooks.php,logo.gif} \
    modules/registrars/ascio/

# SSL Server Module
cp -r /tmp/ascio-whmcs-plugin/ssl modules/servers/asciossl

# Monitoring Server Module (optional)
cp -r /tmp/ascio-whmcs-plugin/monitoring modules/servers/asciomonitoring

# Defensive Registration Module (optional)
cp -r /tmp/ascio-whmcs-plugin/defensive modules/servers/asciodefensive

# TMCH Module (optional)
cp -r /tmp/ascio-whmcs-plugin/tmch modules/servers/asciotmch

# Tools Addon (recommended - provides admin UI and shared settings)
cp -r /tmp/ascio-whmcs-plugin/tools modules/addons/asciotools

# Core library (required by server modules)
cp -r /tmp/ascio-whmcs-plugin/core modules/servers/asciossl/core
```

### 3. Activate Modules in WHMCS Admin

1. **Ascio Tools Addon** (recommended first):
   - Go to: Setup → Addon Modules
   - Find "Ascio Tools" and click Activate
   - This creates the shared settings table

2. **Domain Registrar**:
   - Go to: Setup → Domain Registrars
   - Find "Ascio Domains" and click Activate
   - Enter your API credentials

3. **Server Modules** (SSL, Monitoring, etc.):
   - Go to: Setup → Servers
   - Add a new server with module type "Ascio SSL" (or other)
   - Tables are created automatically on first use

## Configuration

### API Credentials

Configure in WHMCS Admin → Setup → Domain Registrars → Ascio:

- **Username**: Your Ascio account username
- **Password**: Your Ascio account password
- **Test Mode**: Enable for demo/testing environment
- **API Version**: v2 (default) or v3

### Shared Settings (via Tools Addon)

The Tools addon provides a central location for settings used by all modules:
- Addons → Ascio Tools → Settings

## Directory Structure

```
ascio-whmcs-plugin/
├── ascio.php           # Main registrar module
├── lib/                # Domain registrar libraries
├── tlds/               # TLD-specific configurations
├── ssl/                # SSL certificate server module
├── monitoring/         # Domain monitoring server module
├── defensive/          # Defensive registration server module
├── tmch/               # Trademark Clearinghouse server module
├── tools/              # Admin addon module
├── core/               # Shared v3 API components
├── tests/              # PHPUnit tests
└── Makefile            # Test runner
```

## Development

### Running Tests

```bash
# Install dependencies
make install

# Run all tests (excludes E2E)
make test

# Run specific module tests
make test-domains
make test-ssl
make test-core

# Run E2E tests (requires API credentials)
make test-e2e

# Verify monorepo structure
make verify
```

### Environment Variables for Testing

```bash
export ASCIO_TEST_ACCOUNT="your_demo_account"
export ASCIO_TEST_PASSWORD="your_demo_password"
export ASCIO_TEST_MODE="true"
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

Copyright (c) Tucows Inc. All rights reserved.

## Support

For support, contact your Ascio account manager or visit https://aws.ascio.info
