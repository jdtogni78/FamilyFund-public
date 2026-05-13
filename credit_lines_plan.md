# Credit Lines — Planning Document

**Status:** Draft / ideas — not yet scoped for implementation
**Last Updated:** 2026-05-13
**Branch:** `claude/plan-credit-lines-cCjWG`

---

## 1. Goal

Allow a beneficiary to **borrow shares against their own account balance** and pay them back over a chosen period. The borrowing unit is **shares, not money** — the borrower owes shares back to their own account, independent of share price movement. A single person may have **multiple concurrent credit lines** across the accounts they own.

The system should:
1. Open a credit line when an account holder requests to borrow N shares (or value-equivalent) for a chosen term.
2. Generate an amortized **payment plan in shares** over the requested term.
3. Allow the plan to be **readjusted** later (extend/shorten the term, recompute the schedule on the remaining balance).
4. Enforce that **borrowed shares cannot exceed the OWN-share balance** at the time the line is drawn.

---

## 2. What already exists

The codebase already has the *primitives* but no orchestration around them:

| Primitive | Location | Status |
|---|---|---|
| Transaction type `BOR` (Borrow) | `app/Models/TransactionExt.php:31` | Defined, displayed as "Borrow" |
| Transaction type `REP` (Repay) | `app/Models/TransactionExt.php:32` | Defined, displayed as "Repay" |
| `account_balances.type` with values `OWN` / `BOR` | migration `2022_01_07_130430_create_account_balances_table.php` | Schema in place |
| `AccountExt::allSharesAsOf()` returns OWN and BOR balances keyed by type | `app/Models/AccountExt.php:92-116` | Working |
| `AccountExt::sharesAsOf()` | `app/Models/AccountExt.php:118-129` | **TODO at line 126**: `// TODO: discount BORROW!!` — BOR shares are read but not yet subtracted from spendable balance |
| `depositedValueBetween()` already treats BOR as withdrawal and REP as deposit | `app/Models/AccountExt.php:61-88` | Working |

**Implication:** the data model is mostly in place. The missing pieces are (a) the credit-line aggregate that groups BOR/REP transactions into a single facility with a term and a schedule, and (b) the discount of BOR shares from the spendable balance.

---

## 3. Proposed domain model

### 3.1 New model: `AccountCreditLine`

A first-class entity (not a pivot) representing one credit facility against one account.

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `account_id` | FK → `accounts.id` | The account being borrowed against; the account *owner* is the borrower. |
| `principal_shares` | decimal(19,4) | Shares originally drawn. |
| `outstanding_shares` | decimal(19,4) | Computed/cached from BOR − REP transactions linked to this line. |
| `term_months` | int | Current term length (mutable on readjustment). |
| `origination_date` | date | When the line was opened (governs the share price used to value the draw, if needed). |
| `maturity_date` | date | `origination_date + term_months`. Recomputed on readjustment. |
| `payment_frequency` | enum | `monthly` / `quarterly` / `annual` (default monthly). |
| `status` | enum | `active` / `paid_off` / `defaulted` / `cancelled`. |
| `interest_rate` | decimal(7,4) nullable | See §6 — open question. May be NULL / 0 for v1. |
| `descr` | string | Free-text reason. |
| timestamps | | |

**Migration name (suggested):** `create_account_credit_lines_table`.

### 3.2 New model: `CreditLinePayment`

The amortization schedule. Each row is one scheduled payment (a planned future `REP` transaction).

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `account_credit_line_id` | FK | |
| `due_date` | date | |
| `shares_due` | decimal(19,4) | |
| `principal_shares` | decimal(19,4) | Optional split if interest is introduced. |
| `interest_shares` | decimal(19,4) | Optional. |
| `status` | enum | `scheduled` / `paid` / `partial` / `late` / `cancelled`. |
| `paid_transaction_id` | FK → `transactions.id` nullable | The REP transaction that satisfied this payment. |
| timestamps | | |

On **readjustment** the line's remaining scheduled rows are deleted (or marked `cancelled`) and rebuilt from the new term against `outstanding_shares`.

### 3.3 Link from `Transaction` → `AccountCreditLine`

Add `account_credit_line_id` (nullable FK) to `transactions`. A BOR/REP transaction created in service of a credit line points to it. Other BOR/REP transactions (one-offs) remain valid with NULL.

---

## 4. Business rules

1. **Draw cap.** At the moment of drawing, `principal_shares ≤ AccountExt::sharesAsOf(today)` — the borrower cannot borrow more shares than they own. (This requires fixing the TODO at `AccountExt.php:126` so BOR shares already on the books are subtracted from "available to borrow".)
2. **Shares not money.** The credit line tracks shares throughout. If the user wants cash, the BOR transaction may be paired with a SAL (sale) of those borrowed shares — that's a UI concern, not a model concern. The debt remains denominated in shares.
3. **Repayment.** Each REP transaction reduces `outstanding_shares` by `transaction.shares`. The corresponding `CreditLinePayment` row is marked `paid` (or `partial` if `shares < shares_due`).
4. **Multiple lines.** No limit. The draw cap is computed against `OWN − Σ outstanding_shares` across all active lines on that account.
5. **Cross-account lines (clarify w/ user).** A person can own multiple accounts; each line is `account_id`-scoped. Inter-account collateralization is out of scope unless explicitly requested.
6. **Settlement / payoff.** When `outstanding_shares` reaches zero, status → `paid_off`. The cumulative AccountBalance row of type `BOR` returns to zero at that point.

---

## 5. Amortization (payment plan calculation)

For v1 with **no interest**, the schedule is trivially:

```
shares_per_payment = outstanding_shares / number_of_payments
number_of_payments = term_months × (1 for monthly, 1/3 for quarterly, …)
```

Last payment absorbs the rounding remainder so the sum exactly equals `outstanding_shares`.

For a future version with interest (see §6) use standard amortization on a shares basis:

```
P = principal_shares
r = periodic_interest_rate         (interest_rate / periods_per_year)
n = total_periods
payment = P × r / (1 − (1+r)^−n)
```

Interest accrues **in shares**, which is conceptually a "share-rent" — equivalent to surrendering some of the share appreciation that would otherwise accrue to OWN.

### Readjustment

A readjustment is a recomputation:
1. Compute current `outstanding_shares` from BOR/REP transactions to date.
2. Replace `term_months`, `maturity_date`, `payment_frequency` as requested.
3. Cancel any remaining `scheduled` payment rows.
4. Generate a fresh schedule against the current `outstanding_shares` and new term.

Recorded as an event (optional `credit_line_adjustments` audit table) so history is preserved.

---

## 6. Open questions for the user

These should be answered before implementation begins:

1. **Interest.** Should v1 charge interest in shares? Zero-interest first, add later?
2. **Who is the counterparty?** The user described "borrowing against your own shares". Mechanically, who *holds* the shares while borrowed?
   - (a) The shares stay on the account but are flagged `BOR` (current schema suggests this — the `AccountBalance.type='BOR'` row offsets the `OWN` row).
   - (b) The fund itself holds them in escrow.
   - The current schema points to (a); confirm.
3. **Cash flow.** When a user "borrows", do they receive cash (implying an automatic SAL of those shares), or do they receive the shares to use elsewhere? The user said "you only can take money out of the balance of shares" — sounds like (a): borrow + immediate sale → cash to user.
4. **Default / overdue policy.** What happens when a scheduled payment is missed? Penalty? Auto-extend? Status change only?
5. **Cross-account borrowing.** The phrase "against each other" was ambiguous. Confirm: each line is against exactly one account that the person owns, but a person may have many lines (one per account, or many per account)?
6. **Authorization.** Should account owners be able to open credit lines unilaterally, or does this need admin/fund-manager approval (like the existing matching-rule flow)?
7. **Reporting.** Should credit lines appear on the quarterly PDF report? On the account dashboard?

---

## 7. Implementation sketch (only after §6 is resolved)

Rough order of work, following the repo's repository + `*Ext` pattern:

1. **Fix `AccountExt::sharesAsOf()`** so BOR shares discount OWN (`app/Models/AccountExt.php:126`). Add a test before changing behavior — many calculations depend on this.
2. **Migrations**
   - `create_account_credit_lines_table`
   - `create_credit_line_payments_table`
   - `add_account_credit_line_id_to_transactions`
3. **Models** following the `Account` / `AccountExt` pattern:
   - `AccountCreditLine` + `AccountCreditLineExt` (the Ext hosts amortization + readjust logic)
   - `CreditLinePayment` (likely no Ext needed)
4. **Repositories** following `AccountRepository`:
   - `AccountCreditLineRepository`
   - `CreditLinePaymentRepository`
5. **Service / domain logic** (could live on the Ext model or as a dedicated service):
   - `open(account, shares, term, frequency)` → creates line, BOR transaction, schedule
   - `repay(line, shares, date)` → creates REP transaction, advances schedule
   - `readjust(line, new_term, new_frequency)` → recomputes schedule
   - `recomputeOutstanding(line)` → from transactions
6. **Controllers** under `app/Http/Controllers/WebV1/`:
   - `AccountCreditLineControllerExt` — CRUD + draw + readjust actions
   - Follow `AccountMatchingRuleControllerExt` as a structural template
7. **Form requests** in `app/Http/Requests/`:
   - `CreateAccountCreditLineRequest`, `UpdateAccountCreditLineRequest`, `ReadjustCreditLineRequest`, `RepayCreditLineRequest`
8. **Views** under `resources/views/account_credit_lines/` (index, create, show, edit, fields, table) — InfyOm scaffold compatible.
9. **API endpoints** under `app/Http/Controllers/API/` if mobile/external clients need them.
10. **Tests**
    - Repository test (CRUD)
    - Feature test for the draw flow (cap enforcement, balance impact)
    - Feature test for repayment (schedule advance, payoff status)
    - Feature test for readjustment (schedule rebuild, term change)
    - Golden-data integration: a small fund with a draw, partial repay, readjust, full repay sequence.

---

## 8. Risks and watch-items

- **Existing TODO in `AccountExt::sharesAsOf()`.** Many places call this; changing semantics (OWN − BOR) is the right fix but will move numbers across reports and tests. Plan a test-suite pass before merging.
- **Historical (`as_of`) views** must continue to work. `AccountBalance.start_dt / end_dt` already supports temporal queries; credit-line outstanding balances must be reconstructable for any past date.
- **Decimal precision.** Shares are decimal(19,4); rounding in the amortization schedule must sum to the principal exactly (use a remainder-on-last-payment scheme).
- **Race conditions on the draw cap.** Two simultaneous draws could each individually fit but exceed the cap together. Use a DB transaction with `SELECT … FOR UPDATE` on the account row when creating a credit line.
