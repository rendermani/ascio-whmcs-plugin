# Ascio Monitoring Module

WHMCS server module for domain monitoring (NameWatch) via Ascio Web Service API (v3).

## Overview

The Ascio Monitoring module provides domain monitoring services through WHMCS, allowing customers to track domain availability and receive alerts. It uses Ascio's NameWatch service to monitor domains across multiple TLDs.

## Features

### Core Functionality
- Domain name monitoring/watching
- Multi-TLD monitoring support
- Email alert notifications
- Automatic renewal
- Monitoring status tracking

### Monitoring Capabilities
- New registration alerts
- Domain expiration monitoring
- Similar domain detection
- Bulk domain watching

## Installation

### 1. Copy Module Files

```bash
cp -r monitoring /path/to/whmcs/modules/servers/asciomonitoring
```

### 2. Copy Core Library

```bash
cp -r core /path/to/whmcs/modules/servers/asciomonitoring/core
```

### 3. Install Dependencies

```bash
cd /path/to/whmcs/modules/servers/asciomonitoring
composer install --no-dev
```

### 4. Create Server in WHMCS

1. Go to **Setup → Products/Services → Servers**
2. Click **Add New Server**
3. Select **Ascio Monitoring** as the module type
4. Enter API credentials
5. Click **Save**

### 5. Create Monitoring Products

1. Go to **Setup → Products/Services → Products/Services**
2. Create a new product with type **Server/Other**
3. In **Module Settings** tab, select your Ascio Monitoring server
4. Configure monitoring options and pricing

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
| **Monitoring Type** | NameWatch service type |
| **Duration** | Monitoring period (1-3 years) |
| **Alert Email** | Notification email address |

## API Operations

### Supported Operations

| Operation | Description |
|-----------|-------------|
| `CreateOrder` | Create new monitoring order |
| `ValidateOrder` | Validate order before submission |
| `GetOrder` | Retrieve order status |
| `GetNameWatch` | Get monitoring details |
| `GetNameWatchList` | List all monitored domains |
| `PollQueue` | Poll for updates |
| `AckQueueMessage` | Acknowledge messages |

### Order Types

| Order Type | Description |
|------------|-------------|
| `Register_NameWatch` | New monitoring |
| `Renew_NameWatch` | Monitoring renewal |
| `Delete_NameWatch` | Cancel monitoring |

## Database Tables

### mod_asciomonitoring_services

Service tracking table:

| Column | Description |
|--------|-------------|
| `service_id` | WHMCS service ID |
| `namewatch_handle` | Ascio NameWatch handle |
| `domain_name` | Monitored domain |
| `status` | Current status |
| `created_at` | Creation timestamp |

## Callbacks

### Callback URL

Configure in your Ascio account:
```
https://your-whmcs.com/modules/registrars/ascio/callbacks.php
```

The unified callback handler routes NameWatch callbacks to this module.

### Callback Events

| Event | Description |
|-------|-------------|
| `Order_Completed` | Monitoring activated |
| `Order_Failed` | Monitoring setup failed |
| `Alert_Triggered` | Domain match found |
| `Renewal_Due` | Renewal reminder |

## Client Area Integration

### Available Actions

| Action | Description |
|--------|-------------|
| View Status | Display monitoring status |
| View Alerts | Show triggered alerts |
| Modify Settings | Update notification preferences |
| Cancel | Request cancellation |

## Testing

### Run Unit Tests

```bash
cd monitoring
./vendor/bin/phpunit
```

### Test Coverage

| Area | Tests | Coverage |
|------|-------|----------|
| Order creation | 30+ | High |
| Callback processing | 20+ | Medium |
| Status retrieval | 15+ | Medium |

## Troubleshooting

### Common Issues

**Monitoring not activating**
- Check API credentials
- Verify callback URL
- Check order status in admin

**Alerts not received**
- Verify email configuration
- Check spam folder
- Confirm monitoring is active

### Debug Mode

Enable in WHMCS:
1. Go to **Utilities → Logs → Module Log**
2. Enable logging for Ascio Monitoring module
3. Review logged requests/responses

## Related Documentation

- [Domains Module](domains.md)
- [SSL Module](ssl.md)
- [Core Library](../core/README.md)
