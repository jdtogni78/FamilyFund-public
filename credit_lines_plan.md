# Credit Lines — Planning Document

**Status:** Draft / ideas — not yet scoped for implementation
**Last Updated:** 2026-05-13 (rev 3 — multiple lines per account made explicit)
**Branch:** `claude/plan-credit-lines-cCjWG`

---

## 1. Goal

Allow a beneficiary to **borrow shares against their own account balance** and pay them back over a chosen period. The borrowing unit is **shares, not money** — the borrower owes the same *number of shares* back, regardless of how the share price moves in between. There is **no interest, no collateral, no default penalties**.

A **single account can hold any number of concurrent credit lines**, each with its own principal, term, schedule, status, and reminder settings. The lines are tracked independently end-to-end (open → repay → adjust → close) but share a single aggregate `BOR` balance row on the account (§5 rule 8).

The system should:
1. Open a credit line when an account holder requests to borrow N shares for a chosen term.
2. Move the corresponding **cash out of the fund** to the borrower (see §5 — this is a new cash-flow path the system does not currently support).
3. Generate a **payment plan in shares** over the requested term.
4. Allow the plan to be **readjusted** later (extend/shorten the term, recompute the schedule on the remaining balance).
5. Enforce that **borrowed shares cannot exceed the OWN-share balance** at the time the line is drawn.
6. Track the **projected vs. actual payoff trajectory** and surface it on account/fund pages and quarterly reports.
7. Send **configurable reminders and delay notifications** by email.

---

## 2. Use case tracker

High-level cases to design, build, and test against. Use the **ID** column to cross-reference in tests, commit messages, and PR descriptions. **Status:** `planned` for everything in this rev — flip to `in-progress` / `done` as work lands.

| ID    | Use case                                          | Actor          | Trigger / Input                                       | Expected outcome                                                                                       | Key changes / Notes                                                                       | Status  |
|-------|---------------------------------------------------|----------------|-------------------------------------------------------|--------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------|---------|
| UC-01 | Open first credit line on an account              | Account owner  | Choose shares + term + frequency, submit              | New `AccountCreditLine`, BOR transaction, full schedule generated, cash leaves fund                    | New model, draw service, fund cash-out path (§5 rule 2)                                   | planned |
| UC-02 | Open Nth additional line on same account          | Account owner  | Same as UC-01, with existing active lines             | Independent line; draw cap = `OWN − Σ outstanding across all active lines`; account-level BOR balance grows | §5 rules 4 + 5 + 8 (aggregated BOR, per-line bookkeeping)                                 | planned |
| UC-03 | Attempt to over-borrow                            | Account owner  | Request shares > available-to-borrow                   | Reject with clear error; nothing committed                                                              | Validation in CreateAccountCreditLineRequest + service-level recheck inside DB tx         | planned |
| UC-04 | Concurrent draws on same account                  | Two sessions   | Two draws at once that individually fit but collide   | Second draw rejected after lock                                                                         | `SELECT … FOR UPDATE` on account row (§10)                                                 | planned |
| UC-05 | Scheduled on-time payment                         | Account owner  | Pay shares_due on or before due_date, line specified  | REP transaction, schedule row → `paid`, line `outstanding_shares` ↓, cash returns to fund               | Repay service + payment-targeting (§5 rule 3)                                             | planned |
| UC-06 | Partial payment                                   | Account owner  | Pay less than shares_due                              | Schedule row → `partial`, balance carries to next; outstanding still reduced                            | Repay service handles partial state                                                       | planned |
| UC-07 | Extra / early payoff payment                      | Account owner  | Pay more than shares_due or full outstanding          | Excess applied to next scheduled rows; if fully covered, status → `paid_off`                            | Repay service applies excess in due-date order                                            | planned |
| UC-08 | Missed payment                                    | System         | `due_date` passes with no REP                         | Schedule row → `late`; no penalty; line still `active`                                                  | Daily scheduler job; no debt change (§5 rule 7)                                            | planned |
| UC-09 | Readjust term (extend)                            | Account owner  | Change `term_months` upward                           | Remaining schedule rows cancelled, regenerated against current outstanding; `credit_line_adjustments` audit row written | Adjustment service + audit table                                                          | planned |
| UC-10 | Readjust term (shorten)                           | Account owner  | Change `term_months` downward                         | Same as UC-09; per-payment share amount increases                                                       | Same path as UC-09                                                                        | planned |
| UC-11 | Repay line picker / default selection             | Account owner  | Submit REP without explicit line on multi-line account | UI / service picks default (earliest next due_date) but records the explicit `account_credit_line_id`   | UX decision in repay form (§5 rule 3)                                                     | planned |
| UC-12 | Cancel an unused line                             | Account owner  | Request cancel before any draw / on zero outstanding   | Status → `cancelled`; if shares were drawn but already repaid, allowed; otherwise blocked               | Cancel guard in service                                                                   | planned |
| UC-13 | Payoff completion                                 | Account owner  | Final REP brings outstanding to zero                  | Line status → `paid_off`; account BOR balance reduces by that line's contribution                       | Settlement logic (§5 rule 9)                                                              | planned |
| UC-14 | Account-page aggregate + per-line view            | Account owner  | View account detail                                   | Aggregate panel + per-line table + upcoming-due callout merged across lines + new-line button           | View work (§8.2)                                                                          | planned |
| UC-15 | Fund-page exposure view                           | Admin          | View fund detail                                      | Aggregate outstanding BOR shares + cash outflow + line counts + behind-plan count                       | View work (§8.3)                                                                          | planned |
| UC-16 | Quarterly report — account section                | System (queue) | Quarterly report job runs                             | New section listing each line's activity, outstanding, plan vs. projected payoff                        | Template extension (§8.4); wkhtmltopdf                                                    | planned |
| UC-17 | Quarterly report — fund section                   | System (queue) | Quarterly report job runs                             | New section with disbursements, repayments, behind-plan list                                            | Template extension (§8.4)                                                                 | planned |
| UC-18 | Reminder before due date                          | System         | `today + reminder_lead_days == due_date`              | Email to account owner                                                                                  | Daily scheduler + email template + per-line settings (§8.5)                               | planned |
| UC-19 | Delay notification after missed payment           | System         | `today − due_date == delay_notification_grace_days`   | Email to account owner; repeats every `delay_notification_repeat_days` until paid (with hard cap)       | Scheduler + dedup cap (§8.5 + §10 notification spam)                                       | planned |
| UC-20 | Configure reminders                               | Account owner  | Update reminder settings on a line                    | Persisted; next scheduler run respects new values                                                       | Settings on `AccountCreditLine` (§8.5)                                                    | planned |
| UC-21 | Historical (as-of) view                           | Admin          | Query account/fund as of past date                    | Per-line outstanding and account BOR balance reconstructable from BOR/REP transactions on that date     | Reuse existing temporal `AccountBalance` + transaction history                            | planned |
| UC-22 | Fund NAV during life of a line                    | System         | Share price moves while line is open                  | Fund NAV preserved at draw (receivable booked); price drift = fund P&L over loan's life                 | Fund cash-flow design (§10 first risk) — must be settled before UC-01 ships                | planned |
| UC-23 | Person with multiple accounts, each with lines    | Account owner  | Open lines on accounts A and B independently          | Each account caps independently; reports per-account; no cross-account netting                          | §5 rule 6                                                                                 | planned |
| UC-24 | `AccountExt::sharesAsOf()` discounts BOR          | System         | Any call to `sharesAsOf` on an account with BOR shares | Returns `OWN − BOR_aggregate`                                                                          | Fix existing TODO at `AccountExt.php:126`; precedes UC-01                                 | planned |

---

## 3. What already exists

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

## 4. Proposed domain model

### 4.1 New model: `AccountCreditLine`

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
| `status` | enum | `active` / `paid_off` / `cancelled`. (No `defaulted` — see §7.) |
| `descr` | string | Free-text reason. |
| timestamps | | |

No `interest_rate` column — the design is explicitly interest-free. Borrower owes the same share count back; share-price drift is the fund's exposure on the cash leg (see §5).

**Migration name (suggested):** `create_account_credit_lines_table`.

### 4.2 New model: `CreditLinePayment`

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

### 4.3 Link from `Transaction` → `AccountCreditLine`

Add `account_credit_line_id` (nullable FK) to `transactions`. A BOR/REP transaction created in service of a credit line points to it. Other BOR/REP transactions (one-offs) remain valid with NULL.

---

## 5. Business rules

1. **Draw cap.** At the moment of drawing, `principal_shares ≤ AccountExt::sharesAsOf(today)` — the borrower cannot borrow more shares than they own. (This requires fixing the TODO at `AccountExt.php:126` so BOR shares already on the books are subtracted from "available to borrow".)
2. **Shares, not money — but cash *does* move.** The debt is denominated in shares (same number out, same number back, regardless of price). At draw time the system must also **move cash out of the fund** equal to `principal_shares × share_price_at_draw`. This is a **new cash-flow path** the fund does not currently support and is a first-class part of this feature, not a UI concern:
   - The fund's cash position (and therefore portfolio total) drops by the disbursed amount.
   - On repayment, cash flows back into the fund equal to `shares_repaid × share_price_on_repayment_date` (price drift = fund's exposure).
   - We need a way to **record cash leaving the fund** (and returning) without it being modeled as a share purchase/sale. Likely a new transaction type or a new ledger entry on the fund/portfolio side. See §10 risks.
3. **Repayment is line-targeted.** Every REP transaction carries an `account_credit_line_id` FK (§4.3) so the system knows *which* line is being paid down. The borrower must select a target line when making a payment. The system may offer a default (e.g., earliest `origination_date`, or earliest next `due_date`) but the choice is always explicit and recorded. The REP reduces that line's `outstanding_shares` by `transaction.shares` and marks the corresponding `CreditLinePayment` row `paid` or `partial`. Repayment is funded by the borrower depositing cash; that cash returns to the fund.
4. **Multiple lines per account.** No limit. Each line is independent — its own term, schedule, status, adjustments, and reminder settings. They share a single account and therefore a single pool of available OWN shares (rule 5).
5. **Draw cap across all active lines.** Available-to-borrow = `OWN_shares(today) − Σ outstanding_shares` summed across **all `active` lines on this account** plus any other lines whose disbursement has cleared but repayment is incomplete. A new line cannot exceed this remainder. The check runs inside the same transaction as the draw (§10 race conditions).
6. **One account per line.** Each credit line is scoped to a single account; there is no cross-account collateral or netting. A person who owns multiple accounts can open lines on any/all of them independently.
7. **No collateral, no default, no penalty.** A missed payment does not change the debt or incur fees; it only changes the `late` flag on schedule rows and triggers notifications (§8). The line stays `active` until shares are fully repaid.
8. **Aggregated `BOR` balance, per-line bookkeeping.** The existing `account_balances` schema allows **one row per type per account per day** — so an account with N active lines still has exactly **one** `BOR` balance row, holding the **sum** of all lines' outstanding shares. Per-line outstanding is reconstructed from the BOR/REP transactions linked via `account_credit_line_id`. Concretely:
   - `account_balance.shares` (type=BOR) = Σ over all lines of (BOR − REP) transactions
   - `line.outstanding_shares` = Σ over that line's BOR/REP transactions only
   This avoids any schema change to `account_balances` and keeps the existing temporal-balance machinery intact.
9. **Settlement / payoff.** When a line's `outstanding_shares` reaches zero, its status → `paid_off`. The account-level `BOR` balance row only returns to zero when **all** lines on the account are paid off (or cancelled).

---

## 6. Amortization (payment plan calculation)

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

Record each adjustment in a `credit_line_adjustments` audit table (old term, new term, timestamp, actor) so the original plan and all revisions are preserved for the trajectory report (§8).

---

## 7. Resolved design decisions

These were initially open questions; the answers below now constrain the implementation.

| Question | Decision |
|---|---|
| Interest | **None.** Borrower owes the same share count back. No interest column, no interest accrual. |
| Counterparty / share holding | The shares stay on the borrower's account with a `BOR` `AccountBalance` row offsetting the `OWN` row (the existing schema). No escrow account. |
| Cash flow | Borrow disburses cash *out of the fund* to the borrower. Repayment returns cash *into the fund*. The fund's cash position changes — this is **new behavior** the system does not currently support (§5 rule 2, §10 risks). |
| Default / penalty | **None.** Missed payments only update the `late` schedule flag and trigger notifications. The debt is not increased, the status is not changed. |
| Cross-account collateral | **Not a concept.** Each line is against one account's own shares. Multiple lines per person across their accounts is fine; each is independent. |
| Authorization | (Still to confirm with user — current assumption: account owners can open lines unilaterally; mirror the existing transaction-creation auth.) |
| Reporting | **Required** on the account page, fund page, and quarterly PDFs for both account and fund. See §8. |
| Reminders | **Required** with configurable cadence; delay notifications also required. See §8. |

## 8. Reporting and notifications

### 8.1 Payoff trajectory

For each active credit line, compute and display:

- **Plan baseline** — the schedule as originally generated (or as last readjusted), giving `planned_payoff_date`.
- **Actual to date** — cumulative `shares_repaid` from cleared `REP` transactions.
- **Projected payoff** — extrapolation of the current run-rate of repayments (e.g., trailing-3-payments average) → `projected_payoff_date`.
- **Variance** — `projected_payoff_date − planned_payoff_date` (negative = ahead, positive = behind).

A small chart (cumulative-shares-repaid vs. plan line, x-axis = time) is the canonical visualization. The existing `quickchart` service in `docker-compose.yml` can render it.

### 8.2 Account page

On the account detail view (extending `AccountControllerExt`):
- **Aggregate panel** at the top: total `BOR` shares (sum across lines), available-to-borrow remaining, count of active lines, count behind plan.
- **Per-line table** listing every credit line on the account: principal, outstanding, status, planned payoff, projected payoff, variance. Sortable; defaults to oldest-first.
- **Drill-down** to each line showing the full schedule (paid/late/scheduled), the trajectory chart, the adjustment history, and the per-line reminder settings.
- **Upcoming due-date callout** merging the next N payments across **all lines** on the account, sorted by `due_date`, each row labeled with which line it belongs to.
- **New-line button** on the page, disabled when available-to-borrow is zero.

### 8.3 Fund page

On the fund detail view:
- Aggregate outstanding `BOR` shares across all accounts in the fund.
- Cash outflow attributable to active credit lines (cash currently disbursed but not yet repaid).
- Count of active lines, count behind plan.

### 8.4 Quarterly reports

Add a new section to **both** report templates:
- **Account quarterly report**: each line's activity in the quarter (payments made, current outstanding, plan vs. projected payoff).
- **Fund quarterly report**: aggregate disbursements, repayments, outstanding share/cash exposure, and a list of lines currently behind plan.

Reports are generated via queue jobs and rendered with wkhtmltopdf — extend the existing templates rather than introducing a separate pipeline.

### 8.5 Reminders and delay notifications

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

## 9. Implementation sketch

Rough order of work, following the repo's repository + `*Ext` pattern:

1. **Fix `AccountExt::sharesAsOf()`** so BOR shares discount OWN (`app/Models/AccountExt.php:126`). Add a test before changing behavior — many calculations depend on this.
2. **Design fund-side cash flow** (see §10 risks). Decide how a "cash leaves the fund" event is recorded. Probably a new transaction type or a dedicated fund-cash ledger. This is a prerequisite for the draw operation.
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
   - `projectPayoff(line)` → trajectory + projected_payoff_date (§8.1)
7. **Controllers** under `app/Http/Controllers/WebV1/`:
   - `AccountCreditLineControllerExt` — CRUD + draw + readjust + repay actions
   - Follow `AccountMatchingRuleControllerExt` as a structural template
8. **Form requests** in `app/Http/Requests/`:
   - `CreateAccountCreditLineRequest`, `UpdateAccountCreditLineRequest`, `ReadjustCreditLineRequest`, `RepayCreditLineRequest`
9. **Views** under `resources/views/account_credit_lines/` (index, create, show, edit, fields, table) — InfyOm scaffold compatible. Plus trajectory chart partial reusable from account/fund pages.
10. **Reporting**
    - Extend account page (`AccountControllerExt`) and fund page with the §8.2/§8.3 sections.
    - Extend quarterly report templates with §8.4 sections.
11. **Reminder / delay-notification job** (§8.5)
    - Daily Laravel-scheduler job scans schedule rows.
    - Emails via existing mail stack (MailHog dev / SMTP prod).
12. **API endpoints** under `app/Http/Controllers/API/` if mobile/external clients need them.
13. **Tests**
    - Repository test (CRUD)
    - Feature test for the draw flow (cap enforcement, balance impact, fund cash-out)
    - Feature test for repayment (schedule advance, payoff status, fund cash-in)
    - Feature test for readjustment (schedule rebuild, adjustment audit row)
    - Feature test for trajectory calculation (projected payoff with mixed on-time/late payments)
    - **Feature test for multiple concurrent lines on one account**: open 3 lines, verify draw cap is cumulative, repay against a specific line and verify only that line's `outstanding_shares` and schedule change, verify the account-level `BOR` `AccountBalance` row equals the sum across all 3 lines, verify the account page lists 3 lines independently, verify the upcoming-due-dates callout merges payments from all 3.
    - Reminder/notification job test (emails sent at correct lead/grace days)
    - Golden-data integration: small fund with a draw, partial repay, readjust, full repay sequence — verify both account and fund quarterly report sections.

---

## 10. Risks and watch-items

- **Fund-side cash movement is new.** Today the fund's cash position changes only through portfolio activity (asset purchases/sales) and matching contributions. A credit-line draw moves cash *out of the fund* to a borrower with no corresponding asset purchase. We need a clean way to record this without breaking NAV math (`FundExt::shareValueAsOf()` uses `valueAsOf / sharesAsOf` — if disbursed cash is no longer counted in fund value, NAV will drop on draw). Design choices to evaluate:
  - Treat the outstanding receivable (cash owed back to fund) as an *asset* of the fund so NAV is unchanged at draw, then adjusts as share price drifts during the life of the loan.
  - Treat the disbursement as a real value loss to the fund and a value gain on repayment (simpler but distorts NAV with every draw/repay).
  - Recommend the first; the share-price drift then naturally appears as a fund-level P&L over the loan's life.
- **Existing TODO in `AccountExt::sharesAsOf()`.** Many places call this; changing semantics (OWN − BOR) is the right fix but will move numbers across reports and tests. Plan a test-suite pass before merging.
- **Historical (`as_of`) views** must continue to work. `AccountBalance.start_dt / end_dt` already supports temporal queries; credit-line outstanding balances must be reconstructable for any past date.
- **Decimal precision.** Shares are decimal(19,4); rounding in the amortization schedule must sum to the principal exactly (use a remainder-on-last-payment scheme).
- **Race conditions on the draw cap.** Two simultaneous draws could each individually fit but exceed the cap together. Use a DB transaction with `SELECT … FOR UPDATE` on the account row when creating a credit line.
- **Notification spam.** A long-late line with `delay_notification_repeat_days` set will keep emailing forever. Consider a hard cap (e.g., 6 notifications) or a "snooze" action on the account page.
