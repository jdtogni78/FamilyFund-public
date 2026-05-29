# Credit Lines — Planning Document

**Status:** Draft / ideas — not yet scoped for implementation
**Last Updated:** 2026-05-13 (rev 7 — folded accepted items from plan_recommendations.md: admin-only writes, reversal flow, closure block, tax caveat, loans summary, share-value copy, imputed-interest field)
**Branch:** `claude/plan-credit-lines-cCjWG`
**Related sub-projects:**
- [`money_flow_plan.md`](../money_flow_plan.md) — Brazil ↔ US money flow, detection, recipient registry, and the isolated `App\MoneyFlow` subsystem. The fund-side cash flow described in §5 rule 2 and the receivable-handling design in §11 are owned by that doc.
- [`testing_plan.md`](../testing_plan.md) — Test strategy across unit / integration / browser layers, with every UC-* in this doc mapped to a named test in §4.1, plus reviewable-output and traceability conventions.

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
| UC-04 | Concurrent draws on same account                  | Two sessions   | Two draws at once that individually fit but collide   | Second draw rejected after lock                                                                         | `SELECT … FOR UPDATE` on account row (§11)                                                 | planned |
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
| UC-18 | Reminder before due date                          | System         | `today + reminder_lead_days == due_date`              | Email to account owner                                                                                  | Daily scheduler + email template + per-line settings (§8.6)                               | planned |
| UC-19 | Delay notification after missed payment           | System         | `today − due_date == delay_notification_grace_days`   | Email to account owner; repeats every `delay_notification_repeat_days` until paid (with hard cap)       | Scheduler + dedup cap (§8.6 + §11 notification spam)                                       | planned |
| UC-20 | Configure reminders                               | Account owner  | Update reminder settings on a line                    | Persisted; next scheduler run respects new values                                                       | Settings on `AccountCreditLine` (§8.6)                                                    | planned |
| UC-21 | Historical (as-of) view                           | Admin          | Query account/fund as of past date                    | Per-line outstanding and account BOR balance reconstructable from BOR/REP transactions on that date     | Reuse existing temporal `AccountBalance` + transaction history                            | planned |
| UC-22 | Fund NAV during life of a line                    | System         | Share price moves while line is open                  | Fund NAV preserved at draw (receivable booked); price drift = fund P&L over loan's life                 | Fund cash-flow design (§11 first risk) — must be settled before UC-01 ships                | planned |
| UC-23 | Person with multiple accounts, each with lines    | Account owner  | Open lines on accounts A and B independently          | Each account caps independently; reports per-account; no cross-account netting                          | §5 rule 6                                                                                 | planned |
| UC-24 | `AccountExt::sharesAsOf()` discounts BOR          | System         | Any call to `sharesAsOf` on an account with BOR shares | Returns `OWN − BOR_aggregate`                                                                          | Fix existing TODO at `AccountExt.php:126`; precedes UC-01                                 | planned |
| UC-25 | Auto-match REP — single active line               | System         | REP submitted on an account with exactly 1 active line | Assigned to that line, `credit_line_match_status = auto_matched`                                       | Matcher service (§5 rule 3)                                                               | planned |
| UC-26 | Auto-match REP — shares match exactly one line    | System         | REP shares == `shares_due` of exactly one active line | Assigned to that line, `auto_matched`                                                                  | Matcher priority 1 (§5 rule 3)                                                            | planned |
| UC-27 | Auto-match REP — cash match fallback              | System         | REP shares don't match but cash value does (one line) | Assigned to that line, `auto_matched`                                                                  | Matcher priority 2 (§5 rule 3)                                                            | planned |
| UC-28 | Auto-match REP — full-payoff outstanding match    | System         | REP shares == `outstanding_shares` of one active line | Assigned, line → `paid_off`                                                                            | Matcher priority 3 (§5 rule 3)                                                            | planned |
| UC-29 | Ambiguous REP                                     | System         | REP shares match `shares_due` on ≥2 active lines      | `credit_line_match_status = ambiguous`, FK NULL, transaction flagged, banner appears, alert email sent | §4.4, §5 rule 10, §8.2 banner, §8.6 mismatch alert                                        | planned |
| UC-30 | Unmatched REP                                     | System         | REP shares match no active line by any priority       | `credit_line_match_status = unmatched`, FK NULL, transaction flagged, banner appears, alert email sent | §4.4, §5 rule 10, §8.2 banner, §8.6 mismatch alert                                        | planned |
| UC-31 | Resolve flagged transaction                       | Account owner  | Clicks "Resolve" on banner, picks a target line       | `credit_line_match_status = manual`, FK set, line schedule advances, banner count drops                | Resolution screen + service action; idempotent                                            | planned |
| UC-32 | Email on transaction received                     | System         | User-submitted BOR/REP saved                          | Email to account owner with type, shares, value, target line (or "needs review" if flagged)            | Model-event hook on Transaction save (§8.6)                                               | planned |
| UC-33 | Email on transaction detected                     | System         | System-created BOR/REP saved (matcher, scheduler, late-sweep) | Email to account owner with "system-generated" badge                                            | Same model-event hook; distinguished by `created_by` actor                                | planned |
| UC-34 | Email setting opt-out                             | Account owner  | Toggles `transaction_email_enabled = false` on a line | No "received"/"detected" emails for that line; reminders/mismatch alerts respect their own toggles      | Settings UI on per-line edit (§8.6)                                                       | planned |

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
| `imputed_interest_rate` | decimal nullable | **Informational only** (rev 7 addition per CL-R8). Records the AFR or other rate the trust's accountant uses for tax-reporting on this no-interest loan. Does not affect the share math. |
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

### 4.4 Transaction match status

Add a `credit_line_match_status` enum column (nullable) to `transactions` for BOR/REP transactions. Values:

| Value | Meaning |
|---|---|
| `auto_matched` | The matcher uniquely identified a target line (§5 rule 3). `account_credit_line_id` is set. |
| `manual` | A human (account owner or admin) assigned the line explicitly. `account_credit_line_id` is set. |
| `ambiguous` | The matcher found more than one candidate line. `account_credit_line_id` is NULL. Transaction is flagged (§5 rule 10). |
| `unmatched` | The matcher found zero candidate lines. `account_credit_line_id` is NULL. Transaction is flagged. |
| NULL | Not applicable (non-credit-line transaction, or single-line account where the FK was assigned trivially). |

The visible flag on the UI and the account-page header banner key off `credit_line_match_status IN ('ambiguous', 'unmatched')`.

**Alternative:** reuse the existing `transactions.flags` field with new bit values. Cleaner is a dedicated enum because the existing `flags` is a single char used for unrelated states (`A`, `C`, `U`). Decide at implementation time.

### 4.5 New model: `CreditLineAdjustment`

Audit row written every time a line is readjusted (§6). Captures what changed, who changed it, and the state snapshot needed to display the change later.

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `account_credit_line_id` | FK → `account_credit_lines.id` | The adjusted line. |
| `adjusted_at` | datetime | Timestamp of the change. |
| `adjusted_by_user_id` | FK → `users.id` nullable | Who triggered it (NULL for system-initiated adjustments). |
| `outstanding_shares_at_adjustment` | decimal(19,4) | Snapshot of `outstanding_shares` at the moment of the change. Anchors the regenerated schedule and is what makes the diff meaningful. |
| `old_term_months` | int | |
| `new_term_months` | int | |
| `old_payment_frequency` | enum | |
| `new_payment_frequency` | enum | |
| `old_maturity_date` | date | |
| `new_maturity_date` | date | |
| `old_planned_payoff_date` | date | The payoff date implied by the previous schedule (may equal `old_maturity_date`). |
| `new_planned_payoff_date` | date | Implied by the newly generated schedule. |
| `reason` | string nullable | Free-text. Optional borrower note ("got a raise", "tight on cash"). |
| timestamps | | |

**Notes:**
- The full *schedule* snapshot is not stored on this row — the existing schedule preserves it: rows from the previous plan keep `status = cancelled` and remain linked to the line, while the new schedule rows are created fresh. To show "what the schedule looked like before this adjustment" we filter `CreditLinePayment` rows by `created_at <= adjustment.adjusted_at`.
- A `CreditLineAdjustment` row is **immutable** after creation. Edits are not allowed; a correction is itself a new adjustment.
- One row per readjustment event. If two fields change at once (e.g., term and frequency), one row captures both.

**Migration name (suggested):** `create_credit_line_adjustments_table`.

### 4.6 New model: `TransactionReversal` (rev 7 — per CL-R2)

Audit row for every reversed BOR/REP transaction.

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `transaction_id` | FK → `transactions.id` | The reversed transaction. The transaction row itself gets `reversed = true`. |
| `original_target_credit_line_id` | FK → `account_credit_lines.id` nullable | The line the original transaction was matched to (if any). Useful for re-applying to a different line. |
| `reversed_at` | datetime | When the reversal happened. |
| `reversed_by_user_id` | FK → `users.id` | Audit. Must be an admin per §5 rule 12. |
| `reason` | string | Free-text (required). |
| timestamps | | |

`TransactionReversal` rows are **immutable** after creation. If a reversal itself was a mistake, the cure is a fresh forward transaction, not editing the reversal row.

The `transactions` table also gains a `reversed` boolean column (default false). Indexes need a partial unique constraint so a transaction can be reversed at most once.

**Migration name (suggested):** `create_transaction_reversals_table` + `add_reversed_to_transactions`.

---

## 5. Business rules

1. **Draw cap.** At the moment of drawing, `principal_shares ≤ AccountExt::sharesAsOf(today)` — the borrower cannot borrow more shares than they own. (This requires fixing the TODO at `AccountExt.php:126` so BOR shares already on the books are subtracted from "available to borrow".)
2. **Shares, not money — but cash *does* move.** The debt is denominated in shares (same number out, same number back, regardless of price). At draw time the system must also **move cash out of the fund** equal to `principal_shares × share_price_at_draw`. This is a **new cash-flow path** the fund does not currently support and is a first-class part of this feature, not a UI concern:
   - The fund's cash position (and therefore portfolio total) drops by the disbursed amount.
   - On repayment, cash flows back into the fund equal to `shares_repaid × share_price_on_repayment_date` (price drift = fund's exposure).
   - We need a way to **record cash leaving the fund** (and returning) without it being modeled as a share purchase/sale. Likely a new transaction type or a new ledger entry on the fund/portfolio side. See §11 risks. The actual external money movement (Wise / PIX / IBKR) is designed in the [money flow sub-project](../money_flow_plan.md); this rule only covers how the *fund's books* reflect it.
3. **Repayment is line-targeted via auto-matching.** Every REP transaction carries an `account_credit_line_id` FK (§4.3) plus a `match_status` (see §4.4). When an account has **only one active line**, the REP is assigned to it automatically. When an account has **two or more active lines**, the matcher tries to assign the REP to exactly one line using this priority:
   1. **Exact share match** against any scheduled `CreditLinePayment.shares_due` on an active line (within a small tolerance, e.g., the decimal(19,4) precision).
   2. **Exact cash match** against `shares_due × share_value_at_transaction_date` on an active line (share-price drift makes this fuzzier, so used only if shares didn't match).
   3. **Exact outstanding match** against any active line's `outstanding_shares` (interpreted as a payoff payment).
   If exactly one candidate emerges from any of those steps, the REP is `auto_matched` and that line's schedule advances. Otherwise the REP is **flagged** (§5 rule 10) and stays unassigned until a human resolves it.
4. **Multiple lines per account.** No limit. Each line is independent — its own term, schedule, status, adjustments, and reminder settings. They share a single account and therefore a single pool of available OWN shares (rule 6).
5. **Draw cap across all active lines.** Available-to-borrow = `OWN_shares(today) − Σ outstanding_shares` summed across **all `active` lines on this account** plus any other lines whose disbursement has cleared but repayment is incomplete. A new line cannot exceed this remainder. The check runs inside the same transaction as the draw (§11 race conditions).
6. **One account per line.** Each credit line is scoped to a single account; there is no cross-account collateral or netting. A person who owns multiple accounts can open lines on any/all of them independently.
7. **No collateral, no default, no penalty.** A missed payment does not change the debt or incur fees; it only changes the `late` flag on schedule rows and triggers notifications (§8). The line stays `active` until shares are fully repaid.
8. **Aggregated `BOR` balance, per-line bookkeeping.** The existing `account_balances` schema allows **one row per type per account per day** — so an account with N active lines still has exactly **one** `BOR` balance row, holding the **sum** of all lines' outstanding shares. Per-line outstanding is reconstructed from the BOR/REP transactions linked via `account_credit_line_id`. Concretely:
   - `account_balance.shares` (type=BOR) = Σ over all lines of (BOR − REP) transactions
   - `line.outstanding_shares` = Σ over that line's BOR/REP transactions only
   This avoids any schema change to `account_balances` and keeps the existing temporal-balance machinery intact.
   **Unmatched REPs do not reduce any line's outstanding.** They stay parked until matched, so the account-level BOR balance is consistent with the per-line sum at all times.
9. **Settlement / payoff.** When a line's `outstanding_shares` reaches zero, its status → `paid_off`. The account-level `BOR` balance row only returns to zero when **all** lines on the account are paid off (or cancelled).
10. **Mismatch flagging is visible.** When `match_status ∈ {unmatched, ambiguous}` the transaction is rendered with a visual flag in transaction lists, and the **account page header shows a persistent alert banner** with the count of flagged transactions and a link to resolve them. The flag clears when the transaction is manually assigned to a line (or cancelled).
11. **Every transaction triggers an email.** Any BOR or REP transaction — whether **received** (user-submitted) or **detected** (system-created: auto-matcher, scheduled job, draw confirmation, retroactive late-detection) — generates an email to the account owner. Mismatch-flagged REPs generate an *additional* alert email with a link to resolve the assignment. See §8.6.
12. **All credit-line write actions are admin-only** (rev 7 — per CL-R6 and CL-R7). Opening a line, submitting a manual REP, readjusting term/frequency, resolving an ambiguous/unmatched REP, and cancelling a line all require the `admin` (trustee) role. **Borrowers / account owners have read-only access** to their credit-line views (per-line drill-down, schedule, trajectory chart, adjustment history, loans summary). Auto-attributed REPs created by the matcher when a detected `CheckingDeposit` lands bypass the admin gate — the system created them; admin only intervenes when the matcher flags `ambiguous` or `unmatched`.
13. **Account closure is blocked while any line is `active`** (rev 7 — per CL-R5). Operator gets a clear "this account has N active credit lines; close them first" error. The trustee override that lets an admin forgive remaining outstanding is a deferred future improvement (see CL-R1 in `plan_recommendations.md`).
14. **REP transactions are reversible** (rev 7 — per CL-R2). When a REP was matched to the wrong line, or entered in error, an admin can reverse it. The reversal:
    - Marks the original `Transaction` row `reversed = true` (the row stays — never deleted).
    - Re-opens any `CreditLinePayment` rows the REP had marked `paid` / `partial` back to `scheduled`.
    - Recomputes the line's `outstanding_shares`.
    - Writes an immutable `TransactionReversal` audit row (`transaction_id`, `reversed_at`, `reversed_by_user_id`, `reason`, `original_target_credit_line_id`).
    - If the admin wants to re-apply to a different line, they create a fresh REP as a separate step. The chained "reverse-and-reapply" UI is a deferred future improvement.
    - Trajectory chart renders the reversal as a downward step on the actual-repayments line so history stays honest.
15. **Backdated transactions are supported via the "create from scratch" admin page** (rev 7 — per CL-R3). Instead of a separate `effective_date` column, the admin has a full-control transaction-creation page where every field (including `timestamp`) can be set. The existing matcher and late-detection logic already use `transaction.timestamp`, so a backdated REP correctly clears earlier `due_date` rows. No schema change.

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

A readjustment is a recomputation, executed atomically:
1. **Capture old values** — read current `term_months`, `payment_frequency`, `maturity_date`, the existing `planned_payoff_date`, and recompute `outstanding_shares` from BOR/REP transactions to date.
2. **Apply new values** — replace `term_months`, `maturity_date`, `payment_frequency` on the line per the request.
3. **Cancel remaining scheduled rows** — every `CreditLinePayment` with `status = scheduled` for this line → `status = cancelled` (preserved, not deleted, so the historical view in §8.5 can render them).
4. **Generate fresh schedule** — new `CreditLinePayment` rows against the captured `outstanding_shares` and the new term/frequency. The new `planned_payoff_date` is the last new row's `due_date`.
5. **Append audit row** — write one immutable `CreditLineAdjustment` (§4.5) with the snapshot fields: old/new term, old/new frequency, old/new maturity, old/new planned payoff, `outstanding_shares_at_adjustment`, `adjusted_by_user_id`, optional `reason`.

Steps 2–5 run inside a single DB transaction. The audit row is what makes the adjustment history (§8.5) and the multi-generation trajectory chart (§8.1) possible.

---

## 7. Resolved design decisions

These were initially open questions; the answers below now constrain the implementation.

| Question | Decision |
|---|---|
| Interest | **None.** Borrower owes the same share count back. No interest column, no interest accrual. |
| Counterparty / share holding | The shares stay on the borrower's account with a `BOR` `AccountBalance` row offsetting the `OWN` row (the existing schema). No escrow account. |
| Cash flow | Borrow disburses cash *out of the fund* to the borrower. Repayment returns cash *into the fund*. The fund's cash position changes — this is **new behavior** the system does not currently support (§5 rule 2, §11 risks). |
| Default / penalty | **None.** Missed payments only update the `late` schedule flag and trigger notifications. The debt is not increased, the status is not changed. |
| Cross-account collateral | **Not a concept.** Each line is against one account's own shares. Multiple lines per person across their accounts is fine; each is independent. |
| Authorization | **Admin-only for all write actions** (rev 7 — per CL-R6/CL-R7). Open / repay / readjust / resolve-flag / cancel all require the `admin` (trustee) role. Borrowers / account owners are read-only. Auto-attributed REPs created by the matcher bypass the admin gate. See §5 rule 12. |
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

**When a line has been readjusted**, the chart overlays one plan line per generation: the original plan (dashed grey), each historical plan generated by a readjustment (faded), and the current active plan (solid). Each plan line is labeled with the `adjusted_at` date so the reader can see where the trajectory shifted. The actual-repayments line runs on top.

### 8.2 Account page

On the account detail view (extending `AccountControllerExt`):
- **Header alert banner (top of page, full width)** — shown when this account has any BOR/REP transaction with `credit_line_match_status IN ('ambiguous', 'unmatched')`. The banner shows the count of flagged transactions, the most recent one inline, and a "Resolve" CTA linking to a transaction-by-transaction assignment screen. Banner is persistent (sticky on scroll), styled prominently (red/orange), and dismissable only by resolving the underlying transactions (not by clicking away).
- **Loans summary card** (rev 7 — per CL-R10) — top of the credit-lines area, above the per-line table. Shows: total disbursed lifetime, total repaid lifetime, net outstanding (sum across active lines), next-due across all lines. One-screen scan of the borrower's credit-line state. Extended into the §8.4 quarterly account report.
- **Aggregate panel**: total `BOR` shares (sum across lines), available-to-borrow remaining, count of active lines, count behind plan.
- **Per-line table** listing every credit line on the account: principal, outstanding, status, planned payoff, projected payoff, variance. Sortable; defaults to oldest-first.
- **Drill-down** to each line showing the full schedule (paid/late/scheduled), the trajectory chart, the **adjustment history** (§8.5), and the per-line reminder settings.
- **Upcoming due-date callout** merging the next N payments across **all lines** on the account, sorted by `due_date`, each row labeled with which line it belongs to.
- **Recent transactions list** — flagged transactions (`ambiguous`/`unmatched`) render with a highlight (e.g., red border, warning icon) inline, so flags are visible in context, not only in the header.
- **Admin-only actions** (rev 7 — per §5 rule 12): "New credit line", "Record REP", "Readjust", "Resolve flag", "Reverse transaction" buttons appear only for users with the admin role. For non-admin borrowers, the page is read-only.
- **Share-value clarity copy** (rev 7 — per CL-R11) — near every shares-denominated balance, render: *"You owe N shares (currently valued at $X). The share count is what you owe back — it doesn't change with the market. The dollar value will move up or down with the fund's share price."* Apply to: loans summary card, per-line table headers, trajectory chart caption, per-line drill-down headers, transaction-event email templates (§8.6).

### 8.3 Fund page

On the fund detail view:
- Aggregate outstanding `BOR` shares across all accounts in the fund.
- Cash outflow attributable to active credit lines (cash currently disbursed but not yet repaid).
- Count of active lines, count behind plan.

### 8.4 Quarterly reports

Add a new section to **both** report templates:
- **Account quarterly report**: each line's activity in the quarter (payments made, current outstanding, plan vs. projected payoff). **List adjustments made during the quarter** with old → new values and the borrower's `reason` if provided. **Include the §8.2 Loans summary card** (rev 7 — per CL-R10) at the top of the credit-lines section so the quarterly recipient sees the same holistic state as the web view.
- **Fund quarterly report**: aggregate disbursements, repayments, outstanding share/cash exposure, a list of lines currently behind plan, and a count of adjustments made in the period (drilling into per-line details on the account report).

Reports are generated via queue jobs and rendered with wkhtmltopdf — extend the existing templates rather than introducing a separate pipeline.

### 8.5 Adjustment history view

A dedicated section on each line's drill-down page that surfaces the change-by-change record of how the plan evolved.

**Layout** — a vertical timeline, newest first, with one card per `CreditLineAdjustment` row:

```
┌─ 2026-08-14  Term extended  (by: claude@test.local)
│  Term:               24 mo  →  36 mo
│  Payment frequency:  monthly  (unchanged)
│  Maturity date:      2027-05-01  →  2028-05-01
│  Planned payoff:     2027-05-01  →  2028-05-01
│  Outstanding at change: 142.5000 shares
│  Reason: "Tight on cash this year"
│  [View schedule snapshot]  [View trajectory chart through this point]
└─
```

**Each card shows:**
- The date and who made the change (or "system" if unattributed).
- A side-by-side diff of every changed field (unchanged fields rendered greyed-out and labeled "unchanged"). Changed fields rendered in bold with `old → new`.
- The `outstanding_shares_at_adjustment` snapshot — important context for understanding *why* the new per-payment amount looks the way it does.
- The free-text `reason` if present.
- Inline actions: link to the schedule snapshot as of that adjustment (filtering `CreditLinePayment` rows by `created_at <= adjusted_at`), and link to a trajectory chart variant truncated to that point in time.

**Special case — origination row.** The very first card in the timeline is the line's **origination** (not a `CreditLineAdjustment` row — derived from the line itself). It shows the original term, frequency, principal, maturity, and `origination_date`. This gives a complete "from day one" timeline without a special-case rendering on the part of the reader.

**Empty state.** A line with no adjustments still shows the origination card and an "(no adjustments yet)" placeholder under it.

**Sorting / filtering.** Newest-first by default; toggle to oldest-first. No filters needed for v1; adjustments are usually few per line.

**Quarterly report excerpt.** The same timeline (or a compact tabular form of it) is rendered into the §8.4 account quarterly report, scoped to adjustments where `adjusted_at` falls in the quarter.

### 8.6 Reminders and delay notifications

Configurable per-account (or per-line) settings:

**Email types — five distinct templates:**

| Email | Trigger | Recipient | Notes |
|---|---|---|---|
| **Transaction received** | A user-submitted BOR or REP transaction is saved (status `pending` or `cleared`) | Account owner | Subject indicates type ("Borrow recorded", "Repayment received"). Body shows shares, value, target line if matched. Sent immediately on save (queued). |
| **Transaction detected** | The system (auto-matcher, scheduled job, late-detection sweep, draw confirmation) creates or clears a BOR/REP transaction | Account owner | Same template structure as "received" but with a "system-generated" badge. Sent immediately. |
| **Mismatch alert** | A REP is saved with `credit_line_match_status IN ('ambiguous', 'unmatched')` | Account owner | In addition to the "transaction received" email, sends a separate alert with a "Resolve" link to the matching screen (§8.2 banner target). |
| **Reminder** | `today + reminder_lead_days == due_date` on any scheduled `CreditLinePayment` | Account owner | Per-line settings (table below). |
| **Delay notification** | `today − due_date == delay_notification_grace_days` on a still-unpaid scheduled row, then every `delay_notification_repeat_days` until paid | Account owner | Per-line settings. Hard cap on repeats to prevent spam (§11). |

**Per-line configurable settings:**

| Setting | Type | Default |
|---|---|---|
| `reminder_lead_days` | int | 7 — send a reminder N days before each scheduled payment. |
| `reminder_enabled` | bool | true |
| `delay_notification_enabled` | bool | true |
| `delay_notification_grace_days` | int | 3 — send "payment overdue" notification N days after a missed `due_date`. |
| `delay_notification_repeat_days` | int nullable | 14 — re-send every N days while still late; NULL = send once. |
| `transaction_email_enabled` | bool | true — controls both `received` and `detected` emails. |
| `mismatch_alert_enabled` | bool | true |

Implementation notes:
- Store these settings on `AccountCreditLine` (or a sibling `credit_line_settings` table if defaults at the account level make sense — TBD).
- "Received" and "detected" emails are dispatched via Laravel model events on the `Transaction` save lifecycle (`saved` event, gated on `type IN ('BOR', 'REP')`). This ensures every code path that creates a credit-line transaction triggers an email — there is no manual call site to forget.
- "Mismatch alert" emails are dispatched by the auto-matcher service after it computes `credit_line_match_status` and saves the transaction.
- "Reminder" and "delay notification" emails come from a daily scheduled job (Laravel scheduler + queue) that scans `CreditLinePayment` rows, sending via the existing mail stack (MailHog in dev). Hook into the existing `queue:listen` infrastructure.
- Email templates live alongside existing quarterly-report templates.

---

## 9. Implementation sketch

Rough order of work, following the repo's repository + `*Ext` pattern:

1. **Fix `AccountExt::sharesAsOf()`** so BOR shares discount OWN (`app/Models/AccountExt.php:126`). Add a test before changing behavior — many calculations depend on this.
2. **Design fund-side cash flow** (see §11 risks). Decide how a "cash leaves the fund" event is recorded. Probably a new transaction type or a dedicated fund-cash ledger. This is a prerequisite for the draw operation.
3. **Migrations**
   - `create_account_credit_lines_table`
   - `create_credit_line_payments_table`
   - `create_credit_line_adjustments_table` (audit)
   - `add_account_credit_line_id_to_transactions`
   - `add_credit_line_match_status_to_transactions` (§4.4 enum)
   - (possibly) `create_fund_cash_movements_table` or similar — depends on (2)
4. **Models** following the `Account` / `AccountExt` pattern:
   - `AccountCreditLine` + `AccountCreditLineExt` (Ext hosts amortization, readjust, trajectory, settings)
   - `CreditLinePayment`
   - `CreditLineAdjustment`
5. **Repositories** following `AccountRepository`:
   - `AccountCreditLineRepository`, `CreditLinePaymentRepository`, `CreditLineAdjustmentRepository`
6. **Service / domain logic**:
   - `open(account, shares, term, frequency)` → BOR transaction, schedule rows, fund cash-out entry
   - `repay(account, shares, date)` → REP transaction; matcher decides target line (single-line: trivial; multi-line: shares → cash → outstanding priorities); sets `credit_line_match_status` and FK
   - `resolveMatch(transaction, line)` → manual assignment of a flagged REP; updates FK, status → `manual`, advances schedule
   - `readjust(line, new_term, new_frequency)` → cancel scheduled rows, regenerate, record adjustment row
   - `recomputeOutstanding(line)` → from transactions, ignoring `unmatched`/`ambiguous` REPs
   - `projectPayoff(line)` → trajectory + projected_payoff_date (§8.1)
7. **Transaction model events** (§8.6)
   - `Transaction::saved` listener that dispatches the "transaction received/detected" email when `type IN ('BOR','REP')` and `transaction_email_enabled` is true on the relevant line (or account default for unmatched).
   - Matcher service dispatches the additional "mismatch alert" email when status becomes `ambiguous`/`unmatched`.
8. **Controllers** under `app/Http/Controllers/WebV1/`:
   - `AccountCreditLineControllerExt` — CRUD + draw + readjust + repay actions
   - `CreditLineMatchResolutionController` — endpoints powering the §8.2 banner "Resolve" CTA (list flagged, assign to line)
   - Follow `AccountMatchingRuleControllerExt` as a structural template
9. **Form requests** in `app/Http/Requests/`:
   - `CreateAccountCreditLineRequest`, `UpdateAccountCreditLineRequest`, `ReadjustCreditLineRequest`, `RepayCreditLineRequest`, `ResolveCreditLineMatchRequest`
10. **Views** under `resources/views/account_credit_lines/` (index, create, show, edit, fields, table) — InfyOm scaffold compatible. Plus:
    - Trajectory chart partial reusable from account/fund pages.
    - Header alert banner partial included on `accounts/show`.
    - Flagged-transaction inline highlight styling in the transactions table partial.
11. **Reporting**
    - Extend account page (`AccountControllerExt`) and fund page with the §8.2/§8.3 sections.
    - Extend quarterly report templates with §8.4 sections.
12. **Reminder / delay-notification job** (§8.6)
    - Daily Laravel-scheduler job scans schedule rows.
    - Emails via existing mail stack (MailHog dev / SMTP prod).
13. **API endpoints** under `app/Http/Controllers/API/` if mobile/external clients need them.
14. **Tests**
    - Repository test (CRUD)
    - Feature test for the draw flow (cap enforcement, balance impact, fund cash-out)
    - Feature test for repayment (schedule advance, payoff status, fund cash-in)
    - Feature test for readjustment (schedule rebuild, adjustment audit row written with all snapshot fields populated, old `CreditLinePayment` rows marked `cancelled` not deleted)
    - **Adjustment history view test (UC-40, UC-41, UC-44)**: a line with 0 adjustments shows origination card only; with 2 adjustments shows 3 cards newest-first; each card renders the correct old → new diff per field; clicking "schedule snapshot" returns only `CreditLinePayment` rows created on or before that adjustment's `adjusted_at`; quarterly report includes only adjustments whose `adjusted_at` is in the quarter.
    - **Trajectory multi-generation chart test (UC-42)**: a line with N readjustments produces N+1 plan-line series on the chart (original + each readjustment + current), each correctly labeled.
    - Feature test for trajectory calculation (projected payoff with mixed on-time/late payments)
    - **Feature test for multiple concurrent lines on one account**: open 3 lines, verify draw cap is cumulative, repay against a specific line and verify only that line's `outstanding_shares` and schedule change, verify the account-level `BOR` `AccountBalance` row equals the sum across all 3 lines, verify the account page lists 3 lines independently, verify the upcoming-due-dates callout merges payments from all 3.
    - **Matcher feature test (UC-25 through UC-31)**: single-line auto-match; multi-line shares match; multi-line cash-fallback match; full-payoff outstanding match; ambiguous (≥2 candidates) → flagged, banner appears, mismatch email sent; unmatched (0 candidates) → flagged, banner appears, mismatch email sent; manual resolve clears the flag and advances the chosen line's schedule.
    - **Transaction email test (UC-32, UC-33, UC-34)**: assert exactly one "received" email is sent on user-submitted REP; one "detected" email on system-created REP; no email when `transaction_email_enabled = false`; mismatch-alert email is independent of `transaction_email_enabled` but respects `mismatch_alert_enabled`.
    - Reminder/notification job test (emails sent at correct lead/grace days)
    - Golden-data integration: small fund with a draw, partial repay, readjust, full repay sequence — verify both account and fund quarterly report sections.

---

## 10. Transaction detection subsystem

### 10.1 What already exists

The codebase has a **matching subsystem** focused on parental matching contributions (used when one account's transaction "matches" another's by rule). Relevant pieces:

| Component | Location | Role |
|---|---|---|
| `MatchingRule` / `MatchingRuleExt` | `app/Models/` | Defines a matching rule (rate, cap, period) |
| `AccountMatchingRule` / `AccountMatchingRuleExt` | `app/Models/` | Per-account binding of a rule |
| `TransactionMatching` | `app/Models/TransactionMatching.php` | Join row: `matching_rule_id` + `transaction_id` + `reference_transaction_id` — records that transaction X was matched by rule R to reference transaction Y |
| `MatchingReminderLog` | `app/Models/` | Audit/dedup for matching-related reminder emails |
| `TransactionMatchingController` + `*Resource` | `app/Http/Controllers/`, `app/Http/Resources/` | CRUD over matchings |
| `TransactionExt::TYPE_MATCHING = 'MAT'` | `app/Models/TransactionExt.php:30` | Transaction type for the matching contribution itself |
| `TransactionExt::FLAGS_NO_MATCH = 'U'` | `app/Models/TransactionExt.php:40` | Existing flag indicating "not matched" — same conceptual gap we now have for credit-line REPs |

**Implication:** the system already has the *shape* of a detect-and-classify pipeline. What's missing is (a) a single ingestion seam that all new transactions flow through, and (b) a pluggable classifier that can route each transaction to the right downstream handler — matching-rule, credit-line, or unmatched.

### 10.2 Proposed: unified detection pipeline

Introduce a thin **`TransactionDetectionService`** that becomes the single seam for every new BOR/REP/PUR/SAL/MAT transaction. It runs either as a model-event listener on `Transaction::saved` or as a service method that *all* transaction-creating call sites use (we can start with the listener for backward compatibility, then migrate call sites over time).

Pipeline stages per transaction:

```
[ingest] → [classify] → [match] → [persist status] → [notify]
```

1. **Ingest** — the listener fires on every `Transaction::saved`. Transactions can originate from:
   - User submission via web UI (existing flow)
   - API endpoint (existing flow)
   - System-generated: scheduler jobs, auto-matcher, draw confirmation, late-detection sweep (new for credit lines)
   - **Future**: external feeds (bank API, CSV upload, email parsing) — designed in [`money_flow_plan.md`](../money_flow_plan.md); not v1, but the seam supports them
2. **Classify** — inspect `type` to decide which classifier(s) apply:
   - `BOR` / `REP` → credit-line matcher (§5 rule 3)
   - `PUR` (deposit) → existing matching-rule pipeline (find any `MatchingRule` that applies, create candidate `TransactionMatching` rows)
   - `SAL`, `INI`, `MAT` → no matching needed for v1
3. **Match** — call the appropriate matcher service:
   - `CreditLineMatcher::match(transaction)` → returns `{line_id, status}` per §5 rule 3
   - `ContributionMatcher::match(transaction)` → existing logic
   - Both return a uniform result: `{status: matched|ambiguous|unmatched, target_ids: [], reason: ...}`
4. **Persist status** — write the result back to the transaction:
   - For credit-line: `account_credit_line_id` + `credit_line_match_status` (§4.4)
   - For contribution: `TransactionMatching` rows + `flags = 'U'` if unmatched (existing pattern)
5. **Notify** — dispatch emails:
   - Standard "transaction received/detected" email per §8.6
   - "Mismatch alert" email if status is `ambiguous` / `unmatched`
   - Reuse existing `MatchingReminderLog` pattern for dedup, extending it (or creating a sibling `CreditLineEmailLog`) so we don't double-send across pipeline runs

### 10.3 What "expand the existing" means in practice

Rather than building a parallel system, we extend the existing matching primitives:

- **`TransactionMatching` table** gets a new optional column `account_credit_line_id` (nullable FK). A row with `matching_rule_id` set continues to mean "contribution match"; a row with `account_credit_line_id` set means "credit-line match". Both NULL = unmatched / awaiting classification.
- **`MatchingReminderLog`** is generalized (or duplicated for credit-line emails) to dedup transaction-event emails so the listener can fire safely on retries.
- **`FLAGS_NO_MATCH = 'U'`** is reused for the `unmatched` state where it makes semantic sense, but the more specific `credit_line_match_status` enum (§4.4) carries the credit-line-specific detail.
- **Detection sources** (v1: in-app submission and system jobs; v2: external feeds) all funnel through `TransactionDetectionService::ingest($transaction)` so behavior is uniform regardless of origin.

### 10.4 Implementation impact on §9

Insert these steps into the implementation sketch (between §9 step 6 and step 7):

- 6a. **`TransactionDetectionService`** — orchestrator class; entry point `ingest($transaction)`.
- 6b. **`CreditLineMatcher`** — implements §5 rule 3 priorities; returns a uniform `MatchResult`.
- 6c. **Refactor** the existing contribution-matching code path to expose a `ContributionMatcher::match()` returning the same `MatchResult` shape, so the orchestrator treats both uniformly.
- 6d. **Migration** — `add_account_credit_line_id_to_transaction_matchings`.
- 6e. **Model event** — `Transaction::saved` listener delegating to `TransactionDetectionService` (gated on `type IN ('BOR','REP','PUR')` for v1).
- 6f. **Email dedup log** — extend `MatchingReminderLog` or add `CreditLineEmailLog` for the §8.6 emails.

### 10.5 New use cases (extend §2 tracker)

| ID    | Use case                                              | Actor   | Trigger / Input                                         | Expected outcome                                                                            | Key changes / Notes                                                              | Status  |
|-------|-------------------------------------------------------|---------|---------------------------------------------------------|---------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------|---------|
| UC-35 | Unified ingestion: every new transaction is classified | System  | Any `Transaction::saved` event                          | `TransactionDetectionService::ingest()` runs; classifier picked by `type`                   | §10.2 pipeline + model event listener                                            | planned |
| UC-36 | Credit-line classifier routes BOR/REP                 | System  | New BOR/REP saved                                       | Calls `CreditLineMatcher::match`; persists status per §4.4                                  | §10.2 step 2                                                                     | planned |
| UC-37 | Contribution classifier still handles PUR             | System  | New PUR saved                                           | Existing `TransactionMatching` rows created, no regression                                  | Backward compatibility check                                                     | planned |
| UC-38 | Email dedup across retries                            | System  | Same transaction's listener fires twice (queue retry)   | Only one email sent; second skipped via `MatchingReminderLog` / `CreditLineEmailLog`        | §10.2 stage 5                                                                    | planned |
| UC-39 | Future external source (placeholder)                  | Admin   | CSV import or bank feed creates `Transaction` rows      | Same pipeline runs; classification + matching + notification all happen uniformly           | Out of scope for v1 — the seam exists so v2 is additive                          | planned |
| UC-40 | View adjustment timeline                              | Account owner / admin | Open a line's drill-down page                  | Origination card + one card per adjustment, newest-first, with old→new diff per field        | §8.5 timeline view; reads `CreditLineAdjustment` rows                            | planned |
| UC-41 | View schedule snapshot at an adjustment               | Account owner / admin | Click "View schedule snapshot" on an adjustment card | Schedule rendered as it was at that point (CreditLinePayment rows created on or before `adjusted_at`) | Filter on `created_at <= adjusted_at`; reuse schedule partial                    | planned |
| UC-42 | Trajectory chart overlays multiple plan generations   | Account owner / admin | Open the trajectory chart for a readjusted line   | Original plan (dashed), each historical plan (faded), current active plan (solid), all labeled with `adjusted_at` | §8.1 chart enhancement; one series per `CreditLineAdjustment` row                | planned |
| UC-43 | Adjustment audit row written on every readjust        | System  | Any successful `readjust(line, new_term, new_frequency)` | One immutable `CreditLineAdjustment` row appended; old `CreditLinePayment` rows marked `cancelled`; new rows generated | §6 readjustment flow + §4.5 model                                                | planned |
| UC-44 | Quarterly report lists adjustments in the period      | System  | Quarterly report job runs                              | Account section lists each adjustment with old→new diff and reason; fund section shows count | §8.4 extension                                                                   | planned |
| UC-45 | Admin reverses an erroneous REP                       | Admin   | Open reversed transaction in admin UI, confirm reason  | Transaction marked `reversed`, schedule rows re-opened, outstanding recomputed, audit row written | §5 rule 14 + §4.6 `TransactionReversal` (rev 7)                                  | planned |
| UC-46 | Admin creates transaction from scratch with backdated timestamp | Admin | "Create transaction" admin form with full field control | Transaction saved with admin-specified timestamp; matcher and late-detection use it correctly | §5 rule 15 (rev 7)                                                               | planned |
| UC-47 | Account closure blocked when any line is active       | System  | Operator attempts to close an account with active lines | Clear error message; closure blocked; lists the active lines that prevent closure | §5 rule 13 (rev 7)                                                               | planned |
| UC-48 | Only admin can open / repay / readjust / resolve / cancel | System | Non-admin user attempts a credit-line write action      | Action blocked with auth error; UI hides the buttons for non-admin users           | §5 rule 12 + §7 resolved decisions (rev 7)                                       | planned |
| UC-49 | Loans summary card visible on account page and in quarterly report | Account owner / admin | Open account page or receive quarterly report | Single card with total disbursed, total repaid, net outstanding, next-due across all lines | §8.2 + §8.4 (rev 7 — per CL-R10)                                                 | planned |
| UC-50 | Share-value clarification copy near every shares balance | Account owner / admin | View any shares-denominated balance | Inline copy explains share-count is owed, $-value floats with market | §8.2 + §8.6 email templates (rev 7 — per CL-R11)                                 | planned |

---

## 11. Risks and watch-items

- **Tax / legal — verify with counsel before real-money deployment** (rev 7 — per CL-R8). This design is interest-free by intent. In the US, no-interest or below-market-rate loans from a family trust to a beneficiary may trigger imputed-interest reporting under IRS §7872 with gift-tax / income-tax implications depending on loan size and relationship. The optional `imputed_interest_rate` field on `AccountCreditLine` (§4.1) is a place to record the accountant's chosen rate for reporting — it does not affect the share math. **Do not deploy real money until a tax advisor signs off on the structure.**
- **Fund-side cash movement is new.** Today the fund's cash position changes only through portfolio activity (asset purchases/sales) and matching contributions. A credit-line draw moves cash *out of the fund* to a borrower with no corresponding asset purchase. We need a clean way to record this without breaking NAV math (`FundExt::shareValueAsOf()` uses `valueAsOf / sharesAsOf` — if disbursed cash is no longer counted in fund value, NAV will drop on draw). Design choices to evaluate:
  - Treat the outstanding receivable (cash owed back to fund) as an *asset* of the fund so NAV is unchanged at draw, then adjusts as share price drifts during the life of the loan.
  - Treat the disbursement as a real value loss to the fund and a value gain on repayment (simpler but distorts NAV with every draw/repay).
  - Recommend the first; the share-price drift then naturally appears as a fund-level P&L over the loan's life.
- **Existing TODO in `AccountExt::sharesAsOf()`.** Many places call this; changing semantics (OWN − BOR) is the right fix but will move numbers across reports and tests. Plan a test-suite pass before merging.
- **Historical (`as_of`) views** must continue to work. `AccountBalance.start_dt / end_dt` already supports temporal queries; credit-line outstanding balances must be reconstructable for any past date.
- **Decimal precision.** Shares are decimal(19,4); rounding in the amortization schedule must sum to the principal exactly (use a remainder-on-last-payment scheme).
- **Race conditions on the draw cap.** Two simultaneous draws could each individually fit but exceed the cap together. Use a DB transaction with `SELECT … FOR UPDATE` on the account row when creating a credit line.
- **Notification spam.** A long-late line with `delay_notification_repeat_days` set will keep emailing forever. Consider a hard cap (e.g., 6 notifications) or a "snooze" action on the account page.
- **Cash-fallback matcher is fragile.** Priority 2 of the REP matcher (§5 rule 3) compares against `shares_due × share_value_at_transaction_date`. Share-price drift between scheduled-date and payment-date means the cash amount the borrower actually sends may not equal what was originally implied. A small tolerance window (e.g., 1%) helps for honest payments but widens the chance of an ambiguous match across two lines with close `shares_due`. Default to **shares-only matching** in v1; treat cash-fallback as a feature flag that can be enabled per fund.
- **Email-event reliability.** The `Transaction::saved` hook (§8.6) fires inside the same request as the matcher. If the email queue is down, the transaction still saves but the email may be lost. Use queued mailers and verify retry behavior; consider a daily reconciliation job that emails any transaction created in the last 24h that has no record of email dispatch.

---

## 12. Implementation status (2026-05-14)

**Shipped: 49 of 50 use cases.** Branch `claude/elated-pare-eac2e9`, PR #3.

### Build summary

The plan was implemented across 10 phases over a few sessions:

| Phase | Scope |
|-------|-------|
| 0 — Foundation | `AccountExt::sharesAsOf()` OWN−BOR fix (UC-24); migrations for `account_credit_lines`, `credit_line_payments`, `credit_line_adjustments`, `transaction_reversals`; FK + status columns on `transactions`; bare models + repositories; receivable-as-asset design (see [`fund_cashflow.md`](fund_cashflow.md)). |
| 1 — Services (5 parallel waves) | Draw/Repay/Cancel + amortization (UC-01, 03, 05-07, 12, 13); CreditLineMatcher + MatchResolutionService with all 4 priorities (UC-25-31); ReadjustService + adjustment history (UC-09, 10, 40, 41, 43); TransactionDetectionService + 5 mailables + reminder/late jobs (UC-08, 18-20, 32-38); ReverseService (UC-45). |
| 2 — Integration | Notification-settings columns; `is_admin()` via Spatie `system-admin`; ScheduleAdvancer binding; `Transaction::saved` observer; scheduler entries; 6 form requests; 3 WebV1 controllers; 11 named routes; `accounts/show` extension; minimal CRUD blade views; admin-only auth gates (UC-48). |
| 3 — Reporting | TrajectoryBuilder (multi-generation overlay), FundReceivableCalculator, LoansSummaryBuilder, FundExposureBuilder; `FundExt::valueWithCreditLinesAsOf()`; QuickChart trajectory partial; account/fund page extensions; quarterly PDF templates; share-value clarification copy (UC-14-17, 22, 42, 44, 49, 50). |
| 4 — Polish | New temporal `account_credit_line_balances` table + `CreditLineBalanceTracker` wired into Draw/Repay/Reverse (UC-21); per-generation chart colors; admin-only fund cash-position panel; schedule-snapshot + trajectory-through-this-point modals. |
| 5 — Browser tests | Laravel Dusk install + Dusk happy-path tests covering UC-01/05/13/09/45/49/42. Bug-hunt during the UI tour caught two real defects (see "Notable issues found and fixed" below). |
| 6 — Close audit gaps | UC-20 settings UI; UC-47 account closure block; UC-46 admin backdated-tx form; UC-37 contribution-classifier round-trip as a read-only adapter that observes the legacy `TransactionMatching` writer rather than duplicating it. |
| 7 — Beyond plan | `applies_to_rep` toggle on `MatchingRule` (default `true`) — matching contributions allowed on REPs with `shares × shareValueAsOf` fallback when the REP value is 0. See [`matching_on_repayment.md`](matching_on_repayment.md). |
| 8 — Negative coverage | Gap-fill RepayService negative tests; 8 Dusk error-path tests (non-admin, validation, over-borrow, cancel-blocked, account-closure-blocked, no-change readjust, double-reverse); 5 exception types caught into flash errors in the controllers. |
| 9 + 9b — Simulator | New `/credit-lines/{line}/simulator` admin page with two modes: payment-mode ("if I pay $X/mo, when does it pay off?") and time-mode ("if I want it paid off in N months, what $/mo do I need?"). Three growth scenarios using the existing `expected × 0.8 / 1.0 / 1.2` multipliers seen elsewhere in the codebase. |

### Per-UC status

| UC | Status |
|----|--------|
| UC-01–10, 12-19, 22-50 (except 21, 39) | ✅ shipped |
| UC-11 Default line picker on multi-line repay | ⛔ dropped by design — the matcher + `MatchResolutionService` already handle multi-line ambiguity. A free-form repay form would duplicate that flow. |
| UC-21 Historical (as-of) view | ✅ via the temporal `account_credit_line_balances` table, following the same `start_dt/end_dt` pattern as `account_balances`. All three write paths (Draw/Repay/Reverse) call `CreditLineBalanceTracker::recordChange`, so undo via reversal also writes a new row. **Empirically verified** on 2026-05-14 by scanning both backups (`database/prod/familyfund_prod_data_20260419.sql` and `database/dev/familyfund_dev_data_20260419.sql`): zero BOR/REP transactions and no `account_credit_line*` tables exist in either backup — the borrowing feature was never used in production before this build, so no backfill of legacy history is needed. |
| UC-39 External CSV/bank feed | ⛔ explicit v2 in §10.5; out of scope for this build. |

### Beyond-plan features

| Feature | Rationale |
|---|---|
| `applies_to_rep` toggle on `MatchingRule` | Trustee direction: matching contributions allowed on credit-line repayments in principle, with per-rule opt-out. Default `true`. Match-base falls back to `shares × shareValueAsOf` when REP value is 0 — localized to `AccountMatchingRuleExt::match()` so the wider blast radius (rewriting `RepayService` to populate `value`) is avoided. |
| Payment simulator (payment mode) | Trustee can model "if I pay $X/mo, when does it pay off?" |
| Payment simulator (time mode) | Inverse: "if I want to pay off in N months, what $/mo do I need?" Solver uses binary search over the same `simulate()` semantics. |
| Negative-path tests + controller hardening | Surfaced two silent-acceptance bugs in `RepayService` (negative/zero shares; repaying a cancelled or paid-off line) and made 5 exception types render as flash errors instead of 500s. |

### Notable issues found and fixed

- **`AccountTrait::createTransactionsResponse` DivisionByZeroError** when an account had any BOR/REP transaction (value=0 by design). Guarded the two division sites with `$value != 0` ternaries; performance for zero-value transactions reads 0%.
- **`TrajectoryBuilder` original-plan double-count.** Filter used `created_at->lte($firstAdjustedAt)`; because `ReadjustService` writes new rows in the same DB transaction, their `created_at` equals `adjusted_at` to second precision and they slipped into the original generation. Changed to strict `lt`. Caught by the new UI-tour Dusk test.
- **QuickChart URL collision.** A workaround setting `QUICKCHART_URL=http://localhost:3400` for the host browser broke 28 server-side rendering tests (inside the container, `localhost:3400` doesn't resolve to the quickchart container). Resolved by introducing a separate `quickchart.public_url` config key for browser-side embeds; `quickchart.base_url` continues to point at `http://quickchart:3400` for SSR.
- **Mailable assertSent vs assertQueued.** Three feature tests + one unit test were using `Mail::assertSent` for mailables that implement `ShouldQueue`; under `Mail::fake()` those land in the queued bucket, not sent. Aligned with `assertQueued`.

### Test coverage

| Layer | Approx. count | Status |
|---|---|---|
| Unit / Feature in credit-line scope | ~150+ | All green |
| Dusk browser | 17 (happy 6 + negative 8 + tour 1 + simulator 2) | All green |
| Full feature suite delta | +28 fixed vs. baseline (1726 → 1754 passed) | 5 remaining failures are pre-existing baseline issues unrelated to credit lines (HolidaysSync API + one golden-data fixture) |

### Tax / legal caveat (re-stated for clarity)

This design is interest-free by intent. US trust loans below market rate may trigger imputed-interest reporting under IRS §7872. `AccountCreditLine.imputed_interest_rate` exists as a reporting hook but does not affect the share math. **Verify with counsel before real-money deployment.** See §11 first item.

### Reference docs

The bulky per-phase tracking docs that drove the build (one per phase, plus a few audit/bug doc) have been removed now that this section captures the implementation summary. The substantive design notes that the plan references — [`fund_cashflow.md`](fund_cashflow.md) and [`matching_on_repayment.md`](matching_on_repayment.md) — remain in place.

### Deploy note: `applies_to_rep` flip-on for existing rules

> ⚠️ **Deploy note (applies_to_rep):** the migration that introduced `applies_to_rep` on `MatchingRule` sets it to `true` for every existing rule. On the first REP transaction processed after deploy, those rules will start producing `MAT` (matching contribution) transactions valued at `shares × shareValueAsOf` whenever the REP itself has `value = 0`. If you do not want that behaviour for some existing rules, set their `applies_to_rep` to `false` in the database before the first credit-line repayment ships.

### Review fixes (2026-05-14)

Wave-2 review on PR #3 ([review link](https://github.com/jdtogni78/FamilyFund/pull/3#pullrequestreview-4294073848)). Fixed in-PR:

| # | Fix | Commit |
|---|---|---|
| 1 | Admin gate on GET endpoints (`AccountCreditLineControllerExt::{index,show,edit}`, `AdminTransactionController::create`) + 4 Dusk negative tests | `0af3a46` |
| 2 | Deleted temporary `tour-screenshots-2026-05-14/` | `2b09460` |
| 3 | `UpdateAccountCreditLineRequest`: `delay_notification_repeat_days` rule tightened from `min:0` to `min:1` (was a `DivisionByZero` risk in `ScanLatePaymentsJob`) + feature test | `c1ccf98` |
| 4 | Added migration `2026_05_14_000001_add_delay_notification_enabled_to_account_credit_lines` and wired `LineNotificationSettings::delayNotificationEnabled()` to the column (was hard-coded `return true;`) + unit tests | `cfd0df3` |
| 5 | `OutstandingCalculator::updateAggregateBorBalance`: same-day BOR updates rewrite the open row in place instead of close-and-reopen (no more zero-length rows) + unit tests | `2689bb7` |
| 6 | Doc-only: `applies_to_rep` deploy callout (this section) | this commit |

Filed as follow-up GitHub issues (out of scope for this PR):

| Issue | Summary |
|---|---|
| [#4](https://github.com/jdtogni78/FamilyFund/issues/4) | `RepayService::sharesAlreadyPaidOnRow` partial-credit bookkeeping |
| [#5](https://github.com/jdtogni78/FamilyFund/issues/5) | `FundReceivableCalculator::receivableShares` N+1 → single JOIN |
| [#6](https://github.com/jdtogni78/FamilyFund/issues/6) | `FundExt::creditLineReceivableValueAsOf` swallows `\Throwable` |
| [#7](https://github.com/jdtogni78/FamilyFund/issues/7) | Move `ScanLatePaymentsJob` cache counter to a DB ledger |
| [#8](https://github.com/jdtogni78/FamilyFund/issues/8) | Polish migration `2026_05_13_000007` (`insertOrIgnore`, `command->info`) |

#### Non-actionable advisories (re-review wave 2)

Recorded for future awareness; not bugs, not tracked as issues:

- **Precision.** All credit-line code reads `decimal(19,4)` columns through `(float)` casts. Safe inside PHP's IEEE 754 double precision (~15-17 significant digits) for the current scale — personal-fund share counts live in the hundreds-to-low-thousands range. If balances ever approach `10^14` or single lines accumulate millions of transactions, switch to bcmath / string-decimal math. Grep confirms zero bcmath usage in the codebase today.
- **`AccountExt::sharesAsOf` return type.** Changed from a model `decimal` attribute to a plain `(float)` in Phase 0 (OWN − BOR). Verified: no `===` comparisons or bcmath calls on the old return type exist anywhere in `app/` or `tests/`. All consumers use float-safe operations (`<` / `>` / arithmetic / `Utils::shares` formatter).
- **Tax / legal §7872.** Flagged in PR body and §11 (first item). Schema has `imputed_interest_rate` as a reporting hook but does not compute. **Verify with counsel before real money flows.**
- **Browser-test copy coupling.** The Dusk assertions read flash strings and rendered page text (e.g. `assertSee('New credit line')`, `assertSee('Notification settings')`). If trustee-facing copy is reworded later, those assertions need updates. Acceptable for E2E.
