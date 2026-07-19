# Customer/Supplier Ledger + Sale-Edit Fixes — Design

Date: 2026-07-19
Branch: `customer_closing_balance_in_invoice`
Status: approved by user (sections 1–3 approved in conversation)

## Scope

Three deliverables:

1. **Ledger (statement of account) for customers and suppliers** — a proper running-balance
   ledger modeled on the reference PDF (`Custom Statement-2374.pdf`): chronological voucher
   rows with item detail, Debit/Credit/Balance columns, opening/closing balance, and an
   aging footer.
2. **Bug fix: editing a sale must not change its date.**
3. **Bug fix: editing a sale must not consolidate its payment history into one row.**

No database schema changes. All work follows the existing report-module pattern
(Approach A: new endpoints in `Reports.php`, new report models, one new view).

## Decisions made (with user)

- Ledger style: **full turnover** — every invoice debits its full total; every real payment
  credits, including cash paid at sale time (invoice 1000 paid 600 cash = Debit 1000 row +
  Credit 600 row).
- Access: **per-row "Ledger" button** on the Customers and Suppliers manage grids (opens in
  a new tab, default range = last 3 months). No separate Reports-menu entry.
- Page capabilities: **printable view** + **From/To date filter on the page** (opening
  balance recomputes as of From). No spreadsheet export.
- Layout fidelity: **full replication of the example PDF**, including per-item detail rows,
  green-highlighted payment/return rows, closing balance, and the **aging footer**.
- Date on edit: **keep unless explicitly changed** (both edit paths).
- Payments on edit: **preserve existing rows, append only new payments**. No UI for deleting
  prior payments from the register.

## 1. Ledger

### 1.1 Data sources (customer)

| Ledger row | Source | Column |
|---|---|---|
| Opening Balance | `ospos_customers.init_balance` + net of all activity before From | Balance only |
| Invoice (sale total > 0) | `ospos_sales` + `ospos_sales_items` at `sale_time`, with item detail lines | Debit = invoice total |
| Return (sale total < 0) | same (negative-quantity sales) | Credit = abs(total) |
| Payment | `ospos_sales_payments` at `payment_time`, **excluding `payment_type = 'Due'`**, net of `cash_refund` | Credit (negative payment → Debit) |

- Rows ordered chronologically (date, then sale_id/payment_id).
- Running balance = opening + Σ debits − Σ credits.
- Opening balance = `init_balance` + (Σ sale totals dated before From) − (Σ non-Due
  payments dated before From). Payments are dated by their own `payment_time`, so a recent
  payment against an old invoice lands on its actual payment date.
- 'Due' rows are the unpaid-amount marker in this fork, not money movement — always excluded.

### 1.2 Data sources (supplier — mirror)

| Ledger row | Source | Column |
|---|---|---|
| Opening Balance | `ospos_suppliers.init_balance` + net before From | Balance only |
| Purchase (receiving) | `ospos_receivings` + `ospos_receivings_items` at `receiving_time`, with item detail | Debit = receiving total |
| Purchase return (negative receiving) | same | Credit |
| Payment to supplier | `ospos_suppliers_payments` (including on-account rows with `receiving_id = 0`) at `payment_date` | Credit |

Balance = what the business owes the supplier. Both ledgers use identical columns and
layout (deliberately NOT the reversed double-entry convention: balance rises with
transactions, falls with payments, on both sides).

### 1.3 Code shape

- New report models: `application/models/reports/Ledger_customer.php` and
  `application/models/reports/Ledger_supplier.php`, extending the existing `Report` base.
  Each exposes:
  - `getOpeningBalance($person_id, $start_date)`
  - `getLedgerRows($person_id, $start_date, $end_date)` — date-ordered voucher feed with
    nested item lines.
- Controller actions in `application/controllers/Reports.php`:
  - `customer_ledger($start_date, $end_date, $customer_id)`
  - `supplier_ledger($start_date, $end_date, $supplier_id)`
  Running balance, totals, and aging computed in the controller (same style as
  `specific_customer_statement`).
- Routes in `application/config/routes.php` following the existing `specific_*` pattern:
  - `reports/customer_ledger/<start>/<end>/<person_id>`
  - `reports/supplier_ledger/<start>/<end>/<person_id>`
- Existing statement reports (`specific_customer_statement` / `specific_supplier_statement`)
  remain untouched.

### 1.4 View & UX

One shared view `application/views/reports/ledger.php`; a `$ledger_type` flag switches
labels ("Customer Statement" / "Supplier Statement", voucher = invoice # / receiving #).

Layout (matching the reference PDF):

1. **Header**: company name, VAT/tax id, address, phone | email (from `Appconfig`, same
   source as the invoice view); title; `From: … To: …`; person block (name | company,
   phone | email, address); **Opening Balance**.
2. **Table**: `Date | Voucher | Product | Qty | Unit Price | Total Price | Debit | Credit | Balance`.
   - Invoice rows: voucher cell rowspans its item lines; single Debit and Balance per voucher.
   - Payment/return rows highlighted green; payment description = type + reference/comment.
   - Voucher label: `invoice_number` when set, else `POS <sale_id>`; receivings `RECV <id>`.
3. **Footer**: `Closing Balance` (bold, red, right-aligned), note line, aging table.
4. **Print**: print button + print CSS (clean A4, repeating table header), like existing
   statement views.
5. **Date filter**: From/To pickers + Refresh button navigating to the same route with new
   dates. Default range from the manage-screen button: last 3 months.

Access buttons: per-row "Ledger" action on Customers and Suppliers manage grids, opening
the ledger in a new tab. Permission gating: existing customers/suppliers module grants +
the reports grant already used by the statements. No new permission rows or migrations.

### 1.5 Aging footer

Columns: `CURRENT | 1-30 DAYS PAST DUE | 31-60 | 60-90 | OVER 90 | Amount Due`.

Algorithm: **FIFO allocation over the person's full history** (not just the visible range):

- All credits ever (payments + returns), applied oldest-first against debits oldest-first;
  the opening `init_balance` counts as the oldest debit.
- Unpaid remainder of each invoice is bucketed by invoice age relative to the To date:
  0–30 days = CURRENT, 31–60 = "1–30 past due", 61–90 = "31–60", 91–120 = "60–90",
  older = "OVER 90".
- Excess credit (overpayment) appears as a negative amount in CURRENT.
- Invariant: buckets sum exactly to Amount Due = closing balance.

Note: the reference PDF's exact bucket values are not reproducible from its visible data
(the source system likely uses per-invoice due-date terms); FIFO is the standard
receivables-aging method and was approved as the chosen semantics. Same logic for supplier
payables.

## 2. Bug fix: sale date changes on edit

Two broken paths:

**a) Edit dialog** — `Sales::save()` (`application/controllers/Sales.php:745-751`)
re-parses the posted `date` field on every save. A PHP↔moment.js format mismatch between
`to_datetime()` (renders the field) and the datetimepicker (reformats it), or a failed
`date_create_from_format()` returning FALSE, silently shifts/corrupts `sale_time`.

Fix:
- `application/views/sales/form.php` additionally submits the original `sale_time` as a
  hidden field.
- Controller writes `sale_time` only when the posted date, parsed, differs from the
  original; if unchanged or parsing fails, `sale_time` is omitted from the update.
- Fix the render/parse round-trip so deliberate date edits parse with the same format
  string the picker displays.

**b) Register re-complete** — `Sale::save()` (`application/models/Sale.php:667`)
unconditionally sets `sale_time = date('Y-m-d H:i:s')`, including on the UPDATE branch.

Fix: when `$sale_id != -1`, remove `sale_time` from the update data. New sales still get
`now()`.

## 3. Bug fix: payment history consolidated on edit

The edit-dialog path (`Sale::update`, `Sale.php:586-645`) already preserves individual
payments. The damage is in the register reload/re-complete path:

1. `Sale::save()` → `clear_suspended_sale_detail()` (`Sale.php:1477`) deletes ALL
   `sales_payments` rows for the sale;
2. payments are re-inserted from the session, where `Sale_lib::add_payment()`
   (`application/libraries/Sale_lib.php:370-388`) had merged them by payment type.

Fix (per "preserve rows, append new"):
- On the UPDATE branch, stop deleting `sales_payments` (sale items still rebuilt as today).
- When a completed sale is loaded into the register, existing payments carry their
  `payment_id`; on completion, only session payments **without** a `payment_id` (added
  during this edit) are INSERTed. Existing rows keep id, type, amount, `payment_time`.
- Session merging by type still applies only to new payments added within one register
  session (original intended behavior).
- Suspended-sale flow unchanged (suspended sales have no real payment history to protect).

**Pre-requisite check (one-time, scripted):** verify the live DB's `ospos_sales_payments`
has the migrated schema — `payment_id` auto-increment PK (from
`application/migrations/sqlscripts/3.3.0_paymenttracking.sql`, then renamed columns +
`cash_refund` from `3.3.0_refundtracking.sql`). If the table still has the stale
`PRIMARY KEY (sale_id, payment_type)` from `database/tables.sql`, same-type payments cannot
coexist and the payment-tracking migration must run first.

## 4. Verification plan

- **Ledger:** seed a test customer with `init_balance`, sales across 4 months (fully-paid
  cash sale, partial cash + Due sale, return) and standalone payments. Verify: opening
  balance as of From; per-row running balance; closing balance equals the customer's
  outstanding; aging buckets sum to closing balance. Mirror test for a supplier with
  receivings, a purchase return, and on-account payments (`receiving_id = 0`).
- **Date fix:** edit a sale changing only the comment → `sale_time` unchanged in DB;
  deliberately change the date in the dialog → new date saved; reload into register and
  re-complete → date unchanged.
- **Payments fix:** sale with 3 payments (2× Cash on different days + 1 Due) → edit via
  register, add one new payment → DB shows 4 rows, original ids/types/amounts/dates intact;
  ledger shows each payment on its own date.
- **Regression:** existing statement reports still render; new-sale flow (create, pay,
  complete) unaffected; suspended-sale suspend/resume unaffected.

## 5. Out of scope

- Spreadsheet/CSV export of the ledger.
- Deleting or editing individual prior payments from the register UI.
- Reports-menu entries for the ledger.
- Any change to the existing statement reports.
- Double-entry journal tables or invoice-level payment allocation storage.
