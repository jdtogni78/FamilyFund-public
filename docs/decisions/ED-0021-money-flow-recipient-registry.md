# ED-0021 — Money-flow subsystem: USD-only booking + recipient registry + cash-buffer principle

- **Date:** 2026-05-29 · **Status:** Proposed (captured here from `docs/money_flow_plan.md` rev 6; not yet built)
- **Builds on:** [ED-0008](ED-0008-repository-pattern-ext-models.md)

## Context

The fund needs an end-to-end money-flow subsystem so that beneficiaries in
Brazil can deposit BRL (PIX or otherwise) into the USD fund and the system
automatically detects, attributes, and books the arrival — and so that
outbound disbursements (credit-line draws, withdrawals) can go BRL → PIX
through the same plumbing. `docs/money_flow_plan.md` already records the
design at rev 6, but the high-level decisions are scattered across that doc
and easy to relitigate from the code (nothing is built yet). The two
asymmetries that drove the design were (1) physical settlement is slow
(1–3 business days) while the books must stay consistent in seconds, and
(2) PII (CPF, full bank coordinates) lives in the recipient registry, which
needs stricter access controls than the rest of the app.

## Decision

Adopt the rev-6 v1 plan as the canonical first-version money-flow design:

1. **USD-only booking.** All transactions, balances, and reporting stay in
   USD. BRL is a transport detail attached to the inbound/outbound transfer
   record, not to the books.
2. **Cash-buffer principle.** Attribution and bookkeeping happen as soon as
   the deposit is *recognized* at the fund's US checking webhook — in
   seconds. Physical money may still be in transit (checking → IBKR, or
   ACH → Wise → PIX). Explicit cash buffers at each leg keep the books
   consistent without forcing premature trades or sells.
3. **Recipient registry.** A dedicated table stores Brazilian recipient
   info (PIX keys, bank/agency/account, CPF) keyed to a beneficiary so
   outbound transfers can be initiated programmatically. The registry is
   PII-classified and read/write-gated separately from the rest of the
   ACL (system-admin / fund-admin only).
4. **Subsystem isolation.** Money-flow lives in its own namespace, has its
   own queue, its own audit log, and stricter access controls than the
   rest of the app.
5. **v1 scope is one inbound + one outbound path.** Inbound: beneficiary's
   own Wise → fund US checking (Relay or Mercury) → webhook attribution →
   later ACH push to IBKR. Outbound: operator-initiated, books debited
   immediately, blocks if checking cash < amount (no auto-sell), then ACH
   → Wise → BRL → PIX. Everything else in the plan doc is later-phase.
6. **1-year idempotency window** on inbound attribution to absorb retries
   and slow webhook redeliveries; older windows fall through to manual
   reconciliation.

## Consequences

- **Status is Proposed.** No code lives in this repo yet; the plan is
  authoritative until implementation lands and an ED amendment flips this
  to Accepted.
- A future ED-NNNN will need to record the **CashDeposit / DepositRequest
  coexistence plan** that rev 6 already calls out — the rev-6 doc folds
  it into the plan but the on-disk schema decision (extend `CashDeposit`
  vs introduce a parallel `CheckingDeposit`) is the kind of choice the
  ADR log exists for and should not be re-derived from the plan doc.
- The recipient-registry PII surface adds a **new** authorization tier
  beyond [ED-0021's fund-scoped roles](ED-0020-multi-tenant-fund-scoped-roles.md)
  — implementation must reuse the fund + system-admin gating shape (no
  new role primitive) and pin the registry's read/write paths in the ACL
  matrix golden.
- The cash-buffer principle implies new ledger accounts (per-leg buffers)
  that the existing `Transaction` / `AccountBalance` model must accommodate;
  expect schema work, not just controller work.
- Webhook ingress is a **new** unauthenticated entry-point — by
  [ED-0012](ED-0012-no-public-api-endpoints.md) (no public API endpoints)
  it must be signed/authenticated by the bank's webhook secret and pinned
  in `SecurityRouteAutomationTest` as a deliberate exception.

## Source

`docs/money_flow_plan.md` (rev 6, 2026-05-13);
`docs/runbooks/money_flow_runbook.md`; `docs/testing_plan.md` §4.2/§4.3
(per-MF-* test mapping).
