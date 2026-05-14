# Credit Lines — Phase 4 (Polish)

**Status:** Complete
**Date:** 2026-05-13
**Agent:** Phase 4 (polish — items deferred from Phase 3)

Ships the four items called out in `credit_lines_phase_3.md` §Limitations.

## Checklist

| # | Item | Status | Notes |
|---|---|---|---|
| 1 | Receivable historical reconstruction | done | New `account_credit_line_balances` temporal table + `CreditLineBalanceTracker` service. Wired into Draw, Repay (`repay` + `applyToSchedule`), and Reverse. `FundReceivableCalculator::receivableShares` now reads the table when present and falls back to the live column when a line has no balance history (legacy/test data). |
| 2 | Per-generation chart coloring | done | `_trajectory_chart.blade.php` cycles a 5-color palette (`#9999ff`, `#9999cc`, `#99cccc`, `#99cc99`, `#cccc99`) by generation index. Labels already include `adjusted_at` date ("Plan after 2026-03-15"). Also added an optional `$truncateAt` parameter to support item 4. |
| 3 | Admin-only fund "Cash position" panel | done | `_admin_cash_position.blade.php`, included from `funds/show.blade.php`. Visible only when `auth()->user()?->is_admin()`. Shows portfolio value, receivable value, combined value, and a small table of active lines. |
| 4 | Schedule snapshot wiring on adjustment timeline | done | Two inline buttons per adjustment card: "View schedule at this point" and "View trajectory through this point". Bootstrap 5 modals (matches existing `data-bs-toggle="modal"` pattern). Controller now injects `ScheduleSnapshotBuilder` and builds per-adjustment snapshots up front. Trajectory partial accepts a `$truncateAt` param to clip series data. `AdjustmentHistoryBuilder` payload extended with `id` + `adjusted_at`. |

## Files added

- `database/migrations/2026_05_13_000007_create_account_credit_line_balances_table.php`
- `app/Models/AccountCreditLineBalance.php`
- `app/Services/CreditLine/Support/CreditLineBalanceTracker.php`
- `resources/views/funds/_admin_cash_position.blade.php`
- `tests/Unit/Services/CreditLine/Support/CreditLineBalanceTrackerTest.php`

## Files modified (additive — no breaking signature changes)

- `app/Services/CreditLine/Draw/DrawService.php` — optional `?CreditLineBalanceTracker` ctor arg (defaults to `new CreditLineBalanceTracker()`); records initial outstanding after BOR creation.
- `app/Services/CreditLine/Repay/RepayService.php` — same pattern; records post-repay outstanding in both `repay()` and `applyToSchedule()`.
- `app/Services/CreditLine/Reverse/ReverseService.php` — same pattern; records recomputed outstanding after reversal.
- `app/Services/CreditLine/Reporting/FundReceivableCalculator.php` — historical-aware lookup with line-level fallback to live `outstanding_shares` when no balance history exists.
- `app/Services/CreditLine/Adjust/AdjustmentHistoryBuilder.php` — additive: payload now exposes `id` and `adjusted_at`.
- `app/Http/Controllers/WebV1/AccountCreditLineControllerExt.php` — injects `ScheduleSnapshotBuilder`; precomputes per-adjustment snapshots for the show view.
- `resources/views/account_credit_lines/show.blade.php` — two inline action buttons per adjustment card + modal block per adjustment.
- `resources/views/account_credit_lines/_trajectory_chart.blade.php` — per-generation palette; new optional `$truncateAt` param.
- `resources/views/funds/show.blade.php` — includes the new admin panel partial.
- `tests/Feature/CreditLineFlowTest.php` — additional assertions that the show page renders the timeline actions + modal HTML.

## Migration backfill

`docker exec familyfund php artisan migrate`:
```
2026_05_13_000007_create_account_credit_line_balances_table
  backfilled 0 account_credit_line_balances row(s)
```
The dev database has no pre-existing credit lines, so the backfill was a no-op. On a production restore with existing lines, every active row would be backfilled with `start_dt = origination_date`, `end_dt = '9999-12-31'`, `outstanding_shares = current value`. The migration is idempotent (`exists()` check before insert) so re-running it is safe.

## Test counts

- **CreditLineBalanceTrackerTest:** 5 passed (18 assertions).
- **Credit-line scope** (`tests/Unit/Services`, `tests/Unit/Mail`, `tests/Feature/CreditLineFlowTest.php`): **98 passed, 0 failed (265 assertions).**
- Phase 3 baseline was 93 passed. Phase 4 adds **+5 tests** (the new tracker file) and 5 new assertions inside the existing flow test, with zero regressions.
- `FundExtTest`: 89 passed (sanity check — `valueAsOf` still untouched).

## Discoveries / notes worth flagging

1. **`FundReceivableCalculator` fallback path is important.** The existing `FundReceivableCalculatorTest` creates `AccountCreditLine` rows directly via `Model::create()` without going through `DrawService`, so they have no balance-history rows. To keep that test green (and to not break any future caller that creates lines outside the tracker), the calculator falls back to the live `outstanding_shares` column for lines with no balance history. The fallback only fires when there are zero balance rows for that line — once any tracker write has happened, the temporal view is used exclusively.

2. **`updated_at` on the closed row.** The tracker uses Eloquent `->save()` to close the prior row, which bumps `updated_at`. This is intentional — `updated_at` records "when did we close it", which is useful audit info. `start_dt`/`end_dt` are the semantic temporal bounds.

3. **Backfill scope.** The current backfill only seeds one row per existing line at `origination_date` — it does not reconstruct intermediate history from existing BOR/REP transactions. That's a deliberate scope cut: pre-Phase-4 lines didn't have a tracker to record changes, so the only honest reconstruction is "one row at origination → current value". If anyone queries historical receivable for a date between origination and now on a pre-Phase-4 line, they'll see the *final* outstanding throughout. New lines (created after this migration) are fully tracked.

4. **Trajectory truncation is data-only, not chart-time.** When the modal renders the truncated chart, it filters series points by date string before passing to QuickChart. The chart is still built fresh inside the modal — there's no caching, so the modal pulls a separate QuickChart image. Acceptable for an admin tool; if performance becomes a concern, pre-render the truncated images server-side.

5. **Wave-1 services accept the tracker via *optional* constructor arg.** This preserves existing test setup (`new DrawService($builder, $calc)` still works — defaults to a fresh tracker) while letting the dependency container or tests inject a mock. Laravel's container resolves the new arg via autowiring for controller injection. No service-provider changes were needed.
