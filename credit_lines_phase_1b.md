# Credit Lines — Phase 1b: Credit Line Matcher

**Agent:** Wave 1b  
**Branch:** `claude/elated-pare-eac2e9`

---

## Scope & UCs

UC-25 Auto-match single active line · UC-26 Shares match one line · UC-27 Cash fallback · UC-28 Full-payoff outstanding match · UC-29 Ambiguous · UC-30 Unmatched · UC-31 Manual resolution.

---

## Files created (all new)

| File | Lines | Purpose |
|---|---|---|
| `app/Services/CreditLine/Matching/MatchResult.php` | 65 | Value object (status, creditLineId, candidateLineIds, reason) |
| `app/Services/CreditLine/Matching/CreditLineMatcher.php` | 143 | Priority matcher (UC-25–30) |
| `app/Services/CreditLine/Matching/MatcherPersister.php` | 45 | Writes result → transaction row in DB::transaction |
| `app/Services/CreditLine/Matching/MatchResolutionService.php` | 80 | Manual resolution (UC-31), validates status + account |
| `app/Services/CreditLine/Matching/Contracts/ScheduleAdvancer.php` | 28 | Interface seam to wave 1a RepayService |
| `app/Services/CreditLine/Matching/Exceptions/InvalidMatchResolutionException.php` | 40 | Thrown on invalid resolution attempt |
| `database/factories/AccountCreditLineFactory.php` | 30 | Test factory for AccountCreditLineExt |
| `database/factories/CreditLinePaymentFactory.php` | 22 | Test factory for CreditLinePayment |
| `tests/Unit/Services/CreditLine/Matching/CreditLineMatcherTest.php` | 180 | 9 tests covering all match paths |
| `tests/Unit/Services/CreditLine/Matching/MatchResolutionServiceTest.php` | 135 | 5 tests covering UC-31 + guard cases |

All files pass `php -l` syntax checks.

---

## Service API surface

### `CreditLineMatcher::match(TransactionExt $tran, bool $cashFallbackEnabled = false): MatchResult`

Priority rules:
1. If account has exactly 1 active line → `auto_matched` to it (UC-25).
2. Else: exact share match against `CreditLinePayment.shares_due` (status=scheduled, tolerance < 1e-4). Exactly one → `auto_matched` (UC-26); >1 → `ambiguous` (UC-29).
3. If `$cashFallbackEnabled`: cash = `shares × shareValueAsOf(tran.timestamp)`; compare against `shares_due × shareValue`, tolerance 1 %. Exactly one → `auto_matched` (UC-27); >1 → `ambiguous`.
4. Outstanding match: `|tran.shares − line.outstanding_shares| < 1e-4`. Exactly one → `auto_matched` (UC-28).
5. 0 candidates → `unmatched` (UC-30).

Guards: returns no-op if `tran.type ≠ REP` or `tran.account_credit_line_id !== null`.

### `MatcherPersister::persist(TransactionExt $tran, MatchResult $result): void`

Sets `account_credit_line_id` and `credit_line_match_status`; saves inside `DB::transaction`.

### `MatchResolutionService::resolve(TransactionExt $tran, AccountCreditLine $line, ?User $admin): void`

Validates `credit_line_match_status ∈ {ambiguous, unmatched}` and `tran.account_id == line.account_id`; sets FK + status `manual`; calls `ScheduleAdvancer::advance()`.

---

## ScheduleAdvancer contract (seam to wave 1a)

```php
interface ScheduleAdvancer
{
    public function advance(TransactionExt $tran, AccountCreditLine $line): void;
}
```

Location: `app/Services/CreditLine/Matching/Contracts/ScheduleAdvancer.php`

Wave 1a (RepayService) must implement this interface. Phase 2 binds it in `AppServiceProvider`.

---

## Phase 2 dependencies

1. **Bind `ScheduleAdvancer`** in `AppServiceProvider` to the concrete `RepayService` from wave 1a.
2. **Wire `CreditLineMatcher` into `TransactionDetectionService`** (wave 1d) via `Transaction::saved` event.
3. **Factories**: `AccountCreditLine::factory()` and `CreditLinePayment::factory()` were added here; wave 1a and other waves may depend on them.

---

## Test list

| Test | UC | Assertion |
|---|---|---|
| `test_uc25_single_active_line_auto_matched` | UC-25 | Single line → auto_matched |
| `test_uc26_shares_match_exactly_one_line` | UC-26 | 2 lines, shares match 1 → auto_matched |
| `test_uc29_shares_match_both_lines_is_ambiguous` | UC-29 | 2 lines, shares match both → ambiguous, FK NULL, 2 candidateLineIds |
| `test_uc30_no_match_is_unmatched` | UC-30 | 2 lines, no match → unmatched |
| `test_uc27_cash_fallback_matches_one_line` | UC-27 | Cash fallback OFF=unmatched, ON=auto_matched |
| `test_uc28_outstanding_match_full_payoff` | UC-28 | shares=outstanding of 1 line → auto_matched |
| `test_non_rep_transaction_returns_noop` | guard | BOR tran → no-op |
| `test_already_assigned_transaction_returns_noop` | guard | FK set → no-op |
| `test_no_active_lines_is_unmatched` | UC-30 | no lines → unmatched |
| `test_resolve_ambiguous_sets_manual_and_fk` | UC-31 | ambiguous → manual, FK set |
| `test_resolve_unmatched_sets_manual_and_fk` | UC-31 | unmatched → manual, FK set |
| `test_throws_when_already_matched` | UC-31 | auto_matched → throws |
| `test_throws_when_account_mismatch` | UC-31 | wrong account → throws |
| `test_throws_when_status_is_null` | UC-31 | null status → throws |
