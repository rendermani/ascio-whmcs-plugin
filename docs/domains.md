# Ascio Domains Module

WHMCS domain registrar module for Ascio Web Service API (v3).

## Overview

The Ascio Domains module provides domain registration, transfer, renewal, and management capabilities through the Ascio Web Service API. It supports 100+ TLDs with TLD-specific field handling and conditional additional fields.

## Features

### Core Functionality
- Domain registration and renewal
- Domain transfers (with EPP/auth code support)
- Nameserver management
- Contact management (Registrant, Admin, Tech, Billing)
- Domain locking (Transfer Lock)
- WHOIS privacy/ID protection (where supported)
- Domain synchronization

### TLD Support
- **65+ TLD plugins** with TLD-specific validation
- **23 TLDs** with extended v3 API features
- Automatic TLD pricing sync from Ascio API
- Registry-specific additional fields (Legal Type, Tax ID, etc.)

### Additional Fields
- Dynamic conditional field visibility (JS-based)
- Registry-specific validation rules
- Support for all major ccTLD requirements (.DE, .UK, .IT, .CA, etc.)

## Installation

### 1. Copy Module Files

```bash
cp -r ascio.php lib/ tlds/ callbacks.php polling.php hooks.php logo.gif \
    /path/to/whmcs/modules/registrars/ascio/
```

### 2. Install Dependencies

```bash
cd /path/to/whmcs/modules/registrars/ascio
composer install --no-dev
```

### 3. Activate in WHMCS

1. Go to **Setup → Domain Registrars**
2. Find "Ascio" and click **Activate**
3. Enter your Ascio API credentials

### 4. Run Database Setup

```bash
php install.php
```

This creates:
- `tblasciotlds` - TLD pricing and configuration
- `tblasciojobs` - API request/response logging
- `tblasciohandles` - Domain handle mapping
- `mod_asciosession` - API session cache

## Configuration

### API Credentials

| Setting | Description |
|---------|-------------|
| **Username** | Ascio account username |
| **Password** | Ascio account password |
| **Test Mode** | Enable for demo/sandbox environment |

### Optional Settings

| Setting | Description |
|---------|-------------|
| **Auto Expire** | Automatic expiration threshold (days) |
| **Detailed Order Status** | Send detailed status emails |
| **DNS Hosting** | Enable DNS zone management |

## TLD Pricing Sync

The module automatically synchronizes TLD pricing from Ascio:

### Automatic (via Cron)
- Runs daily via WHMCS cron
- Updates pricing, promo flags, and availability

### Manual Sync
```bash
php -r "require 'lib/TldSync.php'; (new \ascio\TldSync())->sync();"
```

## API Operations

### Supported Operations

| Operation | Description |
|-----------|-------------|
| `RegisterDomain` | Register new domain |
| `TransferDomain` | Transfer domain from another registrar |
| `RenewDomain` | Renew domain registration |
| `GetDomainInfo` | Get domain details |
| `GetDNSZone` | Retrieve DNS zone records |
| `UpdateDNSZone` | Update DNS zone records |
| `GetNameservers` | Get nameserver configuration |
| `SaveNameservers` | Update nameservers |
| `GetRegistrarLock` | Check transfer lock status |
| `SaveRegistrarLock` | Set transfer lock |
| `GetContactDetails` | Get registrant/contact info |
| `SaveContactDetails` | Update contacts |
| `GetEPPCode` | Retrieve auth/EPP code |
| `IDProtectToggle` | Toggle WHOIS privacy |

### Order Types

| Order Type | Description |
|------------|-------------|
| `Register_Domain` | New registration |
| `Transfer_Domain` | Incoming transfer |
| `Renew_Domain` | Renewal |
| `Owner_Change` | Registrant change |
| `Registrar_Transfer` | Outgoing transfer |
| `Update_Domain` | Domain update |
| `Trade_Domain` | Trade operation |
| `Delete_Domain` | Domain deletion |
| `Restore_Domain` | Restore from redemption |

## TLD Plugins

TLD-specific plugins are in `tlds/{tld}/` directories:

```
tlds/
├── de/
│   ├── de.php      # Base plugin
│   └── v3/
│       └── de.php  # v3 extensions
├── it/
│   ├── it.php
│   └── v3/
│       └── it.php
└── ...
```

### Plugin Structure

```php
namespace ascio;

class de extends Request {
    protected function mapToRegistrant($params) {
        $contact = parent::mapToRegistrant($params);
        // TLD-specific registrant fields
        return $contact;
    }

    protected function mapToAdmin($params) {
        // TLD-specific admin contact
    }
}
```

### TLDs with v3 Extensions

asia, at, ca, ch, co.uk, com.au, de, dk, es, fi, fr, it, jobs, nl, nu, pl, pro, ru, se, sg, uk, us, xxx

## Additional Domain Fields

### Configuration

Additional fields are defined in `resources/domains/additionalfields.php`:

```php
$additionaldomainfields[".it"][] = [
    "Name" => "Legal Type",
    "Type" => "dropdown",
    "Options" => "Italian and foreign natural persons|Companies/one man companies|...",
    "Required" => true
];
```

### Conditional Fields

The module supports conditional field visibility:

```php
$additionaldomainfields[".it"][] = [
    "Name" => "Birth country",
    "Type" => "dropdown",
    "depends_on" => "Legal Type",
    "show_when" => ["Italian and foreign natural persons"],
    "Required" => false
];
```

JavaScript in `assets/js/ascio-fields.js` handles dynamic visibility.

## Callbacks & Polling

### Callback URL

Configure in your Ascio account:
```
https://your-whmcs.com/modules/registrars/ascio/callbacks.php
```

### Polling

For environments where callbacks are unavailable:

```bash
# Run via cron
php polling.php
```

## Database Tables

### tblasciotlds

Stores TLD configuration from Ascio API:

| Column | Description |
|--------|-------------|
| `tld` | TLD name (.com, .de, etc.) |
| `price_register` | Registration price |
| `price_renew` | Renewal price |
| `price_transfer` | Transfer price |
| `min_period` | Minimum registration period |
| `max_period` | Maximum registration period |
| `supports_dns` | DNS hosting support |
| `supports_privacy` | WHOIS privacy support |
| `promo` | Promotional flag |
| `last_sync` | Last sync timestamp |

### tblasciojobs

API request/response logging:

| Column | Description |
|--------|-------------|
| `order_id` | Ascio order ID |
| `method` | API method called |
| `request` | Full request data |
| `response` | Full response data |
| `date` | Timestamp |

### tblasciohandles

Domain handle mapping:

| Column | Description |
|--------|-------------|
| `type` | Handle type (domain, registrant, etc.) |
| `whmcs_id` | WHMCS domain ID |
| `ascio_id` | Ascio domain handle |
| `domain` | Domain name |

## Testing

### Run Unit Tests

```bash
make test-domains
# or
./vendor/bin/phpunit
```

### Test Coverage

| Area | Tests | Coverage |
|------|-------|----------|
| Request mapping | 200+ | High |
| TLD plugins | 150+ | High |
| Order structure | 100+ | High |
| DNS operations | 50+ | Medium |
| Callbacks | 30+ | Medium |

### Integration Tests

Require API credentials:

```bash
export ASCIO_TEST_ACCOUNT="demo_account"
export ASCIO_TEST_PASSWORD="demo_password"
make test-integration
```

## Troubleshooting

### Common Issues

**Domain sync fails**
- Check API credentials
- Verify network connectivity to Ascio API
- Check `tblasciojobs` for error responses

**Additional fields not showing**
- Ensure `ascio-fields.js` is loaded
- Check browser console for JS errors
- Verify `additionalfields.php` syntax

**Transfer stuck in pending**
- Check callback URL configuration
- Run polling manually
- Verify EPP code is correct

### Debug Mode

Enable detailed logging:

```php
// In WHMCS Admin → Setup → Domain Registrars → Ascio
TestMode = Yes  // Uses demo API with verbose logging
```

### Log Locations

- WHMCS Module Debug Log: **Utilities → Logs → Module Log**
- API requests: `tblasciojobs` table
- Domain notes: `tbldomains.notes` field

## ICANN Compliance

### Supported Features

| Feature | Status |
|---------|--------|
| IRTP Transfer Policy | Supported |
| Registrant Verification | Supported |
| WHOIS Accuracy | Automatic validation |
| Transfer Lock | Supported |
| 60-day Transfer Lock | Automatic |

### Registrant Verification

The module supports ICANN-mandated registrant verification:

```php
// Check verification status
$request->getRegistrantVerificationInfo($params);

// Start verification process
$request->startRegistrantVerification($params);
```

## Related Documentation

- [SSL Module](ssl.md)
- [Monitoring Module](monitoring.md)
- [Defensive Module](defensive.md)
- [TMCH Module](tmch.md)
- [API Reference](../core/API_REFERENCE.md)
