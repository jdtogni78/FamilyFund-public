# Credit Lines — Phase 2 (Integration)

**Status:** Complete
**Date:** 2026-05-13
**Agent:** Phase 2 (sequential integration)

Wires the wave-1 service layer to HTTP, scheduler, and Eloquent model events.

## Checklist

| # | Item | Status | Notes |
|---|---|---|---|
| 1 | Migration `2026_05_13_000006_add_notification_settings_to_account_credit_lines.php` | done | 7 columns added; model fillable + casts updated; `LineNotificationSettings` reads them. |
| 2 | `UserExt::is_admin()` helper | done | Aliases existing `User::isSystemAdmin()`. Added to **both** `User` and `UserExt` because `auth()->user()` may return either depending on auth driver config. No new column needed — uses the existing Spatie role machinery (`system-admin` role at team_id=0). |
| 3 | `RepayService::applyToSchedule` + `advance()` | done | Public method; takes already-saved REP; calls into renamed private `applyToScheduleRows`. `RepayService implements ScheduleAdvancer`. |
| 4 | Bind `ScheduleAdvancer → RepayService` | done | In `AppServiceProvider::register()`. |
| 5 | `TransactionObserver` | done | Hooks `Transaction::saved`. Recursion guard = static `$reentrant` flag plus a load-bearing-columns filter (`type / shares / value / status`) — matcher updates that only touch FK columns are ignored. Promotes to `TransactionExt` before calling `TransactionDetectionService::ingest`. Errors caught and logged so detection never breaks the originating save. |
| 6 | Console Kernel scheduling | done | `ScanRemindersJob` daily 07:00, `ScanLatePaymentsJob` daily 07:15, both `withoutOverlapping`. |
| 7 | Form requests | done | Six request classes, all `authorize() = auth()->user()?->is_admin()`. |
| 8 | Controllers | done | `AccountCreditLineControllerExt`, `CreditLineMatchResolutionController`, `TransactionReversalController`. Services injected via constructor. |
| 9 | Routes | done | 11 named routes under `credit_lines.*`, all behind the existing `middleware('auth')` group. Resolve-index route registered before parameterized `{line}` route to avoid `resolve` being captured as a line ID. |
| 10 | Dedupe `OutstandingCalculator` | done | Added optional `bool $includeReversed = false` to `recomputeForLine()`. `ReversalOutstandingRecomputer` now delegates and is marked `@deprecated`. `ReadjustService` already used the canonical class (verified). |
| 11 | `accounts/show.blade.php` extension | done | Includes `account_credit_lines/_account_summary` partial — header banner for flagged transactions + per-account summary card + per-line table. |
| 12 | `account_credit_lines/*.blade.php` views | done | `_account_summary.blade.php` (partial), `index`, `create`, `show` (with admin action panels for repay / readjust / cancel + schedule + adjustment history), `edit` (redirects to show). Also added `credit_lines/resolve_index.blade.php`. |
| 13 | `tests/Feature/CreditLineFlowTest.php` | done | Single end-to-end test exercising open → repay → readjust → reverse via HTTP. Passes. |

## Key decisions

- **Admin role model.** No new column. Uses the existing Spatie team-scoped `system-admin` role. `is_admin()` is a thin alias to `isSystemAdmin()` defined on both `User` and `UserExt`.
- **Observer recursion guard.** A static reentrancy boolean is the primary defense; the secondary filter on load-bearing column changes (`type / shares / value / status`) gives us a deterministic skip for matcher / reversal updates that only touch FK and flag columns. Both layers are needed: the matcher and `ReverseService` both save the same `Transaction` row, sometimes outside a `DB::transaction` wrapping `ingest()`.
- **`applyToSchedule` vs `advance`.** Kept both: `applyToSchedule` is the natural name used in the prompt and is the public API; `advance` is the contract method required by `ScheduleAdvancer`. The latter delegates.
- **Reverse outstanding dedupe.** Rather than rewriting the wave-1e service signature, `ReversalOutstandingRecomputer` was thinned to a delegate so `ReverseService`'s constructor signature is unchanged and existing tests / DI graphs still work.
- **`LineNotificationSettings::delayNotificationEnabled`.** Spec didn't include a dedicated enabled column. The migration omits it intentionally; the method continues to return the DEFAULT (which is true). If a future iteration wants a dedicated toggle, add `delay_notification_enabled` and update the method.

## Test counts

- **Wave-1 unit + mail tests:** 81 passed, 1 skipped (cash-fallback test, pre-existing baseline-DB issue) — unchanged.
- **New feature test:** 1 passed (`CreditLineFlowTest::test_admin_can_open_repay_readjust_and_reverse_a_credit_line`, 12 assertions).
- **Combined credit-line scope:** 82 passed, 1 skipped.
- **Full suite:** 1388 passed, 376 failed (baseline failures: missing Vite manifest in HTTP tests, golden-data fixtures, pre-existing `AccountExtTest::shareValueAsOf` flakes). None of the failures are introduced by Phase 2 — they exist on the pre-Phase-2 commit per `credit_lines_wave_1_summary.md` and the project's `test_plan.md`.

## Open items for Phase 3 (reporting)

- Trajectory chart and per-account / per-fund reporting widgets.
- Quarterly report sections (loans summary, repayment schedule).
- Loans summary card for the fund overview page.
- `TransactionReversal` row-level immutability enforcement (currently relies on app-level discipline).
- `ContributionClassifier` is still a no-op stub — wire to the existing TransactionMatching flow.
- `EmailDedup` could be promoted from cache to a DB log if persistence across cache wipes matters.
- Phase 4 (tax-counsel review of §7872 implications + production money-flow integration).
