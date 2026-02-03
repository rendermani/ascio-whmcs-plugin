# Ascio TMCH Module

WHMCS server module for Trademark Clearinghouse (TMCH) via Ascio Web Service API (v3).

## Overview

The Ascio TMCH module enables Trademark Clearinghouse mark registration and management through WHMCS. TMCH allows trademark holders to register their marks and receive Sunrise/Claims notifications during new gTLD launches.

## Features

### Core Functionality
- TMCH mark registration
- SMD (Signed Mark Data) file generation
- Document upload for verification
- Sunrise period participation
- Claims notification service
- Mark renewal

### TMCH Benefits
- **Sunrise Period Access** - Priority registration during new gTLD launches
- **Claims Notifications** - Alerts when matching domains are registered
- **Brand Protection** - Verified trademark records
- **SMD Files** - Cryptographic proof for sunrise registrations

## Installation

### 1. Copy Module Files

```bash
cp -r tmch /path/to/whmcs/modules/servers/asciotmch
```

### 2. Copy Core Library

```bash
cp -r core /path/to/whmcs/modules/servers/asciotmch/core
```

### 3. Install Dependencies

```bash
cd /path/to/whmcs/modules/servers/asciotmch
composer install --no-dev
```

### 4. Create Server in WHMCS

1. Go to **Setup → Products/Services → Servers**
2. Click **Add New Server**
3. Select **Ascio TMCH** as the module type
4. Enter API credentials
5. Click **Save**

### 5. Create TMCH Products

1. Go to **Setup → Products/Services → Products/Services**
2. Create a new product with type **Server/Other**
3. In **Module Settings** tab, select your Ascio TMCH server
4. Configure mark type and pricing

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
| **Mark Type** | Registered, Court-Validated, Treaty |
| **Duration** | Registration period (1-5 years) |
| **Includes SMD** | SMD file generation |

## API Operations

### Supported Operations

| Operation | Description |
|-----------|-------------|
| `CreateOrder` | Create mark registration |
| `ValidateOrder` | Validate order |
| `GetOrder` | Retrieve order status |
| `GetMark` | Get mark details |
| `GetMarks` | List all marks |
| `UploadDocumentation` | Upload verification docs |
| `UploadMessage` | Upload supporting messages |
| `PollQueue` | Poll for updates |
| `AckQueueMessage` | Acknowledge messages |

### Order Types

| Order Type | Description |
|------------|-------------|
| `Register_Mark` | New mark registration |
| `Renew_Mark` | Mark renewal |
| `Update_Mark` | Mark update |
| `Delete_Mark` | Mark deletion |

## Workflow

### 1. Mark Registration

```
Customer submits order
    ↓
Provide mark details
    ↓
Upload trademark documents
    ↓
Submit to TMCH
    ↓
Verification process
    ↓
Mark approved/rejected
    ↓
SMD file generated
```

### 2. Document Requirements

**Registered Trademark:**
- Trademark registration certificate
- Proof of use (if required)

**Court-Validated Mark:**
- Court decision document
- Legal validation proof

**Treaty Mark:**
- International registration
- Madrid Protocol documentation

### 3. SMD File Generation

After verification:
1. Mark verified by TMCH
2. SMD file generated
3. File available for download
4. Use for sunrise registrations

## Database Tables

### mod_asciotmch_services

Service tracking:

| Column | Description |
|--------|-------------|
| `service_id` | WHMCS service ID |
| `mark_handle` | Ascio mark handle |
| `mark_name` | Trademark name |
| `status` | Current status |
| `smd_file` | SMD file content |
| `created_at` | Creation timestamp |

### mod_asciotmch_documents

Document uploads:

| Column | Description |
|--------|-------------|
| `service_id` | WHMCS service ID |
| `document_type` | Document category |
| `filename` | Original filename |
| `upload_status` | Upload status |
| `uploaded_at` | Upload timestamp |

## Callbacks

### Callback URL

Configure in your Ascio account:
```
https://your-whmcs.com/modules/registrars/ascio/callbacks.php
```

### Callback Events

| Event | Description |
|-------|-------------|
| `Order_Completed` | Mark registered |
| `Order_Failed` | Registration failed |
| `Pending_Documentation` | Docs required |
| `Verified` | Mark verified |
| `SMD_Ready` | SMD file available |
| `Renewal_Due` | Renewal reminder |

## Client Area Integration

### Available Actions

| Action | Description |
|--------|-------------|
| View Mark | Display mark details |
| Download SMD | Download SMD file |
| Upload Documents | Submit verification docs |
| View Status | Track verification progress |
| Renew | Request renewal |

### Document Upload

Customers can upload documents via client area:
1. Navigate to service details
2. Click "Upload Documents"
3. Select document type
4. Upload file
5. Submit for verification

## Testing

### Run Unit Tests

```bash
cd tmch
./vendor/bin/phpunit
```

### Test Coverage

| Area | Tests | Coverage |
|------|-------|----------|
| Order creation | 30+ | High |
| Document upload | 15+ | Medium |
| Callback processing | 20+ | Medium |
| SMD retrieval | 10+ | Medium |

## Troubleshooting

### Common Issues

**Mark not verifying**
- Check document quality
- Verify trademark validity
- Confirm mark name matches

**SMD file not generating**
- Wait for verification completion
- Check mark status
- Verify order completion

**Document upload failing**
- Check file size limits
- Verify file format
- Ensure service is active

### Debug Mode

Enable in WHMCS:
1. Go to **Utilities → Logs → Module Log**
2. Enable logging for Ascio TMCH module
3. Review logged requests/responses

## TMCH Status Codes

| Status | Description |
|--------|-------------|
| `New` | Order submitted |
| `Pending_Verification` | Awaiting verification |
| `Pending_Documentation` | Documents required |
| `Verified` | Mark verified, SMD ready |
| `Rejected` | Verification failed |
| `Active` | Mark active |
| `Expired` | Mark expired |

## Related Documentation

- [Domains Module](domains.md)
- [Defensive Module](defensive.md)
- [Core Library](../core/README.md)
- [ICANN TMCH](https://newgtlds.icann.org/en/about/trademark-clearinghouse)
