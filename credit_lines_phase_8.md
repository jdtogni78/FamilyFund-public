# Credit Lines Phase 8 — Negative Test Coverage

## Summary

- **Part A (unit):** 5 new negative tests added to `RepayServiceTest` — all pass.
- **Part B (Dusk):** 8 new browser tests in `CreditLineNegativeUITest` — all pass.
- **Test count delta:** +5 unit tests (85 → 90), +8 Dusk browser tests.
- **Guards added:** 2 guards added to `RepayService.repay()`.
- **Controller hardening:** exception handling added in 3 controllers.

---

## Guards Added to Service Code

### `RepayService::repay()` (app/Services/CreditLine/Repay/RepayService.php)

Two guards added at the top of `repay()`, before the DB transaction:

1. **Negative/zero shares guard** — throws `InvalidArgumentException('...must be positive...')` when `$shares <= 0`.
   - Rationale: form validation (`min:0.0001`) protects the happy path, but the service is callable directly (e.g. from tests, queued jobs, future API). The guard closes the gap.

2. **Non-active status guard** — throws `InvalidArgumentException('Cannot repay a credit line with status "X"...')` when `$line->status !== 'active'`.
   - Rationale: repaying a `cancelled` or `paid_off` line is a logic error that would silently create orphaned REP transactions. The guard surfaces it immediately.

### Controller Exception Handling

Three controllers now catch credit-line exceptions and flash errors instead of returning 500:

| Controller | Exception | Flash message |
|---|---|---|
| `AccountCreditLineControllerExt::store()` | `OverBorrowException` | `$e->getMessage()` |
| `AccountCreditLineControllerExt::readjust()` | `NoChangeException` | `$e->getMessage()` |
| `AccountCreditLineControllerExt::cancel()` | `CancelNotAllowedException` | `$e->getMessage()` |
| `AccountCreditLineControllerExt::repay()` | `InvalidArgumentException` | `$e->getMessage()` |
| `TransactionReversalController::store()` | `AlreadyReversedException` | `$e->getMessage()` |

---

## Part A — Unit Test Status

File: `tests/Unit/Services/CreditLine/Repay/RepayServiceTest.php`

| Test | Status |
|---|---|
| `test_repay_with_negative_shares_throws` | PASS |
| `test_repay_with_zero_shares_throws` | PASS |
| `test_repay_against_cancelled_line_throws` | PASS |
| `test_repay_against_paid_off_line_throws` | PASS |
| `test_repay_overpayment_caps_at_outstanding_and_sets_paid_off` | PASS |

All 9 pre-existing tests remain green. Total: 14 passing.

---

## Part B — Dusk Browser Test Status

File: `tests/Browser/CreditLineNegativeUITest.php`

| # | Test | Status | Notes |
|---|---|---|---|
| 1 | `test_non_admin_user_cannot_access_credit_line_create_form` | PASS | Submits as non-admin user (id=1); asserts 403/unauthorized in page source |
| 2 | `test_open_credit_line_with_negative_principal_shows_validation_error` | PASS | JS removes `min` constraint; server rejects; sees "principal" in error |
| 3 | `test_open_credit_line_with_zero_term_shows_validation_error` | PASS | JS removes `min` constraint; server rejects; sees "term" in error |
| 4 | `test_over_borrow_shows_error` | PASS | 9999999 shares → `OverBorrowException` → flash "Cannot borrow" |
| 5 | `test_cancel_active_line_with_outstanding_blocks` | PASS | `CancelNotAllowedException` → flash "Cannot cancel"; line stays active |
| 6 | `test_account_show_blocked_when_active_credit_line_exists` | PASS | JS DELETE submit; account not deleted; line still active |
| 7 | `test_readjust_no_change_shows_error` | PASS | Same term re-submitted → `NoChangeException` → flash "No changes" |
| 8 | `test_reverse_already_reversed_transaction_fails` | PASS | Second form submit → `AlreadyReversedException` → flash "already been reversed" |

All 8 Dusk tests pass in 34s.

---

## Dusk Screenshots Saved

All screenshots saved to `tests/Browser/screenshots/negative/`:

```
01a_non_admin_create_attempt.png
01b_non_admin_result.png
02a_create_form_loaded.png
02b_negative_principal_result.png
03a_create_form_loaded.png
03b_zero_term_result.png
04a_create_form_loaded.png
04b_over_borrow_result.png
05a_line_active.png
05b_cancel_attempt_result.png
06a_account_show.png
06b_after_delete_attempt.png
07a_line_before_readjust.png
07b_readjust_no_change_result.png
08a_after_repay.png
08b_after_first_reverse.png
08c_after_double_reverse.png
```

---

## Full Test Run Results

```
Unit + Feature (docker exec familyfund php artisan test ...):
  90 passed (267 assertions) — Duration: 2.58s

Dusk smoke (--filter test_admin_can_open):
  1 passed — Duration: 3.23s

Dusk negative suite (--filter CreditLineNegativeUI):
  8 passed (11 assertions) — Duration: 34.51s
```
