# Credit-line payment allocation plan

## Problem

A `credit_line_payments` schedule row records exactly one
`paid_transaction_id`. That single column cannot represent three real cases:

1. **One payment → many rows** (an over-payment whose overflow cascades).
2. **One row → many payments** (a partial installment topped up later by a
   different payment).
3. **A payment that matches no row** and must be *allocated* by amount rather
   than matched 1:1 to a single installment.

This caused the credit-line-2 incident: a 100-share payment's 16.67 overflow
cascaded onto a row a 50-share payment had marked `partial`, and the cascade
**overwrote** that row's `paid_transaction_id`, silently orphaning the 50 from
the schedule (it still counted toward `outstanding_shares`, which is
transaction-based, so no money was lost — only the per-row provenance was
corrupted). A guard now stops the *overwrite* (cascade skips an already-linked
row), but the single-column model still cannot truthfully represent the three
cases above.

## Solution

Introduce an **allocation ledger**: the unit of truth becomes
`(transaction, schedule_row, shares)`. One REP transaction can produce many
allocation rows across many installments; one installment can receive
allocations from many transactions. `credit_line_payments.paid_transaction_id`
and `status` become **derived/denormalised** (recomputed from allocations,
kept for backward-compatible reads), not authored directly.

`OutstandingCalculator` stays transaction-based (sum BOR − REP, independent of
rows) — it is already correct and must NOT move onto allocations, or reversed
/ unmatched edge cases would break.

## Schema

New table `credit_line_payment_allocations`:

| col | type | notes |
|---|---|---|
| `id` | bigIncrements | |
| `credit_line_payment_id` | foreignId → `credit_line_payments` | indexed |
| `transaction_id` | foreignId → `transactions` | indexed |
| `shares` | decimal(19,4) | shares of this tx applied to this row |
| `created_at` / `updated_at` | timestamps | match existing table style |

Invariants (enforced in the allocator, asserted in tests):
- `Σ allocations.shares` for a tx `≤ tx.shares` (leftover beyond the last open
  row = principal prepayment; recorded as no row allocation).
- `Σ allocations.shares` for a row `≤ row.shares_due`.
- All share math `round(..., 4)` to avoid drift across splits.

## New service: `App\Services\CreditLine\Repay\PaymentAllocator`

Single owner of "distribute a REP transaction's shares into allocation rows
and re-derive the affected rows' `status` + `paid_transaction_id`."

- `allocate(TransactionExt $tran, AccountCreditLine $line, ?CreditLinePayment $targetRow = null, ?array $manualSplit = null): void`
  - **manualSplit** `[rowId => shares]` → write exactly those allocations.
  - **auto + targetRow** → fill target first, cascade remainder oldest-open
    first.
  - **auto, no target** (unmatched / over-payment) → oldest-open first across
    the line; leftover = principal prepayment (recorded, no row).
- `sharesAllocatedOnRow(CreditLinePayment $row): float` → `SUM(shares)` over
  that row's allocations (replaces the broken
  `RepayService::sharesAlreadyPaidOnRow()` simplified-model hack).
- `deriveRowStatus(CreditLinePayment $row)`: from `Σ alloc vs shares_due` and
  due-date → `paid` / `partial` / `late` / `scheduled`; set
  `paid_transaction_id` = largest contributing allocation's tx (cosmetic only).
- `deallocate(TransactionExt $tran)`: delete that tx's allocation rows and
  re-derive every affected row's status (used by reversal).

## Touch list

| File | Change |
|---|---|
| `database/migrations/*_create_credit_line_payment_allocations_table.php` | new table |
| `app/Models/CreditLinePaymentAllocation.php` | new model |
| `app/Models/CreditLinePayment.php` | `allocations()` hasMany |
| `app/Models/TransactionExt.php` | `creditLineAllocations()` hasMany |
| `app/Services/CreditLine/Repay/PaymentAllocator.php` | new service |
| `database/migrations/*_backfill_credit_line_payment_allocations.php` | one-off backfill |
| `app/Services/CreditLine/Repay/RepayService.php` | `repay()`/`repayRow()`/`applyToSchedule()` delegate to allocator; delete `applyToScheduleRows()` + `sharesAlreadyPaidOnRow()` + the skip-already-linked guard |
| `app/Services/CreditLine/Reverse/ReverseService.php` | `reopenPaymentRows()` → `PaymentAllocator::deallocate()` then re-derive |
| `app/Http/Controllers/WebV1/AccountCreditLineControllerExt.php` | `updatePayment()`/`reversePayment()` go via allocator; new `allocatePayment` admin action |
| `resources/views/.../credit_lines/show.blade.php` | "Paid by tx" → "Payments" (list allocations per row) |
| `resources/views/.../credit_lines/_payment_row_actions.blade.php` | add "Edit allocation" |
| Tests | `PaymentAllocatorTest` (new); extend `RepayServiceTest`; regression-guard `ReverseServiceTest`, `CreditLinePaymentEditTest`, `CreditLineFlowTest`, `CreditLineMessyHistoryTest`; `OutstandingCalculator` byte-identical before/after |

## Backfill

For every existing row with `paid_transaction_id`, create allocation(s) so
history reads identically. Per tx, allocate oldest-first
`min(tx.shares − allocatedSoFar, row.shares_due − rowAllocatedSoFar)`.

**Credit line 2** (we hand-patched row #5 → tx#1214): backfill must reproduce
the *correct* split — tx#1215 = 83.3333 on row #6 + 16.6667 on row #5;
tx#1214 = 50 on row #5 → row #5 shows 66.6667/83.3333 partial from two txs.
Assert `outstanding_shares` (2766.6667) unchanged after backfill.

## Phasing

- **Phase A — additive, zero behaviour change (reversible).** Migration +
  model + relations + `PaymentAllocator` (unit-tested in isolation) + backfill
  migration + `PaymentAllocatorTest`. `RepayService` still authors
  `paid_transaction_id` exactly as today; the ledger is populated and kept
  consistent but not yet read by write paths.
- **Phase B — switch write paths.** `RepayService` + `ReverseService` +
  `updatePayment` delegate to `PaymentAllocator`; delete the cascade hack and
  the simplified-model `sharesAlreadyPaidOnRow()`. Existing suites are the
  guardrail; `OutstandingCalculator` results must be byte-identical.
- **Phase C — UI.** Multi-payment display per row + manual "allocate /
  re-allocate" admin action for intentionally-split / unmatched payments.

## Risks / decisions

- **Keep `paid_transaction_id` as derived** (not dropped) initially — many
  views/tests read it; lower risk. Revisit dropping it later.
- **Outstanding stays transaction-based** — explicitly do NOT move it onto
  allocations.
- **Decimal drift** — standardise allocator on `round(_, 4)`.
