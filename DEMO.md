# Tavro — Client Demo Script

A ~20-minute walkthrough of the running product. Every flow here was verified
end-to-end against the live app (auth → POS order → cash & split payments →
gateway webhook with replay protection → tenant isolation → request signing).
Self-serve signup and the staff-invite/accept flow are also live and verified.

## Demo credentials

Put these on a slide for the client. Emails are pre-verified in the seed data.

| Role            | Email                | Password   | Organization                         |
|-----------------|----------------------|------------|--------------------------------------|
| Restaurant owner| owner@demo.tavro.ng  | `password` | The Golden Fork Restaurant (org 5)   |
| Waiter          | waiter@demo.tavro.ng | `password` | The Golden Fork Restaurant (org 5)   |
| Org admin       | admin@tavro.ng       | `password` | A different organization (org 6)     |

`owner` and `waiter` share the same branch (`Main Branch`), so you can show
collaboration. `admin` lives in a *different* organization — that is your
tenant-isolation demo.

## Before the client arrives (5 minutes)

1. **Start both servers**
   - Backend: `php artisan serve --port=8000` from `backend/`. Health check: `GET /up` → 200.
   - Frontend: `npm run dev` from `frontend/`. Check `http://localhost:3000/login` → 200.

2. **Clear the login rate-limiter** so the demo cannot be throttled mid-flow:
   ```
   redis-cli -p 6379 -n 1 FLUSHDB
   ```

3. **Reset to a clean demo dataset** (recommended so your demo starts identical every time):
   ```
   php artisan migrate:fresh --seed
   ```
   Seeds "The Golden Fork Restaurant", one branch, menu categories, products, and
   the three demo users above. No rows in orders/payments — you create every
   ticket live.

4. **Decide on the gateway (Paystack) demo**
   - **Show the live webhook flow:** temporarily set `PAYSTACK_SECRET_KEY` and
     `PAYSTACK_WEBHOOK_TOKEN` in `backend/.env` (token: `php artisan webhook:token gen paystack`),
     and register `https://<host>/api/v1/webhooks/paystack/{token}` in the Paystack
     dashboard. Revert the `.env` after the demo.
   - **Skip it:** show the bank-transfer → manual-confirm flow instead (requires zero
     provider config). The webhook endpoint correctly returns 503 until configured,
     which is itself a good security talking point.

## The demo (step by step)

### 0. Real signup — sellable from second zero — 3 min
This is the "no phone call required" moment.

- Open `/register`. Create a brand-new business (name + your name + email +
  password). It validates, creates the organization **and** the owner account,
  and drops you straight into a short onboarding wizard (tax/service charge →
  invite a teammate) — no credit card, 14-day pro trial.
- On the onboarding invite step, type any email and finish. The invitation email
  is delivered to the app mailbox (`backend/storage/logs/laravel-*.log`, demo mail
  driver) with a per-invite token link `localhost:3000/invite?token=…&email=…`.
- Open that link in an incognito window, set a password → the staff member signs
  in, land-linked to the same organization and branch.
- Straight into a stocked bar: on `/menu`, hit **Add pack → Beer Parlour Starter**
  to drop a full drink menu (lagers, stouts, spirits, soft drinks — with sizes like
  *Big 60cl / Medium 50cl / Small 33cl* and suggested ₦ prices, editable anytime)
  into the new business in one tap. `Browse the catalog` searches any drink
  (stout, gin, Orijin…) and adds it with its sizes pre-filled.

> *"A real product for real customers — no setup calls, no onboarding consultants.
> Even the drink menu comes pre-stocked."*

> Tip: if you demoed signup at the end, don't re-seed; the new org is one click
> from `DELETE` in the database only. For repeatable client demos use
> `migrate:fresh --seed` before the client arrives.

### 1. Login — 2 min
Open `/login`, sign in as `owner@demo.tavro.ng`.

First, try a wrong password to show the clean **401**, then login correctly.

> *"Every request here is authenticated, and every state-changing request is
> HMAC-signed with a per-session secret — timestamp + nonce + body hash, so
> replaying or altering a request fails before it reaches the API."*

### 2. Dashboard — 2 min
Point at the KPIs (today's sales, orders, open tickets). Note it is **branch-scoped**:

> *"This only shows what's happening in the active branch."*

Show the branch switcher in the top bar if multiple branches exist.

### 3. Floor plan → POS — 5 min
Open `/floorplan`, pick a table (opens in POS). Add items from the menu (e.g. *Peppered
Chicken*). Show that tax (7.5%) and service charge (5%) are applied automatically to the
ticket.

Pay **cash**, watch the ticket flip to **PAID**.

> *"Every payment writes an immutable ledger entry and carries an idempotency key,
> so a double-tap can never double-charge a guest."*

### 4. Split payment — 3 min
On a second ticket take a **partial cash** payment, then **card/transfer** for the balance.
Open the payments list to show the ledger trail (`INTENT → COMPLETED`) and that the order
only closes once fully settled.

### 5. Kitchen / Bar pipeline — 3 min
Open `/kitchen` (and `/bar`). Sent orders appear for prep and advance
`SENT → PREPARING → READY`.

> Bar bonus: each ticket shows **who's serving it** (the floor staff's name), and the
> bar can type a *serve note* per drink (e.g. "3 glasses, no ice") that pops up on the
> waiter's POS ticket the moment it's saved.

> Tip: put the kitchen screen on a second monitor. That live pipeline sells a
> restaurant, not a dashboard.

### 6. Menu + Inventory — 2 min
- `/menu/products`: toggle availability on a product and show the POS menu update live.
- Top of the products tab: **Starter packs** (Beer Parlour Starter / Spirit & Bitters
  Shelf / Full Drinks Menu) and a **Browse the catalog** search — adding a drink
  creates it with sizes *and* suggested prices (editable), category auto-created.
- `/inventory`: show stock on hand; point at low-stock and purchase orders if seeded.

### 7. Gateway payment + replay protection — 3 min (optional)
Create a ticket, pay by card (Paystack), leave it at **PENDING**, trigger the webhook, and
show the payment → **COMPLETED** and the order → **PAID**. Then the **wow moment**: fire the
*same webhook payload again* — the app returns `{"status":"duplicate"}` and the balance does
not change.

> *"Provider webhooks are signature-verified, URL-token-gated, IP-allowlisted, and made
> idempotent — provider retries and replays can never double-settle a bill."*

Show a bad signature rejected with **400**.

### 7b. Owner's free sales digests on Telegram — 2 min
`/settings/notifications`: owner-only page. Generate a **pairing code** and DM it to the
Tavro bot (or just show the flow) — the `telegram:poll` worker binds the chat. Pick
**hourly / daily / weekly**, turn reports on, hit **Send a test digest**. The free channel
kicks the same sales numbers straight to the owner's phone — no paid WhatsApp Business API.

### 8. Staff + roles + tenant isolation — 2 min
- `/settings/team`: show role assignment.
- Open an **incognito window** and sign in as `waiter@demo.tavro.ng`: same branch, same
  live tickets — visible collaboration between two windows.
- In the same incognito window, sign in as `admin@tavro.ng` and try to open one of the
  Golden Fork orders: it returns **404** (no existence leak across organizations).

### 9. Trust & reliability — 2 min
- `/settings/status`: the status page.
- Mention: versioned API (`/api/v1`, RFC 8594 deprecation policy), the audit trail, and
  **Sentry Session Replays**.

> *"If a waiter hits a bug, we can watch the actual session replay with rage-clicks
> flagged. That's wired into production builds."*

### 10. Close (the whole pitch in one ticket) — 2 min
Three windows: POS, kitchen, owner dashboard. Create one order, pay it, watch it reach
the kitchen, close out. That single end-to-end ticket is the pitch.

## If something breaks during the demo

| Symptom                          | Fix                                       |
|----------------------------------|-------------------------------------------|
| Login throttled (429)            | `redis-cli -p 6379 -n 1 FLUSHDB` and retry |
| Paystack webhook demo fails      | Revert `.env`, use the transfer→confirm flow instead (step 4/7) |
| Webhook returns 503              | Expected when `PAYSTACK_WEBHOOK_TOKEN` is empty — endpoint refuses unauthenticated delivery |
| Seeded users can't login (403)   | Email verification flag missing — `php artisan migrate:fresh --seed`, then re-verify in DB if needed |