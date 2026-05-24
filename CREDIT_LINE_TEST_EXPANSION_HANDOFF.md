# Credit-line test expansion — handoff

**Branch:** `worktree-bridge-cse_01Ep1sQPMPRmQs42EyhmJbye`
**Date:** 2026-05-19
**Trigger:** User reported "multiple issues with the new credit lines"
(transaction display, readjustment waveforms, repayment allocation, UI
validations) and asked for review + expanded tests, with UI validations
added separately. **Tests-only deliverable** — no production code changed.

## What was added

### 1. Feature test — transaction display values
`tests/Feature/CreditLineTransactionDisplayValuesTest.php` — 8 tests.

Exercises `createTransactionsResponse()` + the account-show transactions table
for BOR/REP rows. Today these rows render with `value=$0.00` and a
positive-green Current-Value column visually identical to a profitable OWN
purchase. The four assertions below codify the display contract the user
expects; the production fix is out of scope here.

### 2. Unit test — readjustment waveform shape
`tests/Unit/Services/CreditLine/Adjust/ReadjustWaveformTest.php` — 9 tests.

Asserts trajectory invariants the existing `ReadjustServiceTest` doesn't
cover: monotonic-increasing `effective_plan`, terminates at principal,
cancelled rows don't leak into `expected_to_date`, repeated readjusts produce
distinct `historical_plans`, forward-dated readjust keeps pre-effective rows
visible, backdated readjust doesn't double-count late backlog, mixed
frequencies preserve cadence, partial-repayment-then-readjust keeps
`actual_repayments` intact.

### 3. Unit test — repayment-allocation edge cases
`tests/Unit/Services/CreditLine/Repay/PaymentAllocatorEdgeCasesTest.php` —
9 tests.

Edge cases the original `PaymentAllocatorTest` did not exercise:
cancelled-row skip after readjust, LATE-row priority, double-allocate
idempotency, zero-share REP no-op, exact-fit, sub-cent residual on last row,
manual-split sanity, cascade ordering deterministic by `due_date`, deallocate
after cascading reverse.

### 4. Browser test — credit-line CREATE form validations
`tests/Browser/CreditLineCreateValidationTest.php` — 8 tests.

Validation matrix not covered by `CreditLineNegativeUITest`: future
origination date, term=481, principal=0, principal below 4-dp precision,
nickname required, backdate-warning JS visibility, over-borrow inline
warning + Submit-disabled invariant, frequency-dropdown options selectable,
happy-path submission.

### 5. Browser test — REPAY / READJUST form validations
`tests/Browser/CreditLineRepayReadjustValidationTest.php` — 7 tests.

Repay: zero shares, negative shares, repay > outstanding. Readjust: term=0,
term=481, effective_date before origination, frequency switch.

---

## Failing tests — bugs surfaced

Run the failing-only group:

```bash
docker exec familyfund-pool2 php artisan test --group=needs-fix-credit-line
```

(Substitute the active pool container — `familyfund-pool2` for this
session.)

### Bug 1: BOR `Transaction.value = 0` — no dollar context
- **Test:** `CreditLineTransactionDisplayValuesTest::test_bor_transaction_value_reflects_dollar_size_of_draw`
- **Reproducer:** `DrawService::open()` at
  `app/Services/CreditLine/Draw/DrawService.php:103` writes
  `value=0` ("cash-leg deferred to Phase 5"). The transactions table
  (`resources/views/accounts/transactions_table.blade.php:60`) then renders
  the BOR row with `+$0.00` — visually indistinguishable from a no-op.
- **Spec:** `docs/credit_lines/fund_cashflow.md` says
  `value = principal_shares × share_price`.
- **Fix direction:** either set the value at draw time, or compute
  `shares × shareValueAsOf(timestamp)` for BOR/REP rows inside
  `createTransactionsResponse()` (AccountTrait.php:54-83).

### Bug 2: BOR/REP `share_price = $0.00`
- **Test:** `…::test_bor_transaction_share_price_reflects_price_at_draw_date`
- **Reproducer:** `AccountTrait::createTransactionsResponse()`
  (`app/Http/Controllers/Traits/AccountTrait.php:55`) computes
  `share_price = value / shares`. With `value=0`, share_price = 0.
- **Fix direction:** fallback to `account->shareValueAsOf($transaction->timestamp)`
  when `value=0`.

### Bug 3: BOR `current_value` is positive & not labelled as liability
- **Test:** `…::test_bor_transaction_current_value_is_signed_or_labelled_as_liability`
- **Reproducer:** `current_value = shares × shareValueAsOf($asOf)`
  (AccountTrait.php:56) → positive number → rendered in
  `transactions_table.blade.php:71` with the `text-emerald-600` profit color.
  The user cannot tell from the row that they owe this money.
- **Fix direction:** sign-flip current_value for BOR/REP rows, OR carry an
  `is_liability` marker on the response so the view can render it red /
  prefixed "−" / labelled as debt.

### Bug 4: REP `Transaction.value = 0` — same root cause as bug 1
- **Test:** `…::test_rep_transaction_value_reflects_amount_paid`
- **Reproducer:** `RepayService::repay()` creates REP transactions with
  `value=0` (same deferred-cash-leg model). The row shows `+$0.00` in the
  Value column.
- **Fix direction:** same as bug 1.

---

## Tests not yet runnable in this environment

The 15 new Dusk tests (`tests/Browser/CreditLineCreateValidationTest.php`
+ `tests/Browser/CreditLineRepayReadjustValidationTest.php`) require a
working Chromedriver — the leased pool slot has none provisioned
(`/app/vendor/laravel/dusk/bin/chromedriver-linux64/` is empty on
aarch64). They are structured to follow the existing
`CreditLineNegativeUITest` conventions (dev-login, `ACCOUNT_ID = 7`,
try/finally cleanup) so they should run cleanly under whichever Dusk
runner the host uses.

## What was NOT done (per the agreed scope)

- **No production code changes.** The user chose "Tests only, mark failing
  ones" up front.
- **No code-style / lint pass.** PHPUnit emitted deprecation warnings about
  doc-comment `@group` metadata; future-proofing to attribute syntax was
  intentionally deferred.
- **No new test fixtures.** All tests reuse `DataFactory` and the existing
  service-layer helpers (`DrawService`, `RepayService`, `ReadjustService`).

## Total test deltas

| Layer                    | New tests | Passing | Failing |
|--------------------------|-----------|---------|---------|
| Feature                  | 8         | 4       | 4       |
| Unit / waveform          | 9         | 9       | 0       |
| Unit / allocator edges   | 9         | 9       | 0       |
| Browser (Dusk)           | 15        | n/a     | n/a     |
| **Total**                | **41**    | **22**  | **4**   |

Existing 182 credit-line tests: still all passing.
