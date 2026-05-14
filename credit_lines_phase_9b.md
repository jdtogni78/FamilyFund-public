# Credit Lines — Phase 9b: Simulator Time-Based Mode

Extends the Phase 9 payment simulator with a second mode. Instead of "what payoff date does $X/month produce?", the trustee picks a target payoff time (months) and the simulator computes the **required monthly USD payment** under each growth scenario (conservative / expected / aggressive).

## Files

| File | Type |
|---|---|
| `app/Services/CreditLine/Simulation/PaymentSimulator.php` | edited — `solveForPayment()` + `solveForPaymentAllScenarios()` added |
| `app/Http/Controllers/WebV1/AccountCreditLineControllerExt.php` | edited — mode dispatch in `simulator()` |
| `resources/views/account_credit_lines/simulator.blade.php` | edited — mode toggle + conditional inputs + per-scenario "required payment" column |
| `tests/Unit/Services/CreditLine/Simulation/PaymentSimulatorTest.php` | +4 tests |
| `tests/Feature/CreditLineSimulatorTest.php` | +1 test |
| `tests/Browser/CreditLineSimulatorUITest.php` | +1 test |

## Solver approach

**Binary search over payment USD**, reusing `simulate()`.

- Bounds: low = `$0.01`, high = `outstanding_shares × startShareValue × 10` (very generous — enough to clear in one month even after growth).
- Iteration cap: **60**.
- Per iteration: call `simulate(mid)`; if `payoff_month <= target_months`, record as candidate and search lower half; otherwise (or capped), search upper half.
- Stop early when the bracket shrinks below `$0.01`.
- Return: smallest payment whose `payoff_month <= target_months` (pay off a hair early rather than late). If the bracket never produced a payoff-in-time result, return the high bound.

### Why not closed-form

A geometric-series solution exists in continuous time: `P = outstanding × shareValue × ((1+r)^n - r) / ((1+r)^n - 1)` (or similar). I considered it and rejected it because `simulate()`:

1. Rounds `shares_paid` to 4 decimals each month.
2. Caps the final-month `shares_paid` at the remaining outstanding (no over-repayment).
3. Compounds share value monthly using `(1 + annual)^(1/12)`.

Those discretisations break the smooth analytic formula and would require post-correction anyway. Binary search converges in well under 60 iterations and reuses the exact same `simulate()` semantics, so the round-trip (`simulate → solveForPayment` recovers original payment within $1) holds.

## Test count delta

- **Unit**: +4 (`test_solve_for_payment_with_flat_share_value`, `test_solve_for_payment_higher_growth_requires_higher_payment`, `test_solve_for_payment_roundtrips_through_simulate`, `test_solve_for_payment_handles_too_short_target`)
- **Feature**: +1 (`test_simulator_time_mode_returns_per_scenario_payment`)
- **Dusk**: +1 (`test_simulator_time_mode_renders`)

Combined unit + feature run: **14 passed, 41 assertions, 0.58s**.

Dusk `CreditLineSimulatorUI`: **2 passed, 14 assertions, 6.80s**.

## Screenshots

- `app1/family-fund-app/tests/Browser/screenshots/simulator/03_time_mode_with_results.png` — Time-mode summary table with per-scenario "Required monthly payment (USD)" column.

## Notes

- The `<th>` "Required monthly payment (USD)" header is uppercased via CSS in the layout. Dusk's `assertSee` compares against rendered innerText, which on this template returns the uppercased form on some browsers. The Dusk assertion uses `getPageSource()` + `assertStringContainsString` to match the underlying HTML reliably.
- The mode toggle is implemented with two GET-form radio inputs (`mode_payment` / `mode_time`) plus inline JS that shows/hides the relevant `<input>` block. Both inputs remain in the form so the radio click alone switches the visible UI without a server round-trip; the actual computation still happens server-side on submit.
- Existing payment-mode tests and the existing browser test (`test_simulator_renders_form_and_chart`) remain green — backward compatible.
