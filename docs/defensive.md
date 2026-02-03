# Ascio Defensive Module

WHMCS server module for defensive domain registrations (DPML) via Ascio Web Service API (v3).

## Overview

The Ascio Defensive module enables defensive domain registrations and Domain Protected Marks List (DPML) management through WHMCS. This allows trademark holders to protect their brands across multiple TLDs.

## Features

### Core Functionality
- Defensive domain registrations
- DPML (Domain Protected Marks List) blocking
- Multi-TLD protection
- Trademark-based blocking
- Automatic renewal

### Protection Types
- **Block Registration** - Prevent others from registering specific domains
- **DPML** - Trademark-based protection across new gTLDs
- **Defensive Registration** - Register domains defensively

## Installation

### 1. Copy Module Files

```bash
cp -r defensive /path/to/whmcs/modules/servers/asciodefensive
```

### 2. Copy Core Library

```bash
cp -r core /path/to/whmcs/modules/servers/asciodefensive/core
```

### 3. Install Dependencies

```bash
cd /path/to/whmcs/modules/servers/asciodefensive
composer install --no-dev
```

### 4. Create Server in WHMCS

1. Go to **Setup → Products/Services → Servers**
2. Click **Add New Server**
3. Select **Ascio Defensive** as the module type
4. Enter API credentials
5. Click **Save**

### 5. Create Defensive Products

1. Go to **Setup → Products/Services → Products/Services**
2. Create a new product with type **Server/Other**
3. In **Module Settings** tab, select your Ascio Defensive server
4. Configure protection type and pricing

## Configuration

### Server Settings

| Setting | Description |
|---------|-------------|
| **Username** | Ascio account username |
| **Password** | Ascio account password |
| **Test Mode** | Enable for demo environment |

### Product Configuration

| Setting | Description |
|---------|-------------|
| **Protection Type** | Block, DPML, or Defensive |
| **Duration** | Protection period (1-10 years) |
| **Included TLDs** | TLDs covered by protection |

## API Operations

### Supported Operations

| Operation | Description |
|-----------|-------------|
| `CreateOrder` | Create defensive order |
| `ValidateOrder` | Validate order |
| `GetOrder` | Retrieve order status |
| `GetDefensive` | Get defensive details |
| `GetDefensives` | List all defensives |
| `PollQueue` | Poll for updates |
| `AckQueueMessage` | Acknowledge messages |

### Order Types

| Order Type | Description |
|------------|-------------|
| `Register_Defensive` | New defensive registration |
| `Renew_Defensive` | Renewal |
| `Delete_Defensive` | Remove protection |

## Database Tables

### mod_asciodefensive_services

Service tracking:

| Column | Description |
|--------|-------------|
| `service_id` | WHMCS service ID |
| `defensive_handle` | Ascio defensive handle |
| `domain_pattern` | Protected pattern |
| `protection_type` | Type of protection |
| `status` | Current status |
| `created_at` | Creation timestamp |

## Callbacks

### Callback URL

Configure in your Ascio account:
```
https://your-whmcs.com/modules/registrars/ascio/callbacks.php
```

### Callback Events

| Event | Description |
|-------|-------------|
| `Order_Completed` | Protection activated |
| `Order_Failed` | Setup failed |
| `Renewal_Due` | Renewal reminder |
| `Expiring` | Protection expiring soon |

## Client Area Integration

### Available Actions

| Action | Description |
|--------|-------------|
| View Status | Display protection status |
| View Coverage | Show protected TLDs |
| Renew | Request renewal |
| Cancel | Request cancellation |

## Testing

### Run Unit Tests

```bash
cd defensive
./vendor/bin/phpunit
```

### Test Coverage

| Area | Tests | Coverage |
|------|-------|----------|
| Order creation | 25+ | High |
| Callback processing | 20+ | Medium |
| Status retrieval | 15+ | Medium |

## Troubleshooting

### Common Issues

**Protection not activating**
- Verify trademark documentation
- Check order status
- Confirm API credentials

**Coverage gaps**
- Review included TLDs
- Check product configuration
- Verify protection type

### Debug Mode

Enable in WHMCS:
1. Go to **Utilities → Logs → Module Log**
2. Enable logging for Ascio Defensive module
3. Review logged requests/responses

## Related Documentation

- [Domains Module](domains.md)
- [TMCH Module](tmch.md)
- [Core Library](../core/README.md)
