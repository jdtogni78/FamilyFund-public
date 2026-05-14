# Bugs Found via UI Tour (Phase 5 E2E)

Captured during the Dusk `CreditLineUITourTest` run on 2026-05-14. Each bug
has a status + an automated regression test linked.

## 1. `AccountTrait.php` `DivisionByZeroError` on account show page

**Symptom**: Loading `/accounts/{id}` for any account with at least one BOR or
REP transaction crashed with `DivisionByZeroError` at
`app/Http/Controllers/Traits/AccountTrait.php:57` —
`Utils::percent($current/$value - 1)` where `$value = 0`.

**Root cause**: Credit-line BOR/REP transactions are recorded with
`value = 0` because the cash leg is deferred (see
`docs/credit_lines/fund_cashflow.md`). The pre-existing transactions list on
the account show page iterates all transactions and tries to compute a
percentage performance against `$value`, which divides by zero.

**Fix**: Guard the two division sites in `AccountTrait::createTransactionsResponse`.
Returns 0% when `$value == 0`.

**Status**: ✅ FIXED in this thread.

**Regression test**: `tests/Feature/AccountShowWithCreditLineTransactionsTest.php`
— creates a BOR transaction with `value=0` on a test account, hits
`/accounts/{id}`, asserts 200 OK.

## 2. `TrajectoryBuilder` "Original plan" double-counts new rows

**Symptom**: After a readjustment, the trajectory chart's "Original plan" line
ramped up by 27.0833 shares per period instead of the expected 20 (for a
120-share / 6-month line). The increment matched 20 + 7.0833 — i.e., one
original-plan row (20) plus one new-plan row (7.0833) at every step.

**Root cause**: `TrajectoryBuilder::build()` filtered the "original generation"
rows with `created_at->lte($firstAdjustedAt)`. When `ReadjustService` runs in
a single DB transaction, new `CreditLinePayment` rows can have
`created_at == adjusted_at` to second precision, so they slipped into the
original generation alongside the truly-original rows.

**Fix**: Use strict `lt` instead of `lte`. New rows created at the exact same
instant as `adjusted_at` now correctly belong to the new generation.

**Status**: ✅ FIXED in this thread.

**Regression test**:
`tests/Unit/Services/CreditLine/Reporting/TrajectoryBuilderOriginalPlanTest.php`
— creates a line, readjusts, asserts `original_plan` series contains only
original-row increments (no new-row contribution).

## 3. "Actual repayments" chart line is flat

**Symptom**: After two repayments + one reversal, the trajectory chart's
"Actual repayments" line was flat at 20 across all dates.

**Root cause**: NOT A BUG. All forward REPs in the tour happened on the same
date (`2026-05-14`), and the surviving (non-reversed) REP was a single 20-share
payment. The `alignSeries` forward-fill correctly extends a single data point
across the chart's date range. The flat line is mathematically accurate: at
every date in the chart's window, the cumulative repaid is exactly 20.

**Action**: No fix needed. A *display* improvement (showing a step function
instead of forward-fill) is a future cosmetic improvement.

**Status**: ⚪ NOT A BUG (display artifact of test data clustering).

## 4. Quickchart URL hardcoded to container hostname

**Symptom**: Trajectory chart `<img>` tags pointed to `http://quickchart:3400`,
which the *server* can resolve (inside Docker) but a *browser running on the
host* cannot. Charts rendered as broken images for any user not running their
browser inside the docker network.

**Root cause**: `config/quickchart.php` has
`'base_url' => env('QUICKCHART_URL', 'http://quickchart:3400')`. The default
is correct for SSR (e.g., wkhtmltopdf running in the container) but wrong for
the browser-rendered `<img>` on the show page.

**Fix**: `.env.dev` now sets `QUICKCHART_URL=http://localhost:3400` so the
browser uses the host-mapped port. For environments where the same blade
template is consumed by both browsers AND wkhtmltopdf, the URL needs more
nuance — Phase 6 / deferred. Note: the existing PDF jobs in `AccountPDF` and
`FundPDF` render charts to temp PNGs server-side via `QuickchartUtil` and
embed them — so they do NOT consume this URL directly.

**Status**: ⚠️ WORKED AROUND in dev env. Production deployment must verify
its `QUICKCHART_URL` env var points to a browser-reachable host.

**Regression test**: `tests/Unit/CreditLineTrajectoryChartUrlTest.php` —
renders the partial against a stubbed line and asserts the rendered HTML
contains the configured `quickchart.base_url`, not a hardcoded literal.

## 5. Dusk test wait-logic bugs (Phase 5 agent output)

**Symptom**: The agent-written Dusk tests had three wait-logic issues that
caused false failures:
- `openLineViaUi` used `waitForLocation('#', 5)` (no fragment in redirect URL).
- `test_readjust_*` expected 12 schedule rows after readjust; correct is
  12 scheduled + 6 cancelled (preserved per plan §6 step 3).
- `test_reverse_*` queried the DB for the REP transaction before the
  post-`press` redirect completed.

**Status**: ✅ FIXED in this thread during the Phase 5 verification run.
Documented in `credit_lines_phase_5_e2e.md` "Bugs fixed during the run".

## 6. Schedule shows old + new rows mixed by due_date

**Symptom**: After readjust, the schedule table on the show page lists rows
in `due_date` order. For dates that have both an original-plan row (now
`cancelled`) and a new-plan row (`scheduled`), both are shown — visually
confusing because the user sees, for example, two rows for `2026-06-14`:
one `20.0000 paid` and one `7.0833 scheduled`.

**Root cause**: NOT A BUG (by design per plan §6 step 3 — cancelled rows are
preserved for historical schedule snapshots). The mixing is intended for the
audit trail, but the UI presentation could group/filter by status to be
clearer.

**Status**: ⚪ NOT A BUG. Cosmetic UI improvement (separate "current schedule"
from "superseded schedule") deferred to Phase 7+.

## 7. Mixed-state account data pollution

**Symptom**: After running the tour test multiple times, the loans summary
showed `19 active lines, 2,900 disbursed lifetime` even though only one line
existed in the current run.

**Root cause**: The tour test's `cleanupLine()` only runs on the happy path.
Test failures leave their data behind. Across multiple runs the account
accumulates orphan credit lines that count toward the loans summary.

**Status**: ⚪ ENVIRONMENT ISSUE, not a code bug. Same problem affects manual
QA. Mitigation: wrap tour in `DatabaseTransactions` (would require seeding +
fixtures to be DatabaseTransactions-safe — non-trivial in this codebase). For
now: manual cleanup procedure documented at the top of
`tests/Browser/CreditLineUITourTest.php`. The maintenance script
`docker exec familyfund php artisan tinker --execute "..."` used to reset
account 7 during this run is captured in
`docs/credit_lines/tour_test_cleanup.md`.

---

## Summary

| # | Bug                                            | Type           | Status   | Test                                                                                |
|---|------------------------------------------------|----------------|----------|-------------------------------------------------------------------------------------|
| 1 | AccountTrait DivisionByZero on BOR/REP         | Code regression| ✅ Fixed | `tests/Feature/AccountShowWithCreditLineTransactionsTest.php`                       |
| 2 | TrajectoryBuilder original-plan double-counts  | Code regression| ✅ Fixed | `tests/Unit/Services/CreditLine/Reporting/TrajectoryBuilderOriginalPlanTest.php`    |
| 3 | "Actual" flat line                              | Not a bug      | ⚪       | n/a                                                                                  |
| 4 | Quickchart URL hardcoded to container          | Env / config   | ⚠️ Workaround | `tests/Unit/CreditLineTrajectoryChartUrlTest.php`                              |
| 5 | Dusk test wait-logic bugs (3)                  | Test bugs      | ✅ Fixed | self-validating (Dusk tests now pass)                                                |
| 6 | Old + new schedule rows mixed                  | By design      | ⚪       | n/a                                                                                  |
| 7 | Test data pollution                            | Environment    | ⚪       | n/a (procedure doc)                                                                  |
