# Tavro Backend

Laravel API and application backend for the Tavro hospitality operations platform.

## Responsibilities

The backend is the authoritative system for authentication, authorization, tenant and branch isolation, catalog, orders, kitchen and bar workflows, payments, inventory, shifts, reconciliation, audit logging, notifications, reporting, subscriptions, webhooks, integrations, real-time events, and background jobs.

## Stack

- PHP 8.2+
- Laravel 12
- PostgreSQL
- Redis
- Laravel Sanctum
- Laravel Reverb
- Spatie Laravel Permission
- Sentry
- PHPUnit

## Local setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

For a complete local environment, use the repository Docker configuration.

## Common commands

```bash
php artisan test
php artisan migrate
php artisan route:list
php artisan queue:work
php artisan reverb:start
vendor/bin/pint
```

## API

The public API is versioned under `/api/v1`.

All tenant-owned resources must enforce organization and branch authorization.

## Engineering rules

### Financial integrity

Completed orders, successful payments, refunds, and other financially meaningful records must not be physically deleted.

### Inventory integrity

Inventory is movement-based. Corrections create new movements or adjustments rather than rewriting historical stock activity.

### Authorization

Authentication does not imply authorization. Every protected operation must enforce organization, branch, role, and permission context.

### Auditability

Sensitive actions must be attributable to an authenticated actor and recorded with sufficient context for investigation.

### Idempotency

Financial and synchronization mutations must support idempotency where duplicate delivery could create an incorrect business outcome.

## Testing

Backend changes should include appropriate unit or feature tests, especially for tenant isolation, permissions, order state transitions, payments, inventory, reconciliation, idempotency, webhooks, and offline synchronization.
