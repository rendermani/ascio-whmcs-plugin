# Ascio WHMCS Modules - Comprehensive Gap Analysis

## Executive Summary

| Module | Lifecycle Tests | UI Integration | V3 API Coverage | Overall |
|--------|-----------------|----------------|-----------------|---------|
| **Domains** | ★★★★★ Excellent | ★★★★★ Excellent | ★★★★☆ Good | 90% |
| **SSL** | ★★★★★ Excellent | ★★★★☆ Good | ★★★★★ Excellent | 85% |
| **Monitoring** | ★★☆☆☆ Poor | ★★★☆☆ Fair | ★★★★☆ Good | 60% |
| **TMCH** | ☆☆☆☆☆ None | ★★★☆☆ Fair | ★★★★☆ Good | 50% |
| **Defensive** | ☆☆☆☆☆ None | ★★★☆☆ Fair | ★★★★☆ Good | 50% |

---

## 1. V3 API Coverage Analysis

### Available Operations (from WSDL)

#### Order Management
| Operation | Domains | SSL | TMCH | Monitoring | Defensive |
|-----------|---------|-----|------|------------|-----------|
| CreateOrder | ✅ | ✅ | ✅ | ✅ | ✅ |
| ValidateOrder | ✅ | ✅ | ✅ | ✅ | ✅ |
| GetOrder | ✅ | ✅ | ✅ | ✅ | ✅ |
| GetOrders | ✅ | ✅ | ✅ | ✅ | ✅ |
| **CancelOrder** | ❌ | ❌ | ❌ | ❌ | ❌ |

#### Domain Operations
| Operation | Domains | Notes |
|-----------|---------|-------|
| GetDomain | ✅ | Via searchDomain() |
| GetDomains | ✅ | Via searchDomain() |
| **GetPremiumDomains** | ❌ | Not implemented |
| AvailabilityInfo | ✅ | |

#### Product-Specific Retrieval
| Operation | SSL | TMCH | Monitoring | Defensive |
|-----------|-----|------|------------|-----------|
| GetSslCertificate | ✅ | - | - | - |
| GetSslCertificates | ❌ | - | - | - |
| **GetSslApprovers** | ❌ | - | - | - |
| **GetSslCertificateChain** | ❌ | - | - | - |
| GetAutoInstallSsl | ✅ | - | - | - |
| GetMark | - | ✅ | - | - |
| GetMarks | - | ❌ | - | - |
| GetNameWatch | - | - | ✅ | - |
| GetNameWatchList | - | - | ❌ | - |
| GetDefensive | - | - | - | ✅ |
| GetDefensives | - | - | - | ❌ |

#### Messaging & Queue
| Operation | All Modules |
|-----------|-------------|
| PollQueue | ✅ |
| AckQueueMessage | ✅ |
| GetQueueMessage | ✅ |
| GetMessages | ✅ |
| **GetAttachment** | ❌ |
| **ResendMessage** | ❌ |

#### Documentation
| Operation | TMCH | Other |
|-----------|------|-------|
| UploadDocumentation | ✅ | - |
| UploadMessage | ✅ | - |
| **CreateApprovalDocumentation** | ❌ | - |

#### Account & Pricing (NOT IMPLEMENTED)
| Operation | Status | Notes |
|-----------|--------|-------|
| GetAccountBalance | ❌ | Could show in admin |
| GetAccountTransactions | ❌ | Audit trail |
| GetSalesLines | ❌ | Reporting |
| GetPrices | ❌ | Dynamic pricing |
| GetFuturePrices | ❌ | Pricing changes |
| GetPriceHistory | ❌ | Historical data |
| GetInvoice | ❌ | Invoice retrieval |
| GetCreditNote | ❌ | Credit notes |

#### Sub-user Management (NOT IMPLEMENTED)
| Operation | Status | Notes |
|-----------|--------|-------|
| GetSubUsers | ❌ | Multi-tenant |
| GetSubUser | ❌ | |
| CreateSubUser | ❌ | |
| UpdateSubUser | ❌ | |
| DeleteSubUser | ❌ | |

#### Contact/Registrant Management
| Operation | Domains | Notes |
|-----------|---------|-------|
| GetRegistrant | ❌ | Uses handles |
| CreateRegistrant | ❌ | Inline in orders |
| GetContact | ❌ | Uses handles |
| CreateContact | ❌ | Inline in orders |
| GetNameServer | ❌ | Could be useful |
| CreateNameServer | ❌ | |
| **GetRegistrantVerificationInfo** | ✅ | |
| **StartRegistrantVerification** | ✅ | |

#### Customer References (NOT IMPLEMENTED)
| Operation | Status | Notes |
|-----------|--------|-------|
| GetCustomerReferences | ❌ | Custom tagging |
| CreateCustomerReference | ❌ | |
| UpdateCustomerReference | ❌ | |
| DeleteCustomerReference | ❌ | |
| SetCustomerReferences | ❌ | |

---

## 2. Lifecycle Tests Gap

### Current Status

| Module | Unit Tests | Integration Tests | E2E Lifecycle | Status |
|--------|------------|-------------------|---------------|--------|
| Domains | ✅ 834 tests | ✅ 7 files | ✅ Complete | **Complete** |
| SSL | ✅ Extensive | ✅ 9 files | ✅ E2E with DNS | **Complete** |
| Monitoring | ✅ Basic | ❌ None | ❌ None | **Needs Work** |
| TMCH | ❌ None | ❌ None | ❌ None | **Critical Gap** |
| Defensive | ❌ None | ❌ None | ❌ None | **Critical Gap** |

### Required Lifecycle Tests

#### TMCH Module - Critical
```
1. Order Creation Flow
   - Create Mark order (Register_Mark)
   - Validate order structure
   - Verify order submission

2. Document Upload Flow
   - Upload trademark documents
   - Verify document status
   - Handle approval/rejection callbacks

3. Polling & Callbacks
   - Poll queue for updates
   - Process callbacks
   - Acknowledge messages

4. Completion & Retrieval
   - Verify order completion
   - GetMark to retrieve details
   - Verify SMD file generation
```

#### Defensive Module - Critical
```
1. Order Creation Flow
   - Create Defensive order
   - Validate order structure
   - Verify submission

2. Polling & Callbacks
   - Poll for status updates
   - Process callbacks
   - Handle block notifications

3. Renewal Flow
   - Test renewal orders
   - Verify expiry extension

4. Retrieval
   - GetDefensive to verify status
   - Verify blocked domain list
```

#### Monitoring Module - Important
```
1. Order Creation Flow
   - Create NameWatch order
   - Validate tier/frequency settings

2. Polling & Callbacks
   - Poll for alerts
   - Process monitoring callbacks
   - Verify alert delivery

3. Renewal Flow
   - Test renewals
   - Verify tier changes

4. Retrieval
   - GetNameWatch for status
   - Verify monitoring rules
```

---

## 3. UI Integration Gap

### Admin Custom Buttons

| Module | Current Buttons | Missing |
|--------|-----------------|---------|
| Domains | Update EPP Code, Autorenew On/Off | None |
| SSL | Renew Certificate | Reissue, SANs Mgmt, Download |
| Monitoring | Refresh Status | View Alerts, Change Tier |
| TMCH | Refresh Status | Upload Docs, Download SMD |
| Defensive | Refresh Status | View Blocks, Extend |

### Client Area Buttons

| Module | Current Buttons | Missing |
|--------|-----------------|---------|
| Domains | Update EPP Code | None |
| SSL | Download Certificate | Reissue Request |
| Monitoring | None | View Status, Alerts |
| TMCH | None | Upload Docs, Download SMD |
| Defensive | None | View Blocks |

### SSL Module TODOs (from code)
```php
// TODO Create Renew - Button exists but logic incomplete
// TODO Create Reissue - Not implemented
// TODO Create Order SANs - Not implemented
// TODO Create Fail - Error handling incomplete
```

---

## 4. Priority Recommendations

### P0 - Critical (Blocking Production)
1. **Create TMCH lifecycle tests** - Cannot verify orders work
2. **Create Defensive lifecycle tests** - Cannot verify orders work
3. **Fix SSL Renew button** - Admins can't renew certificates

### P1 - High (Should Fix Soon)
1. **Add Monitoring E2E tests** - Limited confidence in product
2. **Implement SSL Reissue** - Common customer request
3. **Add TMCH document upload UI** - Required for workflow
4. **Implement CancelOrder** - Common admin need

### P2 - Medium (Nice to Have)
1. **Add SSL SANs management** - Multi-domain cert management
2. **Add GetSslCertificateChain** - Full chain download
3. **Add GetSslApprovers** - Email validation
4. **Add Monitoring client UI** - Customer visibility

### P3 - Low (Future Enhancement)
1. **Implement Account Balance API** - Admin dashboard
2. **Implement Pricing APIs** - Dynamic pricing
3. **Implement Sub-user APIs** - Multi-tenant support
4. **Add Customer References** - Custom tagging

---

## 5. Estimated Effort

| Task | Complexity | Effort |
|------|------------|--------|
| TMCH lifecycle tests | Medium | 2-3 days |
| Defensive lifecycle tests | Medium | 2-3 days |
| Monitoring E2E tests | Medium | 1-2 days |
| SSL Renew implementation | Low | 0.5 day |
| SSL Reissue implementation | Medium | 1 day |
| TMCH document upload UI | Medium | 1 day |
| CancelOrder implementation | Low | 0.5 day |
| SSL SANs management | High | 2-3 days |

**Total Estimated Effort for P0+P1: ~10-12 days**

---

## 6. Test File Locations

```
Domains:
  tests/Unit/              - 834+ tests
  tests/Integration/       - 7 integration test files
  tests/Unit/Tlds/         - TLD-specific tests

SSL:
  tests/Integration/       - 9 E2E test files
  tests/Unit/              - Unit tests

Monitoring:
  tests/MonitoringTest.php - Basic unit tests only

TMCH:
  (none)

Defensive:
  (none)

Core:
  tests/                   - 5 shared test files
```

---

## 7. Next Steps

1. **Immediate**: Run existing tests to verify current state
   ```bash
   cd ascio/domains && ./vendor/bin/phpunit --exclude-group integration
   cd ascio/ssl && ./vendor/bin/phpunit
   cd ascio/monitoring && ./vendor/bin/phpunit
   ```

2. **This Week**: Create skeleton lifecycle tests for TMCH and Defensive

3. **Next Sprint**: Implement missing SSL admin buttons

4. **Backlog**: Add remaining P2/P3 features

---

*Generated: 2026-02-01*
*Last Updated: Initial analysis*
