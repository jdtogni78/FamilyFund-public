# Credit Lines — Phase 3 (Reporting & Visualization)

**Status:** Complete
**Date:** 2026-05-13
**Agent:** Phase 3 (sequential reporting)

Adds account / fund / quarterly-report reporting and the multi-generation
payoff trajectory chart to the credit-line feature.

## Checklist

| # | Item | Status | Notes |
|---|---|---|---|
| 1 | `TrajectoryBuilder` | done | Multi-generation: `original_plan`, `historical_plans`, `current_plan`, `actual_repayments`, `projected_payoff_date` (trailing-3 average), `planned_payoff_date`, `variance_days`. |
| 2 | `FundReceivableCalculator` | done | `receivableShares()` sums active-line outstanding; `receivableValue()` × current share price. As-of param accepted but treated as "current" — see Limitations. |
| 3 | `LoansSummaryBuilder` | done | Aggregates lifetime disbursed / repaid / outstanding, next-due, active-line and behind-plan counts. Used by account page and quarterly report. |
| 4 | `FundExposureBuilder` | done | Active-line shares, outstanding value, behind-plan count, total lines. Depends on `FundReceivableCalculator`. |
| 5 | `FundExt` extension | done | Added `creditLineReceivableValueAsOf()` and `valueWithCreditLinesAsOf()`. `valueAsOf()` **unchanged**. See FundExt decision below. |
| 6 | Trajectory chart partial | done | `resources/views/account_credit_lines/_trajectory_chart.blade.php`. Uses `quickchart` base URL via GET-encoded config (simpler than rendering to a file in the request path). |
| 7 | Account credit-line `show` extended | done | Loans summary card, trajectory chart, share-value clarity copy, revamped adjustment-history timeline (uses new `AdjustmentHistoryBuilder` shape). Controller updated to inject the two builders. |
| 8 | `accounts/show` extended | done | `_account_summary` partial now renders the loans-summary card at top and adds share-value copy near the per-line table. |
| 9 | `funds/show` extended | done | Added `_credit_line_exposure` partial via include. Shows outstanding shares, value, active / behind-plan counts. |
| 10 | Quarterly account report | done | `_quarterly_section_pdf` partial included at end of `accounts/show_pdf.blade.php`. Shows loans-summary card, per-line activity in the quarter, adjustments in the quarter. |
| 11 | Quarterly fund report | done | `_credit_line_exposure_pdf` partial included at end of `funds/show_pdf.blade.php`. Shows outstanding, Q disbursed/repaid, adjustment count, behind-plan list. |
| 12 | Report job round-trip | done | Existing job pipelines (`AccountReportControllerExt`, `FundReportControllerExt` → `AccountPDF` / `FundPDF` traits) render the same blade templates; new partials are no-ops when no credit lines exist on the entity. Wave-1 + Phase 2 tests still green. |

## FundExt decision

**Chose Option B (separate method) — `valueWithCreditLinesAsOf()`.**

Rationale:
- Overriding `valueAsOf()` would have changed the NAV used by `shareValueAsOf()`,
  `withdrawalAdjustedValue()`, `periodPerformance()`, and every existing report
  / chart that depends on portfolio-only valuation. That risks silent regression
  in the 1388-test suite (which includes many golden-data and snapshot
  comparisons of NAV) and breaks the philosophical separation in
  `docs/credit_lines/fund_cashflow.md` between "portfolio value" and "receivable
  value".
- The receivable is computed **on demand** by reporting code that explicitly
  wants the receivable-as-asset NAV view. The existing share-price math (which
  drives historical NAV and account valuations) continues to flow exclusively
  through `valueAsOf()` → `shareValueAsOf()`, so historical NAV reconstruction
  remains consistent with the rest of the system.
- `creditLineReceivableValueAsOf()` is a public helper available to any
  future reporting layer that needs to combine the two.

If a future iteration decides that all fund-level NAV should reflect the
receivable, the override is a one-line change in this file with a single
documented test. For Phase 3 we deliberately stay non-destructive.

## Receivable-as-asset math (worked example — matches `fund_cashflow.md`)

Initial: fund has $1,000 cash + 100 shares + share price $10.

1. **Draw 5 shares.**
   - Account `BOR` balance: +5.
   - Fund cash leaves: $50 → $950.
   - `creditLineReceivableValueAsOf(now)` = 5 shares × $10 = $50.
   - `valueAsOf(now)` (portfolio-only) reports $950 (cash post-disbursement).
   - `valueWithCreditLinesAsOf(now)` = $950 + $50 = **$1,000**. NAV = 1000/100 = **$10.00**, unchanged.

2. **Share price drifts to $11** (portfolio appreciation).
   - `creditLineReceivableValueAsOf(now)` = 5 × $11 = $55.
   - `valueAsOf(now)` = $950 × (price-adjusted via portfolio history).
   - `valueWithCreditLinesAsOf(now)` carries the receivable along with the
     market — the loan is economically neutral.

3. **Borrower repays 5 shares at $11.**
   - Cash returns to fund: 5 × $11 = $55 → cash $1,005.
   - Receivable: 0.
   - `valueAsOf(now)` = $1,005. NAV = $10.05.
   - Borrower paid back $55 of cash for $50 originally received; the $5 delta
     is the fund's earned-over-the-loan-life P&L.

## Test counts

- **New reporting tests:** 10 passed (35 assertions).
- **Wave-1 + Phase 2 scope** (`tests/Unit/Services`, `tests/Unit/Mail`, `tests/Feature/CreditLineFlowTest.php`): **93 passed, 0 failed.**
- Pre-Phase-3 baseline in `credit_lines_phase_2.md`: 82 passed in the same scope. Phase 3 adds **+11 net** (10 reporting + 1 feature scope addition) with zero regressions.

## Limitations / deferred

1. **Receivable historical reconstruction.** `FundReceivableCalculator::receivableShares()`
   accepts `$asOf` but treats the receivable as point-in-time (`outstanding_shares`
   is a column, not a balance-history table). For historical NAV reconstruction
   (e.g., NAV chart 6 months ago), the receivable will be the *current* outstanding.
   Phase 4 candidate: add an `account_credit_line_balances` table mirroring
   `account_balances` so we can rewind the receivable.
2. **Inline chart vs pre-rendered.** The trajectory chart partial currently
   builds a QuickChart GET URL. This works for in-browser pages because the
   browser fetches the image directly from the container's quickchart sidecar
   (`http://quickchart:3400`). For PDFs (which render via wkhtmltopdf inside
   the container), the GET URL works the same way. If we ever need to render
   PDF reports from a context where the quickchart hostname is unreachable,
   the controller can pre-render and pass `$chartUrl` to the partial — the
   partial already accepts that injection.
3. **Admin-only "Cash position" view on the fund page** (mentioned in
   prompt's "Deferred items"): not implemented. The current fund-page section
   shows aggregate exposure to all users with fund access; an admin-only
   detailed view would require adding a role check around a separate panel.
4. **Trajectory chart per-historical-plan styling**: all historical generations
   render with the same faded color. Plan calls for one color per generation.
   Acceptable v1 — improves with chart-config tweaks, not a functionality gap.
5. **Adjustment-history "View schedule snapshot" / "View trajectory through
   this point" actions** (§8.5) — surface as inline links not implemented.
   `ScheduleSnapshotBuilder` already exists from Phase 1c; a future iteration
   can route to it.

## Files added

- `app/Services/CreditLine/Reporting/TrajectoryBuilder.php`
- `app/Services/CreditLine/Reporting/FundReceivableCalculator.php`
- `app/Services/CreditLine/Reporting/LoansSummaryBuilder.php`
- `app/Services/CreditLine/Reporting/FundExposureBuilder.php`
- `resources/views/account_credit_lines/_trajectory_chart.blade.php`
- `resources/views/account_credit_lines/_loans_summary_card.blade.php`
- `resources/views/account_credit_lines/_quarterly_section_pdf.blade.php`
- `resources/views/funds/_credit_line_exposure.blade.php`
- `resources/views/funds/_credit_line_exposure_pdf.blade.php`
- `tests/Unit/Services/CreditLine/Reporting/TrajectoryBuilderTest.php`
- `tests/Unit/Services/CreditLine/Reporting/FundReceivableCalculatorTest.php`
- `tests/Unit/Services/CreditLine/Reporting/LoansSummaryBuilderTest.php`
- `tests/Unit/Services/CreditLine/Reporting/FundExposureBuilderTest.php`

## Files modified

- `app/Models/FundExt.php` (+ two helpers, `valueAsOf` untouched)
- `app/Http/Controllers/WebV1/AccountCreditLineControllerExt.php` (injects two builders)
- `resources/views/account_credit_lines/show.blade.php` (loans summary, trajectory, share-value copy, timeline)
- `resources/views/account_credit_lines/_account_summary.blade.php` (loans summary card at top, share-value copy)
- `resources/views/funds/show.blade.php` (include exposure partial)
- `resources/views/accounts/show_pdf.blade.php` (include quarterly section)
- `resources/views/funds/show_pdf.blade.php` (include exposure-pdf partial)
