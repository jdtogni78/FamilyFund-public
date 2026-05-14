# Phase 5 — End-to-end tests for the credit-line flow

## Part 1 — Chromium MCP exploration

**Status: BLOCKED — Chrome MCP not authorized for this agent session.**

The `mcp__Claude_in_Chrome__*` tools were loaded via ToolSearch, but the
first call (`list_connected_browsers`) was denied by harness permissions:

> Permission to use mcp__Claude_in_Chrome__list_connected_browsers has been denied.

Per task instructions, the agent did not fall through to `computer-use`
(tier-restricted in this environment).

**Recommended next step (human):** Install / open the Chrome MCP
extension on the host, accept the permission prompt, then re-run a
shorter prompt that just does Part 1 (drive the flow, capture
screenshots, look for UI bugs). The Dusk page objects below already
encode every selector the human session needs to walk.

No UI bugs were found because the live exploration could not be
executed. None are claimed.

## Part 2 — Laravel Dusk codification

### Install outcome

| Step | Result |
| --- | --- |
| `composer require --dev laravel/dusk` | OK (version `^8.6`) |
| `php artisan dusk:install` | OK — scaffolded `tests/Browser/`, downloaded chromedriver `148.0.7778.167` |
| `php artisan dusk` | **Fails to launch chromedriver** — `Couldn't connect to localhost:9515` because **no Chrome / Chromium binary is installed in the `familyfund` container** (Debian 12 bookworm, only PHP + extensions). |

Chromedriver is present at `vendor/laravel/dusk/bin/chromedriver-linux64`
but has nothing to drive. Two ways forward for the user:

1. **Add Chromium to the container** — edit `app1/Dockerfile` (or the
   dev compose override) to install `chromium` + the headless deps:

   ```dockerfile
   RUN apt-get update && apt-get install -y --no-install-recommends \
         chromium chromium-driver fonts-liberation libnss3 libxss1 libasound2 \
       && rm -rf /var/lib/apt/lists/*
   ```

   Then point Dusk at the system chromedriver by overriding
   `DuskTestCase::driver()` or setting `DUSK_DRIVER_URL`. Run with
   `docker exec familyfund php artisan dusk`.

2. **Run Dusk from the host** against `http://localhost:3000` (host's
   Chrome). Set `APP_URL=http://localhost:3000` in `.env.dusk.local`,
   make sure host PHP can reach the database (it currently cannot — see
   `CLAUDE.md` note), or just run the test files through a host PHPUnit
   that uses Selenium against host Chrome.

The test files themselves are committed and ready; they only need the
runtime.

### Files created

- `app1/family-fund-app/tests/Browser/CreditLineBorrowingFlowTest.php`
  — 6 test methods (one per use-case below).
- `app1/family-fund-app/tests/Browser/Pages/AccountCreditLinesPage.php`
  — Page object for `/accounts/{id}/credit-lines`.
- `app1/family-fund-app/tests/Browser/Pages/CreditLineShowPage.php`
  — Page object for `/credit-lines/{id}`, exposes `repay()` and
  `readjust()` helpers.

`tests/Browser/ExampleTest.php` (the Dusk scaffold stub) was removed.

### Test cases

| Test method | Use case |
| --- | --- |
| `test_admin_can_open_credit_line_through_ui` | UC-01 |
| `test_repay_advances_schedule_and_reduces_outstanding` | UC-05 / UC-13 |
| `test_readjust_creates_audit_row_and_new_schedule` | UC-09 |
| `test_reverse_reopens_schedule_row` | UC-45 |
| `test_loans_summary_card_reflects_account_state` | UC-49 |
| `test_trajectory_chart_renders` | UC-42 |

All tests use the `/dev-login/accounts/7/...` route to skip the login
form, drive the real Blade forms, then assert against both the DOM
(`assertSee` / `assertPresent`) and the database
(`AccountCreditLine::find()->payments()` / `outstanding_shares`).

Each test calls `cleanupLine()` at the end which flips the line to
`status = 'cancelled'` so reruns don't accumulate clutter on account 7.

### `php -l` linting

All three new files pass `php -l` with no syntax errors.

### Found bugs

None — no live exploration was performed.

## Phase 6+ deferred

- Re-run Part 1 (Chrome MCP) once the extension is connected; record
  step-by-step screenshots; file any visual bugs.
- Add Chromium to the docker image so `docker exec familyfund php artisan dusk`
  works headless in CI; check chromedriver/Chrome version pinning.
- Inline "Reverse" button on the credit-line show page (currently the
  reverse-tx route is hit via JS in `test_reverse_reopens_schedule_row`
  — a real button would let the Dusk test `->press('Reverse')`).
- Dusk page-object selectors using `data-testid` attributes on the
  Blade views (current selectors lean on form `action` substrings and
  visible text, which is brittle if copy changes).
- Cleanup hardening — the current `cleanupLine()` just sets the status
  column; a richer teardown would call the cancel service so the
  reverse-tx + schedule rows are torn down via the same code path the
  app uses.

---

## Run-from-host outcome (2026-05-14)

Dusk tests **all 6 passing** when run from the host:

```bash
cd app1/family-fund-app
APP_URL=http://localhost:3000 DB_HOST=127.0.0.1 php artisan dusk
```

```
✓ admin can open credit line through ui                                2.08s
✓ repay advances schedule and reduces outstanding                      1.18s
✓ readjust creates audit row and new schedule                          1.36s
✓ reverse reopens schedule row                                         2.16s
✓ loans summary card reflects account state                            0.88s
✓ trajectory chart renders                                             0.94s

Tests:    6 passed (30 assertions)
Duration: 8.68s
```

### Setup steps that worked

1. `php artisan dusk:install` in the container downloaded chromedriver for linux only.
2. Ran `php artisan dusk:chrome-driver --all` from the host — installed the mac-arm64 chromedriver into `vendor/laravel/dusk/bin/`.
3. Created `.env.dusk.local` with `APP_URL=http://localhost:3000` and `DB_HOST=127.0.0.1` so the host PHP process can reach the dockerized DB on the mapped port. Note: Dusk does **not** auto-load `.env.dusk.local` for `config('app.url')`. The two env vars must be passed inline (`APP_URL=… DB_HOST=… php artisan dusk`).
4. Promoted `claude@test.local` (user_id 170) to system-admin role via `model_has_roles` (Spatie pivot row with `fund_id=0`).
5. Tests use account_id 7 (Acct7, 5391 OWN shares).

### Bugs fixed in the agent-written tests during the run

- `openLineViaUi` used `waitForLocation('#', 5)` which times out (no fragment in redirect URL). Replaced with `waitUsing` that polls until the path matches `/credit-lines/\d+`.
- `test_readjust_creates_audit_row_and_new_schedule` expected 12 payments after readjust; correct count is 12 scheduled + 6 cancelled (preserved). Updated assertions to match plan §6 step 3.
- `test_reverse_reopens_schedule_row` raced the post-repay redirect. Added `$browser->on($page)->assertSee(...)` to wait before querying the DB.
- `CreditLineShowPage::assert()` used `assertPathIs` which fires too early after a submit. Replaced with `waitUsing` polling + `waitForText`.

### Not done — Chromium MCP exploration

Chrome MCP `list_connected_browsers` is denied by harness permissions on this machine. Live UI exploration was skipped. The Dusk tests cover the functional paths; for one-shot exploratory testing, the user needs to connect the Chrome extension first.

### Known UI rendering observations from screenshots

A failure screenshot taken during debugging showed the credit-line show page rendering cleanly:
- Loans summary card with the 4 metrics (disbursed, repaid, outstanding, next due).
- Credit-line detail card with principal / outstanding / term / origination / maturity / frequency.
- Payoff trajectory chart placeholder (with planned-vs-projected metrics).
- Admin actions panel (repay, readjust forms + cancel "danger zone").
- Payment schedule table with all rows.
- Adjustment history timeline.

No visual bugs found.
