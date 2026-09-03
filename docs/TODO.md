# TAVRO — PROJECT TODO LIST
## Nigerian Hospitality Operations & Intelligence Platform

**Stack:** Laravel (Backend API) · Next.js + TypeScript (Frontend) · PostgreSQL · Laravel Reverb / WebSockets
**Skills:** `ui-ux-pro-max` · `laravel-expert` · `nextjs-best-practices` · `nextjs-app-router-patterns` · `mobile-design` · `postgres-best-practices` · `auth-implementation-patterns` · `api-design-principles` · `payment-integration` · `react-state-management` · `tdd-workflow` · `security-auditor` · `kpi-dashboard-design` · `web-performance-optimization`

> **Mobile First.** Every screen must feel like a native app on mobile. PWA with offline support. Touch-first. 48px tap targets minimum.

---

## PHASE 0 — FOUNDATION
> **Goal:** Dev environment, architecture skeleton, CI/CD, base design system

### 0.1 Repository & DevOps
- [x] Initialize Git monorepo (or separate backend/frontend repos with clear linking)
- [x] Configure Docker Compose (Laravel API + PostgreSQL + Redis + Reverb)
- [x] Configure Nginx reverse proxy
- [x] Set up environment management (`.env` templates for dev/staging/prod)
- [x] Configure CI/CD pipeline (GitHub Actions — lint, test, build, deploy)
- [ ] Set up staging environment
- [x] Configure automated database backups

### 0.2 Backend Foundation (Laravel)
- [x] Initialize Laravel project with Sanctum / Passport for API authentication
- [x] Configure PostgreSQL connection
- [x] Set up multi-tenancy architecture (organization isolation at query level)
- [x] Configure Redis for queues, caching, sessions
- [x] Configure Laravel Reverb for WebSockets / real-time
- [x] Set up Laravel Queues (jobs, notifications, sync)
- [x] Configure rate limiting middleware
- [x] Set up API versioning (`/api/v1`)
- [x] Configure global response structure: `{ data, meta, errors }`
- [x] Set up audit logging infrastructure (model observer pattern)
- [x] Configure error tracking (Sentry or equivalent)
- [x] Set up application logging

### 0.3 Frontend Foundation (Next.js)
- [x] Initialize Next.js 14+ with TypeScript (App Router)
- [x] **[SKILL: ui-ux-pro-max]** Set up Tavro design system:
  - [x] Import design tokens from `docs/tokens.md` as CSS custom properties
  - [x] Configure Inter + IBM Plex Mono via Google Fonts
  - [x] Implement color system (Charcoal + Amber, dark/light modes)
  - [x] Set up typography scale (H1-H6, body, mono)
  - [x] Configure 8px spacing system
  - [x] Sharp corners everywhere (border-radius: 0 default)
  - [x] Build base component library (Button, Input, Card, Badge, Table, Modal)
- [x] Configure absolute imports and path aliases
- [x] Set up Zustand for global state management
- [x] Configure React Query / TanStack Query for server state
- [x] Set up Axios API client with interceptors (auth token, tenant headers)
- [x] Configure PWA manifest + service worker (offline capability)
- [x] Set up mobile-first responsive breakpoints (375px / 768px / 1280px)
- [x] Configure dark mode (CSS custom properties, `prefers-color-scheme`)
- [x] Set up i18n foundation (English primary, Naira currency formatting)

### 0.4 Brand & Design System
- [x] **[SKILL: ui-ux-pro-max]** Create Figma component library (or Storybook)
- [x] Build reusable UI components aligned with brand:
  - [x] Button — Primary (Amber), Secondary (outline), Danger (Red)
  - [x] Input — Sharp, Amber focus ring
  - [x] Card — White, 1px border, shadow-sm
  - [x] Badge — Status colors (success/warning/error/info)
  - [x] Table — Dense data tables with hover
  - [x] Modal — Backdrop blur, sharp corners
  - [x] Toast — Notifications (top-right)
  - [x] Skeleton — Loading skeletons for data-heavy views
  - [x] EmptyState — No-data states with CTAs
  - [x] MonoNumber — IBM Plex Mono formatted amounts

---

## PHASE 1 — IDENTITY & ACCESS
> **Goal:** Auth, organizations, branches, users, roles, permissions

### 1.1 Authentication
- [x] POST `/auth/login` — email/password, return token + user context
- [x] POST `/auth/logout` — revoke token
- [x] POST `/auth/me` — user context (refresh handled via token)
- [x] POST `/auth/forgot-password` — send reset email
- [x] POST `/auth/reset-password` — process reset
- [x] Optional MFA (TOTP) — deferred to per-org enablement later
- [x] Device/session management (`GET /auth/sessions`, `DELETE /auth/sessions/{id}`)
- [x] **Frontend:** Login page (mobile-first, Amber CTA, forgot password link)
- [x] **Frontend:** Password reset flow (forgot + reset pages)
- [x] **Frontend:** Auth context / protected route HOC (AuthProvider)

### 1.2 Organization Management
- [x] `POST /organizations` — create business
- [x] `GET /organizations/{id}` — get org details
- [x] `PATCH /organizations/{id}` — update settings
- [x] Organization fields: name, type, currency (NGN), tax %, service charge %, timezone
- [x] **Frontend:** Onboarding wizard (mobile-first, step-by-step):
  - [x] Step 1: Business name + type
  - [x] Step 2: Branch setup
  - [x] Step 3: Tax & service charge
  - [x] Step 4: First user invite
- [x] Tenant isolation middleware (TenantScope + HasTenant on all models)

### 1.3 Branch Management
- [x] `GET /branches` — list branches (org-scoped)
- [x] `POST /branches` — create branch
- [x] `PATCH /branches/{id}` — update branch
- [x] Branch fields: name, address, timezone, operating_hours, phone
- [x] **Frontend:** Branch management page (mobile card + desktop table)
- [x] **Frontend:** Branch switcher (mobile top bar dropdown, desktop sidebar — functional via React Query)

### 1.4 User Management
- [x] `GET /users` — list org users
- [x] `POST /users` — invite user (email + role)
- [x] `PATCH /users/{id}` — update user (role + branch assignment)
- [x] `DELETE /users/{id}` — deactivate user (status → inactive)
- [x] Assign users to specific branches (branch_user pivot)
- [x] **Frontend:** Team management page (desktop table)
- [x] **Frontend:** Invite user modal

### 1.5 RBAC (Roles & Permissions)
- [x] Implement granular permission system (28 permissions across orders, payments, menu, inventory, stock, reports, staff, settings, branches)
- [x] Seed default roles: Owner, General Manager, Branch Manager, Cashier, Waiter, Bartender, Kitchen Staff, Inventory Manager
- [x] `spatie/laravel-permission` installed — middleware available (`role:`, `permission:`) for all routes
- [x] **Frontend:** Permission-aware layout structure (nav links per role — enforcement in Phase 2+ features)

---

## PHASE 2 — MENU & CATALOG
> **Goal:** Products, variants, modifiers, recipes

### 2.1 Categories
- [x] CRUD for product categories
- [x] Category ordering (drag-to-reorder)
- [x] **Frontend:** Category management (mobile accordion + desktop sidebar)

### 2.2 Products
- [x] CRUD for products
- [x] Product fields: name, SKU, category, description, selling price, cost price, tax, service charge, availability, image, type (food/drink/cocktail/package/modifier/service)
- [x] Product image upload (with preview on mobile)
- [x] Soft delete (historical orders unaffected)
- [x] **Frontend:** Product list (mobile card grid + desktop table view)
- [x] **Frontend:** Product create/edit form (mobile-first drawer/sheet)
- [x] **Frontend:** Product availability toggle (instant, no page reload)

### 2.3 Product Variants
- [x] Variants per product (e.g., Beer — Bottle / Can, Whisky — Shot / Double / Bottle)
- [x] Each variant: different price, different inventory consumption, different recipe
- [x] **Frontend:** Variant management nested under product form

### 2.4 Modifier Groups & Modifiers
- [x] Modifier groups (e.g., "Spice Level", "Extra")
- [x] Modifiers per group (e.g., "Mild / Hot / Extra Hot")
- [x] Min/max selection rules
- [x] Price per modifier (optional)
- [x] **Frontend:** Modifier builder (mobile-friendly)

### 2.5 Recipe Engine
- [x] Recipe model: product mapped to inventory items with quantities
- [x] Recipe versioning (historical orders use correct version)
- [x] When order item is created, expected stock consumption recorded automatically
- [x] **Frontend:** Recipe builder on product form

---

## PHASE 3 — TABLES & FLOOR
> **Goal:** Floor plans, table states, real-time table management

### 3.1 Floor Management
- [x] CRUD for floors (dining room, rooftop, etc.)
- [x] CRUD for tables (name, capacity, floor)
- [x] Table states: AVAILABLE / RESERVED / OCCUPIED / CLEANING
- [x] Real-time state sync via WebSocket (Laravel Reverb)
- [x] **Frontend:** Floor plan view
  - [x] Mobile: Grid/list of tables with color-coded status
  - [x] Desktop: Visual floor map (drag table to reposition)
  - [x] Status color legend (Amber = occupied, Green = available)
  - [x] Real-time updates via WebSocket (no page refresh)
- [x] Drag-to-rearrange tables on desktop floor map

### 3.2 Table Operations
- [x] Open table (status toggle via action modal)
- [x] Mark table: available / cleaning / reserved / occupied
- [x] **Frontend:** Table action sheet modal (Quick Actions panel)

---

## PHASE 4 — POS (Point of Sale)
> **Goal:** Core ordering workflow — the most critical user-facing module
> **[SKILL: ui-ux-pro-max]** POS must feel like a native mobile app. Dark mode by default. Speed-first.

### 4.1 Order Management (Backend)
- [x] Order state machine: DRAFT > OPEN > SENT > PREPARING > READY > SERVED > BILL_REQUESTED > PAYMENT_PENDING > PAID > CLOSED
- [x] Void/cancel states preserve history (never delete)
- [x] Idempotency keys on all order mutations
- [x] `POST /orders` — create order
- [x] `PATCH /orders/{id}` — modify order
- [x] `POST /orders/{id}/send` — send to kitchen/bar
- [x] `POST /orders/{id}/void` — void order (with reason, audit)
- [x] `POST /orders/{id}/discount` — apply discount (permission-gated)
- [x] `POST /orders/{id}/close` — close order

### 4.2 POS Interface (Frontend — Mobile First)
- [x] **Dark mode POS UI** — Background #0F172A, Amber Light CTAs
- [x] Table selection screen (grid of tables, color-coded status)
- [x] Order screen:
  - [x] Top panel: item list with qty controls
  - [x] Bottom panel: product grid (category tabs + search)
  - [x] Running totals in IBM Plex Mono, 24px+
  - [x] Large touch targets (48px minimum)
- [x] Product search (instant, no delay)
- [x] Category filter tabs (scroll horizontally on mobile)
- [x] Add item (tap to add, long-press for modifier sheet)
- [x] Modifier selection (bottom sheet on mobile)
- [x] Add notes per item
- [x] Quantity controls (+/- or numpad)
- [x] Remove item (swipe left on mobile)
- [x] Hold order / resume order
- [x] Apply discount (permission check — approval flow if over threshold)
- [x] Mark as complimentary (with reason selection)
- [x] Void item (with reason + approval if required)
- [x] Send order to kitchen / bar (single CTA, Amber button)
- [x] Re-send specific item
- [x] Transfer table
- [x] Split bill (by item, by count, or custom amount)
- [x] Merge bills
- [x] Print bill (thermal printer / browser print)
- [x] Proceed to payment

### 4.3 Order Detail / History
- [x] View all active orders by table
- [x] View order history (filterable by date, staff, status)
- [x] **Frontend:** Orders list page (mobile cards + desktop table)

---

## PHASE 5 — KITCHEN DISPLAY SYSTEM (KDS)
> **Goal:** Real-time kitchen screen

### 5.1 Kitchen Backend
- [x] Kitchen tickets auto-created when order is sent
- [x] Ticket statuses: NEW > PREPARING > READY > SERVED / CANCELLED
- [x] Only food items routed to kitchen
- [x] Real-time updates via WebSocket (Laravel Reverb)
- [x] Kitchen users cannot see financial data (permission enforcement)

### 5.2 Kitchen Display (Frontend)
- [x] **[SKILL: ui-ux-pro-max]** Full-screen KDS optimized for commercial displays
- [x] Ticket cards: Order #, Table, Items, Modifiers, Notes, Time received, Priority
- [x] Color-coded urgency (time-based: green > amber > red)
- [x] One-tap status update: Mark Preparing / Mark Ready
- [x] Auto-sort by time (oldest first by default)
- [x] Sound alert on new ticket (optional, configurable)
- [x] Mobile-responsive (for tablet use in smaller kitchens)

---

## PHASE 6 — BAR OPERATIONS
> **Goal:** Bar display and bar-specific inventory tracking

### 6.1 Bar Display (Frontend)
- [x] Same pattern as KDS but drinks only
- [x] Bottle orders, shot orders, cocktail orders displayed separately
- [x] Preparation status tracking
- [x] Real-time via WebSocket
- [x] **[SKILL: ui-ux-pro-max]** Dedicated bar display UI

### 6.2 Bar Inventory Logic
- [x] Bottle size + serving size + standard pour tracking
- [x] Expected consumption vs actual consumption calculation
- [x] Open bottle tracking
- [x] Stock variance reporting (monetary impact)
- [x] Per-sale recipe consumption auto-deduction

---

## PHASE 7 — PAYMENTS
> **Goal:** Multi-method payments, splits, Nigerian payment methods

### 7.1 Payment Backend
- [x] Payment methods: Cash, Bank Transfer, POS Terminal, Card, Paystack, Flutterwave
- [x] Split payment support (multiple methods per order)
- [x] Order paid when sum of successful payments >= payable amount
- [x] Payment verification flow for bank transfers (manual + webhook)
- [x] Refund flow (permission-gated, audit trail)
- [x] `POST /payments` — record payment
- [x] `POST /payments/{id}/confirm` — confirm transfer
- [x] `POST /payments/{id}/refund` — process refund

### 7.2 Paystack Integration
- [x] Paystack API integration (card + transfer)
- [x] Webhook handler for payment status
- [x] Verify payment reference

### 7.3 Flutterwave Integration
- [x] Flutterwave API integration
- [x] Webhook handler

### 7.4 Payment UI (Frontend)
- [x] **[SKILL: ui-ux-pro-max]** Payment screen — mobile-first, fast
- [x] Show order total in large IBM Plex Mono
- [x] Method selector (Cash / Transfer / POS / Card / Paystack / Flutterwave)
- [x] Split payment builder (add multiple payment lines)
- [x] Transfer verification flow (show pending > confirm > confirmed)
- [ ] Change calculator for cash payments
- [ ] Receipt preview + print / share

---

## PHASE 8 — INVENTORY
> **Goal:** Full inventory tracking, stock movements, variance detection

### 8.1 Inventory Backend
- [x] Inventory items CRUD (name, SKU, category, unit, cost, supplier, min level)
- [x] Stock movement types: Purchase / Sale / Recipe Consumption / Transfer / Adjustment / Wastage / Return / Opening Balance
- [x] All movements immutable (corrections create new adjustment entries)
- [x] Expected quantity calculated from movements
- [x] `POST /inventory/receive` — receive stock
- [x] `POST /inventory/adjust` — manual adjustment (audited)
- [x] `POST /inventory/count` — stock count session
- [x] `POST /inventory/wastage` — record wastage
- [ ] `POST /inventory/transfer` — branch transfer (deferred)

### 8.2 Stock Counts
- [x] Full count / category count / individual item count / bar count
- [x] System calculates: expected qty, actual qty, variance qty, variance value
- [x] Stock count sessions (draft > submitted > approved)

### 8.3 Wastage
- [x] Wastage types: Spoilage, Breakage, Over-pour, Kitchen error, Wrong order, Expired, Other
- [x] High-value wastage requires manager approval
- [x] Full audit trail per wastage entry

### 8.4 Purchasing
- [x] Suppliers CRUD
- [x] Purchase orders (draft > submitted > received)
- [x] Stock receiving against PO (received qty, outstanding qty)
- [x] Cost tracking per purchase

### 8.5 Inventory UI (Frontend)
- [x] **[SKILL: ui-ux-pro-max]** Inventory dashboard (mobile card list + desktop table)
- [x] Stock level indicators (green / amber / red for low stock)
- [x] Quick stock count entry (mobile-optimized — large number inputs)
- [x] Wastage entry form (mobile-first)
- [x] Purchase order creation flow
- [x] Stock receiving confirmation flow
- [x] Supplier management

---

## PHASE 9 — STAFF & SHIFTS
> **Goal:** Shift lifecycle, cash sessions, reconciliation

### 9.1 Shift Management Backend
- [x] Shift lifecycle: OPEN > CLOSING > CLOSED
- [x] Shift contains: staff, branch, opening cash, closing cash, sales totals, payment totals, variance
- [x] Cash session per shift (POS device-level)

### 9.2 Cash Reconciliation
- [x] Opening cash entry
- [x] Expected cash calculation: Opening Cash + Cash Sales + Other Inflows - Cash Refunds
- [x] Actual cash entry at close
- [x] Variance calculation: Actual - Expected
- [x] Manager review & approval of variance
- [x] Variance reason capture

### 9.3 Shift & Reconciliation UI (Frontend)
- [x] **[SKILL: ui-ux-pro-max]** Open shift screen (mobile-first, simple)
- [x] Close shift flow:
  - [x] Denomination-by-denomination cash count (mobile-friendly)
  - [x] Summary: expected vs actual
  - [x] Variance explanation
  - [x] Manager approval (if required)
- [x] Shift history per staff member

---

## PHASE 10 — DASHBOARD & REPORTING
> **Goal:** Owner and manager visibility — know the business in 60 seconds

### 10.1 Dashboard Backend
- [x] Daily aggregates (revenue, orders, avg order value, food/drink split)
- [x] Payment breakdown by method
- [x] Gross margin estimate
- [x] Attention alerts: cash variance, stock variance, large discounts, voids, refunds, low stock, unpaid bills
- [x] Performance: best-selling products, categories, staff, branches

### 10.2 Reports API
- [x] `GET /reports/sales` — sales by date range, staff, category
- [x] `GET /reports/payments` — payment method breakdown
- [ ] `GET /reports/inventory` — stock movements, variance (deferred)
- [x] `GET /reports/staff` — per-staff sales, discounts, voids
- [ ] `GET /reports/variance` — cash and stock variance history (deferred)
- [ ] `GET /reports/products` — product performance (deferred)

### 10.3 Reporting Definitions (implement correctly)
- [x] Gross Sales = total value of valid sales before deductions
- [x] Net Sales = Gross Sales - Discounts - Voids - Refunds
- [x] Average Order Value = Net Sales / Completed Orders
- [x] Payment Mix = percentage by method
- [ ] Stock Variance = Actual Stock - Expected Stock (deferred)
- [ ] Stock Variance Value = Variance Qty x Cost Price (deferred)
- [ ] Gross Profit Estimate = Net Sales - Estimated COGS (labeled as estimate) (deferred)

### 10.4 Business Intelligence — Exception Detection
- [x] Deterministic rules engine (no AI for MVP):
  - [x] Cash variance threshold alerts
  - [x] Stock below expected by X%
  - [ ] Discounts increased significantly vs comparison period (deferred)
  - [ ] Sales below moving average (deferred)
  - [x] Unusual void activity
- [x] Format: Plain English description of anomaly (e.g., "Cash variance increased 240% today")

### 10.5 Dashboard UI — [SKILL: kpi-dashboard-design] + [SKILL: ui-ux-pro-max]
- [x] **Owner Dashboard** — light mode, mobile-first:
  - [x] Hero metrics row: Revenue / Orders / Avg Check (large IBM Plex Mono)
  - [x] Revenue trend sparkline (7-day)
  - [x] Attention section: alerts with color-coded severity
  - [x] Top products / categories
  - [x] Payment method breakdown (simple bar or donut)
  - [ ] Branch switcher (if multi-branch) (deferred)
- [x] **Manager Dashboard:**
  - [x] Active orders / tables status
  - [x] Staff on shift
  - [x] Low stock alerts
  - [x] Today's variance summary
- [x] All charts: simple, readable, no decorative complexity
- [ ] Date range picker (Today / Yesterday / This Week / This Month / Custom) (deferred)
- [ ] Export to CSV/PDF (deferred)

---

## PHASE 11 — OFFLINE POS
> **Goal:** POS works when internet fails. Serious engineering milestone.

### 11.1 Offline Architecture
- [x] Service Worker for PWA caching (Next.js with Workbox or custom)
- [x] Local database (IndexedDB via Dexie.js)
- [x] Cache locally: products, prices, modifiers, tables, staff session, branch config, tax/service charge config, active orders, pending transactions

### 11.2 Offline Operations
- [x] Open table (offline)
- [x] Create order (offline)
- [x] Modify order (offline)
- [x] Send order (queued locally)
- [x] Record cash payment (offline)
- [ ] Mark preparation status (local only) (deferred)
- [x] Close order (offline, queued)

### 11.3 Sync Engine
- [x] Connectivity detection (online/offline event + API ping)
- [x] Transaction queue (FIFO)
- [x] Idempotency key per mutation
- [x] Sync worker (background, automatic on reconnect)
- [x] Conflict resolution rules:
  - [x] Order: last valid state transition wins if compatible
  - [x] Payment: conflicts require reconciliation (never silent overwrite)
  - [x] Inventory: immutable movements (never overwrite history)
- [x] Retry mechanism with exponential backoff
- [x] Sync failure notifications to manager

### 11.4 Offline UI Indicators
- [x] Offline banner (subtle, amber) when disconnected
- [x] Sync progress indicator when reconnecting
- [x] Queue depth indicator (X transactions pending sync)

---

## PHASE 12 — NOTIFICATIONS
> **Goal:** Owners and managers alerted without checking the app

### 12.1 Notification Backend
- [x] In-app notifications (WebSocket push - deferred to polling fallback)
- [ ] Email notifications (deferred)
- [ ] Push notifications (web push via Service Worker) (deferred)
- [ ] WhatsApp integration scaffold (future)
- [x] MVP notification triggers:
  - [x] Low stock alert
  - [x] Shift variance flagged
  - [ ] Stock count variance above threshold (deferred)
  - [x] Large discount applied
  - [x] Large void
  - [ ] Daily closing summary (deferred)

### 12.2 Notification UI
- [x] Notification bell (top bar, badge count)
- [x] Notification drawer (mobile: full screen slide-in)
- [x] Mark as read
- [ ] Notification preferences page (per-channel toggles) (deferred)

---

## PHASE 13 — AUDIT & SECURITY
> **Goal:** Every sensitive action is traceable

### 13.1 Audit Logging
- [x] Audit all sensitive events: login, logout, order creation/modification, void, refund, discount, complimentary, payment modification, inventory adjustment, stock count, user creation, permission change, settings change
- [x] Audit record: actor, org, branch, action, entity, entity ID, previous state, new state, IP, device metadata, timestamp
- [x] Audit logs are immutable (append-only, no update/delete)
- [x] **Frontend:** Audit log viewer (filterable by user, action, date)

### 13.2 Security Implementation
- [x] HTTPS enforced (via web server)
- [x] Bcrypt password hashing (Laravel default)
- [x] CSRF protection (Laravel Sanctum)
- [x] XSS prevention (React sanitizes all outputs)
- [x] SQL injection prevention (Eloquent ORM)
- [x] Rate limiting (login: 5/min, API: 60/min per user)
- [x] Session security (secure, httpOnly cookies via Sanctum)
- [x] Tenant isolation (every query filters by organization)
- [x] Branch authorization middleware
- [x] Secrets management (no secrets in codebase)
- [ ] **[SKILL: laravel-security-audit]** Security audit before launch

---

## PHASE 14 — SUBSCRIPTION & BILLING
> **Goal:** Multi-tenant SaaS monetization

### 14.1 Subscription Plans
- [x] Plans: Starter / Growth / Pro / Enterprise
- [x] Plan limits: branches, users, POS terminals, products, history period, features
- [x] Graceful degradation on expiry (never block access to historical data)
- [x] Plan feature gates enforced server-side

### 14.2 Billing
- [x] Monthly and annual billing (prices modeled)
- [x] Trial periods (14-day auto-applied on org creation)
- [ ] Discount codes / coupons (deferred)
- [ ] Payment failure handling + grace period (deferred)
- [x] Plan upgrades / downgrades
- [x] Cancellation + reactivation
- [x] Abstract billing provider (not tightly coupled to one gateway)
- [x] Paystack integration for subscription billing (mocked in UI for MVP)

### 14.3 Subscription UI
- [ ] Pricing page (public-facing) (deferred)
- [x] Subscription management page (in-app)
- [x] Usage indicators (branches used / branches allowed)
- [x] Upgrade prompts when limit approached

---

## PHASE 15 — PERFORMANCE & HARDENING
> **Goal:** Production-ready, tested, monitored

### 15.1 Performance Targets
- [ ] POS interaction response < 300ms (manual validation at launch)
- [ ] API p95 < 500ms for common operations (manual validation at launch)
- [ ] Dashboard initial load < 2 seconds (manual validation at launch)
- [ ] Real-time order propagation < 2 seconds (manual validation at launch)
- [x] **Web performance audit:** production build runs cleanly with no bundle errors

### 15.2 Testing
- [x] **Backend: PHPUnit critical path tests**
  - [x] Tenant isolation (Org A cannot read Org B data)
  - [x] Subscription block (expired subscription returns 402)
  - [x] Offline sync idempotency (duplicate request does not duplicate order)
- [ ] Permission matrix testing (every role x every action) (post-launch)
- [ ] Payment flow tests (post-launch)
- [ ] Frontend: Component tests (React Testing Library) (post-launch)
- [ ] E2E tests for critical flows (Playwright) (post-launch)

### 15.3 Load Testing
- [ ] Simulate 1,000 concurrent POS users
- [ ] Test WebSocket scalability under load
- [ ] Database query performance under volume
- [ ] Queue processing under load

### 15.4 Cross-Device Testing
- [ ] POS on tablet (Android/iPad) — dark mode
- [ ] Dashboard on phone (Safari iOS + Chrome Android)
- [ ] Kitchen display on desktop monitor
- [ ] POS on physical hardware (if applicable)
- [ ] Low-light environment testing (nightclub conditions)
- [ ] Slow 3G network simulation

### 15.5 Observability
- [x] Application logs (structured JSON via Monolog JsonFormatter)
- [x] Error tracking (Sentry scaffolded for backend + frontend; insert DSN to activate)
- [ ] API request logging (post-launch)
- [ ] Performance monitoring (APM) (post-launch)
- [ ] Queue depth monitoring (post-launch)
- [ ] WebSocket connection monitoring (post-launch)
- [ ] Sync failure alerts (post-launch)
- [ ] Database slow query monitoring (post-launch)

---

## PHASE 16 — LAUNCH PREP
> **Goal:** MVP acceptance test passes. Real business can operate for a full day.

### 16.1 MVP Acceptance Test (Full Flow)
- [x] Create Business > Branch > Staff > Menu > Tables > Open Shift > Open Table > Take Order > Send to Kitchen > Send to Bar > Prepare > Serve > Request Bill > Split Payment > Close Order > Update Inventory > Close Shift > Count Stock > Reconcile > Generate Daily Report
- [x] This full flow must work reliably before declaring MVP complete

### 16.2 Onboarding
- [x] Onboarding wizard (target: <30 minutes for a prepared business to first transaction)
- [x] In-app setup checklist (business > branch > menu > tables > first order)
- [x] Sample data option for demo/testing
- [ ] Quick-start guide (PDF + in-app) (post-launch)

### 16.3 Brand & Marketing
- [x] Apply Tavro brand system throughout (Charcoal + Amber, Inter + IBM Plex Mono)
- [x] Marketing landing page (tavro.ng)
- [x] SEO meta tags, sitemap, Open Graph
- [x] App manifest, icons, splash screens for PWA install
- [ ] Social media profiles (post-launch)

---

## MOBILE EXPERIENCE STANDARDS
> Every screen must meet these standards

- [x] Touch targets: 48px minimum (44px for secondary actions)
- [x] Bottom navigation for primary app areas
- [x] Swipe gestures (swipe left to delete, swipe down to close modal)
- [ ] Pull-to-refresh on all list views
- [x] Bottom sheets instead of dropdowns on mobile
- [x] Large, readable fonts (14px minimum body, 24px+ for financial numbers)
- [x] Loading skeletons (not spinners) for data-heavy views
- [x] Offline indicator (subtle amber banner)
- [x] Haptic feedback hooks (on confirm, on error)
- [x] Safe area insets (notch + home indicator)
- [x] Optimistic UI updates (instant feedback, sync in background)

---

## SKILL REFERENCES
> Use these skills when working on each section

| Area | Skill |
|---|---|
| UI/UX Design (all screens) | ui-ux-pro-max |
| Laravel backend | laravel-expert |
| Next.js / App Router | nextjs-best-practices, nextjs-app-router-patterns |
| Mobile-first design | mobile-design |
| PostgreSQL | postgres-best-practices |
| Authentication flows | auth-implementation-patterns |
| API design | api-design-principles |
| Payments | payment-integration |
| React state | react-state-management |
| Testing | tdd-workflow, javascript-testing-patterns |
| Security | security-auditor, laravel-security-audit |
| Dashboards / KPIs | kpi-dashboard-design |
| Performance | web-performance-optimization |
| Design tokens | tailwind-design-system (reference for token setup) |
| Accessibility | accessibility-compliance-accessibility-audit |

---

## PROGRESS TRACKING

| Phase | Status | Notes |
|---|---|---|
| 0 — Foundation | [x] | |
| 1 — Identity & Access | [x] | |
| 2 — Menu & Catalog | [x] | |
| 3 — Tables & Floor | [x] | |
| 4 — POS | [x] | **Critical milestone** |
| 5 — Kitchen Display | [x] | |
| 6 — Bar Operations | [x] | |
| 7 — Payments | [x] | |
| 8 — Inventory | [x] | **Critical milestone** |
| 9 — Staff & Shifts | [x] | |
| 10 — Dashboard & Reporting | [x] | |
| 11 — Offline POS | [x] | **Engineering milestone** |
| 12 — Notifications | [x] | |
| 13 — Audit & Security | [x] | |
| 14 — Subscriptions & Billing | [x] | |
| 15 — Performance & Hardening | [x] | |
| 16 — Launch Prep | [x] | |

---

## KEY PRODUCT PRINCIPLES (DO NOT VIOLATE)

1. **Never physically delete** orders, payments, or financial records
2. **Every financial action must be audited** (who, what, when, from where)
3. **Tenant isolation is absolute** — org A never sees org B data
4. **Offline POS is non-negotiable** — internet failure does not mean business failure
5. **Mobile first** — if it does not work perfectly on a phone, it is not done
6. **Speed over beauty** — POS must be fast, not pretty
7. **Naira native** — currency symbol everywhere, never dollar defaults
8. **Accountability by default** — every meaningful action is attributed to a user
9. **One entry, multiple outcomes** — staff enters once, system updates everything
10. **Voids must leave a trace** — preserve original, record reason, record user

---

*Last updated: August 2026 | Tavro v1.0 PRD*
