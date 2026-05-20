# Plan Review — v2 Backlog and Folded-Item History

**Status:** Active backlog doc. The 18 accepted items have been folded into the relevant plan docs (see §3 historical reference). What remains here is the **7 deferred items** in §1 — known v2 work that is intentionally not in v1 scope.
**Last Updated:** 2026-05-19 (rev 2 — collapsed folded items into historical table; v2 backlog promoted to top)
**Branch:** `worktree-bridge-cse_01XWu8DWAX1QwVBjCfpix8aE`
**Related docs:** [`credit_lines_plan.md`](credit_lines_plan.md), [`money_flow_plan.md`](money_flow_plan.md), [`money_flow_runbook.md`](money_flow_runbook.md), [`testing_plan.md`](testing_plan.md)

---

## Summary

| Bucket | Count | Where |
|---|---|---|
| **Deferred — v2 backlog** | 7 | §1 below (full text) |
| Accepted — folded into plan docs | 18 | §3 historical reference (one-line each) |
| Rejected | 1 | §4 below |

---

## 1. v2 backlog (7 deferred items)

These items were reviewed, accepted in principle, and consciously deferred out of v1 scope. They are *not* bugs in the shipped feature; they are known follow-ups for a future phase. Promote any of them into `credit_lines_plan.md` / `money_flow_plan.md` when the time comes.

### CL-R1 · Cancellation flow with disposition

**Status:** deferred.
**Why it was deferred:** v1 keeps the existing UC-12 behavior (block cancellation when `outstanding > 0`). The richer disposition handling — forgiven / force-repaid + admin gate + audit row — is captured below for when operator demand or trustee policy requires it.
**Suggested home when promoted:** new §5.12 in `credit_lines_plan.md` + UC-12 expansion in the tracker.

**Design sketch.** Add a `CreditLineCancellation` audit model (similar to `CreditLineAdjustment`) capturing `cancelled_at`, `cancelled_by_user_id`, `outstanding_shares_at_cancel`, `disposition` enum (`forgiven` / `force_repaid` / `clean` — `clean` = was already paid off), `reason`. Business rules: cancelling with outstanding shares requires admin / trustee role; books are reconciled by treating the cancelled outstanding as either a phantom REP (force-repaid) or a phantom write-off transaction (forgiven).

### CL-R4 · Concurrency on repayments

**Status:** deferred.
**Why it was deferred:** v1 ships without per-line repayment locking. Risk acknowledged — simultaneous REPs against a single line could double-credit. Mitigation in v1 is low-volume reality; family-scale traffic makes the race extremely unlikely.
**Suggested home when promoted:** extend §11 race-conditions item in `credit_lines_plan.md`.

**Design sketch.** Apply the same locking discipline already used for draws to the repayment path: `SELECT … FOR UPDATE` on the target `AccountCreditLine` row before the matcher reads the schedule. Cheap (one row lock per repayment), eliminates the race. Add an integration test that fires two parallel REPs at one line.

### CL-R9 · Pre-payment preview

**Status:** deferred.
**Why it was deferred:** v1 ships reactive only. Admins commit REPs and see results post-hoc; mistakes get undone via the CL-R2 reversal flow (shipped). Revisit if admin friction warrants.
**Suggested home when promoted:** §8.2 account-page features.

**Design sketch.** A preview action on the repay form: posts to a `/credit-lines/{id}/preview-repay` endpoint with the proposed amount; returns the projected new state (schedule advance, new `projected_payoff_date`, new variance) without creating a transaction.

### MF-R1 · Fee accounting policy

**Status:** deferred.
**Why it was deferred:** v1 records gross amounts only; fees are not first-class. **Knock-on:** the reconciliation job (MF-50 / MF-R9) needs a tolerance window equal to expected fee drift (e.g., 2%) to avoid alerting on every transfer. Runbook §4.1 codifies that tolerance.
**Suggested home when promoted:** new §5.7 in `money_flow_plan.md` (or extend §5.0 buffers section).

**Design sketch.** Add a `fees` decimal field to `CheckingDeposit`, `WiseTransfer`, and `OutboundTransfer`. Add a per-fund policy `fee_absorber` enum: `fund` / `beneficiary` / `split`. Account-page and quarterly-report sections show fees broken out from principal.

### MF-R3 · ACH clawback / NACHA return handling

**Status:** deferred.
**Why it was deferred:** v1 treats deposits as final on detection. If an ACH return happens, an operator handles it manually via the shipped CL-R2 reversal path (see `money_flow_runbook.md` §4.4 incident playbook). **Risk to track:** without listening for the bank's "ACH returned" webhook, a return outside business hours could go unnoticed; the cash leaves the checking account but the beneficiary's shares remain credited. Mitigation: the daily reconciliation job catches the balance drift and alerts the operator within 24h.
**Suggested home when promoted:** new §5.8 in `money_flow_plan.md` + §11 risks.

**Design sketch.**
- `CheckingDeposit` gets a `reversible_until` date (settlement date + 60 days) and a `reversed_at` nullable timestamp.
- A webhook event from the checking bank for "ACH returned" triggers a reversal: original `CheckingDeposit` marked reversed; the corresponding REP/PUR transaction reversed via CL-R2's reversal flow; shares un-credited.
- Notification + audit log entry to the operator and the affected beneficiary.
- For credit-line REPs: the line's outstanding goes back up; the schedule row reverts.
- Document the 60-day risk window; for very large deposits, consider holding the credit until the window closes (configurable).

### MF-R7 · Production monitoring & alerting

**Status:** deferred.
**Why it was deferred:** v1 relies on the daily reconciliation report + operator dashboard. Explicit alert thresholds + transports come later when there's an on-call rotation.
**Suggested home when promoted:** new §7.x in `money_flow_plan.md` + runbook §1 polling cadence.

**Design sketch — alert table:**

| Signal | Threshold | Severity |
|---|---|---|
| Webhook signature-failure rate | > 1% over 5 min | High (possible attack or key rotation) |
| CSV parse-error rate | > 0 in 1 hour | Medium (schema drift) |
| Reconciliation drift | > $1 on any leg | High |
| Buffer floor breach unacknowledged | > 24 hours | Medium |
| Outbound stuck in `funding_wise` | > 8 hours | Medium |
| Outbound failure rate | > 1% over 1 hour | High |

Transport: email to operator + on-call rotation; severity-high also Slacks.

### MF-R10 · Webhook burst async handling

**Status:** deferred.
**Why it was deferred:** v1 processes webhooks synchronously in the request handler. Revisit if vendors start timing out our endpoint or volume warrants async queuing.
**Suggested home when promoted:** §4.4 detection + §7.3 queue isolation in `money_flow_plan.md`.

**Design sketch.** Webhook receiver: validate signature synchronously, persist raw payload + signature, return 200 immediately, then dispatch a queued job for the actual processing. Idempotent on the payload's natural key (Wise transfer id, bank transaction id). Aligns with the existing money-flow queue isolation.

---

## 2. Top-3 to address first when promoting

If forced to pick three to revisit first, these are the ones most likely to bite:

| # | Item | Why first |
|---|---|---|
| 1 | MF-R1 (Fee accounting policy) | Without first-class fees, books won't tie out by a few dollars per transfer — the tolerance window is a workaround, not a fix. |
| 2 | MF-R3 (ACH clawback) | Real 60-day return risk; v1 catches it via daily reconciliation but the operator response is purely manual. |
| 3 | CL-R4 (Concurrency on repayments) | Cheap to add (one-row `FOR UPDATE`), eliminates a real (if rare) double-credit race. |

---

## 3. Historical — accepted items already folded into plan docs (rev 1 → rev 7/6 work)

For traceability. These 18 items came out of the rev-1 self-review and were folded into the named plan docs across multiple revisions. Listed in one line each; the plan-doc sections are the canonical home.

### Folded into `credit_lines_plan.md` rev 7

| ID | Item | Where it lives now |
|---|---|---|
| CL-R2 | Reversal of erroneous REPs (reverse-only) | §4.6 `TransactionReversal`, §5 rule 14, UC-45 |
| CL-R3 | Backdated payments via "create from scratch" admin page | §5 rule 15, UC-46 |
| CL-R5 | Block account closure when any line is `active` | §5 rule 13, UC-47 |
| CL-R6 | Admin-only line creation (no policy table) | §5 rule 12, §7 resolved decisions |
| CL-R7 | Admin-only authorization for all CL write actions | §5 rule 12, UC-48 |
| CL-R8 | Tax/legal caveat + optional `imputed_interest_rate` field | §4.1 field, §11 first risk, §12 re-stated |
| CL-R10 | Holistic "loans summary" card on account + quarterly | §8.2 card, §8.4 quarterly, UC-49 |
| CL-R11 | Share-value clarity copy near every shares balance | §8.2, §8.6 email templates, UC-50 |

### Folded into `money_flow_plan.md` rev 6 (+ runbook)

| ID | Item | Where it lives now |
|---|---|---|
| MF-R2 | FX rate of record: USD-only booking, no FX tracking | §7 resolved decisions ("FX rate: not tracked") |
| MF-R4 | First-time-sender attribution via `pending_attribution` state | §4.5.1, MF-49 tracker entry |
| MF-R5 | Two-phase coexistence of `CheckingDeposit` + `CashDeposit` | §12 migration plan, MF-55 tracker entry |
| MF-R6 | Operational runbook | New sibling doc `money_flow_runbook.md`, MF-56 |
| MF-R8 | Dev `/dev/fake-bank/*` service | §6 external API strategy, MF-57 |
| MF-R9 | Line-item reconciliation (not just balance) | §4.4 (line 294), MF-50 |
| MF-R11 | RBAC matrix for the four roles | §7.5, MF-51 |
| MF-R12 | PII content policy for notification emails | §7.4 + §8.5, MF-52 |
| MF-R13 | Idempotency-key retention: 1 year | §7.7, MF-53 |
| MF-R14 | Closure-block extended to open `DepositRequest`s | §5.7, MF-54 |

---

## 4. Rejected

| ID | Item | Reason |
|---|---|---|
| CL-R12 | BOR migration / backfill of pre-existing one-off BOR transactions | Operator confirmed no pre-existing BOR rows in production — no backfill needed. Verified empirically on 2026-05-14 by scanning both backups (zero BOR/REP transactions; see `credit_lines_plan.md` §12 UC-21 note). |

---

## 5. How to consume this doc

- §1 is the **active backlog**. Read top-to-bottom; promote any item by moving its body into the named plan section and removing it here.
- §3 is **historical reference only** — those items are already in the plan docs; this table just tells you where to find each one.
- This doc is not a TODO list for v1 work. It is a forward-looking backlog for a future phase.
