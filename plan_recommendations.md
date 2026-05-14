# Plan Review — Recommended Additions (pending user response)

**Status:** Recommendations from a self-review of `credit_lines_plan.md` and `money_flow_plan.md`. Not yet incorporated into those plans.
**Last Updated:** 2026-05-13 (rev 1)
**Branch:** `claude/plan-credit-lines-cCjWG`
**Related docs:** [`credit_lines_plan.md`](credit_lines_plan.md), [`money_flow_plan.md`](money_flow_plan.md), [`testing_plan.md`](testing_plan.md)

This doc is a parking lot for review findings the user will respond to later. Each item has a **Status** field — flip from `pending` to `accepted` / `rejected` / `deferred` as you respond, then move the accepted ones into the relevant plan doc.

---

## Status summary (all 26 items reviewed)

**Accepted for v1 — 16 items** (need to be folded into the plan docs):

| ID | Item | Resolution shape |
|---|---|---|
| CL-R2 | Reversal of erroneous REPs | Simplified: reverse-only, no chained reapply |
| CL-R3 | Backdated payments | Different: "create transaction from scratch" page, no new column |
| CL-R5 | Block account closure with outstanding lines | v1 blocks; trustee override deferred |
| CL-R6 | Underwriting limits | Different: admin-only line creation, no policy fields |
| CL-R7 | Authorization model | Admin-only for all credit-line write actions |
| CL-R8 | Tax / legal caveat | Callout in risks + optional `imputed_interest_rate` field |
| CL-R10 | Holistic "your loans" statement | Account page + quarterly report |
| CL-R11 | Share-value clarity | Copy only (no $/share toggle) |
| MF-R2 | FX rate of record | Simplified: book only the USD that arrives, no FX tracking |
| MF-R4 | First-time-sender attribution | Simplified: pending-attribution state + operator assigns; no auto-recipient creation |
| MF-R5 | CashDeposit coexistence | Two-phase: dual-write then converge |
| MF-R6 | Operational runbook | New sibling doc `money_flow_runbook.md` |
| MF-R8 | Dev fakes for money APIs | Build `/dev/fake-bank/*` local service |
| MF-R9 | Line-item reconciliation | Extend daily job to per-line matching |
| MF-R11 | RBAC matrix | Single table in §7.5 |
| MF-R12 | PII email policy | No PII to beneficiaries; redacted PII to operators |
| MF-R13 | Idempotency-key retention | 1 year |
| MF-R14 | Closed-account in-flight | Resolved by extending CL-R5's closure-block to open DepositRequests |

**Deferred to future improvement — 8 items** (kept in this doc as known backlog):

| ID | Item | Reason |
|---|---|---|
| CL-R1 | Cancellation flow with disposition | v1 keeps existing "block if outstanding > 0" |
| CL-R4 | Concurrency on repayments | Low risk at family-scale; lock added later |
| CL-R9 | Pre-payment preview | Add if admin friction warrants |
| CL-R12 | BOR migration | Caveat: rejected per user; listed here for completeness — re-categorize as rejected below |
| MF-R1 | Fee accounting policy | Track via tolerance window in reconciliation for v1 |
| MF-R3 | ACH clawback handling | Manual operator handling via CL-R2 reversal |
| MF-R7 | Production monitoring alerts | Rely on reconciliation report + dashboard for v1 |
| MF-R10 | Webhook burst async handling | Sync-in-request for v1; async if vendors time out |

**Rejected — 1 item:**

| ID | Item |
|---|---|
| CL-R12 | BOR migration (operator confirmed no concern) |

**Net result:** 18 accepted items will be folded into `credit_lines_plan.md` / `money_flow_plan.md` / a new `money_flow_runbook.md`. 7 deferred items stay in this doc as the known v2 backlog. 1 rejected item closed.

---

---

## Top 6 — would address first if forced to pick

These are the items most likely to bite in production or block v1 launch. Three from each plan.

| # | Item | Plan | Why first |
|---|---|---|---|
| 1 | Cancellation flow for credit lines | credit_lines | Guaranteed real-world operation, currently undefined |
| 2 | Reversal / refund of erroneous REPs | credit_lines | Operator-error happens; no audit-safe way to undo today |
| 3 | Backdated payments / `effective_date` | credit_lines | Will hit on day one of operator use |
| 4 | Fee accounting policy (who pays Wise / bank fees, where they're recorded) | money_flow | Without this, books won't tie out by a few dollars per transfer |
| 5 | ACH clawback / NACHA-return handling | money_flow | Real risk over 60-day return window; currently invisible in the plan |
| 6 | First-time-sender attribution in Path A (v1) | money_flow | Plan implicitly assumes every deposit is pre-registered; reality won't match |

---

## Credit lines recommendations

### CL-R1 · Cancellation flow

**Status:** **future improvement** — v1 keeps the existing UC-12 behavior (block when outstanding > 0). Richer disposition handling (forgiven / force-repaid + admin gate + audit row) is captured below for a future phase.
**Suggested section (when promoted):** new §5.12 in `credit_lines_plan.md` + tracker entries UC-12 expanded

**What's missing.** UC-12 only covers the happy path ("cancel when outstanding = 0, blocked otherwise"). The plan doesn't say what happens for:
- Cancelling a line with outstanding shares (write-off? forgiveness? forced repayment?).
- Who can authorize a non-trivial cancellation (borrower, admin, trustee).
- Audit trail: does cancellation get its own immutable row, like `CreditLineAdjustment`?

**Suggested resolution.** Add a `CreditLineCancellation` audit model (similar to `CreditLineAdjustment`) capturing `cancelled_at`, `cancelled_by_user_id`, `outstanding_shares_at_cancel`, `disposition` enum (`forgiven` / `force_repaid` / `clean` — clean = was already paid off), `reason`. Add business rules: cancelling with outstanding shares requires an admin / trustee role; books are reconciled by treating the cancelled outstanding as either a phantom REP (force-repaid) or a phantom write-off transaction (forgiven).

### CL-R2 · Reversal / refund of erroneous REPs

**Status:** **accepted (simplified for v1)** — v1 implements reverse-only: operator can reverse a matched REP, which marks the transaction `reversed`, re-opens schedule rows, recomputes outstanding, and writes the audit row. If the operator wants to reapply to a different line, they do it as a separate manual REP. The chained "reverse-and-reapply" UI action is a future improvement.
**Suggested section:** new §5.13 + UC-45 tracker entry. UC-46 (chained reapply) becomes a future-improvement item.

**What's missing.** No mechanism to undo a REP after it's been auto-matched (or manually assigned). Common failure: operator processes a REP, system matches to line A, borrower says it was for line B.

**Suggested resolution.** Add a reversal operation that:
- Marks the original transaction `reversed = true` (don't delete it).
- Re-opens any `CreditLinePayment` rows the REP had marked `paid` / `partial` (status → `scheduled`).
- Recomputes line `outstanding_shares`.
- Writes an immutable `TransactionReversal` audit row.
- Optionally creates a fresh REP transaction targeting the correct line.
- Doesn't break the trajectory chart — reversal is rendered as a return on the actual-repayments series.

### CL-R3 · Backdated payments

**Status:** **accepted (different resolution)** — instead of a separate `effective_date` column, the operator gets a "create transaction from scratch" page with full field control, including the timestamp. The existing matcher and late-detection logic already use `transaction.timestamp`, so backdating works correctly as long as the UI exposes timestamp on create. No schema change.
**Suggested section:** New UC entry for "operator creates transaction from scratch with full control over timestamp / type / shares / value / account / target line." Add a short note in `credit_lines_plan.md` §8 or wherever the transaction-creation UI is referenced.

**Original problem.** Plan assumes `transaction.timestamp = now`. Operator processes a check the borrower mailed two weeks ago; the system records "today" but the payment was effectively two weeks ago. Late-flag computation, schedule matching, and trajectory all use the wrong date.

**Original proposal (superseded).** Add a nullable `effective_date` column. Not needed — letting the operator set `timestamp` on the create form covers the same ground with no new schema.

### CL-R4 · Concurrency on repayments

**Status:** **deferred / future improvement** — v1 ships without per-line repayment locking. Risk acknowledged: simultaneous REPs against a single line could double-credit. Mitigation in v1 is low-volume reality. To add later: `SELECT … FOR UPDATE` on the `AccountCreditLine` row at the start of every repayment transaction, plus an integration test for two parallel REPs.
**Suggested section (when promoted):** §11 risks (extend race-conditions item)

**What's missing.** §11 covers concurrent *draws* with `SELECT … FOR UPDATE`. Concurrent *repayments* could each mark the same `CreditLinePayment` row `paid`, double-decrement outstanding, or split-credit ambiguously.

**Suggested resolution.** Apply the same locking discipline to the repayment path: `SELECT … FOR UPDATE` on the target `AccountCreditLine` row before the matcher reads the schedule. Cheap (one row lock per repayment), eliminates the race.

### CL-R5 · Account closure with outstanding lines

**Status:** **accepted (v1 blocks; override deferred)** — v1 unconditionally blocks account closure when any credit line has `outstanding > 0`. Operator gets a clear "close lines first" error. The trustee override that lets an admin forgive remaining outstanding becomes a future improvement when CL-R1 ships.
**Suggested section:** §5 business rules in `credit_lines_plan.md` as a new rule; new UC entry.

**What's missing.** Beneficiary leaves the fund / account is deactivated — what happens to active credit lines?

**Suggested resolution.** Default rule: block account closure until all lines are `paid_off` or `cancelled`. Add an admin override for the family-trust edge case (e.g., minor beneficiary closes account, trustee forgives the outstanding). The override goes through CL-R1's cancellation flow with `disposition = forgiven`.

### CL-R6 · Underwriting / eligibility limits (per-fund config)

**Status:** **accepted (different resolution)** — instead of machine-enforced limits, **only admins (trustees) can open credit lines**. Borrower self-service is removed; every draw goes through an admin who exercises human judgment on size, frequency, eligibility. Simpler than a policy table and more aligned with how a family trust actually operates. This also resolves CL-R7 (authorization): line creation is admin-gated by default.
**Suggested section:** §5 business rules (new rule "Line creation requires admin role"). Update §7 resolved decisions for authorization.

**What's missing.** No limits on draw size, concurrent lines, account age, or cool-down. "Any person can borrow against their own shares" is the principle but the implementation needs guardrails.

**Suggested resolution.** A per-fund policy table with:
- `max_principal_per_line` (USD or share count, configurable).
- `max_concurrent_active_lines` per account.
- `min_account_age_days` before first draw.
- `cooldown_days_after_payoff` before opening a new line.
- `max_total_outstanding_per_account` (haircut on OWN shares).

All optional / nullable so each fund decides what to enforce.

### CL-R7 · Authorization model resolution

**Status:** **accepted** — v1 RBAC for credit lines is uniformly **admin-only** for every write action:
- Open a line: admin only.
- Submit a REP (manual entry): admin only.
- Readjust a line's term: admin only.
- Resolve an ambiguous/unmatched flagged REP: admin only.
- Cancel a line (existing UC-12 "block when outstanding > 0" path): admin only.

**Borrowers / account owners have read-only access** to their credit-line views (per-line drill-down, schedule, trajectory chart, adjustment history). They don't submit any write action directly.

**Auto-generated REPs** (the matcher attributing a detected `CheckingDeposit` to a line) bypass the admin gate — the system did it, no human action required. Admin involvement only kicks in when the matcher flags `ambiguous` or `unmatched`.

**Suggested section:** §7 resolved decisions — flip "Authorization" from open to closed with the rules above.

**What's missing.** Current assumption is "mirror existing transaction-creation auth." For a family trust, parents likely want approval over kids' draws. Today: undefined.

**Suggested resolution.** Make this an explicit per-fund setting: `borrower_can_self_serve` (bool) and `approver_role_required_above` (USD threshold, nullable). When approval is required, the draw enters a `pending_approval` status and the trustee/parent gets an in-app notification + email.

### CL-R8 · Tax / legal caveat callout

**Status:** **accepted (callout + optional field)** — add the "verify with tax counsel before real-money deployment; no-interest loans may trigger imputed-interest reporting (IRS §7872) and gift/income-tax implications" caveat to §11 risks. Also add an optional nullable `imputed_interest_rate` field on `AccountCreditLine` that's purely informational for tax-reporting purposes — it does **not** affect the share math.
**Suggested section:** §11 risks (caveat); §4.1 `AccountCreditLine` schema (the optional field).

**What's missing.** No-interest loans from a family trust to a beneficiary have specific US tax treatment (IRS §7872 imputed-interest rules below the AFR, family-trust distribution rules). Plan currently silent.

**Suggested resolution.** Add a "Verify with tax counsel before deployment — the no-interest design may create imputed-interest reporting obligations" caveat. Not asking Claude to design the tax treatment; just flagging the requirement. Possibly add an optional `imputed_interest_rate` field on `AccountCreditLine` that's purely informational for reporting and doesn't affect the share math.

### CL-R9 · Pre-payment preview

**Status:** **deferred / future improvement** — v1 ships reactive only. Admins commit REPs and see results post-hoc; mistakes get undone via CL-R2 reversal. Revisit if admin friction warrants.
**Suggested section (when promoted):** §8.2 account page features

**What's missing.** Before submitting a repayment, the borrower should see "if I pay X shares: payments 3 & 4 marked paid, projected payoff moves to Y, variance becomes Z." Currently the UI is reactive (after-the-fact display only).

**Suggested resolution.** Add a preview action on the repay form: posts to a `/credit-lines/{id}/preview-repay` endpoint with the amount; returns the projected new state (schedule advance, new `projected_payoff_date`, new variance). No transaction created.

### CL-R10 · Holistic "your loans" statement

**Status:** **accepted** — add a "Loans summary" section on the account page (above the per-line table) showing total disbursed lifetime, total repaid lifetime, net outstanding, next-due across all lines, and a per-line summary row. Extend the §8.4 quarterly report (account section) to include the same summary.
**Suggested section:** §8.2 account page + §8.4 quarterly report extension.

**What's missing.** Trajectory chart is per-line. A borrower with three active lines wants a single summary at the top: total outstanding across lines, total disbursed lifetime, total repaid lifetime, next-due across all lines, total interest paid (n/a; useful as a "shares paid" or "value paid back" figure).

**Suggested resolution.** Add a "Loans summary" card at the top of the account page, above the per-line table. Same data, aggregated.

### CL-R11 · Borrower-facing share-value clarity

**Status:** **accepted (copy only)** — add explanatory copy near every shares-denominated balance: "You owe N shares (currently valued at $X). The share count is what you owe back — it doesn't change with the market. The dollar value will move up or down with the fund's share price." Apply to account-page balances, trajectory chart caption, per-line drill-down headers, and transaction emails. The dollar/share toggle on the chart becomes a future improvement.
**Suggested section:** §8.2 account page UI notes + §8.6 email-template updates.

**What's missing.** Math is in shares; the borrower thinks in dollars. When the share price falls, "I owe fewer dollars now" feels like the debt got cheaper (true in dollars, false in shares). Risks confusion and support burden.

**Suggested resolution.** Add explicit UI copy near every shares-denominated balance: "You owe N shares, currently valued at $X — this dollar amount changes with the market; the share count does not." Apply to: account page balances, trajectory chart, statements, emails.

### CL-R12 · Migration of existing BOR transactions

**Status:** **rejected** — no concern about pre-existing one-off BOR transactions; the operator either knows there are none or doesn't need them migrated. Proceed with the new model without a backfill step.
**Suggested section (n/a — rejected):** —

**What's missing.** The codebase already supports `BOR` as a transaction type. Any pre-existing one-off BOR transactions in production data — how do they map to the new model? Plan doesn't address.

**Suggested resolution.** Survey production data first. If there are existing BOR rows, either: (a) leave them with `account_credit_line_id = NULL` and `credit_line_match_status = NULL` (treat as legacy); (b) backfill into synthetic `AccountCreditLine` rows with `status = legacy_imported`. Decision depends on volume.

---

## Money flow recommendations

### MF-R1 · Fee accounting policy

**Status:** **deferred / future improvement** — v1 records gross amounts only; fees are not first-class. **Knock-on:** the reconciliation job (MF-40 / MF-R9) needs a tolerance window equal to expected fee drift (e.g., 2%) to avoid alerting on every transfer. Without that tolerance, every healthy transfer would look like a reconciliation failure.
**Suggested section (when promoted):** new §5.7 or extend §5.0 buffers

**What's missing.** Wise charges FX fees (~0.5–1.5%). Mercury / Relay may charge wire fees, sometimes ACH fees. Plan doesn't specify:
- Where fees are recorded.
- Who absorbs them (borrower, fund, beneficiary).
- How they appear in reports.

**Suggested resolution.** Add a `fees` decimal field to `CheckingDeposit`, `WiseTransfer`, and `OutboundTransfer`. Add a per-fund policy `fee_absorber` enum: `fund` (the fund eats them), `beneficiary` (added to outbound or deducted from inbound), `split`. Account-page and quarterly report sections show fees broken out from principal.

### MF-R2 · FX rate of record for accounting

**Status:** **accepted (simplified)** — the system books only the **final USD value that arrives at the checking account**. No FX rate is stored on our side; conversion is an upstream Wise-side concern that doesn't affect our books. `CheckingDeposit.amount` is the USD amount; beneficiary's share credit is computed from that USD × current share price. Borrowers can see the BRL→USD detail in their own Wise app; the FamilyFund system shows what actually arrived. This nicely closes Q5 in §11 — no FX rate of record because no FX rate is needed.
**Suggested section:** §7 resolved decisions ("FX rate: not tracked; the system records only the USD value that arrives at checking. FX conversion is upstream and out of scope.").

**What's missing.** Three candidates for "the rate we book at": Wise's quoted mid-market, the realized conversion rate, daily-fixed BACEN rate. Each gives different fund NAV implications and different beneficiary-statement experiences.

**Suggested resolution.** Recommend: **realized conversion rate** (the rate Wise actually used) as the system of record. It's what the money actually moved at, what shows on the Wise statement, and what the beneficiary sees if they check their Wise account. The trade-off — NAV moves intra-day with the actual conversion — is real but minor for family-scale volumes.

### MF-R3 · ACH clawback / NACHA return handling

**Status:** **deferred / future improvement** — v1 treats deposits as final on detection. If an ACH return happens, an operator handles it via CL-R2's manual reversal path (once that ships). **Risk to track:** without listening for the bank's "ACH returned" webhook, a return that arrives outside business hours could go unnoticed; the cash leaves the checking account but the beneficiary's shares remain credited. Mitigation: the daily reconciliation job (when added) catches the balance drift and alerts the operator within 24h.
**Suggested section (when promoted):** new §5.8 + risks

**What's missing.** NACHA rules let an ACH be reversed up to 60 days after settlement. If a beneficiary's deposit lands → system credits their shares → 30 days later the ACH is reversed → shares already credited, money is gone.

**Suggested resolution.**
- `CheckingDeposit` gets a `reversible_until` date (settlement date + 60 days) and a `reversed_at` nullable timestamp.
- A webhook event from the checking bank for "ACH returned" triggers a reversal: original CheckingDeposit marked reversed; the corresponding REP/PUR transaction reversed via CL-R2's reversal flow; shares un-credited.
- Notification + audit log entry to the operator and the affected beneficiary.
- For credit-line REPs: the line's outstanding goes back up; the schedule row reverts.
- Document that there's a 60-day risk window; for very large deposits, consider holding the credit until the window closes (configurable).

### MF-R4 · First-time-sender attribution in Path A (v1)

**Status:** **accepted (simplified for v1)** — Add `attribution_status = pending` state for unmatched `CheckingDeposit` rows and surface them on the operator dashboard. The operator manually assigns to a beneficiary. **No** automatic recipient-registry creation on assign (operator does it as a separate step if useful). **No** escalation emails in v1. Recipient registry auto-creation and the 14-day escalation become future improvements.
**Suggested section:** Extend §4.5.1 of `money_flow_plan.md` with the pending-attribution state. UC MF-R4a (pending-attribution) goes into the tracker. MF-R4b (auto-create recipient on assign) becomes a future improvement.

**What's missing.** Plan covers Path B's "first-time sender → draft recipient pending operator confirmation." Path A (v1) has no equivalent. Plan implicitly assumes every deposit is pre-registered via a `DepositRequest`. Reality: beneficiaries forget; one-off transfers happen.

**Suggested resolution.** When a `CheckingDeposit` arrives with no matching `DepositRequest`:
- Don't auto-credit. Flag the deposit with status `pending_attribution`.
- Show on operator dashboard with the sender description, amount, date.
- Operator assigns it to a beneficiary; if it's a new Wise sender, that assignment also creates / updates the `BrazilianRecipient` registry row so subsequent transfers from the same sender attribute automatically (fingerprint accumulation).
- After N days unattributed (configurable, e.g., 14), operator gets escalation email.

### MF-R5 · Backfill / coexistence with existing CashDeposit flow

**Status:** **accepted (two-phase coexistence)** — Phase 1: both flows alive, reconciler links each `CheckingDeposit` to its eventual `CashDeposit` once funds settle at IBKR; mismatches alert the operator. Phase 2 (after 30+ days clean): `CheckingDeposit` is primary; `CashDeposit` becomes pure validation. Existing pre-feature `CashDeposit` rows stay as-is — no retroactive backfill.
**Suggested section:** new §13 migration plan in `money_flow_plan.md`.

**What's missing.** The codebase has a working `CashDeposit` flow via IBKR CSV today. When the checking-hub flow lands, do existing rows backfill into the new `CheckingDeposit` model? Are both flows alive simultaneously?

**Suggested resolution.** Two-phase migration:
1. Both flows alive. `FetchDeposits` job continues to run and create `CashDeposit` rows from IBKR CSV (as the IBKR-side reconciler — §4.4). The new `CheckingDeposit` rows from the webhook are the primary detection point. They get linked.
2. Once the webhook flow is proven on 30+ days of dual-run data with zero drift, the IBKR-side becomes pure reconciliation; the `CashDeposit` rows from CSV are validation, not primary records.

Existing pre-feature `CashDeposit` rows: leave as-is, no `CheckingDeposit` peer.

### MF-R6 · Operational runbook for v1 launch

**Status:** **accepted** — create `money_flow_runbook.md` as a sibling doc with daily / weekly / monthly task lists + incident playbooks. Light cross-reference from `money_flow_plan.md` §1.5.

**What's missing.** Plan covers what code does, not what humans do. Day-1 of running this — what does the operator do each morning?

**Suggested resolution.** A short separate runbook covering:
- **Daily** — check overnight reconciliation report; resolve flagged transactions; review buffer-breach signals; approve pending outbounds; sweep if needed.
- **Weekly** — sandbox contract test review; deposit-request stale-cleanup; recipient registry verification (any pending verification past N days).
- **Monthly** — quarterly-report preview; KYB renewal check.
- **Incident playbooks** — webhook signature failure spike; reconciliation drift > $X; vendor outage.

### MF-R7 · Production monitoring & alerting

**Status:** **deferred / future improvement** — v1 relies on the daily reconciliation report + operator dashboard. Explicit alert thresholds + transports come later when there's an on-call rotation.

**What's missing.** Tests catch dev-time issues; alerting catches runtime issues. Plan doesn't list either the metrics or the thresholds.

**Suggested resolution.** Define alerts:
| Signal | Threshold | Severity |
|---|---|---|
| Webhook signature-failure rate | > 1% over 5 min | High (possible attack or key rotation) |
| CSV parse-error rate | > 0 in 1 hour | Medium (schema drift) |
| Reconciliation drift | > $1 on any leg | High |
| Buffer floor breach unacknowledged | > 24 hours | Medium |
| Outbound stuck in `funding_wise` | > 8 hours | Medium |
| Outbound failure rate | > 1% over 1 hour | High |

Transport: email to operator + on-call rotation; severity-high also Slacks.

### MF-R8 · Dev environment fakes for money APIs

**Status:** **accepted** — build a Laravel-side `/dev/fake-bank/*` service (only when `APP_ENV=local`) with a UI to fire webhook payloads and store fake state. Unblocks local end-to-end development without sandbox credentials.
**Suggested section:** §6 external API strategy in `money_flow_plan.md`, plus a brief note in the local-dev README.

**What's missing.** Dev stack has MailHog for email. No equivalent "fake Wise" or "fake Mercury" — meaning local development against the webhook flow either hits real sandbox (slow, requires secrets) or stays purely test-driven (no exploratory hands-on).

**Suggested resolution.** A small Laravel-side "fake banking" service in dev that:
- Listens on a sub-route `/dev/fake-bank/*`.
- Has an operator UI to fire webhooks (incoming ACH, outgoing ACH, returns).
- Stores fake state so multi-step flows work end-to-end without external APIs.
- Only enabled when `APP_ENV=local`.

P0 task because it unblocks all the rest of the local development.

### MF-R9 · Line-item reconciliation (not just balance)

**Status:** **accepted** — daily reconciliation job does per-line matching: every `CheckingDeposit` row must have a corresponding bank-statement line; every `OutboundTransfer` step has matching debits at each hop. Unmatched line items raise alerts, not just balance gaps.
**Suggested section:** §4.4 reconciliation (extend).

**What's missing.** §4.4 mentions three-way balance reconciliation (sum of A ↔ sum of B ↔ sum of C). That catches gross drift but misses per-line drift (a single missing $50 deposit hidden by a single extra $50 elsewhere).

**Suggested resolution.** Add per-line reconciliation: every `CheckingDeposit` row must have a matching bank-statement line; every `OutboundTransfer` step has matching debits at each hop. Daily report shows unmatched line items, not just balance gaps.

### MF-R10 · Webhook burst handling

**Status:** **deferred / future improvement** — v1 processes webhooks synchronously in the request handler. Revisit if vendors start timing out our endpoint or volume warrants async queuing. The async design (validate sig sync, persist, return 200, queue) is the planned upgrade path.
**Suggested section (when promoted):** §4.4 detection + §7.3 queue isolation.

**What's missing.** Plan doesn't say whether webhook processing is synchronous (in the request) or queued (async). Wise can send several status updates back-to-back; bank webhooks for a busy account can burst.

**Suggested resolution.** Webhook receiver: validate signature synchronously, persist raw payload + signature, return 200 immediately, then dispatch a queued job for the actual processing. Idempotent on the payload's natural key (Wise transfer id, bank transaction id). Aligns with the existing money-flow queue isolation in §7.3.

### MF-R11 · RBAC matrix for the four roles

**Status:** **accepted** — add a single role × action matrix in §7.5 covering account owner / operator / approver / trader for every money-flow action (create DepositRequest, initiate outbound, approve, attribute deposit, sweep, edit registry, trade, adjust buffer settings). Per CL-R7, the credit-line side is admin-only — the money-flow side keeps the existing role separation but is now explicit in one table.
**Suggested section:** §7.5 authorization (extend with the matrix).

**What's missing.** Roles mentioned: `money_flow_operator`, `money_flow_approver`, `trader`, plus implicitly the account owner (beneficiary). Permissions are scattered across §5.x and §7.5.

**Suggested resolution.** A single matrix:

| Action | Account owner | Operator | Approver | Trader |
|---|---|---|---|---|
| Create DepositRequest | ✓ (own account) | ✓ (any) | — | — |
| Initiate outbound | — | ✓ | — | — |
| Approve outbound | — | (only as 2nd approver if distinct) | ✓ | — |
| Manually attribute / resolve flagged deposit | — | ✓ | — | — |
| Sweep IBKR ↔ checking | — | ✓ | — | ✓ |
| Update recipient registry | ✓ (own) | ✓ (any) | — | — |
| Trade securities at IBKR | — | — | — | ✓ |
| Adjust buffer settings | — | — | ✓ | — |

### MF-R12 · PII content policy for notification emails

**Status:** **accepted** — beneficiary-facing emails contain amount, type, date, link to the app. No CPF, no full account number, no PIX key. Operator-bound emails (reconciliation, alerts) may contain redacted PII (last-4, masked CPF). Apply to all transaction-event templates.
**Suggested section:** §7.4 secret isolation + §8.5 reminder/notification templates.

**What's missing.** Beneficiaries get emails when transactions are detected. What goes in them? Full amount? Last-4 of recipient account? CPF?

**Suggested resolution.** Policy: emails to beneficiaries contain amount, type (deposit / repayment), date, and a link to the app. **No** PII (no CPF, no full account number, no PIX key). Operator-bound emails (reconciliation, alerts) may contain redacted PII (last-4, masked CPF) because they're internal.

### MF-R13 · Idempotency-key retention window

**Status:** **accepted (1 year)** — idempotency keys retained for 1 year (covers ACH return window plus dispute-resolution edge cases). Stored in `mf_idempotency_keys` with `expires_at`; daily job purges expired rows.
**Suggested section:** §7.7 idempotency.

**What's missing.** Plan says "idempotent retries" but not for how long the key cache lives.

**Suggested resolution.** Minimum **90 days** (covers the ACH return window). Stored in `mf_idempotency_keys` with `expires_at`; daily job purges expired rows.

### MF-R14 · Beneficiary account closure with money in flight

**Status:** **resolved by closure rule** — instead of handling closed-account deposits at attribution time, **block account closure while any open `DepositRequest` exists** (parallel to CL-R5 blocking closure with outstanding credit lines). Operator must wait for the request to close (or cancel it) before closing the account. The closed-account in-flight scenario can't arise.
**Suggested section:** extend the same §5 business rule used by CL-R5 to also cover open DepositRequests.

**What's missing.** Beneficiary closes their FamilyFund account; a deposit lands at checking the next day with their old `DepositRequest` matching. What now?

**Suggested resolution.** Closed-account deposits enter `orphan_attribution` state: not credited, flagged for operator. Operator either reopens the account temporarily to apply, or initiates a refund outbound back to the sender (which itself is a hard problem — see MF-R3 reversal).

---

## How to consume this doc

Read top-to-bottom; for each item:
- Flip **Status** from `pending` to `accepted` / `rejected` / `deferred`.
- For `accepted` items, write a one-line note on which plan section it belongs in (most have suggestions).
- I'll then move the accepted items into the corresponding plan and delete the rows here.

A `deferred` item stays in this doc as a known-but-not-now backlog.
