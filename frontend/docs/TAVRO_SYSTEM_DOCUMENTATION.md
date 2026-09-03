# Tavro — Complete System Documentation

> **Version:** 1.0.0  
> **Last Updated:** August 26, 2026  
> **Platform:** Laravel 12 (PHP 8.2) + Next.js 16 (React 19)  
> **Target Market:** African restaurant & hospitality businesses

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Architecture](#2-architecture)
3. [Frontend](#3-frontend)
4. [Backend API](#4-backend-api)
5. [Authentication & Security](#5-authentication--security)
6. [Multi-Tenancy](#6-multi-tenancy)
7. [Roles & Permissions](#7-roles--permissions)
8. [Core Features](#8-core-features)
9. [Payment System](#9-payment-system)
10. [Real-Time & Offline](#10-real-time--offline)
11. [Security Hardening](#11-security-hardening)
12. [Legal & Compliance Pages](#12-legal--compliance-pages)
13. [Deployment & Configuration](#13-deployment--configuration)
14. [Testing](#14-testing)
15. [Database Schema](#15-database-schema)
16. [API Reference](#16-api-reference)

---

## 1. System Overview

Tavro is a cloud-based restaurant management platform providing:

| Module | Description |
|---|---|
| **Point of Sale (POS)** | Tablet-optimized order entry with split payments, modifiers, and offline capability |
| **Kitchen Display System (KDS)** | Real-time order tickets for kitchen staff with status tracking |
| **Bar Display System (BDS)** | Separate display for bar/drink orders with bottle tracking |
| **Inventory Management** | Stock levels, wastage tracking, purchase orders, supplier management |
| **Floor Plan** | Visual table management with drag-and-drop positioning |
| **Staff Management** | Role-based access, shift tracking, performance reports |
| **Financial Reporting** | Sales reports, payment breakdowns, dashboard analytics |
| **Subscription Billing** | Tiered SaaS billing via Paystack integration |

**Key Statistics:**
- 28 controllers, 26 models, 43 migrations
- 112+ API routes (all prefixed with `api/v1`)
- 26 granular permissions across 8 roles
- 10 rate limiters
- 3 real-time broadcast channels
- 6 legal/compliance pages

---

## 2. Architecture

### High-Level

```
┌──────────────────────────────────────────────────────────────┐
│                     CLIENT LAYER                             │
│  Next.js 16 (React 19) — SSR + Client Components            │
│  Zustand (state) · TanStack Query (data) · Axios (HTTP)     │
│  Dexie/IndexedDB (offline) · Framer Motion (animations)     │
└──────────────────────┬───────────────────────────────────────┘
                       │ REST API (api/v1)
                       │ WebSocket (Reverb :8080)
┌──────────────────────┴───────────────────────────────────────┐
│                     SERVER LAYER                             │
│  Laravel 12 (PHP 8.2)                                       │
│  Sanctum (auth) · Spatie RBAC · Reverb (WS)                 │
│  Paystack + Flutterwave (payments)                          │
│  PostgreSQL 15 · Redis · Sentry                             │
└──────────────────────────────────────────────────────────────┘
```

### Backend Structure

```
backend/
├── app/
│   ├── Console/              # Scheduled tasks (none active)
│   ├── Exceptions/           # InsufficientStockException
│   ├── Events/               # KitchenTicketUpdated, BarTicketUpdated, TableStatusUpdated
│   ├── Http/
│   │   ├── Controllers/      # 28 controllers (see §4)
│   │   └── Middleware/       # 7 middleware (see §11)
│   ├── Models/               # 26 Eloquent models (see §15)
│   ├── Notifications/        # SystemAlert (database channel)
│   ├── Observers/            # (none active)
│   ├── Policies/             # (none active)
│   ├── Scopes/               # TenantScope (global)
│   ├── Services/             # InventoryService, AuditLogger
│   └── Traits/               # HasTenant
├── config/                   # sanctum, permission, reverb, sentry, services
├── database/
│   ├── factories/            # UserFactory, OrganizationFactory
│   ├── migrations/           # 43 migration files
│   └── seeders/              # DatabaseSeeder, RolesAndPermissionsSeeder, PlanSeeder, DemoSeeder
├── routes/
│   ├── api.php               # All API routes
│   ├── channels.php          # Broadcast channel definitions
│   └── web.php               # Redirects to frontend
├── tests/                    # Feature tests (tenant isolation, subscription, idempotency)
└── bootstrap/app.php         # Route prefix, middleware stack, exception handling
```

### Frontend Structure

```
frontend/src/
├── app/
│   ├── (auth)/               # login, forgot-password, reset-password
│   ├── (dashboard)/          # All authenticated app pages
│   │   ├── dashboard/        # Owner & Manager dashboards
│   │   ├── pos/              # POS (tableId > payment > order)
│   │   ├── orders/           # Order history
│   │   ├── floorplan/        # Visual floor plan editor
│   │   ├── kitchen/          # Kitchen Display System
│   │   ├── bar/              # Bar Display System
│   │   ├── bar-inventory/    # Open bottle tracking
│   │   ├── menu/             # Menu management + product detail
│   │   ├── inventory/        # Stock, counts, purchase orders, wastage
│   │   ├── shifts/           # Shift management
│   │   ├── onboarding/       # New org setup wizard
│   │   └── settings/         # Team, branches, billing, audit logs
│   ├── (legal)/              # Privacy, Terms, Compliance, IP, AUP, Cookies
│   ├── layout.tsx            # Root layout (fonts, providers)
│   └── page.tsx              # Landing page
├── components/
│   ├── ui/                   # 12 reusable UI components
│   │   ├── Badge.tsx, Button.tsx, Card.tsx, drawer.tsx
│   │   ├── EmptyState.tsx, Input.tsx, Modal.tsx
│   │   ├── MonoNumber.tsx, Progress.tsx, Skeleton.tsx
│   │   ├── Table.tsx, Toast.tsx
│   └── layout/               # MobileNav.tsx, BranchSwitcher.tsx
├── lib/
│   ├── axios.ts              # Axios instance + interceptor (token injection, 419 handling)
│   ├── db.ts                 # Dexie IndexedDB setup
│   ├── echo.ts               # Laravel Echo (Reverb) client
│   ├── haptics.ts            # Haptic feedback utilities
│   ├── sanitize.ts           # Input sanitization functions
│   ├── syncEngine.ts         # Offline sync queue
│   └── utils.ts              # General utilities
└── stores/                   # Zustand stores (useAuthStore, etc.)
```

---

## 3. Frontend

### Tech Stack

| Layer | Technology |
|---|---|
| Framework | Next.js 16 (App Router) |
| UI Library | React 19 |
| Language | TypeScript |
| Styling | Tailwind CSS v4 |
| State | Zustand |
| Data Fetching | TanStack React Query + Axios |
| Real-Time | Laravel Echo (Reverb WebSocket) |
| Offline | Dexie (IndexedDB) |
| Animations | Framer Motion |
| Icons | Lucide React |
| Fonts | Geist Sans + Geist Mono |

### Design System

**Color Palette:**
- `charcoal` scale: 50–950 (neutral grays)
- `amber` scale: 50–950 (brand accent)
- Dark backgrounds: `charcoal-900` / `charcoal-950`
- Light backgrounds: `charcoal-50` / white
- Accent: `amber-500` / `amber-600`

**Component Patterns:**
- Cards: `rounded-2xl` with `border-charcoal-100`
- Buttons: `rounded-full`, variant-aware (primary/secondary/danger/outline)
- Containers: `max-w-6xl mx-auto px-6`
- Typography: `font-display` for headings, `font-sans` for body

**12 UI Components:**
Badge, Button, Card, Drawer, EmptyState, Input, Modal, MonoNumber, Progress, Skeleton, Table, Toast

### Page Routes

| Route | Page | Access |
|---|---|---|
| `/` | Landing page | Public |
| `/login` | Login | Public |
| `/forgot-password` | Forgot password | Public |
| `/reset-password` | Reset password | Public (token) |
| `/privacy` | Privacy Policy | Public |
| `/terms` | Terms of Use | Public |
| `/compliance` | Data & Compliance | Public |
| `/ip-infringement` | IP Infringement | Public |
| `/acceptable-use` | Acceptable Use | Public |
| `/cookies` | Cookie Policy | Public |
| `/dashboard` | Owner/Manager dashboard | Authenticated |
| `/pos` | POS floor view | Authenticated |
| `/pos/[tableId]` | Table order screen | Authenticated |
| `/pos/[tableId]/payment/[orderId]` | Payment screen | Authenticated |
| `/orders` | Order history | Authenticated |
| `/floorplan` | Floor plan editor | Authenticated |
| `/kitchen` | Kitchen Display | Authenticated |
| `/bar` | Bar Display | Authenticated |
| `/bar-inventory` | Open bottle tracking | Authenticated |
| `/menu` | Menu management | Authenticated |
| `/menu/products/[id]` | Product detail | Authenticated |
| `/inventory` | Stock levels | Authenticated |
| `/inventory/count` | Stock count sessions | Authenticated |
| `/inventory/purchase-orders` | Purchase orders | Authenticated |
| `/inventory/wastage` | Wastage log | Authenticated |
| `/shifts` | Shift management | Authenticated |
| `/onboarding` | New org setup | Authenticated |
| `/settings/team` | Staff management | Authenticated |
| `/settings/branches` | Branch management | Authenticated |
| `/settings/billing` | Subscription billing | Authenticated |
| `/settings/audit` | Audit log viewer | Authenticated |

---

## 4. Backend API

### Controllers

| Controller | Responsibility |
|---|---|
| `AuthController` | Login, logout, sessions, password reset, email verification |
| `OrganizationController` | Org CRUD + onboarding (creates org + 14-day Pro trial) |
| `BranchController` | Branch CRUD (timezone, operating hours) |
| `UserController` | User CRUD, role assignment, branch sync |
| `ProductController` | Product CRUD, image upload, availability toggle |
| `ProductVariantController` | Bulk variant create/delete |
| `CategoryController` | Menu category CRUD + reorder |
| `ModifierGroupController` | Modifier group + modifier CRUD |
| `RecipeController` | Versioned recipe CRUD |
| `OrderController` | Full order lifecycle (open → items → send → close → void) |
| `PaymentController` | Idempotent payment with ledger |
| `RefundController` | Full-amount refund (manager+) |
| `TableController` | Table CRUD, status updates, real-time broadcast |
| `FloorController` | Floor CRUD with eager-loaded tables |
| `KitchenController` | KDS tickets + status updates |
| `BarController` | BDS tickets + status updates |
| `BarInventoryController` | Open bottle tracking |
| `InventoryItemController` | Stock CRUD, receive, adjust, wastage |
| `SupplierController` | Supplier CRUD |
| `PurchaseOrderController` | PO create + receive (updates stock) |
| `StockCountSessionController` | Stock count workflow (start → count → submit → approve) |
| `ShiftController` | Shift lifecycle (open → prepare-close → close → approve variance) |
| `DashboardController` | Owner BI dashboard + Manager ops dashboard |
| `ReportController` | Sales, payments, staff reports |
| `SubscriptionController` | Plan listing, subscribe (Paystack), cancel |
| `NotificationController` | Notification inbox (list, read, unread count) |
| `AuditLogController` | Filterable audit log viewer |
| `WebhookController` | Paystack + Flutterwave webhook handlers |

---

## 5. Authentication & Security

### Login Flow

```
POST /api/v1/auth/login
  ↓ validate email + password
  ↓ Auth::attempt()
  ↓ check status === 'active' (403 if deactivated)
  ↓ check hasVerifiedEmail() (403 if unverified)
  ↓ createToken('auth_token') → prefix tav_
  ↓ return { token, user }
```

**Token Configuration:**
- Prefix: `tav_`
- Expiry: 1440 minutes (24 hours)
- Stateful domains: localhost:3000

**Password Reset:**
- Rate: 3 requests/hour per email
- Token expiry: 15 minutes
- Complexity: min 8, upper + lower + digit + symbol

**Email Verification:**
- Rate: 3 resend attempts/hour
- Standard `MustVerifyEmail` flow

**Brute Force Protection:**
- 5 failures → throttle to 1/15min
- 20+ failures → critical security alert

### Session Management

```
GET    /auth/sessions              → list all active tokens
DELETE /auth/sessions/{tokenId}    → revoke specific session
```

### Request Signing (HMAC-SHA256)

Every **mutating** API call (`POST` / `PUT` / `PATCH` / `DELETE`) must be signed. A single replayable, tamperable request is impossible: the signature binds the body, URL, a timestamp and a unique nonce to the caller's credential. `GET / HEAD / OPTIONS` pass through unsigned.

**Headers (required on every mutation):**

| Header | Meaning |
|---|---|
| `X-Timestamp` | Unix seconds at signing time (freshness window ±300s) |
| `X-Nonce` | UUID — used once. Replays are rejected (`REPLAYED_NONCE`) |
| `X-Signature` | `hex(HMAC-SHA256(secret, canonical))` |
| `X-Api-Key` | Only for API-key (gateway) clients |

**Canonical string (byte-for-byte, client and server):**

```
METHOD\n{fullPath}\n{sha256hex(rawBody)}\n{timestamp}\n{nonce}
```

Example: `POST\n/api/v1/orders\ne3b0c44298fc…2b855\n1787833440\n8f14e45f-…`

**Credentials:**
- First-party web (Bearer token): the login response returns a `signing_secret` (64 hex chars) bound to that Sanctum token. It is issued once, stored encrypted server-side, and dies with the token on logout. The POS frontend signs automatically in `frontend/src/lib/axios.ts`.
- API keys (gateway): `POST /api/v1/api-keys` returns a `signing_secret` alongside the raw key, once at creation.

**Failure codes (401):** `MISSING_SIGNATURE`, `INVALID_TIMESTAMP`, `STALE_TIMESTAMP`, `SIGNING_SECRET_MISSING`, `REPLAYED_NONCE`, `INVALID_SIGNATURE`.

**Nonces** are held in Redis for `window + 60s` via atomic `SET NX EX`, so a captured request can never be replayed — even within the freshness window.

---

## 6. Multi-Tenancy

### Isolation Model

Every data query is automatically scoped to the authenticated user's organization via:

1. **`HasTenant` trait** — applied to 15+ models, adds `TenantScope` global scope
2. **`TenantScope`** — `where('organization_id', auth()->user()->organization_id)`
3. **`TenantScopeMiddleware`** — binds `current_org_id` to the service container
4. **Manual checks** — every controller validates `branch_id` matches user's assignment

### Tenant-Scoped Models

Organization, Branch, Product, ProductVariant, Category, ModifierGroup, Order, OrderItem, Table, Floor, InventoryItem, InventoryTransaction, Supplier, PurchaseOrder, StockCountSession

### Branch Scoping

Operations are further scoped to the user's active branch:
- Orders, Payments, Inventory, Shifts, Tables, Kitchen/Bar tickets

---

## 7. Roles & Permissions

### Roles (8)

| Role | Access Level |
|---|---|
| `owner` | Full access — all 26 permissions |
| `general_manager` | Everything except settings.manage, branches.manage |
| `branch_manager` | Orders, payments, inventory, stock, limited reports, staff |
| `cashier` | Orders, payments, menu view, inventory view, sales report |
| `waiter` | Orders (view/create/edit), menu view |
| `bartender` | Orders (view/create/edit), menu view, inventory view |
| `kitchen_staff` | Orders view, menu view |
| `inventory_manager` | Menu manage, inventory (full), stock (purchase/transfer), inventory report |

### Permissions (26)

| Domain | Permissions |
|---|---|
| Orders | `orders.view`, `orders.create`, `orders.edit`, `orders.void`, `orders.discount` |
| Payments | `payments.create`, `payments.refund`, `payments.view` |
| Menu | `menu.view`, `menu.manage` |
| Inventory | `inventory.view`, `inventory.manage`, `inventory.adjustments` |
| Stock | `stock.purchase`, `stock.transfer` |
| Reports | `reports.sales`, `reports.financial`, `reports.inventory`, `reports.staff` |
| Staff | `staff.view`, `staff.manage` |
| Settings | `settings.view`, `settings.manage` |
| Branches | `branches.view`, `branches.manage` |

### Enforced Endpoints

| Endpoint | Required Role |
|---|---|
| `PATCH /users/{user}/role` | owner |
| `POST /payments/{payment}/refund` | owner, general_manager, branch_manager |
| `POST /shifts/{shift}/approve-variance` | owner, general_manager, branch_manager |
| `POST /inventory/counts/{session}/approve` | owner, general_manager, branch_manager |

---

## 8. Core Features

### 8.1 Point of Sale (POS)

**Order Lifecycle:**
```
OPEN → (add items) → SENT → (kitchen preparing) → (all ready) → PAID → CLOSED
                          ↓
                        VOIDED (with reason)
```

**Key Features:**
- Table assignment with visual floor plan
- Cover count tracking
- Order items with modifiers and special instructions
- Void individual items (with reason)
- Apply flat or percentage discounts (with approval)
- Split payments across multiple methods
- Offline order queuing with sync

**Subscription Gate:** All mutating order routes require an active subscription (402 if expired).

### 8.2 Kitchen Display System (KDS)

- Filtered view: only food-type items in `SENT` or `PREPARING` status
- Per-item status updates: SENT → PREPARING → READY
- Bulk order status updates
- Real-time WebSocket broadcasting on `branch.{id}.kitchen` channel

### 8.3 Bar Display System (BDS)

- Filtered view: drink/cocktail/bottle/shot items
- Per-item and bulk status updates
- Open bottle tracking (deducts 1 unit from inventory)
- Real-time WebSocket broadcasting on `branch.{id}.bar` channel

### 8.4 Menu Management

- **Categories** with custom sort order (drag-and-drop reorder)
- **Products** with image upload, pricing, tax flags, service charge flags
- **Product Variants** (e.g., sizes, preparations) — bulk create/replace
- **Modifier Groups** (e.g., toppings, add-ons) with min/max selection rules
- **Recipes** — versioned ingredient lists linking products to inventory items

### 8.5 Inventory Management

- Branch-scoped stock levels with unit of measure
- **Receive Stock** — update quantity and cost
- **Manual Adjustments** — with notes and high-value approval
- **Wastage Logging** — with approval threshold
- **Purchase Orders** — create POs, receive against POs (auto-updates stock)
- **Stock Counts** — full, category, bar, or individual item counts with manager approval
- **Bar Inventory** — open bottle tracking per category
- **Negative Stock Guard** — throws `InsufficientStockException` (422) preventing stock below zero

### 8.6 Shift Management

- Open a new shift (auto-associates with branch)
- **Prepare Close** — calculates expected cash (cash sales - cash refunds)
- **Close Shift** — records actual cash and variance
- **Approve Variance** — manager/owner approval required for variances

### 8.7 Dashboard & Reports

**Owner Dashboard:**
- Hero metrics (revenue, orders, avg ticket, profit margin)
- 7-day revenue sparkline
- Payment method breakdown
- Top products
- Deterministic BI alerts

**Manager Dashboard:**
- Live operational metrics
- Active shift summary
- Low stock alerts

**Reports:**
- Sales report (date range, daily breakdown)
- Payment method breakdown
- Staff performance report

---

## 9. Payment System

### Architecture

```
Client → X-Idempotency-Key (UUID) → Server
  ↓
Check ledger for duplicate → return cached result if found
  ↓
Create Payment row (PENDING) + write INTENT to ledger
  ↓
[Immediate: CASH/POS] → COMPLETED + ledger entry
[Gateway: CARD/PAYSTACK/FLUTTERWAVE] → PENDING → await webhook
  ↓
Webhook → VerifyWebhook (URL token + IP allowlist + signature) → atomic claim → append ledger → update payment
  ↓
If fully paid → mark order PAID
```

### Payment Methods

| Method | Flow | Confirmation |
|---|---|---|
| `CASH` | Immediate completion | Manual |
| `POS` | Immediate completion | Manual |
| `TRANSFER` | PENDING → manual confirm | Manager confirms |
| `CARD` | PENDING → gateway webhook | Automated |
| `PAYSTACK` | PENDING → Paystack webhook | Automated |
| `FLUTTERWAVE` | PENDING → Flutterwave webhook | Automated |

### Key Design Principles

1. **Client-generated idempotency keys** — UUIDs sent via `X-Idempotency-Key` header
2. **Intent-first ledger** — INTENT entry written BEFORE any external API call
3. **Immutable ledger** — every state transition is a new row (never UPDATE)
4. **Event ID idempotency** — atomic first-insert wins on composite unique `(provider, event_id)`; replays/retries reconciled by a RECEIVED → PROCESSED / DUPLICATE / FAILED state machine with `attempts`/`payload_hash`/`received_ip` tracking (see §17 Webhook Security)
5. **Row-level locking** — `lockForUpdate()` prevents TOCTOU race conditions
6. **Integer math** — currency compared in kobo (×100) to avoid float precision issues

### Gateway Integration

**Paystack:**
- Signature: `x-paystack-signature` = HMAC-SHA512(raw body, `PAYSTACK_SECRET_KEY`) — verified centrally in `VerifyWebhook`
- Transaction verification for subscription payments
- Webhook events: `charge.success`, `charge.failed`

**Flutterwave:**
- Signature: `verif-hash` header must equal `FLUTTERWAVE_SECRET_HASH` — verified centrally in `VerifyWebhook`
- Webhook events: `charge.completed`, `charge.failed`

---

## 10. Real-Time & Offline

### WebSocket Channels (Reverb)

| Channel | Access | Events |
|---|---|---|
| `App.Models.User.{id}` | Private (self only) | User-specific notifications |
| `branch.{id}.tables` | Private (branch members) | `TableStatusUpdated` |
| `branch.{id}.kitchen` | Private (branch members) | `KitchenTicketUpdated` |
| `branch.{id}.bar` | Private (branch members) | `BarTicketUpdated` |

**WebSocket Server:** Port 8080 (configurable), 60 req/min rate limit

### Offline Mode

- **Dexie/IndexedDB** stores pending orders, cart data, and transaction records
- **Sync Engine** replays queued operations with stable idempotency keys
- **Backend deduplication** ensures no duplicate records from replayed operations
- **OfflineBanner** component shows connection status

---

## 11. Security Hardening

### Middleware Stack

| Middleware | Type | Purpose |
|---|---|---|
| `SecurityHeaders` | Global | CSP, HSTS, X-Frame-Options DENY, Permissions-Policy, strips Server header |
| `ForceHttps` | Global | HTTP → HTTPS redirect in production |
| `TenantScopeMiddleware` | API | Binds `current_org_id` to container |
| `DetectSuspiciousTraffic` | API | Blocks 55+ attack User-Agents, scanner paths, rate limits per IP |
| `EnsureUserIsActive` | API | Blocks deactivated users |
| `CheckSubscription` | API | Blocks order mutations if subscription inactive |
| `TrackLoginFailures` | Login | Cache-based failure counting, alerts at 20+ |
| `VerifyRequestSignature` | API | HMAC signature check on every mutating call (see §5) |
| `NegotiateApiVersion` | API | Enforces path version + negotiation; adds `Sunset`/`Warning` headers (see §17) |

### Attack Detection

- **Known tool blocking:** 55+ User-Agent patterns (sqlmap, nikto, dirsearch, etc.)
- **Path scanning:** Blocks .env, wp-admin, phpmyadmin, .git, etc.
- **IP rate limiting:** 120 req/min per IP, progressive blocking
- **404 scanner detection:** 30+ 404s from one IP → critical alert

### Rate Limiters (10)

| Limiter | Limit | Window |
|---|---|---|
| `login` | 5 → 1 | 5min → 15min after failures |
| `password.reset` | 3 | per hour |
| `api` | 120 | per minute per user |
| `organizations` | 3 | per hour per IP |
| `users.create` | 10 | per hour per org |
| `subscriptions` | 10 | per hour per org |
| `webhooks` | 30 | per minute per IP |
| `heavy` | 20 | per minute per user |
| `verification` | 3 | per hour per user |
| `strict` | 30 | per minute per IP |

### Data Protection

- TLS 1.3 in transit, AES-256 at rest
- Role-based access control on every endpoint
- Audit logging on all state changes
- Payment data tokenised (never stored on our servers)
- NDPR/NDPA compliant data processing

---

## 12. Legal & Compliance Pages

Six public legal pages accessible from the landing page footer:

| Page | Route | Content |
|---|---|---|
| **Privacy Policy** | `/privacy` | Data collection, usage, sharing, retention, NDPR rights, DPO contact |
| **Terms of Use** | `/terms` | Account terms, subscriptions, IP, liability, dispute resolution, Nigerian law |
| **Data & Compliance** | `/compliance` | NDPR/NDPA, PCI DSS, financial records, ISMS, employee data, cross-border transfers |
| **IP Infringement** | `/ip-infringement` | Tavro IP rights, reporting process, counter-notices, repeat infringers |
| **Acceptable Use** | `/acceptable-use` | Permitted use, prohibited activities, content standards, enforcement |
| **Cookie Policy** | `/cookies` | Essential/functional/analytics cookies, local storage, IndexedDB, third-party |

**Layout:** Shared `(legal)` layout with sidebar navigation (desktop), horizontal scroll tabs (mobile), and footer with all legal links.

**Contact Emails:**
- `privacy@tavro.ng` — Privacy matters
- `dpo@tavro.ng` — Data Protection Officer
- `legal@tavro.ng` — Legal / Terms
- `ip@tavro.ng` — IP infringement reports
- `abuse@tavro.ng` — AUP violations
- `security@tavro.ng` — Security concerns

---

## 13. Deployment & Configuration

### Environment Variables

**Backend (`.env`):**
```
APP_NAME=Tavro
APP_ENV=production
APP_KEY=CHANGE_ME
APP_URL=https://api.tavro.ng

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tavro
DB_USERNAME=CHANGE_ME
DB_PASSWORD=CHANGE_ME

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

SANCTUM_STATEFUL_DOMAINS=tavro.ng,localhost:3000
FRONTEND_URL=https://tavro.ng

PAYSTACK_PUBLIC_KEY=CHANGE_ME
PAYSTACK_SECRET_KEY=CHANGE_ME
FLUTTERWAVE_PUBLIC_KEY=CHANGE_ME
FLUTTERWAVE_SECRET_KEY=CHANGE_ME
FLUTTERWAVE_SECRET_HASH=CHANGE_ME

REVERB_APP_KEY=CHANGE_ME
REVERB_APP_SECRET=CHANGE_ME
REVERB_HOST=wss://reverb.tavro.ng

SENTRY_DSN=CHANGE_ME
```

**Frontend (`.env.local`):**
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
NEXT_PUBLIC_REVERB_APP_KEY=CHANGE_ME
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
```

### Starting Services

```bash
# Backend
cd backend
php artisan serve --port=8000

# Frontend
cd frontend
npm run dev

# Reverb WebSocket Server
php artisan reverb:start --port=8080
```

### Subscription Plans

| Plan | Monthly (₦) | Yearly (₦) | Branches | Users | Terminals |
|---|---|---|---|---|---|
| Starter | 15,000 | 150,000 | 1 | 3 | 1 |
| Growth | 35,000 | 350,000 | 3 | 10 | 5 |
| Pro | 75,000 | 750,000 | 10 | 50 | 20 |
| Enterprise | 150,000 | 1,500,000 | Unlimited | Unlimited | Unlimited |

---

## 14. Testing

### Feature Tests

| Test | File | Purpose |
|---|---|---|
| `TenantIsolationTest` | `tests/Feature/TenantIsolationTest.php` | Verifies cross-org data isolation |
| `SubscriptionBlockTest` | `tests/Feature/SubscriptionBlockTest.php` | Verifies subscription gate on order mutations |
| `OfflineSyncIdempotencyTest` | `tests/Feature/OfflineSyncIdempotencyTest.php` | Verifies idempotent order creation |

### Running Tests

```bash
cd backend
php artisan test
```

---

## 15. Database Schema

### Core Tables (43 migrations)

**Organisation & Auth:**
`organizations`, `branches`, `branch_user`, `users`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `personal_access_tokens`

**Menu & Catalog:**
`categories`, `products`, `product_variants`, `modifier_groups`, `modifiers`, `recipes`, `recipe_items`

**Floor & Tables:**
`floors`, `tables`

**Orders & Payments:**
`orders`, `order_items`, `payments`, `payment_ledger`, `refunds`, `webhook_events`

**Inventory:**
`inventory_items`, `inventory_transactions`, `open_bottles`, `suppliers`, `purchase_orders`, `purchase_order_items`, `stock_count_sessions`, `stock_count_entries`, `wastage_entries`

**Operations:**
`shifts`, `audit_logs`, `notifications`

**Billing:**
`plans`, `subscriptions`

**System:**
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`

### Key Relationships

```
Organization ─┬── Branch ─┬── User (belongsToMany)
              │           ├── Floor ──── Table
              │           ├── Order ─┬── OrderItem
              │           │         ├── Payment ─┬── Refund
              │           │         │             └── PaymentLedger (append-only)
              │           │         └── Shift
              │           ├── InventoryItem ─┬── InventoryTransaction
              │           │                 └── OpenBottle
              │           ├── Supplier ──── PurchaseOrder
              │           └── StockCountSession ──── StockCountEntry
              └── User
              └── Subscription ──── Plan
```

---

## 16. API Reference

### Authentication

```
POST   /api/v1/auth/login                    → { token, user }
POST   /api/v1/auth/logout                   → 200
GET    /api/v1/auth/me                       → { user with roles }
GET    /api/v1/auth/sessions                 → [tokens]
DELETE /api/v1/auth/sessions/{tokenId}       → 200
POST   /api/v1/auth/forgot-password          → 200
POST   /api/v1/auth/reset-password           → 200
POST   /api/v1/auth/email/verification-notification → 200
GET    /api/v1/auth/email/verify/{id}/{hash} → redirect
```

### Orders

```
GET    /api/v1/orders                        → [orders]
GET    /api/v1/orders/{order}                → { order with items }
POST   /api/v1/orders                        → { order } (subscription required)
POST   /api/v1/orders/{order}/items          → { item }
PATCH  /api/v1/orders/{order}/items/{item}   → { item }
POST   /api/v1/orders/{order}/items/{item}/void → 200
POST   /api/v1/orders/{order}/send           → 200 (deducts inventory)
POST   /api/v1/orders/{order}/void           → 200
POST   /api/v1/orders/{order}/discount       → { order }
POST   /api/v1/orders/{order}/close          → 200
```

### Payments

```
GET    /api/v1/orders/{order}/payments       → { payments, total, paid, is_fully_paid }
POST   /api/v1/orders/{order}/payments       → { payment } (X-Idempotency-Key required)
POST   /api/v1/payments/{payment}/confirm    → { payment }
POST   /api/v1/payments/{payment}/refund     → { refund }
```

### Menu

```
GET/POST          /api/v1/categories
POST              /api/v1/categories/reorder
PATCH/DELETE      /api/v1/categories/{category}
GET/POST          /api/v1/products
GET/PATCH/DELETE  /api/v1/products/{product}
PATCH             /api/v1/products/{product}/availability
POST/DELETE       /api/v1/products/{product}/variants
GET/POST          /api/v1/products/{product}/recipe
GET/POST/PATCH/DEL /api/v1/modifier-groups
```

### Inventory

```
GET/POST          /api/v1/inventory
PUT               /api/v1/inventory/{item}
POST              /api/v1/inventory/receive
POST              /api/v1/inventory/adjust
POST              /api/v1/inventory/wastage
GET/POST          /api/v1/suppliers
GET/POST          /api/v1/purchase-orders
POST              /api/v1/purchase-orders/{po}/receive
GET/POST          /api/v1/inventory/counts
PATCH             /api/v1/inventory/counts/{session}/entries
POST              /api/v1/inventory/counts/{session}/submit
POST              /api/v1/inventory/counts/{session}/approve
```

### Webhooks (unauthenticated, hardened)

Webhooks are outside auth but protected by `VerifyWebhook` middleware on top of
the `webhooks` rate limit. Every delivery is checked for: (1) URL token,
(2) IP allowlist when configured, (3) provider signature.

```
POST   /api/v1/webhooks/paystack/{token}     → 200 (status: success | duplicate)
POST   /api/v1/webhooks/flutterwave/{token}  → 200
```

| Response | Meaning |
|---|---|
| `200 success` | Event claimed and processed |
| `200 duplicate` | Replay / concurrent duplicate of a known event (idempotent) |
| `400` | Invalid/missing provider signature |
| `403` | Caller IP not on the allowlist |
| `404` | Wrong URL token (kept ambiguous) |
| `503` | Webhook endpoint not configured (token/secret missing) |

Generate tokens: `php artisan webhook:token gen paystack|flutterwave` → set
`PAYSTACK_WEBHOOK_TOKEN` / `FLUTTERWAVE_WEBHOOK_TOKEN` (and optionally
`PAYSTACK_ALLOWED_IPS` / `FLUTTERWAVE_ALLOWED_IPS` as comma-separated IPs/CIDRs)
in `.env`, then register `https://<host>/api/v1/webhooks/{provider}/{token}` in
the provider dashboard.

### Error Responses

| Code | Meaning |
|---|---|
| 400 | Bad request / invalid state |
| 401 | Unauthenticated (includes request-signature failures, see §5) |
| 403 | Forbidden (role/branch mismatch) |
| 404 | Resource not found |
| 406 | Accept media type does not match the URL version |
| 409 | Conflict / version mismatch (`X-API-Version` ≠ path) |
| 419 | Session expired |
| 422 | Validation error / business rule violation |
| 429 | Rate limited |
| 500 | Server error |

## 17. API Versioning & Deprecation Policy

### Versioning Strategy

- **Path-based (authoritative):** the major version is part of the URL — `/api/v1`, future `/api/v2`. Each version lives in its own route file (`routes/api.php` dispatches `routes/api/v1.php`).
- **Discovery:** `GET /api` returns every published version, its media type, status, and sunset dates, so a client can boot knowing only the base URL.
- **Negotiation:**
  - `X-API-Version: <int>` makes intent explicit but **must match the path version** → else `409 VERSION_MISMATCH` with the correct URL in the `Location` header.
  - `Accept: application/vnd.tavro.v{n}+json` must match the path version → else `406 MEDIA_TYPE_MISMATCH` with available media types in `Link` headers.
- Every response advertises `X-API-Version` and `X-API-Supported-Versions`.

### Deprecation Lifecycle (RFC 8594)

1. **current** → newest minor of the active major. No warnings.
2. **deprecated** → announced via `Sunset: <RFC 1123 date>`, `Warning: 299 ...` and `Link: rel="deprecation"` headers on **every** call, from `sunset_date - grace` (365 days default) until removal.
3. **retired** → removed; requests receive 404 with the `Sunset`/`Link` `rel="deprecation"` guidance.

Dates live in `backend/config/api_versioning.php` (`versions[].sunset_date`) and are surfaced by `GET /api`. Client integrators **must** watch `Sunset` headers and migrate within the grace window.

### Current Matrix

| Version | Status | Media type | Sunset |
|---|---|---|---|
| v1 | current | `application/vnd.tavro.v1+json` | none |
| v2 | planned | `application/vnd.tavro.v2+json` | — |

---

## 18. Sentry & Session Replays

Sentry monitors both sides of the stack. Everything below is inert until a DSN is
configured — no network traffic is sent while `NEXT_PUBLIC_SENTRY_DSN` /
`SENTRY_DSN` are empty.

### Frontend wiring (Next.js 16 + Turbopack)

- `next.config.ts` wraps the config with `withSentryConfig` (release injection +
  server-component/route-handler instrumentation). Source-map upload only runs
  when `SENTRY_AUTH_TOKEN`/`SENTRY_ORG`/`SENTRY_PROJECT` are set.
- `src/instrumentation.ts` → `register()` initializes the **server & edge**
  runtimes (replaces the removed `sentry.server.config.ts` / `sentry.edge.config.ts`).
- `src/instrumentation-client.ts` initializes the **browser** (replaces the
  removed `sentry.client.config.ts`), enables Session Replay, seeds user/org
  context from the `tavro-auth` store, installs the rage-click detector, and
  exports `onRouterTransitionStart` for navigation tracing.

### Session Replay ↔ error tracking

- `replaysOnErrorSampleRate` (default `1`) starts a replay whenever an error is
  captured; every event is additionally stamped with the active `replay_id`
  (tag + `replay` context) via `src/lib/sentry.ts#stampReplayId` → each issue
  links straight to the recorded segment and vice-versa.
- `replaysSessionSampleRate` (default `0.1`) records a sample of sessions with
  no error for UX forensics.
- Privacy: `maskAllText`, `maskAllInputs` and `blockAllMedia` are on by default.

### Rage clicks

- `src/lib/rageClicks.ts#initRageClickDetection` flags a burst of ≥5 clicks on
  one element within 2.5s (cooldown 10s per element) as a `rage_click` issue
  plus breadcrumb, tagged with the active `replay_id`. Sentry's server-side
  dead/rage-click analysis still runs on top inside the replay viewer.

### Backend

- `sentry/sentry-laravel` (DSN in `backend/.env` → `SENTRY_LARAVEL_DSN`) reports
  exceptions with request context; `bootstrap/app.php` wires
  `Sentry\Laravel\Integration::handles($exceptions)`.

---

*This document was generated for Tavro v1.0.0. For questions, contact hello@tavro.ng.*
