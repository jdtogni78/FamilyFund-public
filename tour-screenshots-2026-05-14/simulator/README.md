# Payment Simulator (Phase 9)

The simulator is a per-credit-line, read-only "what if I paid $X/month?"
calculator. Three growth-rate scenarios using the same multipliers the
codebase uses elsewhere (`expected × 0.8` conservative, `expected`,
`expected × 1.2` aggressive). 600-month safety cap.

URL: `/credit-lines/{line}/simulator` (admin-only).

## 01 — Empty form
<img src="01_empty_form.png" width="380">

## 02 — Payment mode with results
<img src="02_with_results.png" width="380">

## 03 — Time mode with results (Phase 9b)
<img src="03_time_mode_with_results.png" width="380">

Trustee picks a target payoff time (e.g. 12 months); the simulator
back-solves the *required* monthly USD payment under each growth
scenario. Higher growth makes share price climb faster, so each dollar
buys fewer shares → conservative scenarios need slightly less per
month than aggressive ones.

Note: in the screenshot all three scenarios coincide (payoff month 5)
because the test inputs are short-horizon enough that the rate
differences don't yet move the rounded integer-month payoff. With a
longer horizon you'd see the conservative line ahead and aggressive
behind — the chart is plumbed correctly for that case; see
`tests/Unit/Services/CreditLine/Simulation/PaymentSimulatorTest::test_higher_growth_means_slower_payoff`
which asserts the direction holds at higher growth differentials.
