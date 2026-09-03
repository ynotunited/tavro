# Product Requirements Document (PRD)

## Nigerian Hospitality Operations & Intelligence Platform

**Document Status:** Product Foundation / Production PRD
**Version:** 1.0
**Product Type:** Multi-tenant SaaS
**Primary Market:** Nigeria
**Target Businesses:** Restaurants, Bars, Lounges, Clubs, Hotels & Hospitality Groups
**Primary Platform:** Web / PWA
**Backend:** Laravel
**Frontend:** Next.js + TypeScript
**Database:** PostgreSQL
**Real-Time:** Laravel Reverb / WebSockets
**Deployment:** Docker + Nginx on VPS/cloud infrastructure

---

# 1. Executive Summary

The product is a Nigeria-first hospitality operations and business intelligence platform designed for restaurants, bars, lounges, clubs and hotels.

It combines:

* Point of Sale
* Table management
* Kitchen operations
* Bar operations
* Inventory
* Payments
* Staff management
* Shift reconciliation
* Revenue controls
* Business reporting
* Owner intelligence

into a single operational platform.

The product is intentionally **not positioned as a generic POS**.

The POS is the operational entry point. The larger objective is to help hospitality businesses answer:

> **What did we sell, what should we have consumed, where did the money go, who handled it, and what needs my attention?**

The platform must make daily operations extremely simple for staff while providing owners and managers with deep visibility and accountability.

---

# 2. Product Vision

## Vision

Build the operating system Nigerian hospitality businesses use to run, control and understand their businesses.

## Mission

Make hospitality operations:

* Easier for staff
* More accountable for managers
* More transparent for owners
* More resilient to Nigerian operating conditions
* More profitable through better control

## Core Product Promise

> **Sell more. Control stock. Know your numbers.**

---

# 3. Product Philosophy

The following principles govern product decisions.

### 3.1 Simplicity for Staff

A waiter, bartender or cashier should be able to perform their primary tasks with minimal training.

### 3.2 Intelligence for Management

Managers should receive operational information without having to manually analyze raw transactions.

### 3.3 Visibility for Owners

An owner should understand the health of the business within 60 seconds of opening the dashboard.

### 3.4 Accountability by Default

Financially meaningful actions must always be attributable to a user.

### 3.5 One Entry, Multiple Outcomes

Staff should enter information once.

The system should automatically update:

* Sales
* Inventory
* Reports
* Payments
* Analytics
* Audit trails

### 3.6 Nigeria First

The product must reflect:

* Nigerian payment behavior
* Bank transfers
* POS terminals
* Cash
* Intermittent connectivity
* Naira
* Local hospitality workflows
* Complimentary items
* Stock leakage
* Manual reconciliation

### 3.7 Offline Resilience

Internet failure must not immediately stop a hospitality business from selling.

---

# 4. Target Market

## 4.1 Primary ICP

Small and medium-sized hospitality businesses operating in Nigeria.

### Primary segments

1. Restaurants
2. Bars
3. Lounges
4. Clubs
5. Restaurants + Bars
6. Hotels with F&B operations
7. Hospitality groups with multiple branches

---

# 5. Target User Personas

## 5.1 Owner

### Goals

* Know daily revenue
* Monitor profitability
* Detect leakage
* Monitor multiple locations
* Reduce dependency on manual reports
* Understand staff performance

### Pain Points

* Cannot always be physically present
* Receives unreliable reports
* Suspects stock leakage
* Uses spreadsheets and WhatsApp
* Doesn't know actual profit
* Finds existing POS systems too operational and not insightful

---

## 5.2 General Manager

### Goals

* Run daily operations
* Monitor staff
* Control stock
* Approve discounts
* Reconcile shifts
* Resolve operational issues

---

## 5.3 Branch Manager

### Goals

* Manage one branch
* Monitor sales
* Manage employees
* Control inventory
* Handle closing procedures

---

## 5.4 Cashier

### Goals

* Process orders
* Receive payments
* Print receipts
* Reconcile cash
* Close shift

### UX priority

Speed and accuracy.

---

## 5.5 Waiter

### Goals

* Open tables
* Take orders
* Send orders
* Request bills
* Transfer tables

### UX priority

Extreme simplicity.

---

## 5.6 Bartender

### Goals

* Receive drink orders
* Prepare drinks
* Track wastage
* Record complimentary drinks

---

## 5.7 Kitchen Staff

### Goals

* Receive kitchen tickets
* Prepare orders
* Mark orders ready

---

## 5.8 Inventory Manager

### Goals

* Receive stock
* Count stock
* Track wastage
* Monitor inventory
* Manage suppliers
* Detect stock variance

---

# 6. Product Scope

## 6.1 MVP

The MVP must contain:

* Multi-tenant business setup
* Branches
* User management
* Roles and permissions
* Product/menu management
* Table management
* POS
* Kitchen display
* Bar display
* Payments
* Basic inventory
* Bar inventory
* Staff shifts
* Cash reconciliation
* Audit logs
* Owner dashboard
* Core reports
* Notifications
* Subscription management
* Offline POS capability

---

# 7. MVP Non-Goals

The following are intentionally excluded from MVP:

* Full accounting
* Payroll
* Full hotel PMS
* Loyalty program
* Delivery marketplace
* Customer mobile application
* AI forecasting
* Advanced CRM
* Full procurement automation
* Advanced HR
* Franchise management
* Multi-country tax engine
* Full event management

---

# 8. Core Product Modules

```text
Platform
├── Identity & Access
├── Organizations
├── Branches
├── Menu & Catalog
├── Tables & Floor
├── POS
├── Kitchen
├── Bar
├── Payments
├── Inventory
├── Purchasing
├── Staff & Shifts
├── Reconciliation
├── Customers
├── Reservations
├── Reports
├── Business Intelligence
├── Notifications
├── Subscriptions
├── Audit & Security
└── Platform Administration
```

---

# 9. Functional Requirements

# 9.1 Organization Management

The system shall allow an organization to:

* Create a business
* Select business type
* Configure business name
* Configure currency
* Configure tax
* Configure service charge
* Configure receipt information
* Create branches
* Configure operating hours

### Acceptance Criteria

* Organization creation creates an isolated tenant.
* Organization cannot access another organization's data.
* Default branch can be created during onboarding.
* Business configuration is persisted.
* Only authorized users can change business configuration.

---

# 9.2 Branch Management

Each organization can have multiple branches.

### Requirements

* Create branch
* Edit branch
* Disable branch
* Configure branch address
* Configure branch timezone
* Configure branch operating hours
* Assign users
* Assign inventory
* Assign POS devices

### Acceptance Criteria

* Branch data is isolated.
* Users can only access branches permitted by their role.
* Owner can switch between branches.
* Branch reports aggregate correctly into organization reports.

---

# 9.3 Authentication

Support:

* Email/password
* Secure session management
* Password reset
* Account verification
* Optional MFA
* Device/session management

Future:

* Passkeys
* SSO

---

# 9.4 Role-Based Access Control

Roles:

* Owner
* General Manager
* Branch Manager
* Cashier
* Waiter
* Bartender
* Kitchen Staff
* Inventory Manager
* Accountant/Finance

Permissions must be granular.

Example:

```text
orders.create
orders.update
orders.void
orders.discount
orders.refund

payments.create
payments.refund

inventory.view
inventory.adjust
inventory.receive

reports.view
reports.financial

staff.manage
settings.manage
```

---

# 10. Menu & Catalog

## Requirements

Products must support:

* Name
* SKU
* Category
* Description
* Selling price
* Cost price
* Tax
* Service charge
* Availability
* Image
* Modifier groups
* Recipe
* Inventory mapping

### Product Types

* Food
* Drink
* Cocktail
* Package
* Modifier
* Service

---

# 11. Product Variants

Examples:

```text
Beer
├── Bottle
└── Can

Whisky
├── Shot
├── Double
└── Bottle

Food
├── Regular
└── Large
```

Each variant may have:

* Different price
* Different inventory consumption
* Different recipe

---

# 12. Recipe Engine

Recipes define expected inventory consumption.

Example:

```text
Hennessy Cocktail

30ml Hennessy
20ml syrup
100ml mixer
Ice
```

When sold, the system automatically creates inventory consumption entries.

### Acceptance Criteria

* Product sales create expected stock movements.
* Recipe quantities are configurable.
* Recipe versions are preserved.
* Historical orders use the correct recipe version.

---

# 13. Table & Floor Management

## Requirements

* Create floors
* Create tables
* Configure table capacity
* Configure table name/number
* Drag/drop floor layout
* Assign table to waiter
* Open table
* Transfer table
* Merge tables
* Split table
* Mark table available
* Mark table occupied
* Mark table reserved
* Mark table cleaning

### Table states

```text
AVAILABLE
RESERVED
OCCUPIED
ORDER_READY
BILL_REQUESTED
PAYMENT_PENDING
CLEANING
```

---

# 14. POS

POS is the primary staff interface.

## Core workflow

```text
Select Table
    ↓
Create Order
    ↓
Add Products
    ↓
Apply Modifiers
    ↓
Send to Station
    ↓
Prepare
    ↓
Serve
    ↓
Request Bill
    ↓
Payment
    ↓
Close Order
```

## Requirements

POS must support:

* New order
* Add item
* Remove item
* Modify quantity
* Add modifiers
* Notes
* Hold order
* Resume order
* Send order
* Re-send item
* Void item
* Cancel order
* Discount
* Service charge
* Tax
* Split bill
* Merge bills
* Transfer table
* Print bill
* Print receipt

---

# 15. Order State Machine

```text
DRAFT
 ↓
OPEN
 ↓
SENT
 ↓
PREPARING
 ↓
READY
 ↓
SERVED
 ↓
BILL_REQUESTED
 ↓
PAYMENT_PENDING
 ↓
PAID
 ↓
CLOSED
```

Cancellation/void states must preserve historical records.

Transactions must never be physically deleted.

---

# 16. Kitchen Display System

Kitchen screen displays:

* Order number
* Table
* Items
* Modifiers
* Notes
* Time received
* Priority
* Status

Statuses:

```text
NEW
PREPARING
READY
SERVED
CANCELLED
```

Kitchen users cannot access financial information.

---

# 17. Bar Operations

Bar screen displays only bar-related items.

Requirements:

* New drink order
* Bottle order
* Shot order
* Cocktail order
* Modifier
* Notes
* Preparation status
* Ready status

---

# 18. Bar Inventory

This is a core differentiator.

The system must support:

* Bottle size
* Serving size
* Standard pour
* Open bottle
* Recipe consumption
* Expected consumption
* Actual consumption
* Stock count
* Stock variance

Example:

```text
Opening:
4 bottles

Purchases:
2 bottles

Expected closing:
3 bottles

Actual closing:
2.5 bottles

Variance:
0.5 bottle
```

The system calculates the monetary impact.

---

# 19. Inventory

## Inventory Item

Fields:

* Name
* SKU
* Category
* Unit
* Cost
* Supplier
* Minimum level
* Current quantity
* Branch
* Storage location

## Stock movements

Types:

* Purchase
* Sale
* Recipe consumption
* Transfer
* Adjustment
* Wastage
* Return
* Opening balance

Every stock movement must be immutable.

Corrections create new adjustment transactions.

---

# 20. Inventory Counts

Users can perform:

* Full stock count
* Category count
* Individual item count
* Bar count

System calculates:

```text
Expected quantity
Actual quantity
Variance quantity
Variance value
```

---

# 21. Wastage

Wastage types:

* Spoilage
* Breakage
* Over-pour
* Kitchen error
* Wrong order
* Expired
* Other

Wastage requires:

* User
* Item
* Quantity
* Reason
* Timestamp
* Optional note

High-value wastage may require manager approval.

---

# 22. Purchasing

MVP purchasing includes:

* Suppliers
* Purchase orders
* Stock receiving
* Purchase cost
* Supplier reference
* Invoice reference
* Received quantity
* Outstanding quantity

---

# 23. Payments

Payment methods:

* Cash
* Bank transfer
* Card
* POS
* Paystack
* Flutterwave
* Other configured methods

Payment record:

```text
payment_id
order_id
amount
method
status
reference
provider
terminal_reference
received_by
created_at
```

---

# 24. Split Payments

Example:

```text
Total: ₦50,000

Cash: ₦20,000
Transfer: ₦15,000
POS: ₦15,000
```

Order becomes paid only when:

```text
sum(successful payments) >= payable amount
```

---

# 25. Payment Verification

For transfers:

Statuses:

```text
PENDING
CONFIRMED
FAILED
REVERSED
```

Manual confirmation must be permission-controlled.

Every confirmation must be audited.

---

# 26. Discounts

Discounts must support:

* Percentage
* Fixed amount
* Product-specific
* Order-wide
* Reason
* Approval threshold

Example:

```text
0–10%
Cashier allowed

10–25%
Manager approval

>25%
Owner/authorized manager
```

Thresholds configurable by organization.

---

# 27. Complimentary Items

Complimentary items are separate from discounts.

Reasons:

* Management
* Birthday
* Staff
* Influencer
* Promotion
* Other

System must report complimentary value separately.

---

# 28. Voids

A void:

* Never deletes the original transaction.
* Records reason.
* Records user.
* Records timestamp.
* May require approval.

Reports must distinguish:

* Sales
* Voids
* Refunds
* Discounts
* Complimentary items

---

# 29. Staff & Shift Management

## Shift lifecycle

```text
SCHEDULED
 ↓
OPEN
 ↓
ACTIVE
 ↓
CLOSING
 ↓
CLOSED
```

Shift contains:

* Staff member
* Branch
* Device
* Opening cash
* Closing cash
* Sales
* Payment totals
* Variance

---

# 30. Cash Reconciliation

At closing:

```text
Opening Cash
+ Cash Sales
+ Other Cash Inflows
- Cash Refunds
= Expected Cash
```

Compare against actual cash.

Variance:

```text
Actual Cash - Expected Cash
```

Manager must review variance.

---

# 31. Audit Logging

Audit all sensitive events:

* Login
* Logout
* Order creation
* Order modification
* Void
* Refund
* Discount
* Complimentary
* Payment modification
* Inventory adjustment
* Stock count
* User creation
* Permission change
* Settings change

Audit log must contain:

* Actor
* Organization
* Branch
* Action
* Entity
* Entity ID
* Previous state where applicable
* New state
* IP
* Device metadata
* Timestamp

---

# 32. Dashboard

## Owner Dashboard

Must show:

### Today

* Revenue
* Orders
* Average order value
* Food revenue
* Drink revenue
* Payment breakdown
* Gross margin estimate
* Active tables

### Attention

* Cash variance
* Stock variance
* Large discounts
* Voids
* Refunds
* Low stock
* Unpaid bills

### Performance

* Best-selling products
* Best-performing categories
* Branch performance
* Staff performance

---

# 33. Reporting Definitions

## Gross Sales

Total value of valid sales before deductions.

## Net Sales

```text
Gross Sales
- Discounts
- Voids
- Refunds
```

Tax treatment must be configurable.

## Average Order Value

```text
Net Sales / Completed Orders
```

## Payment Mix

Percentage of revenue by:

* Cash
* Transfer
* POS
* Card
* Other

## Stock Variance

```text
Actual Stock - Expected Stock
```

## Stock Variance Value

```text
Variance Quantity × Cost Price
```

## Gross Profit Estimate

```text
Net Sales - Estimated Cost of Goods Sold
```

This must be clearly labeled as an estimate where actual accounting costs are unavailable.

---

# 34. Business Intelligence

The system should identify exceptions instead of only presenting charts.

Examples:

```text
⚠️ Cash variance increased 240% today.

⚠️ Hennessy stock is 0.4 bottles below expected.

⚠️ Discounts increased 31% compared with last Friday.

⚠️ Saturday drink sales are 18% below the previous four Saturdays.
```

MVP uses deterministic rules.

AI-based intelligence is a later phase.

---

# 35. Notifications

Channels:

* In-app
* Push
* Email
* WhatsApp

MVP:

* Low stock
* Shift variance
* Stock variance
* Large discount
* Large void
* Daily summary

---

# 36. WhatsApp Integration

Future-facing capability.

Owner receives:

```text
Daily Business Summary

Sales: ₦2,840,500
Orders: 287
Food: ₦1,120,000
Drinks: ₦1,620,500

Stock variance: ₦61,500
Cash variance: ₦8,000

Attention:
3 high-value voids
2 low-stock products
```

WhatsApp should not become the core POS interface in MVP.

---

# 37. Offline POS Requirements

Offline mode applies primarily to operational POS workflows.

## Cached locally

* Products
* Prices
* Modifiers
* Tables
* Staff session
* Branch configuration
* Tax/service charge configuration
* Active orders
* Pending transactions

## Offline operations

Must support:

* Open table
* Create order
* Modify order
* Send order
* Mark preparation status
* Close order
* Record cash payment
* Queue transactions

---

# 38. Offline Sync

Architecture:

```text
POS
 ↓
Local Database
 ↓
Sync Queue
 ↓
Connectivity Detection
 ↓
Sync Worker
 ↓
API
 ↓
Server Database
```

Each mutation receives a globally unique:

```text
idempotency_key
```

The backend must reject duplicate processing.

---

# 39. Conflict Resolution

Server is authoritative for financial records.

Rules:

### Order

Last valid state transition wins only when compatible.

### Payment

Never silently overwrite.

Conflicts require reconciliation.

### Inventory

Inventory uses immutable movements.

Never directly overwrite stock history.

### Configuration

Server version wins unless explicit conflict resolution exists.

---

# 40. API Architecture

REST API:

```text
/api/v1
```

## Authentication

```text
POST /auth/login
POST /auth/logout
POST /auth/refresh
POST /auth/forgot-password
POST /auth/reset-password
```

## Organizations

```text
GET    /organizations
POST   /organizations
GET    /organizations/{id}
PATCH  /organizations/{id}
```

## Branches

```text
GET    /branches
POST   /branches
GET    /branches/{id}
PATCH  /branches/{id}
```

## Products

```text
GET    /products
POST   /products
GET    /products/{id}
PATCH  /products/{id}
DELETE /products/{id}
```

Deletion should be soft deletion where historical transactions are affected.

## Tables

```text
GET  /tables
POST /tables
PATCH /tables/{id}
POST /tables/{id}/open
POST /tables/{id}/transfer
POST /tables/{id}/merge
```

## Orders

```text
GET  /orders
POST /orders
GET  /orders/{id}
PATCH /orders/{id}
POST /orders/{id}/send
POST /orders/{id}/void
POST /orders/{id}/discount
POST /orders/{id}/close
```

## Payments

```text
POST /payments
GET  /payments/{id}
POST /payments/{id}/confirm
POST /payments/{id}/refund
```

## Inventory

```text
GET  /inventory
POST /inventory/receive
POST /inventory/adjust
POST /inventory/count
POST /inventory/wastage
POST /inventory/transfer
```

## Reports

```text
GET /reports/sales
GET /reports/payments
GET /reports/inventory
GET /reports/staff
GET /reports/variance
GET /reports/products
```

---

# 41. API Standards

All API endpoints must:

* Use versioning.
* Return consistent JSON structures.
* Validate inputs server-side.
* Enforce tenant authorization.
* Enforce branch authorization.
* Use idempotency where appropriate.
* Return meaningful error codes.
* Log sensitive operations.
* Support pagination.
* Support filtering.
* Support sorting.
* Support date ranges.

Example response:

```json
{
  "data": {},
  "meta": {},
  "errors": []
}
```

---

# 42. Database Architecture

Use PostgreSQL.

## Core tables

```text
organizations
branches
users
roles
permissions
role_permissions
user_roles

products
product_categories
product_variants
modifier_groups
modifiers
recipes
recipe_items

floors
tables

orders
order_items
order_item_modifiers
bills
payments
refunds
discounts
voids

kitchen_tickets
kitchen_ticket_items
bar_tickets
bar_ticket_items

inventory_items
inventory_locations
stock_movements
stock_counts
stock_count_items
stock_adjustments
wastage
wastage_items

suppliers
purchase_orders
purchase_order_items
goods_receipts

shifts
cash_sessions

customers
reservations

audit_logs
notifications

subscriptions
plans
subscription_items
invoices
```

---

# 43. Database Rules

### Financial records

Must be immutable wherever possible.

### Inventory

Use movement-based accounting.

### Orders

Never physically delete completed orders.

### Payments

Never physically delete successful payments.

### Audit

Sensitive changes must be traceable.

### Tenant isolation

Every tenant-owned resource must have organization context.

---

# 44. Permission Matrix

| Capability        | Owner | General Manager | Branch Manager |  Cashier | Waiter | Bartender | Kitchen | Inventory |
| ----------------- | ----: | --------------: | -------------: | -------: | -----: | --------: | ------: | --------: |
| View Dashboard    |     ✓ |               ✓ |              ✓ |  Limited |      — |         — |       — |   Limited |
| Create Orders     |     ✓ |               ✓ |              ✓ |        ✓ |      ✓ |         ✓ |       — |         — |
| Edit Orders       |     ✓ |               ✓ |              ✓ |        ✓ |      ✓ |   Limited |       — |         — |
| Void Orders       |     ✓ |               ✓ |              ✓ | Approval |      — |         — |       — |         — |
| Discount          |     ✓ |               ✓ |              ✓ |  Limited |      — |         — |       — |         — |
| Refund            |     ✓ |               ✓ |       Approval |        — |      — |         — |       — |         — |
| Payments          |     ✓ |               ✓ |              ✓ |        ✓ |      — |         — |       — |         — |
| Inventory View    |     ✓ |               ✓ |              ✓ |        — |      — |         ✓ |       — |         ✓ |
| Inventory Adjust  |     ✓ |               ✓ |              ✓ |        — |      — |   Limited |       — |         ✓ |
| Receive Stock     |     ✓ |               ✓ |              ✓ |        — |      — |         — |       — |         ✓ |
| Manage Products   |     ✓ |               ✓ |              ✓ |        — |      — |         — |       — |   Limited |
| Manage Staff      |     ✓ |               ✓ |              ✓ |        — |      — |         — |       — |         — |
| View Reports      |     ✓ |               ✓ |              ✓ |  Limited |      — |         — |       — |   Limited |
| Business Settings |     ✓ |         Limited |        Limited |        — |      — |         — |       — |         — |
| Subscription      |     ✓ |               — |              — |        — |      — |         — |       — |         — |

The permission engine must be more granular than the role labels shown here.

---

# 45. Security Requirements

The system must implement:

* HTTPS
* Secure password hashing
* CSRF protection where applicable
* XSS protection
* SQL injection prevention
* Rate limiting
* Session security
* Authorization middleware
* Tenant isolation
* Secure secrets management
* Encryption for sensitive data
* Audit logs
* Backup strategy
* Database encryption at rest where infrastructure supports it

---

# 46. Non-Functional Requirements

## Performance

Target:

* POS interaction response: <300ms under normal conditions
* API p95: <500ms for common operations
* Dashboard initial load: <2 seconds on reasonable connections
* Real-time order propagation: <2 seconds

Offline POS should respond immediately from local storage.

---

# 47. Availability

Target:

**99.9% monthly uptime**

excluding:

* Planned maintenance
* Third-party provider outages
* Infrastructure incidents outside platform control

POS offline mode reduces operational dependency on uptime.

---

# 48. Scalability

Architecture must support:

* Thousands of organizations
* Multiple branches
* Thousands of concurrent POS users
* Large transaction volumes
* Long-term historical reporting

Initial implementation should remain a modular monolith.

---

# 49. Observability

Implement:

* Application logs
* API logs
* Error tracking
* Performance monitoring
* Queue monitoring
* WebSocket monitoring
* Database monitoring
* Sync failure monitoring

Critical alerts:

* Payment failure spikes
* Sync failures
* Database errors
* Queue backlog
* WebSocket failures

---

# 50. Backup & Disaster Recovery

Database:

* Automated daily backups
* Point-in-time recovery where infrastructure supports it
* Backup retention policy
* Off-site backup
* Restore testing

Target:

**RPO:** ≤15 minutes for production database
**RTO:** ≤2 hours

These targets should be validated against actual infrastructure cost.

---

# 51. Subscription Architecture

Multi-tenant SaaS subscription model.

## Example plans

### Starter

For small restaurants.

Features:

* 1 branch
* Limited users
* POS
* Basic inventory
* Basic reports

### Growth

For restaurants, bars and lounges.

Features:

* More users
* Advanced inventory
* Bar inventory
* Staff management
* Reconciliation
* Advanced reports

### Pro

For larger hospitality businesses.

Features:

* Multiple branches
* Advanced intelligence
* Advanced permissions
* Advanced reporting
* Integrations
* Priority support

### Enterprise

For:

* Hotels
* Chains
* Hospitality groups

Features:

* Custom limits
* Multiple locations
* Custom integrations
* SLA
* Dedicated support

---

# 52. Subscription Enforcement

Subscription limits can apply to:

* Branches
* Users
* POS terminals
* Products
* Inventory locations
* Historical reporting period
* Advanced features

Never block critical access to historical business data because of subscription expiration.

Graceful degradation should be used.

---

# 53. Billing

Subscription architecture should support:

* Monthly billing
* Annual billing
* Trials
* Discounts
* Coupons
* Payment failures
* Grace periods
* Plan upgrades
* Plan downgrades
* Cancellation
* Reactivation

Payment provider integration should be abstracted so the billing engine isn't tightly coupled to one provider.

---

# 54. MVP → V1 → V2 Roadmap

# MVP — Operational Core

### Goal

Make a restaurant/bar able to run daily operations.

### Features

* Organization
* Branches
* Users
* RBAC
* Menu
* Products
* Tables
* POS
* Kitchen
* Bar
* Payments
* Basic inventory
* Bar inventory
* Staff shifts
* Cash reconciliation
* Audit logs
* Dashboard
* Reports
* Offline POS
* Subscription system

### Success Criteria

A business can operate an entire day without needing its old POS or paper order system.

---

# V1 — Business Control

### Add

* Advanced inventory
* Suppliers
* Purchasing
* Stock transfers
* Advanced stock counts
* Customer profiles
* Reservations
* VIP tables
* Minimum spend
* Advanced reporting
* Branch comparison
* WhatsApp summaries
* Notifications
* Advanced discount controls
* Advanced audit tools
* Expense tracking
* Better profitability reporting

### Goal

Move from:

**POS**

to:

**Business Operating System**

---

# V2 — Hospitality Intelligence

### Add

* AI business assistant
* Sales forecasting
* Demand forecasting
* Inventory forecasting
* Automated anomaly detection
* Staff performance intelligence
* Automated purchasing recommendations
* Customer loyalty
* CRM
* Hotel room charges
* Guest folios
* Room service
* Advanced reservations
* Multi-location inventory optimization
* Supplier intelligence

### Goal

Move from:

**Business Operating System**

to:

**Hospitality Intelligence Platform**

---

# 55. Future AI Layer

AI should not be added just because AI is fashionable.

The platform must first collect high-quality operational data.

Once enough data exists, the AI assistant can answer:

> "How did we perform this weekend?"

> "Why did drink profit drop?"

> "Which products are losing money?"

> "Which stock items are being wasted most?"

> "What should we reorder?"

> "Which branch performed best?"

> "Why is this month's revenue lower?"

The AI should answer from the platform's actual business data.

---

# 56. Build Sequence

## Phase 0 — Foundation

### Week 1

* Repository
* Architecture
* Docker
* PostgreSQL
* Laravel
* Next.js
* Authentication
* CI/CD
* Environment management
* Base UI system
* Multi-tenancy foundation

---

## Phase 1 — Identity & Business

### Week 2

Build:

* Organizations
* Branches
* Users
* Roles
* Permissions
* Settings

---

## Phase 2 — Catalog

### Week 3

Build:

* Categories
* Products
* Variants
* Modifiers
* Recipes
* Pricing

---

## Phase 3 — Tables & POS

### Weeks 4–5

Build:

* Floors
* Tables
* Orders
* Order items
* Modifiers
* Table transfers
* Bill splitting
* Discounts
* Voids

This is the first major milestone.

---

## Phase 4 — Kitchen & Bar

### Week 6

Build:

* Kitchen tickets
* Bar tickets
* Real-time updates
* Ticket statuses
* Preparation workflow

---

## Phase 5 — Payments

### Week 7

Build:

* Cash
* Transfer
* POS
* Card
* Paystack integration
* Flutterwave integration
* Split payments
* Refunds
* Payment verification

---

## Phase 6 — Inventory

### Weeks 8–9

Build:

* Inventory items
* Stock movements
* Recipes
* Consumption
* Purchases
* Receiving
* Wastage
* Stock counts
* Variance

This is another major milestone.

---

## Phase 7 — Staff & Reconciliation

### Week 10

Build:

* Shifts
* Cash sessions
* Opening cash
* Closing cash
* Reconciliation
* Variances
* Approvals

---

## Phase 8 — Dashboard & Reporting

### Week 11

Build:

* Sales dashboard
* Payment reports
* Product reports
* Inventory reports
* Staff reports
* Variance reports
* Daily closing report

---

## Phase 9 — Offline POS

### Week 12

Build:

* Local database
* Offline detection
* Transaction queue
* Sync engine
* Idempotency
* Conflict handling
* Retry mechanism

This should be treated as a serious engineering milestone, not a UI feature.

---

## Phase 10 — Hardening

### Weeks 13–14

* Security testing
* Permission testing
* Tenant isolation testing
* Load testing
* Offline testing
* Payment failure testing
* Backup restoration testing
* Audit testing
* Browser/device testing
* POS hardware testing

---

# 57. MVP Acceptance Test

A real business should be able to perform:

```text
Create Business
       ↓
Create Branch
       ↓
Create Staff
       ↓
Create Menu
       ↓
Create Tables
       ↓
Open Shift
       ↓
Open Table
       ↓
Take Order
       ↓
Send Food to Kitchen
       ↓
Send Drinks to Bar
       ↓
Prepare
       ↓
Serve
       ↓
Request Bill
       ↓
Split Payment
       ↓
Close Order
       ↓
Update Inventory
       ↓
Close Shift
       ↓
Count Stock
       ↓
Reconcile
       ↓
Generate Daily Report
```

If that workflow works reliably, the MVP is operationally viable.

---

# 58. Critical Product Metrics

## Activation

Percentage of businesses that:

* Complete onboarding
* Add products
* Create tables
* Create staff
* Process first order

## Time to First Value

Time from signup to first completed transaction.

Target:

**<30 minutes for a prepared business**

---

## Daily Active Businesses

Businesses processing at least one transaction per day.

---

## Transaction Volume

Total:

* Orders
* Sales
* Payments

processed through the platform.

---

## Retention

Measure:

* 7-day
* 30-day
* 90-day

business retention.

---

## Operational Reliability

Track:

* Failed transactions
* Sync failures
* Payment failures
* Duplicate transactions
* Offline transaction failures

---

# 59. Product Success Definition

The product succeeds when an owner can say:

> **"I don't need to call the manager every night to know what happened."**

And a waiter can say:

> **"I don't need to understand the system. I just know how to take an order."**

And a manager can say:

> **"I know exactly where the money and stock went."**

That is the actual product outcome.

---

# 60. Final Product Definition

## Category

**Hospitality Operations & Intelligence SaaS**

## Primary Market

**Nigeria**

## Initial Verticals

**Restaurants, bars, lounges and clubs**

## Expansion

**Hotels and multi-location hospitality groups**

## Core Product

```text
POS
+
Kitchen
+
Bar
+
Payments
+
Inventory
+
Staff
+
Reconciliation
+
Business Intelligence
```

## Core Differentiator

**Revenue and stock accountability.**

## Core User Promise

### Staff

**Sell faster.**

### Managers

**Control operations.**

### Owners

**Know what's really happening.**

## Product Philosophy

> **Simple on the surface. Powerful underneath.**

## Long-Term Vision

The platform should eventually become the **operating system and intelligence layer for Nigerian hospitality businesses** — from the first order of the day to the final reconciliation at closing.

---
