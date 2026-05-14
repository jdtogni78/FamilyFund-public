# Credit Lines — Phase 9: Payment Simulator

Read-only "what if" simulator. Trustee picks a credit line, types a hypothetical monthly USD payment, and sees three projections (conservative / expected / aggressive) of payoff month, payoff date, and cumulative shares repaid.

## Files

| File | Lines | Type |
|---|---:|---|
| `app1/family-fund-app/app/Services/CreditLine/Simulation/PaymentSimulator.php` | 182 | new — service |
| `app1/family-fund-app/app/Services/CreditLine/Simulation/SimulationResult.php` | 38 | new — value object |
| `app1/family-fund-app/app/Http/Controllers/WebV1/AccountCreditLineControllerExt.php` | +56 | edited — added `simulator()` action + import |
| `app1/family-fund-app/routes/web.php` | +4 | edited — `GET /credit-lines/{line}/simulator` named `credit_lines.simulator` |
| `app1/family-fund-app/resources/views/account_credit_lines/simulator.blade.php` | 223 | new — view |
| `app1/family-fund-app/tests/Unit/Services/CreditLine/Simulation/PaymentSimulatorTest.php` | 150 | new — 6 unit tests |
| `app1/family-fund-app/tests/Feature/CreditLineSimulatorTest.php` | 144 | new — 3 feature tests |
| `app1/family-fund-app/tests/Browser/CreditLineSimulatorUITest.php` | 102 | new — 1 Dusk test |

## Growth-rate scenarios

Multipliers match the codebase convention in `ChartBaseTrait.php:301-310` and `QuickChartService::generateForecastChart()`.

| Scenario | Rate |
|---|---|
| Conservative | `fund.getExpectedGrowthRate() * 0.8` |
| Expected | `fund.getExpectedGrowthRate()` |
| Aggressive | `fund.getExpectedGrowthRate() * 1.2` |

Higher growth → share price climbs faster → fewer shares purchased per USD → slower payoff.

Safety cap: 600 months (`PaymentSimulator::MONTH_CAP`).

## Test count delta

- Unit: +6 (PaymentSimulatorTest)
- Feature: +3 (CreditLineSimulatorTest)
- Dusk: +1 (CreditLineSimulatorUITest)

Combined run `php artisan test tests/Unit/Services/CreditLine tests/Feature/CreditLineFlowTest.php tests/Feature/CreditLineSimulatorTest.php`: **99 passed, 297 assertions, 2.78s**.

Dusk `--filter CreditLineSimulator`: **1 passed, 10 assertions, 4.50s**.

## Screenshots

- `app1/family-fund-app/tests/Browser/screenshots/simulator/01_empty_form.png` — simulator form, no input
- `app1/family-fund-app/tests/Browser/screenshots/simulator/02_with_results.png` — 3-scenario summary table + chart after submitting $50/month

## Notes / discoveries

- `account_credit_lines/show.blade.php` uses `<x-app-layout> @section('content')` **without** a closing `@endsection`. Adding `@endsection` silently breaks rendering (sections aren't flushed in the component-layout pattern). I matched the existing convention.
- Feature tests need `tearDown()` to drain output buffers (`ob_end_clean`) — same gotcha as `CreditLineFlowTest`. Without it tests are marked "risky" not failing.
- The `simulate()` core is pure given `$startShareValue` — only `simulateAllScenarios()` touches the fund relationship to read `getExpectedGrowthRate()`. This kept unit tests deterministic.
- The growth-vs-payoff differential test originally used 7% vs 8.4%: too small for `outstanding=100, payment=$5` over ~22 months (both rounded to month 22). Widened to 5% vs 50% to exercise the directional invariant robustly without baking in exact month numbers.
- Admin gating: done in the controller action (`if (!auth()->user()?->is_admin()) abort(403)`) since this is a GET that doesn't go through a FormRequest. Matches the pattern used by `exchange-holidays.index` and `emails.index` (commented as "admin only - checked in controller").
- Chart reuse: `QuickChartService::generateForecastChart()` was considered but it builds three lines from a single `predictions` array by multiplying by 0.8/1.2 — our three lines are independently simulated. Inline QuickChart config (same pattern as `_trajectory_chart.blade.php`) was a better fit.
