# Tavro — World-Class Engineering & Product Hardening Roadmap

**Status:** Active
**Objective:** Take Tavro from a functional SaaS application to a production-grade, secure, scalable, auditable and commercially credible platform.

---

## 0. Definition of "World Class"

Tavro should satisfy all of the following:

* Secure by default
* Strict multi-tenant isolation
* Financially/accounting correct
* Transactionally safe
* Idempotent
* Auditable
* Horizontally scalable
* Observable
* Recoverable
* Well tested
* API-consistent
* Type-safe
* Accessible
* Fast
* Mobile/responsive
* Production deployable
* Easy to maintain
* Easy to extend
* Safe under concurrent requests
* Safe under retries and network failures
* Safe under partial failures
* Explicit about authorization
* Explicit about business invariants
* Capable of supporting multiple organizations without data leakage
* Capable of handling real production traffic

A green CI pipeline is **necessary but not sufficient**.

---

# PHASE 1 — CI/CD BASELINE

## 1.1 Backend CI

### Required

* [ ] Laravel test suite runs in CI
* [ ] PostgreSQL service is provisioned correctly
* [ ] Database migrations run from a clean database
* [ ] Seeders/factories work in CI
* [ ] Feature tests run
* [ ] Unit tests run
* [ ] Architecture tests run where applicable
* [ ] Static analysis runs
* [ ] PHP formatting check runs
* [ ] No test depends on developer-local environment
* [ ] No test requires real third-party credentials
* [ ] No hardcoded credentials
* [ ] No test ordering dependency
* [ ] Tests can run repeatedly without state contamination

### Quality gates

* [ ] PHPStan/Psalm configured
* [ ] PHPStan baseline reviewed and minimized
* [ ] PHP CS Fixer/Pint enforced
* [ ] Deprecation warnings reviewed
* [ ] Failed tests fail CI
* [ ] Coverage threshold established

---

# PHASE 2 — FRONTEND ENGINEERING

## 2.1 TypeScript

* [ ] `strict: true`
* [ ] No unnecessary `any`
* [ ] No `@ts-ignore` without documented justification
* [ ] API responses have explicit types
* [ ] Forms have typed schemas
* [ ] Shared domain types exist
* [ ] Error responses are typed
* [ ] Loading/error/empty states are typed

## 2.2 ESLint

* [ ] ESLint passes with zero errors
* [ ] Warnings are progressively eliminated
* [ ] React Hooks rules enforced
* [ ] Accessibility rules enabled
* [ ] Import rules enforced
* [ ] Unused code detection enabled

**Important:** Warnings should not become a permanent dumping ground.

---

# PHASE 3 — MULTI-TENANT SECURITY

This is one of Tavro's most important areas.

## 3.1 Tenant Context

* [x] Explicit tenant context exists
* [x] Tenant context can be established per request
* [x] Tenant context can be cleared
* [x] Missing tenant context fails closed
* [x] Tenant-scoped queries use tenant context
* [ ] Tenant context is automatically established from authenticated identity
* [ ] Tenant context cannot be supplied by an untrusted arbitrary request parameter
* [ ] Tenant context cannot leak between requests
* [ ] Long-running workers explicitly establish tenant context
* [ ] Queued jobs restore tenant context
* [ ] Scheduled jobs explicitly establish tenant context
* [ ] Console/CLI commands explicitly establish tenant context

## 3.2 Tenant Isolation Tests

Create automated tests proving:

* [ ] Tenant A cannot read Tenant B records
* [ ] Tenant A cannot update Tenant B records
* [ ] Tenant A cannot delete Tenant B records
* [ ] Tenant A cannot attach Tenant B records
* [ ] Tenant A cannot manipulate Tenant B inventory
* [ ] Tenant A cannot manipulate Tenant B orders
* [ ] Tenant A cannot manipulate Tenant B payments
* [ ] Tenant A cannot access Tenant B invoices
* [ ] Tenant A cannot access Tenant B subscriptions
* [ ] Tenant A cannot access Tenant B users
* [ ] Tenant A cannot access Tenant B branches
* [ ] Tenant A cannot access Tenant B reports
* [ ] Tenant A cannot access Tenant B audit records

## 3.3 Cross-Tenant Attack Tests

Explicitly test:

* [ ] IDOR
* [ ] UUID substitution
* [ ] Numeric ID substitution
* [ ] Route parameter manipulation
* [ ] Mass assignment
* [ ] Relationship manipulation
* [ ] Bulk update
* [ ] Bulk delete
* [ ] Export endpoints
* [ ] Search endpoints
* [ ] Report endpoints
* [ ] Background jobs

---

# PHASE 4 — AUTHORIZATION

Authentication answers:

> "Who are you?"

Authorization answers:

> "Are you allowed to do this?"

Tavro needs both.

## 4.1 Roles

Define explicit roles and capabilities.

Example:

* [ ] Owner
* [ ] Administrator
* [ ] Manager
* [ ] Cashier
* [ ] Inventory Manager
* [ ] Accountant
* [ ] Staff
* [ ] Viewer

## 4.2 Permissions

Create explicit permissions for:

* [ ] Organizations
* [ ] Users
* [ ] Branches
* [ ] Products
* [ ] Inventory
* [ ] Orders
* [ ] Customers
* [ ] Payments
* [ ] Refunds
* [ ] Voids
* [ ] Reports
* [ ] Invoices
* [ ] Subscriptions
* [ ] Settings
* [ ] Audit logs

## 4.3 Authorization Tests

Every sensitive action should have:

* [ ] Authorized test
* [ ] Unauthorized test
* [ ] Wrong-role test
* [ ] Wrong-tenant test

---

# PHASE 5 — INVENTORY LEDGER

The inventory ledger should behave like an immutable financial ledger.

## 5.1 Immutability

* [x] Inventory movements are recorded
* [x] Ledger entries are immutable
* [ ] Existing ledger entries cannot be edited
* [ ] Existing ledger entries cannot be deleted
* [ ] Corrections create compensating movements
* [ ] Every movement has a reason
* [ ] Every movement has an actor/system source
* [ ] Every movement has timestamp
* [ ] Every movement has source/reference
* [ ] Every movement can be traced to its originating business event

## 5.2 Inventory Invariants

Test:

* [ ] Quantity cannot silently become negative
* [ ] Reversal restores the exact quantity
* [ ] Partial reversal works correctly
* [ ] Multiple reversals cannot exceed original consumption
* [ ] Concurrent deductions cannot corrupt stock
* [ ] Duplicate requests cannot double-consume stock
* [ ] Failed transactions roll back completely
* [ ] Successful transactions commit completely

## 5.3 Concurrency

Test scenarios such as:

```text
Stock = 1

Request A → consume 1
Request B → consume 1

Expected:
Exactly one succeeds.
Exactly one fails.
Stock never becomes -1.
```

Use database transactions and appropriate locking.

---

# PHASE 6 — ORDERS

## 6.1 Order lifecycle

Define explicit states:

```text
DRAFT
→ CONFIRMED
→ PROCESSING
→ COMPLETED
```

With explicit exceptional states where required:

```text
CANCELLED
VOIDED
REFUNDED
PARTIALLY_REFUNDED
```

## 6.2 State machine

* [ ] Illegal transitions are rejected
* [ ] Completed orders cannot be edited arbitrarily
* [ ] Voided orders cannot silently disappear
* [ ] Refunds create auditable financial events
* [ ] Order totals are deterministic
* [ ] Tax calculations are deterministic
* [ ] Discounts are deterministic
* [ ] Inventory consumption is tied to order state
* [ ] Order reversal is tied to exact order lineage

---

# PHASE 7 — PAYMENTS & FINANCIAL INTEGRITY

This needs serious attention before production.

## 7.1 Payment model

* [ ] Payment states are explicit
* [ ] Pending
* [ ] Successful
* [ ] Failed
* [ ] Cancelled
* [ ] Refunded
* [ ] Partially refunded

## 7.2 Idempotency

Every payment-mutating endpoint should handle retries safely.

Test:

```text
Request
↓
timeout
↓
client retries
↓
same idempotency key
```

Expected:

```text
ONE financial operation
```

Not:

```text
TWO charges
```

## 7.3 Webhooks

* [ ] Webhooks authenticated
* [ ] Webhooks idempotent
* [ ] Duplicate webhook delivery safe
* [ ] Out-of-order webhook handling defined
* [ ] Webhook payload persisted where necessary
* [ ] Failed webhook processing retryable
* [ ] Webhook processing observable

## 7.4 Financial Audit

Never rely only on mutable balances.

Maintain:

```text
Transaction
Payment
Refund
Adjustment
Audit Event
```

with immutable history.

---

# PHASE 8 — SUBSCRIPTIONS & BILLING

* [x] Billing interval is persisted
* [x] Renewal interval is data-driven
* [ ] Subscription state machine exists
* [ ] Trial state defined
* [ ] Grace period defined
* [ ] Failed payment handling defined
* [ ] Cancellation defined
* [ ] Downgrade defined
* [ ] Upgrade defined
* [ ] Proration policy defined
* [ ] Subscription renewal idempotent
* [ ] Billing webhook idempotent
* [ ] Expired subscription behavior defined
* [ ] Entitlement checks tested

---

# PHASE 9 — DATABASE ENGINEERING

## 9.1 Integrity

* [ ] Foreign keys everywhere appropriate
* [ ] Correct FK types
* [ ] Correct indexes
* [ ] Unique constraints
* [ ] Check constraints where supported
* [ ] Nullable fields intentional
* [ ] Cascades reviewed individually
* [ ] No dangerous cascading deletes
* [ ] Soft deletion policy defined

## 9.2 Performance

Identify indexes for:

* [ ] organization_id
* [ ] branch_id
* [ ] product_id
* [ ] order_id
* [ ] customer_id
* [ ] payment_id
* [ ] created_at
* [ ] status
* [ ] subscription_id

Review:

* [ ] N+1 queries
* [ ] Large joins
* [ ] Unbounded queries
* [ ] Missing pagination
* [ ] Expensive reports

---

# PHASE 10 — API ENGINEERING

## 10.1 API consistency

Every API should have consistent:

```json
{
  "data": {},
  "message": "",
  "errors": {}
}
```

or a documented alternative.

## 10.2 API requirements

* [ ] Request validation
* [ ] Authorization
* [ ] Tenant isolation
* [ ] Pagination
* [ ] Filtering
* [ ] Sorting
* [ ] Consistent error responses
* [ ] HTTP status correctness
* [ ] Idempotency where required
* [ ] Rate limiting
* [ ] API versioning strategy
* [ ] OpenAPI documentation

---

# PHASE 11 — SECURITY HARDENING

## Application

* [ ] CSRF protection where applicable
* [ ] Secure cookies
* [ ] Secure session configuration
* [ ] Password hashing
* [ ] Rate limiting
* [ ] Brute-force protection
* [ ] Account lockout/abuse controls
* [ ] Input validation
* [ ] Output encoding
* [ ] Mass assignment protection
* [ ] File upload validation
* [ ] SSRF protection where applicable
* [ ] SQL injection protection
* [ ] XSS protection

## Secrets

* [ ] No secrets in Git
* [ ] No API keys in frontend source
* [ ] No production credentials in tests
* [ ] `.env` excluded
* [ ] `.env.example` contains placeholders only
* [ ] Secret rotation process documented

## Dependencies

* [ ] Dependabot/Renovate configured
* [ ] Vulnerability scanning enabled
* [ ] Composer audit enabled
* [ ] npm audit/dependency scanning enabled
* [ ] Critical vulnerabilities block release

---

# PHASE 12 — AUDIT LOGGING

Every important business action should be auditable.

Record:

```text
WHO
WHAT
WHEN
WHERE
TENANT
TARGET
BEFORE
AFTER
REQUEST ID
IP
USER AGENT
```

At minimum audit:

* [ ] Login
* [ ] Logout
* [ ] User creation
* [ ] User permission changes
* [ ] Product creation
* [ ] Product changes
* [ ] Inventory adjustments
* [ ] Inventory consumption
* [ ] Order creation
* [ ] Order modification
* [ ] Order void
* [ ] Refund
* [ ] Payment
* [ ] Subscription changes
* [ ] Settings changes

Audit logs themselves should be protected from normal mutation.

---

# PHASE 13 — OBSERVABILITY

Production systems need visibility.

## Logging

* [ ] Structured logs
* [ ] Request ID
* [ ] User ID
* [ ] Organization ID
* [ ] Job ID
* [ ] Exception context
* [ ] No sensitive data leakage

## Monitoring

* [ ] Application errors
* [ ] API latency
* [ ] Database latency
* [ ] Queue failures
* [ ] Job retries
* [ ] Payment failures
* [ ] Webhook failures
* [ ] Authentication failures

## Alerting

Alerts for:

* [ ] High error rate
* [ ] Queue failure
* [ ] Database failure
* [ ] Payment failures
* [ ] Repeated webhook failure
* [ ] Abnormal authentication activity

---

# PHASE 14 — QUEUES & ASYNCHRONOUS WORK

* [ ] Queue architecture defined
* [ ] Jobs idempotent
* [ ] Retry strategy defined
* [ ] Backoff defined
* [ ] Maximum attempts defined
* [ ] Failed jobs monitored
* [ ] Tenant context propagated
* [ ] Jobs safe after application restart
* [ ] Duplicate job execution safe

---

# PHASE 15 — CACHING

Only cache things where correctness remains guaranteed.

* [ ] Cache strategy documented
* [ ] Cache keys tenant-aware
* [ ] Cache invalidation defined
* [ ] No cross-tenant cache leakage
* [ ] Cache stampede considered
* [ ] Distributed cache supported if required

---

# PHASE 16 — FRONTEND UX QUALITY

Every important screen needs:

* [ ] Loading state
* [ ] Empty state
* [ ] Error state
* [ ] Success feedback
* [ ] Retry capability
* [ ] Form validation
* [ ] Disabled states
* [ ] Confirmation for destructive operations
* [ ] Optimistic updates only where safe

## Accessibility

* [ ] Keyboard navigation
* [ ] Screen reader support
* [ ] Semantic HTML
* [ ] Accessible labels
* [ ] Focus management
* [ ] Color contrast
* [ ] Error announcements

---

# PHASE 17 — PERFORMANCE

## Backend

* [ ] Query profiling
* [ ] N+1 elimination
* [ ] Proper indexes
* [ ] Pagination
* [ ] Queue heavy operations
* [ ] Cache expensive reads

## Frontend

* [ ] Bundle analysis
* [ ] Code splitting
* [ ] Lazy loading
* [ ] Image optimization
* [ ] Avoid unnecessary rerenders
* [ ] API request deduplication

Targets should eventually be established for:

```text
API p50
API p95
API p99
DB query latency
Frontend LCP
Frontend INP
Frontend CLS
```

---

# PHASE 18 — BACKUP & DISASTER RECOVERY

This is mandatory for production.

* [ ] Automated database backups
* [ ] Backup retention policy
* [ ] Off-server backups
* [ ] Backup encryption
* [ ] Restore procedure documented
* [ ] Restore tested
* [ ] Recovery Point Objective defined
* [ ] Recovery Time Objective defined
* [ ] Disaster recovery runbook

A backup that has never been restored is not a verified backup.

---

# PHASE 19 — DEPLOYMENT

## Production

* [ ] Separate staging environment
* [ ] Production environment
* [ ] Environment-specific configuration
* [ ] Deployment automation
* [ ] Database migration strategy
* [ ] Rollback strategy
* [ ] Health checks
* [ ] Zero/minimal downtime deployment
* [ ] Queue worker deployment strategy
* [ ] Cache deployment strategy

## Release process

```text
PR
↓
CI
↓
Tests
↓
Security scan
↓
Build
↓
Staging
↓
Smoke tests
↓
Approval
↓
Production
```

---

# PHASE 20 — TESTING STRATEGY

Tavro should have multiple testing layers.

## Unit

* [ ] Domain logic
* [ ] Calculations
* [ ] State transitions
* [ ] Policies
* [ ] Services

## Feature

* [ ] Authentication
* [ ] Authorization
* [ ] Tenant isolation
* [ ] Orders
* [ ] Inventory
* [ ] Payments
* [ ] Subscriptions
* [ ] Reports

## Integration

* [ ] Database
* [ ] Payment providers
* [ ] Webhooks
* [ ] Queue system
* [ ] External APIs

## Browser/E2E

Critical flows:

* [ ] Registration
* [ ] Login
* [ ] Organization creation
* [ ] Branch creation
* [ ] Product creation
* [Inventory adjustment
* [ ] Order creation
* [ ] Payment
* [ ] Refund
* [ ] Subscription
* [ ] User invitation

## Failure testing

Explicitly test:

* [ ] Network timeout
* [ ] Duplicate request
* [ ] Duplicate webhook
* [ ] Database deadlock
* [ ] Database timeout
* [ ] Queue retry
* [ ] Concurrent inventory deduction
* [ ] Concurrent order modification
* [ ] Expired authentication
* [ ] Missing tenant
* [ ] Unauthorized tenant

---

# PHASE 21 — CONTRACT TESTING

Frontend and backend must agree.

* [ ] API schemas documented
* [ ] API response contracts tested
* [ ] Breaking API changes detected
* [ ] Frontend generated types considered
* [ ] Error contract tested

---

# PHASE 22 — DATA EXPORT & PORTABILITY

Users must not feel trapped.

* [ ] Organization data export
* [ ] Orders export
* [ ] Customers export
* [ ] Products export
* [ ] Inventory export
* [ ] Financial export
* [ ] Audit export
* [ ] CSV support
* [ ] JSON export where appropriate

Exports must respect tenant authorization.

---

# PHASE 23 — PRIVACY & COMPLIANCE

At minimum:

* [ ] Privacy policy
* [ ] Terms of service
* [ ] Data retention policy
* [ ] Account deletion process
* [ ] Data export process
* [ ] Data access controls
* [ ] Sensitive data classification
* [ ] PII minimization
* [ ] Auditability

If Tavro targets multiple jurisdictions, review applicable:

* [ ] Nigeria NDPA
* [ ] GDPR where applicable
* [ ] Other applicable privacy regulations

---

# PHASE 24 — PRODUCT BILLING & ENTITLEMENTS

Separate:

```text
Subscription
```

from:

```text
Entitlements
```

For example:

```text
Plan
├── max_users
├── max_branches
├── max_products
├── reports
├── advanced_inventory
└── API_access
```

Then enforce entitlements consistently on:

* [ ] Backend
* [ ] Frontend
* [ ] API
* [ ] Background jobs

Frontend hiding a button is **not** authorization.

---

# PHASE 25 — RATE LIMITING & ABUSE PROTECTION

Protect:

* [ ] Login
* [ ] Registration
* [ ] Password reset
* [ ] OTP
* [ ] API
* [ ] Webhooks
* [ ] Search
* [ ] Exports
* [ ] Expensive reports

Define:

* [ ] Per-IP limits
* [ ] Per-user limits
* [ ] Per-tenant limits
* [ ] Burst limits

---

# PHASE 26 — API IDEMPOTENCY

Create a reusable idempotency mechanism.

Example:

```http
Idempotency-Key: 01JXXXXXXXXXXXX
```

Use it for:

* [ ] Payments
* [ ] Orders
* [ ] Inventory mutations
* [ ] Refunds
* [ ] Subscription operations
* [ ] Other financially/business-critical mutations

Test repeated requests.

---

# PHASE 27 — TRANSACTION BOUNDARIES

Every business operation should clearly define its atomic boundary.

Example:

```text
Create Order
    ↓
Validate
    ↓
Lock Inventory
    ↓
Consume Inventory
    ↓
Create Order
    ↓
Create Ledger Entries
    ↓
Commit
```

If any step fails:

```text
ROLLBACK
```

No partial business state.

---

# PHASE 28 — DOMAIN ARCHITECTURE

Avoid turning controllers into business-logic dumping grounds.

Prefer:

```text
Controller
    ↓
Application Service / Action
    ↓
Domain Rules
    ↓
Models / Repositories
    ↓
Database
```

Business-critical operations should have dedicated services/actions.

Examples:

```text
CreateOrder
VoidOrder
ConsumeInventory
ReverseInventory
ProcessPayment
RefundPayment
CreateSubscription
RenewSubscription
CancelSubscription
```

---

# PHASE 29 — DOCUMENTATION

Create:

* [ ] README
* [ ] Architecture documentation
* [ ] Local development setup
* [ ] Environment variables documentation
* [ ] API documentation
* [ ] Database/domain documentation
* [ ] Deployment documentation
* [ ] Disaster recovery runbook
* [ ] Security model
* [ ] Tenant isolation model
* [ ] Billing model
* [ ] Inventory model
* [ ] Contribution guide
* [ ] Changelog

---

# PHASE 30 — ENGINEERING STANDARDS

Establish:

```text
No undocumented business rule.
No untested financial mutation.
No untested authorization rule.
No untested tenant boundary.
No silent data mutation.
No hardcoded credentials.
No production secrets in Git.
No controller-level business complexity.
No cross-tenant cache keys.
No non-idempotent critical mutation.
No destructive operation without audit trail.
```

---

# PHASE 31 — FINAL SECURITY AUDIT

Before production:

* [ ] Authentication audit
* [ ] Authorization audit
* [ ] Tenant isolation audit
* [ ] IDOR audit
* [ ] Mass assignment audit
* [ ] SQL injection audit
* [ ] XSS audit
* [ ] CSRF audit
* [ ] SSRF audit
* [ ] File upload audit
* [ ] Secret audit
* [ ] Dependency audit
* [ ] Rate-limit audit
* [ ] Session audit
* [ ] API exposure audit

---

# PHASE 32 — LOAD & STRESS TESTING

Establish realistic scenarios.

Example:

```text
100 concurrent users
500 concurrent users
1,000 concurrent users
```

Measure:

* [ ] Request latency
* [ ] Error rate
* [ ] DB CPU
* [ ] DB connections
* [ ] Memory
* [ ] Queue latency
* [ ] Cache performance

Also test concurrency-sensitive operations:

* [ ] Inventory consumption
* [ ] Orders
* [ ] Payments
* [ ] Refunds

---

# PHASE 33 — PRODUCTION SMOKE TEST

After deployment:

### Authentication

* [ ] Register
* [ ] Login
* [ ] Logout
* [ ] Password reset

### Organization

* [ ] Create organization
* [ ] Create branch
* [ ] Invite user
* [ ] Assign role

### Product

* [ ] Create product
* [ ] Edit product
* [ ] Inventory adjustment

### Order

* [ ] Create order
* [ ] Complete order
* [ ] Void order
* [ ] Verify inventory

### Payment

* [ ] Create payment
* [ ] Receive webhook
* [ ] Verify transaction
* [ ] Refund

### Billing

* [ ] Subscribe
* [ ] Verify entitlement
* [ ] Renew
* [ ] Cancel

### Security

* [ ] Attempt cross-tenant access
* [ ] Attempt unauthorized action
* [ ] Verify rejection

---

# PHASE 34 — FINAL RELEASE GATE

Tavro is NOT production-ready until all of these are true:

## Engineering

* [ ] CI green
* [ ] Tests green
* [ ] Static analysis green
* [ ] Lint green
* [ ] Build green
* [ ] Security scan green

## Security

* [ ] Tenant isolation proven
* [ ] Authorization proven
* [ ] Secrets audited
* [ ] Dependencies audited
* [ ] Rate limiting active

## Data

* [ ] Database integrity proven
* [ ] Inventory integrity proven
* [ ] Financial integrity proven
* [ ] Backups tested
* [ ] Restore tested

## Operations

* [ ] Monitoring active
* [ ] Error tracking active
* [ ] Alerts configured
* [ ] Deployment documented
* [ ] Rollback documented

## Product

* [ ] Critical UX flows complete
* [ ] Empty/error/loading states complete
* [ ] Mobile responsive
* [ ] Accessibility reviewed
* [ ] Billing verified

---

# CURRENT TAVRO PROGRESS

## Already Added / Addressed During This Hardening Cycle

* [x] Explicit tenant context
* [x] Fail-closed tenant scope
* [x] Tenant-aware model architecture
* [x] Tenant isolation tests
* [x] Inventory ledger architecture
* [x] Immutable inventory movement direction
* [x] Inventory lineage
* [x] Exact order-item inventory binding
* [x] Inventory reversal lineage
* [x] Inventory transaction factory
* [x] Inventory item factory support
* [x] Branch factory support
* [x] Subscription billing interval persistence
* [x] Data-driven subscription renewal interval
* [x] Interval-aware checkout
* [x] Protection against dangerous payment-history cascading deletes
* [x] Auditable order-item void operation
* [x] Transactional order creation
* [x] Transactional order sending
* [x] Backend test contract alignment
* [x] Frontend CI lint unblock

---

# STILL HIGH PRIORITY

The following should be treated as the next major engineering priorities:

## P0 — Critical

1. [ ] Make CI completely green
2. [ ] Complete tenant isolation audit
3. [ ] Complete authorization matrix
4. [ ] Harden inventory concurrency
5. [ ] Harden payment idempotency
6. [ ] Harden webhook idempotency
7. [ ] Verify financial transaction invariants
8. [ ] Verify subscription entitlement enforcement
9. [ ] Security audit
10. [ ] Database integrity audit

## P1 — Production

11. [ ] Observability
12. [ ] Error tracking
13. [ ] Queue reliability
14. [ ] Backup/restore
15. [ ] Deployment/rollback
16. [ ] API contract testing
17. [ ] E2E testing
18. [ ] Performance testing
19. [ ] Rate limiting
20. [ ] Data export

## P2 — Product Excellence

21. [ ] Accessibility
22. [ ] UX consistency
23. [ ] Performance optimization
24. [ ] Documentation
25. [ ] Privacy/compliance
26. [ ] Advanced reporting
27. [ ] Operational dashboards
28. [ ] Developer experience

---

# LOCAL EXECUTION PROTOCOL

Work through this roadmap locally.

For every completed item:

1. Implement it.
2. Add/update tests.
3. Run the relevant test suite.
4. Run the full backend suite.
5. Run frontend lint.
6. Run TypeScript checks.
7. Run production build.
8. Commit the changes.
9. Push to GitHub.
10. Verify CI.

Do NOT mark an item complete merely because the code exists.

It is complete only when:

```text
IMPLEMENTED
+
TESTED
+
VERIFIED
```

---

# FINAL AUDIT REQUEST

When the local implementation is complete, bring the repository back for an external audit.

The audit must NOT assume the checklist is correct merely because items are marked `[x]`.

For each major area, verify the implementation independently:

```text
Architecture
Security
Tenant Isolation
Authorization
Inventory
Orders
Payments
Subscriptions
Database
API
Frontend
Testing
Performance
Observability
Deployment
Backups
Documentation
```

The audit should actively attempt to find:

* security vulnerabilities
* tenant isolation failures
* authorization bypasses
* race conditions
* transaction inconsistencies
* idempotency failures
* data corruption scenarios
* broken state transitions
* API inconsistencies
* performance problems
* missing tests
* misleading tests
* dead code
* architectural weaknesses
* production deployment risks

## Final standard

Do not ask:

> "Does Tavro work?"

Ask:

> **"What happens when everything goes wrong?"**

That is the standard this roadmap is designed to enforce.
