# Tavro Frontend

Next.js web application and PWA for Tavro.

## Responsibilities

The frontend provides role-specific operational interfaces for owners, managers, cashiers, waiters, bartenders, kitchen staff, inventory managers, and finance users.

## Stack

- Next.js 16
- React 19
- TypeScript
- Tailwind CSS
- TanStack React Query
- Zustand
- Dexie
- Laravel Echo / WebSockets
- Sentry

## Local development

```bash
npm install
npm run dev
```

## Quality checks

```bash
npm run lint
npm run build
```

TypeScript must remain type-safe. Avoid introducing `any` as a shortcut around domain or API contracts.

## Application principles

- Operational screens prioritize speed and clarity.
- Backend authorization is the security boundary.
- Server state belongs in React Query.
- Offline operational state belongs in the local persistence layer.
- UI state remains separate from server state.
- Financial mutations must be safe to retry.
- Offline actions must enter the sync queue.

## Offline POS

The POS uses local persistence and a synchronization queue for core operational workflows during connectivity interruptions. The server remains authoritative for financial records.

## Real-time

Operational updates use Laravel Reverb/Echo where real-time propagation is required. UI consumers must handle reconnects and stale events safely.
