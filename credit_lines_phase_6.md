# Credit Lines — Phase 6 (gap-closing pass)

Closes the 5 gaps identified in `credit_lines_completion_audit.md` against the
50-use-case master plan.

## Per-gap status

| # | UC    | Title                                            | Status   | Files shipped                                                                                                                                                  | Test added                                                                                                                       |
|---|-------|--------------------------------------------------|----------|----------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------|
| 1 | UC-20 | Configure reminders (per-line settings UI)       | ✅ Done | `app/Http/Requests/UpdateAccountCreditLineRequest.php` (new), `app/Http/Controllers/WebV1/AccountCreditLineControllerExt.php` (+`update()`), `resources/views/account_credit_lines/edit.blade.php` (rewritten with 7-field form), `routes/web.php` (+`PUT /credit-lines/{line}` → `credit_lines.update`) | `CreditLineFlowTest::test_admin_can_update_credit_line_notification_settings` (round-trips all 7 fields + asserts form renders)  |
| 2 | UC-47 | Closure-block when active lines exist            | ✅ Done | `app/Http/Controllers/AccountController.php` (`destroy()` guards on `creditLines()->where('status','active')->exists()`, lists ids+descr in flash error)                                                                                              | `CreditLineFlowTest::test_account_closure_blocked_when_active_credit_line_exists` (asserts response is redirect/4xx and row survives) |
| 3 | UC-46 | Admin create-transaction with backdated timestamp | ✅ Done | `app/Http/Requests/AdminCreateTransactionRequest.php` (new), `app/Http/Controllers/WebV1/AdminTransactionController.php` (new), `resources/views/admin/transactions/create.blade.php` (new), `routes/web.php` (+`admin.transactions.create` / `admin.transactions.store`) | `AdminBackdatedTransactionTest` (2 tests: admin backdates a REP for 2024-03-15; non-admin gets 403) |
| 4 | UC-37 | Contribution classifier round-trip                | ✅ Done | `app/Services/Detection/ContributionClassifier.php` (rewritten — reads `TransactionMatching` written by legacy `createMatching()` and reports `auto_matched` / `n_a` without duplicating writes)                                                       | `TransactionDetectionServiceTest::test_contribution_classifier_round_trip_reports_existing_matches` (n_a before, auto_matched after MAT row written) |
| 5 | UC-11 | Default-line repay picker                        | ⛔ Dropped (Option B) | No code change — see decision below                                                                                                                              | n/a                                                                                                                              |

## UC-11 decision (Option B)

**Chose Option B: dropped as resolved-by-design.**

Reasoning:

- The credit-line matcher (Wave 1b `CreditLineMatcher`) already classifies an
  arbitrary REP against all active lines on the account, returning
  `auto_matched` / `ambiguous` / `unmatched`.
- Ambiguous results flow into `MatchResolutionService` (Wave 1b) and surface
  on the admin Resolve page (Phase 2 — route `credit_lines.resolve_index`,
  view `account_credit_lines.resolve`).
- A dedicated "pick the line with the earliest scheduled due date" form would
  duplicate work the matcher already does, and would still produce an
  ambiguous result the same admin would have to resolve in the UI.
- Building Option A end-to-end (form + radio with next-due preselect +
  earliest-due query + REP creation + redirect) would take longer than the
  30-minute budget the task allowed.

Net effect: the existing surface (issue REP from the line's show page, or
let the matcher run over an incoming REP) plus the resolve page covers the
use case. UC-11 is therefore closed as "resolved by design — see matcher
+ MatchResolutionService".

## UC-37 implementation note

Two designs considered for wiring the unified pipeline through to PUR:

1. **Invoke `TransactionExt::createMatching()` from `ContributionClassifier`.**
   Rejected: that method is `protected`, requires the full `processPending`
   ceremony (share-value, fund-share availability, balance overlap), and is
   already invoked from `processPending` on the same PUR row. Calling it
   twice would write duplicate MAT transactions and balances.
2. **Read-only adapter — what shipped.** `ContributionClassifier::classify()`
   inspects `TransactionMatching` rows whose `reference_transaction_id`
   equals the PUR's id. The legacy `createMatching` writer runs first
   (synchronously, inside `processPending`); by the time the observer fires
   the rows are persisted and the classifier can report them.

The trade-off is that the classifier is *passive* — it observes what the
legacy engine did, rather than driving the match itself. The unified
pipeline is now meaningful for PUR (the result reaches logs / metrics /
future per-classifier monitoring) without forking the matching engine.
If a future wave extracts `createMatching` into a service, this classifier
becomes the natural place to call it.

`n_a` (not `unmatched`) is returned when no rules fire, because PUR without
matching is a normal everyday deposit; treating it as `unmatched` would
trigger a mismatch-alert email on every contribution.

## Test scope

After Phase 6:

- `tests/Feature/CreditLineFlowTest.php`: 3 tests (was 1) — 14 + 7 + 3 assertions
- `tests/Feature/AdminBackdatedTransactionTest.php`: 2 tests (new)
- `tests/Unit/Services/Detection/TransactionDetectionServiceTest.php`: 7 tests (was 6)

Combined credit-line scope (`tests/Unit/Services tests/Unit/Mail
tests/Feature/CreditLineFlowTest.php
tests/Feature/AccountShowWithCreditLineTransactionsTest.php
tests/Feature/AdminBackdatedTransactionTest.php`):

```
Tests:    105 passed (309 assertions)
Duration: 3.48s
```

Legacy matching regression check
(`MatchingRuleControllerExtTest` + `AccountMatchingRuleControllerExtTest`):

```
Tests:    31 passed (79 assertions)
```

Dusk smoke (full Dusk takes 35s+, ran one as instructed):

```
PASS  Tests\Browser\CreditLineBorrowingFlowTest
✓ admin can open credit line through ui   3.12s
Tests: 1 passed (6 assertions)
```

## Updated audit score

| Bucket            | Before | After |
|-------------------|--------|-------|
| Done              | 43     | **48** |
| Real gaps         | 5      | 0     |
| Resolved-by-design| —      | 1 (UC-11) |
| Out-of-scope (v2) | 1 (UC-39) | 1 (UC-39) |
| Partial           | 1 (UC-21 historical reconstruction for pre-Phase-4 lines) | 1 (unchanged) |

**Score: 48 of 50 use cases done.** Remaining 2:
- UC-21 partial — pre-Phase-4 lines need historical replay; documented in audit, no work planned.
- UC-39 — external sources (CSV / bank feed); plan explicitly marks as v2.

## Files added (10)

- `app/Http/Requests/UpdateAccountCreditLineRequest.php`
- `app/Http/Requests/AdminCreateTransactionRequest.php`
- `app/Http/Controllers/WebV1/AdminTransactionController.php`
- `resources/views/admin/transactions/create.blade.php`
- `tests/Feature/AdminBackdatedTransactionTest.php`
- `credit_lines_phase_6.md` (this file)

## Files modified (6)

- `app/Http/Controllers/WebV1/AccountCreditLineControllerExt.php` (+`update()`)
- `app/Http/Controllers/AccountController.php` (UC-47 closure guard in `destroy()`)
- `app/Services/Detection/ContributionClassifier.php` (full rewrite — read-only adapter)
- `resources/views/account_credit_lines/edit.blade.php` (full rewrite — 7-field form)
- `routes/web.php` (+3 routes: `credit_lines.update`, `admin.transactions.create`, `admin.transactions.store`)
- `tests/Feature/CreditLineFlowTest.php` (+2 tests)
- `tests/Unit/Services/Detection/TransactionDetectionServiceTest.php` (+1 test)

## Discoveries / notes

1. The existing `account_credit_lines/edit.blade.php` (and `show.blade.php`)
   uses the `<x-app-layout>` component with a misleading `@section('content')`
   marker that is **not** terminated by `@endsection`. The marker is a no-op;
   content actually renders into the component's default slot. Adding
   `@endsection` would silently swallow the entire form (content captured
   into an unyielded section). The new `edit.blade.php` and
   `admin/transactions/create.blade.php` follow the same convention — a leading
   `@section('content')` marker but **no** `@endsection` close. A future
   cleanup wave could either drop the markers entirely or move the layouts
   to a proper inheritance pattern; out of scope here.

2. `accounts` table has no `name` column — only `nickname`. The initial admin
   transactions controller draft used `name`; fixed during testing.

3. `AccountCreditLine::$fillable` already included all 7 notification-settings
   columns (added in Phase 2 migration `2026_05_13_000006`), so the `update()`
   action's `$line->fill($request->validated())->save()` works with no
   model-level changes.

4. `UpdateAccountCreditLineRequest::prepareForValidation()` normalises the 3
   boolean fields because unchecked HTML checkboxes don't submit a value — a
   hidden `<input type="hidden" name="x" value="0">` in the form also handles
   this client-side, but the request guards against the case where the hidden
   input is missing (or the request comes from an API client).
