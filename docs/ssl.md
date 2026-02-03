# Ascio SSL Module

WHMCS server module for SSL certificate provisioning via Ascio Web Service API (v3).

## Overview

The Ascio SSL module enables SSL certificate ordering, management, and renewal through WHMCS. It supports multiple certificate types and integrates with Ascio's v3 API for certificate lifecycle management.

## Features

### Core Functionality
- SSL certificate ordering (DV, OV, EV certificates)
- Certificate reissue/renewal
- CSR generation and validation
- Domain Control Validation (DCV) - Email, DNS, HTTP
- Auto-installation support
- Certificate chain retrieval

### Certificate Types
- **Domain Validated (DV)** - Basic encryption
- **Organization Validated (OV)** - Business identity verification
- **Extended Validation (EV)** - Highest trust level
- **Wildcard** - Secure unlimited subdomains
- **Multi-Domain (SAN)** - Multiple domains on one certificate

### Validation Methods
- **Email** - Approver email confirmation
- **DNS** - CNAME record verification
- **HTTP** - File-based verification

## Installation

### 1. Copy Module Files

```bash
cp -r ssl /path/to/whmcs/modules/servers/asciossl
```

### 2. Copy Core Library

```bash
cp -r core /path/to/whmcs/modules/servers/asciossl/core
```

### 3. Install Dependencies

```bash
cd /path/to/whmcs/modules/servers/asciossl
composer install --no-dev
```

### 4. Create Server in WHMCS

1. Go to **Setup → Products/Services → Servers**
2. Click **Add New Server**
3. Select **Ascio SSL** as the module type
4. Enter API credentials
5. Click **Save**

### 5. Create SSL Products

1. Go to **Setup → Products/Services → Products/Services**
2. Create a new product with type **Server/Other**
3. In **Module Settings** tab, select your Ascio SSL server
4. Configure certificate type and pricing

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
| **Certificate Type** | DV, OV, EV, Wildcard, SAN |
| **Validation Years** | 1, 2, or 3 year validity |
| **Auto Renew** | Enable automatic renewal |

## API Operations

### Supported Operations

| Operation | Description |
|-----------|-------------|
| `CreateOrder` | Submit new certificate order |
| `ValidateOrder` | Validate order before submission |
| `GetOrder` | Retrieve order status |
| `GetSslCertificate` | Get certificate details |
| `GetSslCertificateChain` | Get full certificate chain |
| `GetSslApprovers` | List available DCV email addresses |
| `GetAutoInstallSsl` | Check auto-installation status |

### Order Types

| Order Type | Description |
|------------|-------------|
| `Register_SslCertificate` | New certificate |
| `Renew_SslCertificate` | Certificate renewal |
| `Reissue_SslCertificate` | Certificate reissue |

## Workflow

### 1. Certificate Order

```
Customer submits order
    ↓
Generate/validate CSR
    ↓
Select validation method
    ↓
Submit to Ascio API
    ↓
Wait for validation
    ↓
Certificate issued
```

### 2. Domain Validation

**Email Validation:**
1. Customer selects approver email
2. Ascio sends validation email
3. Customer clicks approval link
4. Certificate issued

**DNS Validation:**
1. Module provides CNAME record details
2. Customer adds record to DNS
3. Ascio verifies record
4. Certificate issued

**HTTP Validation:**
1. Module provides file content
2. Customer uploads to web server
3. Ascio verifies file
4. Certificate issued

### 3. Certificate Retrieval

After validation:
1. Certificate available via API
2. Module stores in WHMCS
3. Customer can download from client area
4. Auto-installation (if enabled)

## Database Tables

### mod_ascio_settings

Shared settings (created by Tools addon or SSL module):

| Column | Description |
|--------|-------------|
| `setting` | Setting name |
| `value` | Setting value |

### mod_asciossl_orders

SSL order tracking:

| Column | Description |
|--------|-------------|
| `service_id` | WHMCS service ID |
| `order_id` | Ascio order ID |
| `certificate_id` | Certificate handle |
| `status` | Order status |
| `csr` | Certificate signing request |
| `certificate` | Issued certificate |
| `created_at` | Order timestamp |

## Callbacks

### Callback URL

Configure in your Ascio account:
```
https://your-whmcs.com/modules/servers/asciossl/callback.php
```

### Callback Processing

The callback handler processes:
- Order status updates
- Certificate issuance notifications
- Validation completion
- Error notifications

## Client Area Integration

### Available Actions

| Action | Description |
|--------|-------------|
| View Certificate | Display certificate details |
| Download Certificate | Download in various formats |
| View CSR | Display the CSR |
| Reissue | Request certificate reissue |
| Resend Approver Email | Resend DCV email |

### Custom Fields

The module uses these service custom fields:

| Field | Description |
|-------|-------------|
| `Certificate Type` | Selected certificate type |
| `Common Name` | Primary domain |
| `Organization` | Organization name (OV/EV) |
| `Approver Email` | DCV email address |

## Testing

### Run Unit Tests

```bash
cd ssl
./vendor/bin/phpunit
```

### Test Coverage

| Area | Tests | Coverage |
|------|-------|----------|
| Order creation | 50+ | High |
| Callback processing | 30+ | High |
| Certificate retrieval | 20+ | Medium |
| API integration | 40+ | High |

### Integration Tests

```bash
export ASCIO_TEST_ACCOUNT="demo_account"
export ASCIO_TEST_PASSWORD="demo_password"
make test-ssl-integration
```

## Troubleshooting

### Common Issues

**Order stuck in pending**
- Check DCV status
- Verify callback URL
- Check approver email deliverability

**CSR validation fails**
- Ensure valid CSR format
- Check key size (2048+ bits)
- Verify domain name matches

**Certificate not downloading**
- Check order status
- Verify certificate was issued
- Check `mod_asciossl_orders` table

### Debug Mode

Enable in WHMCS:
1. Go to **Utilities → Logs → Module Log**
2. Enable logging for Ascio SSL module
3. Reproduce the issue
4. Review logged requests/responses

### Log Locations

- WHMCS Module Log: **Utilities → Logs → Module Log**
- Order details: `mod_asciossl_orders` table
- API requests: `mod_asciossl_jobs` table (if enabled)

## Integration with Other Modules

### Shared Credentials

SSL module shares credentials with other Ascio modules via `mod_ascio_settings`:

```php
// SSL module checks for shared settings
$username = Capsule::table('mod_ascio_settings')
    ->where('setting', 'username')
    ->value('value');
```

### Unified Callbacks

All Ascio modules can share a single callback endpoint:

```
https://your-whmcs.com/modules/registrars/ascio/callbacks.php
```

The callback router determines the product type and routes accordingly.

## API Reference

### GetSslCertificate

Retrieve certificate details:

```php
$response = $client->GetSslCertificate([
    'SslCertificateHandle' => 'SSL-12345'
]);

// Response includes:
// - CommonName
// - Status
// - NotBefore / NotAfter
// - Certificate (PEM)
// - CertificateChain
```

### GetSslCertificateChain

Get the full certificate chain:

```php
$response = $client->GetSslCertificateChain([
    'SslCertificateHandle' => 'SSL-12345'
]);

// Returns intermediate and root certificates
```

### GetSslApprovers

List available approver emails for DCV:

```php
$response = $client->GetSslApprovers([
    'CommonName' => 'example.com'
]);

// Returns: admin@, administrator@, webmaster@, etc.
```

## Related Documentation

- [Domains Module](domains.md)
- [Core Library](../core/README.md)
- [Ascio API Documentation](https://aws.ascio.info)
