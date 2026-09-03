# Tavro MVP Acceptance Test

> **Version:** 1.0 | **Environment:** Staging / Production
> **Goal:** A real business can operate for a full working day using Tavro.
> Run this checklist top-to-bottom before declaring the MVP complete.
> Each step must be completed successfully before proceeding to the next.

---

## Pre-conditions

- [ ] Server is running (Laravel backend accessible at `/api/v1/up`)
- [ ] Frontend is deployed and accessible
- [ ] Database is migrated (`php artisan migrate`)
- [ ] Plans are seeded (`php artisan db:seed --class=PlanSeeder`)
- [ ] Paystack test credentials are configured in `.env`

---

## Section 1 — Business Setup

### 1.1 Register Organization
- [ ] Navigate to the Tavro landing page
- [ ] Click "Start Free Trial"
- [ ] Fill in: Business Name (`The Golden Fork Restaurant`), Type (`restaurant`)
- [ ] Submit and verify a **14-day trial** on the Pro plan is automatically created
- [ ] Redirected to the Onboarding Wizard

### 1.2 Onboarding Wizard
- [ ] Step 1 (Business): Name pre-filled from registration ✓
- [ ] Step 2 (Branch): Enter `Main Branch`, city `Lagos`
- [ ] Step 3 (Staff): Skip (will invite later)
- [ ] Step 4 (Confirmation): Click "Go to Dashboard"
- [ ] Dashboard loads and shows the **Setup Checklist** widget

---

## Section 2 — Menu Setup

### 2.1 Categories
- [ ] Navigate to **Menu > Categories**
- [ ] Create category: `Starters`
- [ ] Create category: `Mains`
- [ ] Create category: `Drinks`

### 2.2 Products
- [ ] Create product: `Jollof Rice` (Mains, ₦3,500)
- [ ] Create product: `Peppered Chicken` (Starters, ₦2,500)
- [ ] Create product: `Chilled Chapman` (Drinks, ₦1,200)
- [ ] Toggle `Peppered Chicken` to **unavailable**, verify it is greyed out in POS

---

## Section 3 — Floor & Tables

- [ ] Navigate to **Floorplan**
- [ ] Create floor: `Ground Floor`
- [ ] Create 3 tables: `T1`, `T2`, `T3` on Ground Floor, capacity 4
- [ ] Verify all 3 tables show as **Available** on the floor map

---

## Section 4 — Staff & Roles

- [ ] Navigate to **Settings > Team**
- [ ] Invite staff: `Amaka Obi` (waiter role), `Chidi Nwosu` (kitchen role)
- [ ] Verify invitations sent
- [ ] Log in as `Amaka Obi` — verify she can access POS but NOT Settings or Reports

---

## Section 5 — Open Shift

- [ ] Log back in as the **manager** account
- [ ] Navigate to **Shifts**
- [ ] Open a new shift: Opening cash `₦50,000`
- [ ] Verify shift status shows **OPEN** with the correct opening balance

---

## Section 6 — Full Order Flow (Happy Path)

### 6.1 Open Table & Take Order
- [ ] Navigate to **POS**
- [ ] Select `T1` (Table 1)
- [ ] Add `Jollof Rice` × 2
- [ ] Add `Chilled Chapman` × 1
- [ ] Verify order total: `₦8,200`
- [ ] Add note to item: `"No salt on rice"`

### 6.2 Send to Kitchen & Bar
- [ ] Click **Send Order**
- [ ] Navigate to **Kitchen Display**
- [ ] Verify `Jollof Rice` ticket appears on the KDS
- [ ] Navigate to **Bar Display**
- [ ] Verify `Chilled Chapman` ticket appears on the BDS

### 6.3 Kitchen Prepares & Serves
- [ ] On KDS: Mark `Jollof Rice` as **Ready**
- [ ] Verify the ticket moves to the "Ready" column
- [ ] On BDS: Mark `Chilled Chapman` as **Ready**

### 6.4 Apply Discount (Optional)
- [ ] Return to POS > T1
- [ ] Apply a **10% manager discount**
- [ ] Verify new total: `₦7,380`
- [ ] Check **Audit Logs** — discount event must appear with actor and amount

### 6.5 Payment
- [ ] Click **Request Bill**
- [ ] Select **Split Payment**: ₦3,690 cash + ₦3,690 card (Paystack)
- [ ] Process Paystack payment using test card `4084 0841 1840 1`
- [ ] Verify payment status: **Confirmed**
- [ ] Verify order status: **Closed**
- [ ] Verify table T1 status: **Available**

---

## Section 7 — Void Flow

- [ ] Open a new order on T2
- [ ] Add `Jollof Rice` × 1
- [ ] Send to kitchen
- [ ] Void the entire order with reason `"Customer left"`
- [ ] Verify order status: **Voided**
- [ ] Verify `Jollof Rice` ticket on KDS is removed
- [ ] Check Audit Logs — void event must appear

---

## Section 8 — Inventory

- [ ] Navigate to **Inventory**
- [ ] Create stock item: `Rice` (unit: `kg`, quantity: `50`)
- [ ] Record wastage: `2kg` with reason `"Burned"`
- [ ] Verify stock: `48kg`
- [ ] Create a Purchase Order: `20kg Rice` from supplier `Golden Grains Ltd.`
- [ ] Receive the PO — verify stock updates to `68kg`

---

## Section 9 — Close Shift

- [ ] Navigate to **Shifts**
- [ ] Click **Close Shift**
- [ ] Enter closing cash count: `₦53,690` (opening ₦50,000 + cash collected ₦3,690)
- [ ] Verify variance is `₦0`
- [ ] Confirm and close shift
- [ ] Verify shift status: **CLOSED**

---

## Section 10 — Stock Count

- [ ] Navigate to **Inventory > Stock Count**
- [ ] Start a new count session
- [ ] Enter counted: `Rice → 65kg` (variance of -3kg from expected)
- [ ] Submit count
- [ ] Manager approves — verify inventory adjusted to `65kg`

---

## Section 11 — Reports & Dashboard

- [ ] Navigate to **Dashboard**
- [ ] Verify today's revenue shows `₦7,380`
- [ ] Navigate to **Reports > Sales**
- [ ] Filter to today — verify `₦7,380` total
- [ ] Navigate to **Reports > Payments**
- [ ] Verify split payment (cash + card) is correctly listed
- [ ] Navigate to **Reports > Staff**
- [ ] Verify the waiter (`Amaka Obi`) shows their orders and performance

---

## Section 12 — Notifications

- [ ] Trigger a low-stock alert by reducing Rice to below threshold
- [ ] Verify the **notification bell** shows a badge
- [ ] Open notification drawer — verify the alert is displayed
- [ ] Mark as read — verify badge clears

---

## Section 13 — Offline POS

- [ ] On the POS screen, disable network (airplane mode / disconnect Wi-Fi)
- [ ] Verify the **amber offline banner** appears
- [ ] Create an order for T3 (cash only — as per offline policy)
- [ ] Re-enable network
- [ ] Verify the **sync banner** briefly appears and the order syncs to the server
- [ ] Verify the order appears in the backend Orders list

---

## Section 14 — Billing

- [ ] Navigate to **Settings > Billing**
- [ ] Verify the current plan shows as **Pro (Trialing)**
- [ ] View usage indicators — verify branch and user counts are correct

---

## Section 15 — Security

- [ ] Log in with wrong password 5 times — verify **429 Too Many Requests** (rate limiting)
- [ ] Navigate to **Settings > Audit Logs**
- [ ] Verify all significant actions from this test session are logged (login, order created, void, payment, discount, shift opened/closed)

---

## Acceptance Criteria

| Criterion | Result |
|---|---|
| Full order flow completed without errors | |
| Kitchen and bar displays update in real-time | |
| Payments processed correctly (split + full) | |
| Inventory updated after every relevant event | |
| Shift summary is accurate | |
| Reports reflect real data | |
| Offline mode works and syncs correctly | |
| Audit log shows every sensitive action | |
| Rate limiting blocks brute-force login | |
| Notifications fire for relevant events | |

> **MVP is declared complete when ALL acceptance criteria are checked.** 🎉
