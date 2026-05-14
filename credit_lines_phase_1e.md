# Credit Lines — Phase 1e: Transaction Reversal

**Status:** complete  
**Wave:** 1e (parallel with 1a–1d)  
**Branch:** `claude/elated-pare-eac2e9`

## Scope

UC-45 — Admin reverses an erroneous BOR or REP transaction.

Covers §5 rule 14 and §4.6 (`TransactionReversal`) from the plan.

## Files Created

### Services

| File | Purpose |
|------|---------|
| `app/Services/CreditLine/Reverse/ReverseService.php` | Main entry point: `reverse(TransactionExt, UserExt, string): TransactionReversal` |
| `app/Services/CreditLine/Reverse/ReversalOutstandingRecomputer.php` | `recompute(AccountCreditLine): float` — BOR−REP sum excluding `reversed=true` |
| `app/Services/CreditLine/Reverse/Exceptions/AlreadyReversedException.php` | Thrown when transaction already reversed |
| `app/Services/CreditLine/Reverse/Exceptions/NotReversibleTypeException.php` | Thrown when type is not BOR/REP |

### Tests

`tests/Unit/Services/CreditLine/Reverse/ReverseServiceTest.php` — 12 test cases.

## Service API

```php
// Constructor
new ReverseService(ReversalOutstandingRecomputer $recomputer)

// Main method
reverse(TransactionExt $tran, UserExt $admin, string $reason): TransactionReversal

// Throws:
//   AlreadyReversedException    — if $tran->reversed === true
//   NotReversibleTypeException  — if $tran->type not in (BOR, REP)
//   InvalidArgumentException    — if $reason is empty/whitespace
```

## Design Decisions

1. **BOR reversal does NOT auto-set paid_off.** When reversing a BOR brings outstanding to 0, the line stays `active`. `paid_off` is a forward-state-only transition triggered by repaying all shares. Reversing a draw means the draw never happened — the line is open with lower outstanding. Admin must explicitly cancel it if desired.

2. **Both `paid` and `partial` rows re-opened.** Per §5 rule 14, the service reopens `CreditLinePayment` rows with status `paid` OR `partial` that have `paid_transaction_id = $tran->id`. This handles partial payments wave 1a's RepayService leaves in `partial` state.

3. **Re-opening status is context-aware.** If `due_date` is past → `late`; otherwise → `scheduled`. `paid_transaction_id` is cleared to null.

4. **Transaction row is never deleted** — flagged `reversed = true` only.

5. **No forward corrective transaction** created here. Re-applying to a different line is a fresh REP via wave 1a's `RepayService`.

## Phase 2 Dependencies

| Item | Detail |
|------|--------|
| **Admin-only auth** | `UserExt` has no `is_admin()` method in the codebase. The controller calling `reverse()` must enforce the admin role via middleware. The service accepts any `UserExt` and records it as the auditor. |
| **Immutable audit rows** | `TransactionReversal` rows are not enforced immutable at the Eloquent layer. Phase 2 should add a model observer or policy to block updates after creation. |
| **Aggregate BOR balance update** | After reversing a BOR, the aggregate BOR `AccountBalance` row (§5 rule 8) should be updated via `OutstandingCalculator::updateAggregateBorBalance()`. To avoid coupling wave 1e to wave 1a during parallel dev, this is deferred to Phase 2 composition. Per-line `outstanding_shares` IS updated correctly. |
| **ReversalOutstandingRecomputer dedup** | Intentionally duplicates wave 1a's `OutstandingCalculator::recomputeForLine()`. Phase 2 should eliminate the duplicate and have `ReverseService` call `OutstandingCalculator` directly. |

## Test List

1. `test_reverse_rep_reopens_paid_schedule_row` — paid row → re-opened, paid_transaction_id cleared
2. `test_reverse_rep_reopens_partial_schedule_row` — partial row → re-opened, FK cleared
3. `test_reverse_rep_restores_outstanding_shares` — outstanding restored after REP reversed
4. `test_reverse_rep_writes_audit_row_with_all_fields` — TransactionReversal has all required fields
5. `test_reverse_rep_fully_paid_off_reopens_line` — line reverts to active when payoff REP reversed
6. `test_reverse_bor_decreases_outstanding` — BOR reversal reduces outstanding to 0
7. `test_reverse_bor_does_not_auto_set_paid_off` — line stays active, not paid_off, after BOR reversal
8. `test_reverse_twice_throws_already_reversed_exception` — second reversal throws AlreadyReversedException
9. `test_reverse_purchase_throws_not_reversible_type_exception` — PUR → NotReversibleTypeException
10. `test_reverse_sale_throws_not_reversible_type_exception` — SAL → NotReversibleTypeException
11. `test_reverse_with_empty_reason_throws_invalid_argument_exception` — empty reason → InvalidArgumentException
12. `test_reverse_with_whitespace_only_reason_throws_invalid_argument_exception` — whitespace reason → exception
13. `test_reversed_transaction_still_exists_in_db` — row not deleted; `reversed = true`
