# Money Flow — Operational Runbook

**Status:** Draft — covers v1 of the money-flow subsystem
**Last Updated:** 2026-05-13 (rev 1)
**Companion to:** [`money_flow_plan.md`](money_flow_plan.md) — the runbook describes what the operator does; the plan describes what the code does.

This doc is the operator's day-to-day reference for running the money-flow subsystem after v1 launch. It assumes the v1 scope per `money_flow_plan.md` §1.5: Wise → fund US checking → IBKR, attribution at the checking-webhook, admin role gates all credit-line writes.

---

## 1. Daily checklist (typical sitting: 10–15 minutes)

Performed each business morning, ideally before the trader looks at the portfolio.

| # | Task | Where | What you're looking for | Action if not clean |
|---|---|---|---|---|
| 1 | **Reconciliation report** | Operator dashboard or daily email | "All legs match" or "drift detected on X". | Investigate the named leg. See incident playbook §4.1. |
| 2 | **Flagged transactions** | Account-page banner / operator dashboard | Count of `ambiguous` / `unmatched` BOR/REP transactions across all accounts. Target: zero unresolved overnight. | Open each flagged item, assign to a line, save. |
| 3 | **Pending-attribution deposits** | Operator dashboard `CheckingDeposit` filter `attribution_status = pending` | Should be empty most mornings. A few rows is fine if recent. Old (>3 days) rows shouldn't linger. | Assign each to a beneficiary, or initiate refund. |
| 4 | **Buffer signals** | Operator dashboard | `checking_cash_floor` not breached; `checking_cash_ceiling` not exceeded. | If floor: notify trader, consider sweep IBKR→checking. If ceiling: notify trader, consider sweep checking→IBKR. |
| 5 | **Pending outbounds awaiting approval** | Operator dashboard | Any draft outbounds older than 24h pending approval. | Approve (if you're the approver and approval criteria met) or follow up. |
| 6 | **Webhook health (light)** | Server log search for `WebhookSignature failed` and `ParseError` in the last 24h | Zero of each. | See incident playbook §4.2. |

---

## 2. Weekly checklist (sitting: 20–30 minutes, e.g. Mondays)

| # | Task | What you're checking |
|---|---|---|
| 1 | **Sandbox contract test status** | The nightly sandbox CI job from `testing_plan.md` §6.2 should have been green every night. Investigate any red day. |
| 2 | **Stale DepositRequest cleanup** | Open `DepositRequest`s with `expected_window` ending more than 14 days ago that never matched. Cancel them or follow up with the beneficiary. |
| 3 | **Recipient registry verification queue** | `BrazilianRecipient` rows with `status = pending_verification` for more than 7 days. Either complete the verification or remove. |
| 4 | **Audit-log spot check** | Open `mf_audit_log` for the week, scan for any `action = manual_override` rows. Each should have a written reason; flag anything looking unusual. |

---

## 3. Monthly checklist (sitting: 30–60 minutes)

| # | Task | What you're checking |
|---|---|---|
| 1 | **Quarterly-report preview** (in months 3/6/9/12) | Generate the quarterly report against current data and skim before the scheduled queue job runs. Catches template breakage early. |
| 2 | **KYB / KYC renewal** | If the bank (Mercury / Relay) requires periodic KYB confirmation, check the calendar; complete before deadline. |
| 3 | **Unattributed-deposit aging** | Any `pending_attribution` rows older than 30 days. These are either orphaned cash or operational oversight — resolve them. |
| 4 | **Idempotency-key purge sanity** | Confirm the daily purge job is dropping expired rows. Table size should be roughly bounded by 1 year × volume. |
| 5 | **Buffer-policy review** | Look at the last month of buffer-breach signals. Adjust `checking_cash_floor` / `ceiling` if signals are too noisy or too quiet. |

---

## 4. Incident playbooks

Each playbook: trigger → immediate steps → escalation.

### 4.1 Reconciliation drift > $X on any leg

**Trigger:** Daily reconciliation report emails "drift detected on Wise/Checking/IBKR leg" or shows unmatched line items.

**Immediate steps:**
1. Open the report. Identify which leg drifted and by how much.
2. **Balance drift** (sum mismatch): export the daily transactions on each leg, diff them. Most common cause: a `CheckingDeposit` that hasn't yet been linked to a `CashDeposit` (timing — expected within 1–3 days), or vice versa. If outside the timing window, look for a missing transaction.
3. **Line-item drift** (per MF-R9): the report names the unmatched bank-statement line or our-side row. Cross-check against the bank's web UI to confirm the line is real and ours, then create or correct the matching internal row.
4. **Fee drift** (per MF-R1 deferred decision): a transfer-sized drift roughly matching the expected fee tolerance is *expected* in v1 — confirm by checking the Wise / bank fee on the suspect transaction. If so, no action.

**Escalation:** Persistent drift > 24h that isn't fee-tolerance → page the developer + trustee. Do not initiate new outbounds until resolved.

### 4.2 Webhook signature-failure spike

**Trigger:** Server log shows multiple `WebhookSignature failed` events in a short window.

**Immediate steps:**
1. Open the bank vendor's dashboard. Confirm the webhook-signing secret hasn't been rotated.
2. Check whether the failed payloads share an IP / source. Could be a probe / attack — let the developer know.
3. If the secret is fine and source is the vendor's IP, the vendor may be using a different signing scheme on a subset of events — read recent API changelog notices.

**Escalation:** > 5 minutes of continuous signature failures → assume compromise risk. Disable the webhook endpoint at the load balancer (or via a feature flag), notify developer + trustee, do not re-enable until investigation completes.

### 4.3 Vendor outage (Wise / Mercury / Relay)

**Trigger:** API calls return 5xx / timeouts repeatedly; webhooks stop arriving.

**Immediate steps:**
1. Check the vendor's status page.
2. Confirm via their dashboard whether your specific account is affected or it's a global outage.
3. **Inbound impact:** detection is degraded; deposits land at the bank but the system doesn't see them. They'll be picked up by the IBKR-side reconciler when settled (Phase 1 of MF-R5) — but attribution is delayed.
4. **Outbound impact:** in-flight `OutboundTransfer` rows are stuck. They will not be auto-reversed — they wait for the vendor to recover. Communicate to any waiting recipients.

**Escalation:** Outage > 4 hours → trustee briefing email. Outage > 24 hours → consider switching the inbound webhook to manual CSV reconciliation; consult `money_flow_plan.md` §3.6 for the fallback vendor option.

### 4.4 ACH return notification (when MF-R3 ships)

**Note:** MF-R3 (ACH clawback) is deferred for v1 (see `plan_recommendations.md`). If an ACH return notification arrives **in v1**, there is no automated handler. The operator handles it manually:

**Immediate steps (v1 manual handling):**
1. The bank notifies you (email or dashboard) that an ACH credit has been reversed.
2. Identify the affected `CheckingDeposit` and trace through to the credit-line REP (or `DepositRequest`) it was attributed to.
3. Use the credit-lines reversal flow (CL-R2 in v1) to reverse the downstream REP — `Transactions > Reverse` admin action.
4. Manually adjust the operator-facing reconciliation report to note the return.
5. Notify the affected beneficiary by email (clear language: "the deposit we credited has been reversed by the originating bank; please contact us").
6. Add an `mf_audit_log` row with `action = ach_return_manual_handled`.

**Escalation:** Two+ returns from the same beneficiary in 90 days → trustee briefing.

### 4.5 Operator role compromise / lost credentials

**Trigger:** Suspected compromised account, lost 2FA device, etc.

**Immediate steps:**
1. Rotate the operator's session tokens (admin UI or DB).
2. Rotate the bank's API tokens for the money-flow subsystem.
3. Audit `mf_audit_log` for any unexpected actions in the suspected window.
4. Disable the affected user account until 2FA is re-established.

**Escalation:** Any sign of malicious action in audit log → freeze all outbound (operator-level kill-switch in UI), notify trustee, engage incident response.

---

## 5. Tooling & links

- **Reconciliation report:** Operator dashboard → "Reconciliation" tab (renders the daily job output).
- **Audit log:** Operator dashboard → "Audit" tab. Read-only. Append-only per `money_flow_plan.md` §7.6.
- **Banking vendor dashboard:** [Relay](https://app.relayfi.com) or [Mercury](https://app.mercury.com) — see `money_flow_plan.md` §3.6 for selection.
- **Wise Business dashboard:** [wise.com/business](https://wise.com/business) (used in v2 for direct PIX reception).
- **IBKR Flex Query portal:** Existing setup; CSV pulls happen via `FetchDeposits` job — manual pulls also available from the IBKR portal.
- **Dev fake bank:** Local development only at `/dev/fake-bank/*` (per MF-R8). Not available in production.

---

## 6. Roles cheat sheet

See `money_flow_plan.md` §7.5 for the full RBAC matrix. Quick reference:

| You are… | You can… |
|---|---|
| **account_owner** | View your own data, create `DepositRequest`s, update your own `BrazilianRecipient`s. No write actions on credit lines (those are admin-only per `credit_lines_plan.md` §5 rule 12). |
| **money_flow_operator** | All inbound attribution, all sweep actions, recipient registry maintenance, initiate outbound (subject to approval gate for large amounts). |
| **money_flow_approver** | Confirm outbound transfers above threshold; adjust buffer settings. Cannot self-approve. |
| **trader** | View everything money-flow-side; initiate IBKR↔checking sweeps; trade securities at IBKR (outside this subsystem). |
| **admin** (credit-line trustee) | Every credit-line write action (open / repay / readjust / cancel / resolve / reverse). Per `credit_lines_plan.md` §5 rule 12. |

A single person can hold multiple roles, but the operator/approver split must be honored: outbound approval requires two **different** human users.

---

## 7. Glossary

| Term | Meaning |
|---|---|
| Checking-hub | The fund's US business checking account at Relay / Mercury — the cash orchestration layer. |
| `CheckingDeposit` | A row in the new model representing a USD credit arriving at the checking-hub. |
| `CashDeposit` | The existing IBKR-CSV-driven deposit row. Co-exists with `CheckingDeposit` in Phase 1 (see §13 of the plan). |
| `DepositRequest` | A beneficiary-pre-registered expected payment. Matched against `CheckingDeposit` for attribution. |
| Pending-attribution | A `CheckingDeposit` with no matching `DepositRequest`. Sits on the operator dashboard until manually assigned. |
| Buffer floor / ceiling | Operating-balance thresholds at the checking-hub. Breach signals notify the trader. |
| Sweep | Operator-initiated transfer between fund-owned accounts (typically IBKR ↔ checking). |
| Trader notification | Signal (email / dashboard) that the buffer is breached. The trader decides what (if anything) to sell or buy in response. The system never trades automatically. |
