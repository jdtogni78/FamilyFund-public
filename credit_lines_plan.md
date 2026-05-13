# Credit Lines — Planning Document

**Status:** Draft / ideas — not yet scoped for implementation
**Last Updated:** 2026-05-13 (rev 2 — incorporates user clarifications)
**Branch:** `claude/plan-credit-lines-cCjWG`

---

## 1. Goal

Allow a beneficiary to **borrow shares against their own account balance** and pay them back over a chosen period. The borrowing unit is **shares, not money** — the borrower owes the same *number of shares* back, regardless of how the share price moves in between. There is **no interest, no collateral, no default penalties**. A single person may have **multiple concurrent credit lines**, each scoped to a single account they own.

The system should:
1. Open a credit line when an account holder requests to borrow N shares for a chosen term.
2. Move the corresponding **cash out of the fund** to the borrower (see §4 — this is a new cash-flow path the system does not currently support).
3. Generate a **payment plan in shares** over the requested term.
4. Allow the plan to be **readjusted** later (extend/shorten the term, recompute the schedule on the remaining balance).
5. Enforce that **borrowed shares cannot exceed the OWN-share balance** at the time the line is drawn.
6. Track the **projected vs. actual payoff trajectory** and surface it on account/fund pages and quarterly reports.
7. Send **configurable reminders and delay notifications** by email.

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
| `status` | enum | `active` / `paid_off` / `cancelled`. (No `defaulted` — see §6.) |
| `descr` | string | Free-text reason. |
| timestamps | | |

No `interest_rate` column — the design is explicitly interest-free. Borrower owes the same share count back; share-price drift is the fund's exposure on the cash leg (see §4).

**Migration name (suggested):** `create_account_credit_lines_table`.

### 3.2 New model: `CreditLinePayment`

The amortization schedule. Each row is one scheduled payment (a planned future `REP` transaction).

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `account_credit_line_id` | FK | |
| `due_date` | date | |
| `shares_due` | decimal(19,4) | |
| `status` | enum | `scheduled` / `paid` / `partial` / `late` / `cancelled`. (`late` is informational only — no penalty applies.) |
| `paid_transaction_id` | FK → `transactions.id` nullable | The REP transaction that satisfied this payment. |
| timestamps | | |

On **readjustment** the line's remaining scheduled rows are deleted (or marked `cancelled`) and rebuilt from the new term against `outstanding_shares`.

### 3.3 Link from `Transaction` → `AccountCreditLine`

Add `account_credit_line_id` (nullable FK) to `transactions`. A BOR/REP transaction created in service of a credit line points to it. Other BOR/REP transactions (one-offs) remain valid with NULL.

---

## 4. Business rules

1. **Draw cap.** At the moment of drawing, `principal_shares ≤ AccountExt::sharesAsOf(today)` — the borrower cannot borrow more shares than they own. (This requires fixing the TODO at `AccountExt.php:126` so BOR shares already on the books are subtracted from "available to borrow".)
2. **Shares, not money — but cash *does* move.** The debt is denominated in shares (same number out, same number back, regardless of price). At draw time the system must also **move cash out of the fund** equal to `principal_shares × share_price_at_draw`. This is a **new cash-flow path** the fund does not currently support and is a first-class part of this feature, not a UI concern:
   - The fund's cash position (and therefore portfolio total) drops by the disbursed amount.
   - On repayment, cash flows back into the fund equal to `shares_repaid × share_price_on_repayment_date` (price drift = fund's exposure).
   - We need a way to **record cash leaving the fund** (and returning) without it being modeled as a share purchase/sale. Likely a new transaction type or a new ledger entry on the fund/portfolio side. See §8 risks.
3. **Repayment.** Each REP transaction reduces `outstanding_shares` by `transaction.shares`. The corresponding `CreditLinePayment` row is marked `paid` (or `partial` if `shares < shares_due`). Repayment is funded by the borrower depositing cash; that cash returns to the fund.
4. **Multiple lines.** No limit. The draw cap is computed against `OWN − Σ outstanding_shares` across all active lines on that account.
5. **One account per line.** Each credit line is scoped to a single account; there is no cross-account collateral or netting. A person who owns multiple accounts can open lines on any/all of them independently.
6. **No collateral, no default, no penalty.** A missed payment does not change the debt or incur fees; it only changes the `late` flag on schedule rows and triggers notifications (§7). The line stays `active` until shares are fully repaid.
7. **Settlement / payoff.** When `outstanding_shares` reaches zero, status → `paid_off`. The cumulative AccountBalance row of type `BOR` returns to zero at that point.

---

## 5. Amortization (payment plan calculation)

The schedule is a simple even split of shares across the term:

```
n_payments         = term_months × periods_per_month       (1, 1/3, 1/12 …)
shares_per_payment = outstanding_shares / n_payments
```

Last payment absorbs the rounding remainder so the sum equals `outstanding_shares` exactly to decimal(19,4) precision.

There is no interest computation. The cash side floats with share price independently and is not part of the schedule.

### Readjustment

A readjustment is a recomputation:
1. Compute current `outstanding_shares` from BOR/REP transactions to date.
2. Replace `term_months`, `maturity_date`, `payment_frequency` as requested.
3. Cancel any remaining `scheduled` payment rows.
4. Generate a fresh schedule against the current `outstanding_shares` and new term.

Record each adjustment in a `credit_line_adjustments` audit table (old term, new term, timestamp, actor) so the original plan and all revisions are preserved for the trajectory report (§7).

---

## 6. Resolved design decisions

These were initially open questions; the answers below now constrain the implementation.

| Question | Decision |
|---|---|
| Interest | **None.** Borrower owes the same share count back. No interest column, no interest accrual. |
| Counterparty / share holding | The shares stay on the borrower's account with a `BOR` `AccountBalance` row offsetting the `OWN` row (the existing schema). No escrow account. |
| Cash flow | Borrow disburses cash *out of the fund* to the borrower. Repayment returns cash *into the fund*. The fund's cash position changes — this is **new behavior** the system does not currently support (§4 rule 2, §8 risks). |
| Default / penalty | **None.** Missed payments only update the `late` schedule flag and trigger notifications. The debt is not increased, the status is not changed. |
| Cross-account collateral | **Not a concept.** Each line is against one account's own shares. Multiple lines per person across their accounts is fine; each is independent. |
| Authorization | (Still to confirm with user — current assumption: account owners can open lines unilaterally; mirror the existing transaction-creation auth.) |
| Reporting | **Required** on the account page, fund page, and quarterly PDFs for both account and fund. See §7. |
| Reminders | **Required** with configurable cadence; delay notifications also required. See §7. |

## 7. Reporting and notifications

### 7.1 Payoff trajectory

For each active credit line, compute and display:

- **Plan baseline** — the schedule as originally generated (or as last readjusted), giving `planned_payoff_date`.
- **Actual to date** — cumulative `shares_repaid` from cleared `REP` transactions.
- **Projected payoff** — extrapolation of the current run-rate of repayments (e.g., trailing-3-payments average) → `projected_payoff_date`.
- **Variance** — `projected_payoff_date − planned_payoff_date` (negative = ahead, positive = behind).

A small chart (cumulative-shares-repaid vs. plan line, x-axis = time) is the canonical visualization. The existing `quickchart` service in `docker-compose.yml` can render it.

### 7.2 Account page

On the account detail view (extending `AccountControllerExt`):
- List of credit lines on this account with: principal, outstanding, status, planned payoff, projected payoff, variance.
- Drill-down to each line showing the full schedule (paid/late/scheduled) and the trajectory chart.
- Upcoming due-date callout (next N payments).

### 7.3 Fund page

On the fund detail view:
- Aggregate outstanding `BOR` shares across all accounts in the fund.
- Cash outflow attributable to active credit lines (cash currently disbursed but not yet repaid).
- Count of active lines, count behind plan.

### 7.4 Quarterly reports

Add a new section to **both** report templates:
- **Account quarterly report**: each line's activity in the quarter (payments made, current outstanding, plan vs. projected payoff).
- **Fund quarterly report**: aggregate disbursements, repayments, outstanding share/cash exposure, and a list of lines currently behind plan.

Reports are generated via queue jobs and rendered with wkhtmltopdf — extend the existing templates rather than introducing a separate pipeline.

### 7.5 Reminders and delay notifications

Configurable per-account (or per-line) settings:

| Setting | Type | Default |
|---|---|---|
| `reminder_lead_days` | int (e.g., 7) | 7 — send a reminder N days before each scheduled payment. |
| `reminder_enabled` | bool | true |
| `delay_notification_enabled` | bool | true |
| `delay_notification_grace_days` | int | 3 — send "payment overdue" notification N days after a missed `due_date`. |
| `delay_notification_repeat_days` | int nullable | 14 — re-send every N days while still late; NULL = send once. |

Implementation notes:
- Store these settings on `AccountCreditLine` (or a sibling `credit_line_settings` table if defaults at the account level make sense — TBD).
- A scheduled job (Laravel scheduler + queue) runs daily, scans schedule rows, sends emails via the existing mail stack (MailHog in dev). Hook into the existing `queue:listen` infrastructure.
- Email templates live alongside existing quarterly-report templates.

---

## 8. Implementation sketch

Rough order of work, following the repo's repository + `*Ext` pattern:

1. **Fix `AccountExt::sharesAsOf()`** so BOR shares discount OWN (`app/Models/AccountExt.php:126`). Add a test before changing behavior — many calculations depend on this.
2. **Design fund-side cash flow** (see §9 risks). Decide how a "cash leaves the fund" event is recorded. Probably a new transaction type or a dedicated fund-cash ledger. This is a prerequisite for the draw operation.
3. **Migrations**
   - `create_account_credit_lines_table`
   - `create_credit_line_payments_table`
   - `create_credit_line_adjustments_table` (audit)
   - `add_account_credit_line_id_to_transactions`
   - (possibly) `create_fund_cash_movements_table` or similar — depends on (2)
4. **Models** following the `Account` / `AccountExt` pattern:
   - `AccountCreditLine` + `AccountCreditLineExt` (Ext hosts amortization, readjust, trajectory logic)
   - `CreditLinePayment`
   - `CreditLineAdjustment`
5. **Repositories** following `AccountRepository`:
   - `AccountCreditLineRepository`, `CreditLinePaymentRepository`, `CreditLineAdjustmentRepository`
6. **Service / domain logic**:
   - `open(account, shares, term, frequency)` → BOR transaction, schedule rows, fund cash-out entry
   - `repay(line, shares, date)` → REP transaction, schedule advance, fund cash-in entry
   - `readjust(line, new_term, new_frequency)` → cancel scheduled rows, regenerate, record adjustment row
   - `recomputeOutstanding(line)` → from transactions
   - `projectPayoff(line)` → trajectory + projected_payoff_date (§7.1)
7. **Controllers** under `app/Http/Controllers/WebV1/`:
   - `AccountCreditLineControllerExt` — CRUD + draw + readjust + repay actions
   - Follow `AccountMatchingRuleControllerExt` as a structural template
8. **Form requests** in `app/Http/Requests/`:
   - `CreateAccountCreditLineRequest`, `UpdateAccountCreditLineRequest`, `ReadjustCreditLineRequest`, `RepayCreditLineRequest`
9. **Views** under `resources/views/account_credit_lines/` (index, create, show, edit, fields, table) — InfyOm scaffold compatible. Plus trajectory chart partial reusable from account/fund pages.
10. **Reporting**
    - Extend account page (`AccountControllerExt`) and fund page with the §7.2/§7.3 sections.
    - Extend quarterly report templates with §7.4 sections.
11. **Reminder / delay-notification job** (§7.5)
    - Daily Laravel-scheduler job scans schedule rows.
    - Emails via existing mail stack (MailHog dev / SMTP prod).
12. **API endpoints** under `app/Http/Controllers/API/` if mobile/external clients need them.
13. **Tests**
    - Repository test (CRUD)
    - Feature test for the draw flow (cap enforcement, balance impact, fund cash-out)
    - Feature test for repayment (schedule advance, payoff status, fund cash-in)
    - Feature test for readjustment (schedule rebuild, adjustment audit row)
    - Feature test for trajectory calculation (projected payoff with mixed on-time/late payments)
    - Reminder/notification job test (emails sent at correct lead/grace days)
    - Golden-data integration: small fund with a draw, partial repay, readjust, full repay sequence — verify both account and fund quarterly report sections.

---

## 9. Risks and watch-items

- **Fund-side cash movement is new.** Today the fund's cash position changes only through portfolio activity (asset purchases/sales) and matching contributions. A credit-line draw moves cash *out of the fund* to a borrower with no corresponding asset purchase. We need a clean way to record this without breaking NAV math (`FundExt::shareValueAsOf()` uses `valueAsOf / sharesAsOf` — if disbursed cash is no longer counted in fund value, NAV will drop on draw). Design choices to evaluate:
  - Treat the outstanding receivable (cash owed back to fund) as an *asset* of the fund so NAV is unchanged at draw, then adjusts as share price drifts during the life of the loan.
  - Treat the disbursement as a real value loss to the fund and a value gain on repayment (simpler but distorts NAV with every draw/repay).
  - Recommend the first; the share-price drift then naturally appears as a fund-level P&L over the loan's life.
- **Existing TODO in `AccountExt::sharesAsOf()`.** Many places call this; changing semantics (OWN − BOR) is the right fix but will move numbers across reports and tests. Plan a test-suite pass before merging.
- **Historical (`as_of`) views** must continue to work. `AccountBalance.start_dt / end_dt` already supports temporal queries; credit-line outstanding balances must be reconstructable for any past date.
- **Decimal precision.** Shares are decimal(19,4); rounding in the amortization schedule must sum to the principal exactly (use a remainder-on-last-payment scheme).
- **Race conditions on the draw cap.** Two simultaneous draws could each individually fit but exceed the cap together. Use a DB transaction with `SELECT … FOR UPDATE` on the account row when creating a credit line.
- **Notification spam.** A long-late line with `delay_notification_repeat_days` set will keep emailing forever. Consider a hard cap (e.g., 6 notifications) or a "snooze" action on the account page.
