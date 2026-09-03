# Tavro

**Nigeria-first Hospitality Operations & Intelligence Platform**

Tavro is a multi-tenant SaaS platform built for restaurants, bars, lounges, clubs, and hospitality groups in Nigeria. It combines point of sale, kitchen and bar operations, payments, inventory, staff shifts, reconciliation, auditability, reporting, and business intelligence in one operational system.

> **Sell more. Control stock. Know your numbers.**

## Product

Tavro is deliberately more than a POS. The POS is the operational entry point; the platform helps hospitality businesses understand what they sold, what they should have consumed, where money went, who handled it, and what needs attention.

## Core capabilities

- Multi-tenant organizations and branches
- Role-based access control
- Menu, products, variants, modifiers, and recipes
- Table and floor management
- POS and order lifecycle management
- Kitchen and bar operations
- Cash, transfer, POS/card and provider payments
- Inventory and bar inventory
- Stock counts, wastage, and variance
- Staff shifts and cash reconciliation
- Audit logging and operational accountability
- Owner dashboards and business reporting
- Notifications and subscriptions
- Offline-capable POS and synchronization
- Real-time operational updates

## Architecture

Tavro is implemented as a modular monolith with a separate web application.

```
Next.js Web/PWA
      |
 REST / WebSocket
      |
 Laravel API
      |
 PostgreSQL + Redis
      |
 Realtime / Queues

Infrastructure: Docker + Nginx + VPS/cloud
```

### Technology stack

**Backend**
- PHP 8.2+
- Laravel 12
- PostgreSQL
- Redis
- Laravel Sanctum
- Laravel Reverb
- Spatie Laravel Permission
- Sentry

**Frontend**
- Next.js 16
- React 19
- TypeScript
- Tailwind CSS
- TanStack React Query
- Zustand
- Dexie
- Laravel Echo
- Sentry

**Infrastructure**
- Docker
- Nginx
- PostgreSQL
- Redis
- CI/CD
- VPS/cloud deployment

## Repository structure

```
tavro/
├── backend/              # Laravel API and application backend
├── frontend/             # Next.js web/PWA
├── docs/                 # Product, architecture and engineering docs
├── docker/               # Container and infrastructure configuration
├── ops/                  # Operational tooling
├── scripts/              # Automation scripts
└── .github/              # Repository automation and CI
```

## Local development

### Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

### Quality checks

```bash
cd backend
php artisan test
vendor/bin/pint

cd ../frontend
npm run lint
npm run build
```

## Engineering principles

- Tenant isolation
- Explicit authorization
- Immutable financial and inventory history where applicable
- Idempotent financial mutations
- Auditable sensitive operations
- Server-side validation
- Offline resilience
- Deterministic synchronization
- Observable production systems
- Automated testing
- Backward-compatible API evolution

## Offline POS

```
POS
 ↓
Local database
 ↓
Sync queue
 ↓
Connectivity detection
 ↓
Sync worker
 ↓
Laravel API
 ↓
PostgreSQL
```

Mutations use globally unique idempotency keys. The server remains authoritative for financial records.

## Documentation

The `docs/` directory contains product, architecture, engineering, security, operations, and brand documentation. Existing documentation is being progressively standardized without blindly moving or deleting historical material.

## Security

Security issues should be reported privately. See `SECURITY.md`.

## Contributing

Development standards are documented in `CONTRIBUTING.md`.

## License

Tavro is proprietary software unless a specific repository file states otherwise. No license is granted to copy, redistribute, or commercially exploit the software without explicit authorization.
