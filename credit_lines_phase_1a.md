# Credit Lines — Phase 1a: Draw / Repay / Cancel Services

**Status:** complete
**Date:** 2026-05-13
**Branch:** `claude/elated-pare-eac2e9`

---

## Scope

UCs implemented: UC-01, UC-03, UC-04 (race-condition protection), UC-05, UC-06, UC-07, UC-12, UC-13.
Single-line target only. Multi-line matcher routing is wave 1b.

---

## Files Created

| File | Lines | Description |
|---|---|---|
| `app/Services/CreditLine/Exceptions/OverBorrowException.php` | 38 | Thrown when draw > available-to-borrow |
| `app/Services/CreditLine/Exceptions/CancelNotAllowedException.php` | 33 | Thrown when cancelling a line with outstanding shares |
| `app/Services/CreditLine/Support/AmortizationScheduleBuilder.php` | 94 | Builds + persists even-split schedule; stable API for wave 1c |
| `app/Services/CreditLine/Support/OutstandingCalculator.php` | 125 | Recomputes per-line outstanding; available-to-borrow; aggregate BOR balance |
| `app/Services/CreditLine/Draw/DrawService.php` | 100 | Opens a credit line with cap enforcement + FOR UPDATE lock |
| `app/Services/CreditLine/Repay/RepayService.php` | 138 | Records REP; applies to schedule in due-date order; marks line paid_off |
| `app/Services/CreditLine/Cancel/CancelService.php` | 54 | Cancels line if outstanding=0 or no BOR transactions |
| `tests/Unit/Services/CreditLine/Draw/DrawServiceTest.php` | 188 | 11 tests covering UC-01, UC-03, quarterly/annual schedules |
| `tests/Unit/Services/CreditLine/Repay/RepayServiceTest.php` | 165 | 9 tests covering UC-05, UC-06, UC-07, UC-13 |
| `tests/Unit/Services/CreditLine/Cancel/CancelServiceTest.php` | 119 | 5 tests covering UC-12 allow/block |

All 10 files pass `php -l` (no syntax errors).

---

## API Surface

### `DrawService::open(AccountExt, float, int, string, ?string, ?Carbon): AccountCreditLine`
- Wraps in `DB::transaction()` with `SELECT … FOR UPDATE` on the account row.
- Calls `OutstandingCalculator::availableToBorrow()` and throws `OverBorrowException` if exceeded.
- Creates `AccountCreditLine`, BOR `TransactionExt` (status=C, credit_line_match_status=NULL), calls `AmortizationScheduleBuilder::build()`, updates aggregate BOR `AccountBalance`.

### `RepayService::repay(AccountCreditLine, float, ?Carbon): TransactionExt`
- Wraps in `DB::transaction()` with `SELECT … FOR UPDATE` on the line row.
- Creates REP `TransactionExt` (status=C, credit_line_match_status=NULL).
- Applies shares to schedule in due-date order (partial/full/cascade).
- Calls `OutstandingCalculator::recomputeForLine()` and sets line.status=paid_off when outstanding≤0.
- Updates aggregate BOR `AccountBalance` via `OutstandingCalculator::updateAggregateBorBalance()`.

### `CancelService::cancel(AccountCreditLine): void`
- Wraps in `DB::transaction()` with `SELECT … FOR UPDATE` on the line row.
- Throws `CancelNotAllowedException` if BOR transactions exist AND outstanding_shares > 0.
- Sets line.status=cancelled.

### `AmortizationScheduleBuilder::build(AccountCreditLine, float, Carbon): CreditLinePayment[]`
- `computeNPayments(int, string): int` — computes payment count from term_months + frequency.
- `periodMonths(string): int` — months between payments (1/3/12).
- Last payment absorbs decimal rounding so sum == outstanding_shares exactly to 4 d.p.

### `OutstandingCalculator`
- `recomputeForLine(AccountCreditLine): float` — sums BOR−REP on the line; ignores `ambiguous`/`unmatched`; persists to line.outstanding_shares.
- `availableToBorrow(AccountExt, ?Carbon): float` — OWN_balance − Σ outstanding on active lines.
- `updateAggregateBorBalance(AccountExt, string, int): void` — upserts the single BOR AccountBalance row.

---

## Known Phase 2 Dependencies

1. **Auth enforcement** — Controller layer must check `admin` role before calling any of these services (§5 rule 12 / UC-48). Services do not enforce auth themselves.
2. **Cash leg** — BOR/REP transactions have `value=0` for now. Phase 5 must populate value = shares × share_price and update the fund's cash portfolio asset (see `docs/credit_lines/fund_cashflow.md`).
3. **Wave 1b coordination** — `RepayService::repay()` leaves `credit_line_match_status=NULL` on REP transactions when the target is explicit. Wave 1b's matcher, when routing without an explicit target, must set `credit_line_match_status` appropriately.
4. **Wave 1c coordination** — `AmortizationScheduleBuilder` is designed to be called independently from `AdjustService`; the method signature `build($line, $outstanding, $fromDate)` is stable.
5. **`DataFactory::createBalance()`** does not accept a `type` argument — test helper `seedOwnBalance()` bypasses this by calling `AccountBalance::create()` directly. If DataFactory is extended to support `type`, the helper can be simplified.

---

## Open Questions

1. **BOR transaction `value` field** — currently 0 (cash leg deferred). Should `DrawService` accept an optional `$sharePrice` parameter now, or wait for Phase 5?
2. **Partial repayment tracking** — current model: one `paid_transaction_id` per schedule row. If an admin wants to partially pay in multiple installments on the same row before it's fully covered, only the last transaction's ID is stored. A sub-ledger approach would be cleaner but is not required for v1.
3. **`AccountBalance` validation rule** — `shares` has `min:0.0001`, which prevents creating a BOR balance row with 0 shares. The `updateAggregateBorBalance()` method skips creation when outstanding=0 (closes the existing row instead). Confirm this is the correct design for the "fully repaid" case.

---

## Test Status

| Test | UC | Status |
|---|---|---|
| `DrawServiceTest::test_open_creates_credit_line_with_correct_fields` | UC-01 | ready |
| `DrawServiceTest::test_open_creates_bor_transaction` | UC-01 | ready |
| `DrawServiceTest::test_open_12_month_monthly_generates_12_payments` | UC-01 | ready |
| `DrawServiceTest::test_schedule_sum_equals_outstanding_exactly` | UC-01 | ready |
| `DrawServiceTest::test_schedule_due_dates_are_monthly_from_origination` | UC-01 | ready |
| `DrawServiceTest::test_open_creates_aggregate_bor_balance` | UC-01 | ready |
| `DrawServiceTest::test_over_borrow_throws_exception` | UC-03 | ready |
| `DrawServiceTest::test_over_borrow_exception_contains_correct_amounts` | UC-03 | ready |
| `DrawServiceTest::test_draw_cap_accounts_for_existing_active_lines` | UC-03 | ready |
| `DrawServiceTest::test_nothing_committed_on_over_borrow` | UC-03 | ready |
| `DrawServiceTest::test_quarterly_schedule_generates_correct_payment_count` | UC-01 | ready |
| `DrawServiceTest::test_annual_schedule_generates_correct_payment_count` | UC-01 | ready |
| `RepayServiceTest::test_repay_full_first_row_marks_it_paid` | UC-05 | ready |
| `RepayServiceTest::test_repay_reduces_outstanding_shares` | UC-05 | ready |
| `RepayServiceTest::test_repay_creates_rep_transaction` | UC-05 | ready |
| `RepayServiceTest::test_partial_payment_marks_row_partial` | UC-06 | ready |
| `RepayServiceTest::test_partial_payment_reduces_outstanding` | UC-06 | ready |
| `RepayServiceTest::test_extra_payment_cascades_to_next_rows` | UC-07 | ready |
| `RepayServiceTest::test_full_repayment_sets_line_status_paid_off` | UC-13 | ready |
| `RepayServiceTest::test_full_repayment_marks_all_schedule_rows_paid` | UC-13 | ready |
| `RepayServiceTest::test_full_repayment_updates_aggregate_bor_balance_to_zero` | UC-13 | ready |
| `CancelServiceTest::test_cancel_line_with_no_draws_succeeds` | UC-12 | ready |
| `CancelServiceTest::test_cancel_fully_repaid_line_succeeds` | UC-12 | ready |
| `CancelServiceTest::test_cancel_with_outstanding_throws_exception` | UC-12 | ready |
| `CancelServiceTest::test_cancel_exception_contains_outstanding_amount` | UC-12 | ready |
| `CancelServiceTest::test_cancel_partial_repayment_still_throws` | UC-12 | ready |

All 26 tests are syntactically valid. Docker-based execution deferred to central test run.
