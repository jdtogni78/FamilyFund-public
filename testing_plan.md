# Testing Plan — Credit Lines & Money Flow Subsystems

**Status:** Draft / sub-project proposal — not yet scoped for implementation
**Last Updated:** 2026-05-13 (rev 2 — balanced coverage across both features; corrected for PHPUnit (not Pest); added §3.5 regression strategy against the existing 164-file suite)
**Branch:** `claude/plan-credit-lines-cCjWG`
**Related docs:** [`credit_lines_plan.md`](credit_lines_plan.md), [`money_flow_plan.md`](money_flow_plan.md), [`test_plan.md`](test_plan.md) (the broader codebase test status).

This document defines the testing strategy for the two new subsystems planned on this branch (credit lines + money flow). It does not duplicate or replace `test_plan.md`, which tracks coverage of the existing app.

---

## 1. Goal

Ensure that every behavior captured in the UC-* (credit lines) and MF-* (money flow) trackers is verified by at least one named, automated test, and that the test suite gives developers fast local feedback while still catching schema drift in external APIs and visual regressions in UI flows.

**Three layers, deliberately balanced:**

1. **Unit tests** — pure logic, no DB, no HTTP, no time. Run in milliseconds. Cover amortization math, matcher priority logic, payoff-trajectory extrapolation, buffer-policy decisions, CPF check-digit validation, idempotency-key generation.
2. **Integration tests** (Laravel "Feature" tests) — full HTTP request → controller → DB → queue → response. Cover the user-visible flows on the back end. The repo already uses this style heavily (`tests/Feature/`, `tests/APIs/`).
3. **Browser tests** (Laravel Dusk) — real browser, real Livewire/Volt interactions, real CSS/JS. Cover the visual surfaces: account-page banner, adjustment-history timeline, trajectory chart, matcher resolve screen, operator outbound flow.

Plus four cross-cutting categories that the per-feature lists call out:

| Category | Purpose |
|---|---|
| **Contract tests** for external APIs (Wise, banking API, IBKR Flex Query) | Catch schema drift in vendor payloads. v1 strategy: hand-written stubs locally + a nightly sandbox job. See §6. |
| **Webhook-replay tests** | Every webhook handler must be idempotent. Tests fire the same event twice and assert single-effect. |
| **Reconciliation tests** | Three-way checks (Wise ↔ checking ↔ IBKR) and one-way (per-line outstanding vs. transactions) must produce zero drift on healthy data and detect every kind of single-source drift. |
| **Migration safety tests** | Migrations that change semantics (e.g., the `AccountExt::sharesAsOf` OWN−BOR fix) get a before/after data fixture so we can prove the change doesn't move numbers we didn't intend to move. |

---

## 2. Testing pyramid

Target proportions, by count, **for the new subsystems** (not the existing app):

```
                    ╱   Browser   ╲          ~5%  (Dusk)
                  ╱─────────────────╲
                ╱   Integration    ╲       ~25% (Feature, APIs)
              ╱─────────────────────╲
            ╱        Unit           ╲     ~70% (Pure logic, fast)
          ╱─────────────────────────╲
```

Note: the **existing** codebase test_plan.md targets 50% line coverage overall. For the new subsystems we target **higher** — ≥80% line coverage on credit-line and money-flow code, because we're writing it from scratch and the math is unforgiving.

**Speed budget.** A full local run (`docker exec familyfund php artisan test`) for the new tests must stay under 60 seconds for the unit tier and under 5 minutes for unit + integration. Browser tests run on demand or in CI, not in every local cycle.

---

## 3. Tools and conventions

The repo runs **PHPUnit 11** (no Pest). Tests use the standard `public function test_*()` convention or the PHP 8 `#[Test]` attribute; both are present in the codebase. New tests follow whichever the surrounding file uses.

| Layer | Tool | Where | Naming |
|---|---|---|---|
| Unit | PHPUnit 11 (existing) | `tests/Unit/CreditLines/`, `tests/Unit/MoneyFlow/` (new subdirectories under the existing `Unit/`) | `*Test.php`, one file per class under test |
| Integration | PHPUnit 11, Laravel feature style (existing `tests/Feature/`) | `tests/Feature/CreditLines/`, `tests/Feature/MoneyFlow/` (new subdirectories) | `test_<what>_<expected_outcome>()`, snake_case, matches repo convention |
| API | Existing `tests/APIs/` style + `ApiTestTrait` | `tests/APIs/CreditLines/`, `tests/APIs/MoneyFlow/` for new API endpoints | Matches `tests/ApiTestTrait.php` patterns |
| Repository | Existing `tests/Repositories/` style | `tests/Repositories/CreditLines/`, `tests/Repositories/MoneyFlow/` | One file per repository |
| Golden data | Existing `tests/GoldenData/` style | Add scenarios to existing `tests/GoldenData/` (don't fork) | |
| Browser | Laravel Dusk (**new dependency** — not yet installed) | `tests/Browser/CreditLines/`, `tests/Browser/MoneyFlow/` (new) | Page-Object pattern; one Dusk test per major user journey |
| Contract / sandbox | PHPUnit + `@group sandbox` | `tests/Sandbox/` (new) | Runs only on `--group=sandbox`; nightly CI only |
| Architecture | PHPUnit-compatible arch lib (e.g. `ta-tikoma/phpunit-architecture-test` or `pestphp/pest-plugin-arch` standalone) — **new dependency** | `tests/Arch/MoneyFlowArchTest.php` | Asserts namespace boundaries (`App\MoneyFlow` internals not importable outside the facade) |

**Test-name shorthand.** Throughout §4 the names are written as descriptive shorthand like `outbound_blocks_when_operating_cash_below_floor`. In code these become `public function test_outbound_blocks_when_operating_cash_below_floor()` (or the `#[Test]` attribute equivalent), matching the existing repo style — see examples in `tests/Unit/AccountExtTest.php` like `test_shares_as_of_returns_shares()`.

**Test class as the UC/MF grouping mechanism.** PHPUnit has no `describe()` block, so the grouping is done at the **test class** level — one class per UC-* / MF-* (or per closely-related cluster). Example layout:

```
tests/Feature/CreditLines/UC29AmbiguousRepTest.php
  ├── public function test_flags_transaction_when_two_lines_have_same_shares_due()
  ├── public function test_shows_mismatch_banner_on_account_page()
  ├── public function test_sends_mismatch_alert_email_separate_from_received_email()
  └── public function test_does_not_advance_any_lines_schedule_until_resolved()
```

Run output (via `--testdox`) reads like a spec keyed by the class name. The traceability table (§11.5) scans for `UC\d{2}` / `MF\d{2}` in class names to map back to the planning docs.

**Command conventions** (matches `CLAUDE.md`):

```bash
# All non-sandbox tests (default for local + CI)
docker exec familyfund php artisan test

# Just the unit tier (fast inner loop)
docker exec familyfund php artisan test --testsuite=Unit

# Just the new subsystems
docker exec familyfund php artisan test tests/Unit/CreditLines tests/Feature/CreditLines

# Excluding the existing-suite known-incomplete groups (mirrors CLAUDE.md)
docker exec familyfund php artisan test --exclude-group=incomplete,needs-data-refactor

# Browser (nightly CI; on-demand local with --group=browser)
docker exec familyfund php artisan dusk

# Sandbox / contract (nightly CI only)
docker exec familyfund php artisan test --group=sandbox

# Coverage
docker exec familyfund ./vendor/bin/phpunit --coverage-text 2>&1 | grep -E "^  (Lines|Methods|Classes):"
```

**Data setup.**
- Existing `tests/DataFactory.php` is the project's standard data builder — extend it, don't fork. Add factory methods for `AccountCreditLine`, `CreditLinePayment`, `CreditLineAdjustment`, `BrazilianRecipient`, `CheckingDeposit`, `WiseTransfer`, `OutboundTransfer` alongside the existing methods.
- Per-model Eloquent factories under `database/factories/` for low-level tests that don't need the full `DataFactory` setup.
- **Database isolation uses `DatabaseTransactions`** (the existing repo standard — every test wraps in a transaction and rolls back), **not** `RefreshDatabase`. This is significantly faster and matches what every other test in the repo does. Dusk is the exception — it needs `DatabaseMigrations` or a dedicated test DB because it runs against the real HTTP server.

---

## 3.5 Existing test suite — what we keep working

The repo already has **164 test files across 5 suites**. The new work must extend this, not duplicate or break it. This section is the regression strategy.

### 3.5.1 Existing suite at a glance

| Suite (`phpunit.xml`) | File count | Style |
|---|---|---|
| `Unit` | 36 | Pure model / trait / service tests, no HTTP |
| `Feature` | 73 | Full HTTP request → controller → DB |
| `APIs` | 25 | API endpoint tests via `ApiTestTrait` |
| `Repositories` | 25 | Repository-pattern CRUD |
| `GoldenData` | 4 | Versioned-dataset integration |
| `Models` | 1 | (small) |
| **Total active** | **164** | |

Default exclusions (already in `phpunit.xml`): `@group incomplete`, plus the convention from `CLAUDE.md` of `--exclude-group=incomplete,needs-data-refactor` in development.

`test_plan.md` at the repo root tracks the broader-codebase test status (288 passing as of 2026-01-10, ~45% line coverage). This document does not duplicate or replace it — **`test_plan.md` is the source of truth for the existing-suite status; this doc owns the new-subsystem additions.**

### 3.5.2 Existing tests that touch code we plan to change

The credit-lines and money-flow work modifies semantics or schema in several places that already have test coverage. Each row is a coordination point: read the existing tests before changing the code, lock them green as the baseline, then extend.

| Code we plan to change | Planned change | Existing tests to preserve / extend |
|---|---|---|
| `AccountExt::sharesAsOf()` / `allSharesAsOf()` | OWN − BOR semantics fix (`credit_lines_plan.md` §9 step 1) | `tests/Unit/AccountExtTest.php` (`test_shares_as_of_*` near line 132+), `tests/GoldenData/FundApiGoldenDataTest.php`, `tests/Feature/FundSetupTest.php` |
| `AccountBalance` (BOR rows used differently) | Aggregated `BOR` row across multiple credit lines | `tests/APIs/AccountBalanceApiTest.php`, `tests/Repositories/AccountBalanceRepositoryTest.php`, `tests/Feature/AccountBalanceControllerTest.php` |
| `Transaction` model | Adding `account_credit_line_id` FK + `credit_line_match_status` enum | `tests/Unit/TransactionExtTest.php`, `tests/APIs/TransactionApiTest.php` |
| `TransactionExt` | New credit-line-related logic | `tests/Unit/TransactionExtTest.php` (`test_type_constants_are_defined()` and similar — confirm `BOR` and `REP` constants still pass) |
| `CashDeposit` / `CashDepositTrait` / `FetchDeposits` job | Extend for the checking-hub webhook flow | `tests/Feature/CashDepositControllerTest.php`, `tests/Feature/CashDepositTraitTest.php` (already has "FROM WISE INC" fixtures — extend, don't rewrite), `tests/Feature/CashDepositControllerExtTest.php` |
| `TransactionMatching` / `MatchingRule` | Extend for credit-line matcher; possibly add `account_credit_line_id` to `transaction_matchings` | `tests/APIs/MatchingRuleApiTest.php`, `tests/APIs/TransactionMatchingApiTest.php`, `tests/Feature/MatchingRuleControllerExtTest.php`, `tests/Feature/AccountMatchingRuleControllerTest.php` |

**Strategy per row:**

1. Before changing the code, run the existing tests in that file: `docker exec familyfund php artisan test tests/Unit/AccountExtTest.php` (etc.). Confirm they pass.
2. Note any tests that **document the current behavior**. After the semantics change (especially `sharesAsOf` OWN−BOR), any test asserting the old behavior must be updated *or* renamed to capture the new behavior, with an inline comment naming the migration that flipped the meaning. Never silently delete an existing assertion.
3. The §9 migration-safety pattern (before/after fixture tests) is mandatory for `sharesAsOf` — the change moves numbers across many reports, and the existing test is our first witness.

### 3.5.3 Existing infrastructure to reuse

| Existing | What to do |
|---|---|
| `tests/TestCase.php` | New tests extend this base; don't introduce a parallel base class. |
| `tests/DataFactory.php` | Extend with credit-line and money-flow builders alongside the existing fund/account/transaction builders. Keep the fluent style. |
| `tests/CreatesApplication.php` | Already bootstraps the Laravel app for tests; no changes expected. |
| `tests/ApiTestTrait.php` | API tests in `tests/APIs/CreditLines/` and `tests/APIs/MoneyFlow/` use this trait for assertion helpers; no parallel API helper. |
| `DatabaseTransactions` trait usage | Match the existing pattern. Don't introduce `RefreshDatabase` in new tests outside of Dusk. |
| `phpunit.xml` | Add the new directories to existing suites; don't add new top-level suites unless we add a truly new tier (e.g., `Sandbox`). Reuse `@group incomplete` exclusion. |
| `@group needs-data-refactor` (seen in `tests/Feature/TransactionExtApiTest.php`) | When extending TransactionExt, check if any of these tests need un-flagging. |

### 3.5.4 Known-disabled / incomplete tests to be aware of

Discovered during audit; not blockers, but worth knowing they exist:

| Test | Reason it's disabled |
|---|---|
| `tests/Feature/HolidaysSyncApiTest.php` | `markTestIncomplete('Requires HTTP server...')` |
| `tests/Feature/TwoFactorAuthTest.php` | `markTestSkipped('Requires Livewire...')` |
| Some `tests/Feature/TransactionControllerExtTest.php` | `markTestSkipped('View has template issues...')` |
| Various `tests/Feature/PortfolioAssetControllerExtTest.php` | Conditional data-availability skips |
| `tests/Feature/TransactionExtApiTest.php` | `@group needs-data-refactor` — excluded by `--exclude-group` in CLAUDE.md's recommended invocation |

None of these are on our critical path. The §3.5.2 table is the actual coordination list.

### 3.5.5 Regression strategy in CI

A new CI step **runs the entire existing suite before the new-subsystem tests on every PR.** If any pre-existing test breaks because of our changes, the build fails with a clear message ("regression in existing test X — see commit Y"). This is the protective net.

```yaml
# pseudocode for the PR workflow
- name: Existing suite (regression net)
  run: docker exec familyfund php artisan test --exclude-group=incomplete,needs-data-refactor

- name: New-subsystem suite (credit lines + money flow)
  run: docker exec familyfund php artisan test tests/Unit/CreditLines tests/Feature/CreditLines tests/Unit/MoneyFlow tests/Feature/MoneyFlow
```

Local-dev habit: before pushing, run the existing suite at least once per session in addition to your focused new-subsystem tests. The full run is under 5 minutes today.

---

## 4. Per-subsystem test plans

Each row maps a UC-* / MF-* to one or more named tests with a layer label. Layers: **U** = Unit, **I** = Integration, **B** = Browser, **C** = Contract, **R** = Reconciliation, **M** = Migration safety.

### 4.0 Balance at a glance — both subsystems covered

This doc covers **both planned features in parallel**. The two trackers — `credit_lines_plan.md` §2 (UC-*) and `money_flow_plan.md` §10 (MF-*) — together are the source of truth for what must be tested. Every ID from those trackers appears in exactly one of the sub-tables below.

| Subsystem | Tracker IDs covered | v1 scope | Deferred | Test rows | Section |
|---|---|---|---|---|---|
| **Credit lines** (borrowing feature) | UC-01..UC-44 | 44 | 0 | 49 | §4.1 |
| **Money flow inbound — Path A (Wise→Checking→IBKR)** | MF-01, 02, 07, 08, 23..28, 34..37, 40, 42 | 18 | 0 | 19 | §4.2 |
| **Money flow inbound — Path B (Fund Wise Business + webhook)** | MF-09, 10, 29..33 | 0 | 7 | 7 (deferred) | §4.2 + §4.6 |
| **Money flow inbound — Path C (BR fintech direct PIX)** | MF-21 | 0 | 1 | 1 (deferred) | §4.6 |
| **Money flow outbound** | MF-03, 11..13, 39, 43..48 | 7 (v1) + 5 (deferred) | 5 | 13 | §4.3 |
| **Recipient registry & attribution** (cross-cutting) | shared with MF-04..08, 27..33 | covered above | — | 6 | §4.4 |
| **Subsystem isolation** | MF-14..17 | 4 | 0 | 4 | §4.5 |
| **Compliance hardening** | MF-19, 20 | 0 | 2 | 2 (deferred) | §4.6 |
| **Reconciliation reports & sweeps** | MF-18, 38, 41 | 1 (MF-38, P1) + 2 (MF-18, MF-41, P2) | 2 | 3 | §4.3 + §4.6 |
| **TOTALS** | 92 IDs | **74 in v1** | **18 deferred** | **104 named tests** | — |

**Reading guide:**

- **§4.1** is the credit-lines tier — the borrowing feature in `credit_lines_plan.md`. Every UC has at least one test.
- **§4.2** through **§4.5** are the money-flow tiers. §4.2 covers the v1 inbound path (Wise → fund US checking → IBKR) at full depth, with the deferred Path B fields kept for visibility. §4.3 covers outbound including the v1 minimum and the v2 Wise-API state machine.
- **§4.6** (new) collects all explicitly deferred test plans for v2+ work so reviewers can confirm they're not forgotten, just not in v1.

### 4.1 Credit lines

Maps UC-01..UC-44 from `credit_lines_plan.md` §2.

| UC | Test name(s) | Layer | Notes |
|---|---|---|---|
| UC-01 | `it_opens_a_credit_line_and_creates_bor_transaction_with_schedule` | I | + unit test for `Amortization::splitEvenly()` |
| UC-02 | `it_opens_nth_line_independently_and_updates_aggregate_bor_balance` | I | Plus `it_computes_total_outstanding_across_lines` (U) |
| UC-03 | `it_rejects_draw_when_amount_exceeds_available` | I | Plus `available_to_borrow_subtracts_outstanding_across_lines` (U) |
| UC-04 | `concurrent_draws_serialize_via_for_update_lock` | I | Uses `DB::transaction` + parallel processes |
| UC-05 | `repay_on_time_advances_schedule_and_reduces_outstanding` | I | |
| UC-06 | `partial_payment_marks_row_partial_and_carries_balance` | U + I | Logic at unit level; full flow at integration |
| UC-07 | `excess_payment_applies_to_next_rows_until_paid_off` | U + I | |
| UC-08 | `daily_late_sweep_flips_overdue_rows_to_late_status` | I | Time-traveled with `Carbon::setTestNow()` |
| UC-09 | `readjust_extend_term_writes_audit_row_and_regenerates_schedule` | I | + U test for `Amortization::regenerate()` |
| UC-10 | `readjust_shorten_term_increases_per_payment_amount` | I | |
| UC-11 | `repay_picker_offers_default_line_for_multi_line_account` | B | Browser — UI shows default selection |
| UC-12 | `cancel_blocks_when_outstanding_nonzero_and_allows_when_zero` | U + I | |
| UC-13 | `final_repayment_sets_status_paid_off` | I | |
| UC-14 | `account_page_shows_aggregate_panel_and_per_line_table` | B | Dusk; assert table renders 3 lines |
| UC-15 | `fund_page_aggregates_bor_across_accounts` | I + B | Data via API; visual via Dusk |
| UC-16 | `quarterly_report_account_section_lists_line_activity` | I | PDF rendering verified by string assertions on HTML pre-snappy |
| UC-17 | `quarterly_report_fund_section_aggregates_disbursements` | I | |
| UC-18 | `reminder_email_sent_n_days_before_due_date` | I | `Mail::fake()` |
| UC-19 | `delay_notification_repeats_per_settings_with_hard_cap` | I | |
| UC-20 | `reminder_settings_per_line_persist_and_affect_scheduler` | I + B | |
| UC-21 | `as_of_query_reconstructs_outstanding_at_past_date` | I + R | Reconciliation: outstanding at T = Σ BOR-REP at T |
| UC-22 | `fund_nav_unchanged_when_line_opened_if_receivable_booked` | I | Validates the §11-risk-1 design |
| UC-23 | `lines_on_different_accounts_of_same_person_are_independent` | I | |
| UC-24 | `shares_as_of_subtracts_bor_aggregate` | U + M | Migration safety: before/after fixture asserts which numbers move |
| UC-25 | `single_line_account_auto_assigns_rep_trivially` | I | |
| UC-26 | `multi_line_rep_matches_unique_shares_due` | I | |
| UC-27 | `multi_line_rep_falls_back_to_cash_match` | I | Feature-flagged off in v1; test still asserts the off-path |
| UC-28 | `rep_matching_full_outstanding_marks_line_paid_off` | I | |
| UC-29 | `ambiguous_rep_flags_transaction_and_sends_alert` | I | `Mail::fake()` |
| UC-30 | `unmatched_rep_flags_transaction_and_sends_alert` | I | |
| UC-31 | `resolve_flagged_transaction_advances_schedule_and_clears_banner` | I + B | Browser confirms banner disappears |
| UC-32 | `received_email_fires_on_user_submitted_rep` | I | |
| UC-33 | `detected_email_fires_on_system_created_rep` | I | |
| UC-34 | `transaction_email_disabled_per_line_suppresses_emails` | I | |
| UC-35 | `every_new_transaction_routes_through_detection_service` | I | Spy on the service to assert invocation |
| UC-36 | `credit_line_classifier_routes_bor_and_rep_only` | U + I | |
| UC-37 | `contribution_classifier_handles_pur_unchanged` | I | Regression — existing matching behavior |
| UC-38 | `email_dedup_log_prevents_duplicate_sends_on_retry` | I | Trigger listener twice |
| UC-39 | `external_csv_import_feeds_same_detection_pipeline` | I | Placeholder — out of scope for v1 |
| UC-40 | `adjustment_history_view_shows_origination_card_when_no_adjustments` | B | |
| UC-41 | `clicking_schedule_snapshot_filters_payments_by_created_at` | B | |
| UC-42 | `trajectory_chart_renders_n_plus_one_plan_series_for_n_adjustments` | B | Assert SVG element count |
| UC-43 | `every_successful_readjust_appends_immutable_audit_row` | U + I | |
| UC-44 | `quarterly_report_includes_adjustments_in_quarter_window` | I | |

### 4.2 Money flow — inbound (v1: Path A only)

Maps MF-01..MF-08, MF-23..MF-28, MF-34..MF-37, MF-40, MF-42 from `money_flow_plan.md` §10.

| MF | Test name(s) | Layer | Notes |
|---|---|---|---|
| MF-01 | `fetch_deposits_job_runs_on_schedule` | I | Asserts the scheduler config |
| MF-02 | `csv_parser_tags_wise_origin_deposits` | U | Pure parsing logic |
| MF-04 | `brazilian_recipient_crud_via_repository` | I | |
| MF-05 | `encrypted_columns_round_trip_via_eloquent_casts` | U + I | Verify the `encrypted` cast on each PII field |
| MF-06 | `cpf_check_digit_accepts_valid_and_rejects_invalid` | U | Property-based: 100 valid, 100 invalid |
| MF-07 | `deposit_attributes_via_registry_lookup_by_name` | I | |
| MF-08 | `attributed_deposit_routes_to_credit_line_matcher` | I | |
| MF-14 | `audit_log_is_append_only_no_update_or_delete` | I + Arch | Architecture: enforce via repo + migration |
| MF-15 | `money_flow_queue_connection_is_separate` | I | Config assertion |
| MF-16 | `app_money_flow_namespace_does_not_import_app_models` | Arch | Pest arch test |
| MF-17 | `operator_role_required_to_initiate_outbound` | I | |
| MF-23 | `beneficiary_creates_deposit_request_with_expected_amount` | I + B | |
| MF-24 | `matcher_pairs_checking_deposit_to_deposit_request_by_amount_and_window` | I | |
| MF-25 | `matcher_flags_when_multiple_deposit_requests_fit` | I | |
| MF-26 | `matcher_flags_orphan_deposit_with_no_request` | I | |
| MF-27 | `fuzzy_match_description_against_wise_profile_name` | U | Levenshtein / token similarity at unit level |
| MF-28 | `document_number_hash_and_pix_key_hash_allow_indexed_lookup` | U + I + M | Migration safety: prove no plaintext PII in indexed columns |
| MF-34 | `banking_api_client_abstraction_has_mercury_and_relay_implementations` | U | Interface conformance |
| MF-35 | `mercury_client_round_trips_quote_and_transfer_on_stub` | U | + Sandbox version (C) nightly |
| MF-36 | `webhook_receiver_rejects_unsigned_or_wrong_signature_requests` | I | |
| MF-36 | `webhook_replay_with_same_signature_creates_one_row` | I | **Webhook idempotency** |
| MF-37 | `checking_deposit_reconciles_with_cash_deposit_when_settled` | I + R | |
| MF-40 | `three_way_reconciliation_passes_on_healthy_data_and_fails_on_drift` | R | Inject drift in each leg; assert detection |
| MF-42 | `kyb_status_check_blocks_outbound_when_account_not_verified` | I | |

### 4.3 Money flow — outbound and sweeps (v1 minimum + deferred state-machine richness)

Maps MF-03, MF-38, MF-39, MF-43..MF-48 from `money_flow_plan.md` §10. Plus deferred rows (P2+) noted at the bottom.

| MF | Test name(s) | Layer | Phase | Notes |
|---|---|---|---|---|
| MF-03 | `manual_outbound_recording_writes_audit_and_marks_completed` | I + B | P0 | |
| MF-38 | `scheduled_sweep_checking_to_ibkr_fires_above_threshold` | I | P1 | Time-traveled; also asserts no sweep when balance below threshold |
| MF-38 | `scheduled_sweep_respects_operating_floor` | I | P1 | Sweep leaves at least floor at checking |
| MF-39 | `outbound_state_machine_progresses_through_each_stage` | I | P2 | Deferred — full state machine; v1 ships happy path only |
| MF-43 | `outbound_debits_beneficiary_shares_and_fund_cash_before_external_chain` | I | P1 | Asserts books-first sequence |
| MF-44 | `outbound_blocks_when_operating_cash_below_floor` | I | P1 | Plus `it_does_not_trigger_any_sell_at_ibkr` (I) |
| MF-45 | `trader_notification_fires_on_buffer_floor_breach` | I | P1 | `Mail::fake()` |
| MF-45 | `trader_notification_fires_on_buffer_ceiling_breach` | I | P1 | |
| MF-46 | `operator_initiated_sweep_ibkr_to_checking_records_ach` | I + B | P1 | Browser: operator's sweep UI |
| MF-46 | `operator_initiated_sweep_checking_to_ibkr_records_ach` | I + B | P1 | The reverse direction |
| MF-47 | `outbound_state_machine_rolls_back_books_on_funding_failure` | I | P2 | Deferred — each stage past `books_debited` has a rollback test |
| MF-48 | `operating_cash_floor_and_ceiling_settings_per_fund_persist` | I + B | P1 | |

**Deferred outbound tests (P2 — listed for visibility):**

| MF | Test name(s) | Layer | Notes |
|---|---|---|---|
| MF-11 | `wise_client_round_trips_quote_and_transfer_with_idempotency` | U + C | Unit stub + nightly sandbox call |
| MF-12 | `two_user_approval_blocks_self_approval_and_requires_distinct_approver` | I + B | |
| MF-13 | `outbound_status_polling_advances_state_machine_on_each_event` | I | |

### 4.4 Recipient registry & attribution (cross-cutting)

| Topic | Test name(s) | Layer | Notes |
|---|---|---|---|
| Path-A attribution priority | `matcher_prefers_unique_deposit_request_over_fuzzy_name` | U + I | Documented priority order |
| Schema invariants | `recipient_inbound_requires_legal_name_and_cpf_minimum` | U + I | |
| Schema invariants | `recipient_outbound_requires_pix_or_bank_account` | U + I | |
| First-time sender | `first_time_unknown_sender_draft_recipient_pending_operator` | I + B | Path B test, kept ready for v2 |
| Fingerprint accumulation | `successful_match_appends_to_recipient_fingerprints` | U + I | |
| Encrypted lookup | `lookup_by_document_number_uses_hash_not_decrypt` | U + I | Asserts no `decrypt()` called during query |

### 4.5 Subsystem isolation

| Topic | Test name(s) | Layer | Notes |
|---|---|---|---|
| Namespace boundary | `arch_no_import_from_outside_money_flow_into_app_money_flow_internals` | Arch | The reverse direction: prove the facade is the only allowed seam |
| Queue isolation | `money_flow_jobs_dispatch_to_money_flow_queue` | I | Default queue assertions |
| Secret isolation | `no_api_key_appears_in_logs_or_queue_payloads` | I | Inject sentinel, scan log files + queue payloads |
| Idempotency | `external_api_call_with_same_idempotency_key_returns_cached_result` | U + I | Two layers: unit at the client level, integration at the service level |

### 4.6 Deferred test plans — kept here so v2+ isn't forgotten

These tests are **not in the v1 build**. They're listed so reviewers can verify the deferred work in the planning docs has corresponding test coverage planned.

**Money flow inbound — Path B (Wise Business webhook) — P2:**

| MF | Test name(s) | Layer | Maps to plan section |
|---|---|---|---|
| MF-09 | `wise_webhook_receiver_verifies_signature_and_creates_wise_transfer` | I | `money_flow_plan.md` §4.2 |
| MF-10 | `wise_transfer_reconciles_with_checking_deposit_via_amount_and_date` | I + R | §4.2 |
| MF-29 | `path_b_matcher_attributes_by_wise_sender_profile_id` | U + I | §4.5.2 |
| MF-30 | `path_b_matcher_fallback_cpf_then_pix_then_reference` | U + I | §4.5.2 |
| MF-31 | `path_b_first_time_sender_creates_draft_recipient_pending_review` | I + B | §4.5.2 |
| MF-32 | `successful_match_appends_to_recipient_fingerprints` | U + I | §6 |
| MF-33 | `recipient_with_match_confidence_review_always_flags_for_human` | I | §6 |

**Money flow inbound — Path C (BR fintech direct PIX) — P4:**

| MF | Test name(s) | Layer | Maps to plan section |
|---|---|---|---|
| MF-21 | `br_fintech_webhook_creates_pix_inbound_with_full_sender_block` | I + C | `money_flow_plan.md` §4.3 |

**Money flow outbound — Path C (BR fintech direct PIX outbound) — P4:**

| MF | Test name(s) | Layer | Maps to plan section |
|---|---|---|---|
| MF-22 | `br_fintech_outbound_pix_to_recipient_via_partner_api` | I + C | `money_flow_plan.md` §5.3 |

**Reconciliation reports — P2:**

| MF | Test name(s) | Layer | Maps to plan section |
|---|---|---|---|
| MF-18 | `daily_reconciliation_report_summarizes_balances_and_emails_operator` | I | `money_flow_plan.md` §4.4 |
| MF-41 | `operating_balance_auto_replenish_proposes_sweep_when_below_floor` | I | `money_flow_plan.md` §5.4 |

**Compliance hardening — P3:**

| MF | Test name(s) | Layer | Maps to plan section |
|---|---|---|---|
| MF-19 | `ofac_screen_blocks_recipient_add_when_match_found` | I + C | §11 risks |
| MF-19 | `ofac_screen_re_runs_periodically_on_active_recipients` | I | |
| MF-20 | `per_recipient_outbound_limits_enforced_daily_monthly` | U + I | |
| MF-20 | `per_period_outbound_limit_blocks_or_requires_extra_approval` | I + B | |

Each of these has a planning-doc anchor so when the work gets prioritized, the test plan is already aligned. None of them count against the v1 coverage target.

---

## 5. UI / browser test strategy (Laravel Dusk)

Dusk is a new dependency for this repo. **Tasks to bootstrap Dusk:**

1. `composer require --dev laravel/dusk` inside the container.
2. `php artisan dusk:install` to scaffold `tests/Browser/`, `DuskTestCase.php`, and the `.env.dusk.local`.
3. Add a `chrome` service to `docker-compose.dev.yml` (Selenium-Chrome image) or use the bundled Chromedriver — the latter is simpler but requires running Chrome inside the existing container.
4. Configure a **separate test database** for Dusk (it cannot use `RefreshDatabase` because it runs against the real HTTP server). Use the `DatabaseMigrations` trait or a per-test seeder.
5. Add CI workflow step for `php artisan dusk` (nightly initially; can promote to per-PR once the suite stabilizes).

**Page-object convention.** One PHP class per page under `tests/Browser/Pages/`:

```php
// tests/Browser/Pages/Accounts/ShowPage.php
class ShowPage extends Page {
    public function url() { return "/accounts/{$this->id}"; }
    public function elements() {
        return [
            '@mismatch-banner' => '.mismatch-alert-banner',
            '@credit-lines-table' => 'table.credit-lines',
            '@new-line-button' => 'button.new-line',
        ];
    }
    public function dismissMismatchByResolving(Browser $browser, int $lineId) {
        $browser->click('@mismatch-banner button.resolve')
                ->waitFor('@resolve-modal')
                ->select('@line-select', $lineId)
                ->press('Confirm');
    }
}
```

**Major user journeys to cover** (one Dusk test per journey, ~20 tests total in v1):

| Journey | Pages | Notes |
|---|---|---|
| Open a credit line on a single-line account | `/dev-login` → account show → new-line modal → submit | Uses the dev-login route from `CLAUDE.md` |
| Open a second line, see aggregate panel update | account show → new-line × 2 | UC-02 + UC-14 |
| Repay a line, watch schedule advance | account show → repay form | UC-05 |
| Hit ambiguous match, resolve via banner | account show with seeded ambiguous REP → click Resolve | UC-29 + UC-31 |
| View adjustment history timeline | account show → line drill-down → history | UC-40 + UC-41 |
| Operator initiates outbound, hits insufficient-cash block | operator login → outbound form | MF-44 |
| Operator sweeps IBKR → checking | operator login → sweep form | MF-46 |
| Beneficiary creates a DepositRequest then sees the deposit attributed | beneficiary → deposit request → seed CheckingDeposit → refresh | MF-23 + MF-24 |

**Visual regression** (optional, P2): consider snapshot-style tests for the trajectory chart SVG once it stabilizes. Out of scope for v1.

---

## 6. External API testing strategy

**Strategy chosen** (per session decision): hand-written stubs locally, real sandbox calls nightly. Best feedback loop for inner-loop dev; periodic drift detection without slowing every test run.

### 6.1 Stubs (local + CI per-PR)

Every external client (`BankingApiClient`, `WiseClient`, `IbkrFlexQueryClient`) has:
- A real implementation (`MercuryClient`, `RelayClient`, `WiseClientV1`, `IbkrFlexQueryClient`).
- A `Fake*` implementation under `tests/Stubs/MoneyFlow/` that conforms to the same interface.
- A factory binding in `tests/CreatesApplication.php` so feature tests get the fake by default.

The fakes maintain in-memory state per test (rows, idempotency keys, signatures) so they can simulate:
- Successful calls.
- Webhook payloads matching the docs.
- Retries (same idempotency key → same response).
- Error responses (rate limit, signature mismatch, network).

### 6.2 Sandbox (nightly)

A separate `tests/Sandbox/` directory holds contract tests gated behind `@group sandbox`. CI workflow:

```yaml
# .github/workflows/sandbox.yml (nightly schedule)
- name: Run sandbox contract tests
  run: docker exec familyfund php artisan test --group=sandbox
  env:
    WISE_SANDBOX_TOKEN: ${{ secrets.WISE_SANDBOX_TOKEN }}
    MERCURY_SANDBOX_TOKEN: ${{ secrets.MERCURY_SANDBOX_TOKEN }}
    IBKR_FLEXQUERY_TOKEN: ${{ secrets.IBKR_FLEXQUERY_TOKEN }}
```

Each sandbox test:
- Makes one round-trip to the real sandbox endpoint.
- Asserts that the response shape still matches the fake's contract (key presence, types, basic ranges).
- Does **not** assert business semantics — only structural drift.

When a sandbox test fails, the next morning's "nightly sandbox failed" email triggers an investigation: update the fake, update the parsing code, file an issue.

### 6.3 IBKR Flex Query — slightly different

IBKR doesn't expose a sandbox in the same way. The contract test instead:
- Loads a fixture CSV captured from a known-good past production run.
- Re-loads a freshly-pulled CSV from the production Flex Query (small, recent window).
- Asserts the column set and value patterns haven't changed.

This serves as both schema-drift detection and a smoke test that the credentials still work.

---

## 7. Webhook-replay testing

Every webhook handler must be idempotent. Generic test pattern:

```php
public function test_webhook_replay_produces_no_duplicate_side_effects(): void
{
    $payload   = WiseWebhookFactory::incomingTransfer()->make();
    $signature = WiseClient::signFake($payload);

    Mail::fake();
    $this->assertSame(0, WiseTransfer::count());

    // First delivery
    $this->postJson('/money-flow/wise/webhook', $payload, ['Signature' => $signature])
         ->assertOk();
    $this->assertSame(1, WiseTransfer::count());
    Mail::assertSentCount(1);

    // Replay
    $this->postJson('/money-flow/wise/webhook', $payload, ['Signature' => $signature])
         ->assertOk();
    $this->assertSame(1, WiseTransfer::count()); // no duplicate row
    Mail::assertSentCount(1);                    // no duplicate email
}
```

Apply this pattern to:
- Wise webhook (when Path B lands in v2).
- Checking-account webhook (Mercury / Relay) — **v1**.
- Any future BR-fintech webhook (v3+).

---

## 8. Reconciliation tests

Reconciliation produces a report; tests assert the report's correctness on **synthesized** data with known drift.

```php
public function test_three_way_reconciliation_detects_drift_at_each_leg(): void
{
    [$wise, $checking, $ibkr] = $this->seedConsistentTrio(/* ... */);

    $this->assertTrue($this->reconcile($wise, $checking, $ibkr)->ok());

    // Drift the Wise leg
    $wise->first()->update(['amount_usd' => 99999]);
    $report = $this->reconcile($wise, $checking, $ibkr);
    $this->assertFalse($report->ok());
    $this->assertSame('wise', $report->failedLeg());

    // ...repeat for checking, then ibkr
}
```

Also: time-window correctness. Reconciliation runs daily; tests assert it correctly excludes in-flight items at the window boundary.

---

## 9. Migration safety

Three migrations from the plans change behavior on existing data:

1. **`AccountExt::sharesAsOf()` OWN−BOR fix** (credit_lines_plan §9 step 1).
2. **`add_account_credit_line_id_to_transactions`**.
3. **`add_credit_line_match_status_to_transactions`**.

For each, a fixture-based test:

```php
public function test_shares_as_of_after_migration_returns_same_value_when_no_bor(): void
{
    // Seed data representing pre-migration state
    $account = Account::factory()->create();
    AccountBalance::factory()->type('OWN')->shares(100)->forAccount($account)->create();

    $before = $account->sharesAsOf(today());           // pre-migration call: 100

    Artisan::call('migrate');                          // apply the migration

    $after = $account->sharesAsOf(today());            // post-migration call
    $this->assertSame($before, $after);                // unchanged when no BOR shares
}

public function test_shares_as_of_after_migration_subtracts_bor_when_present(): void
{
    // Seed: 100 OWN + 30 BOR
    // Pre-migration call: 100 (BOR ignored)
    // Post-migration call: 70 (BOR subtracted)
    // Assert the change happens only where intended
}
```

Plus a **shadow-run plan**: before merging, run the credit-line tests against a copy of production data restored to a dev DB. Compare reports (account values, fund NAV) before vs after. Any deltas must be explainable.

---

## 10. CI workflow

| Workflow | Trigger | What runs | Notes |
|---|---|---|---|
| **Unit + Integration** | Per PR + push to main | `php artisan test` (excludes `@group=sandbox`, `@group=browser`) | Required to merge. Aim < 5 min. |
| **Browser (Dusk)** | Nightly + on-demand (`workflow_dispatch`) | `php artisan dusk` | Slower (10–20 min). |
| **Sandbox contract** | Nightly | `php artisan test --group=sandbox` | Hits real vendor sandbox endpoints. Email-on-failure goes to the maintainer. |
| **Coverage** | Nightly | `phpunit --coverage-text` for the new namespaces | Track ≥80% line coverage for `App\CreditLines\*` and `App\MoneyFlow\*`. |
| **Migration shadow** | Manual before merging migration PRs | Restore prod-data dev DB + run reports before/after | Operator-driven (not automated). |

---

## 11. Reviewable test output — human-readable reports

Tests are only useful if a human can scan the results and trust them. This section codifies how integration and browser tests produce output that you can review at a glance, without scrolling through stack traces.

### 11.1 Test naming as the first line of documentation

The repo convention is `test_<what>_<expected_outcome>()` — snake_case, descriptive, complete sentence after the `test_` prefix. See existing examples in `tests/Unit/AccountExtTest.php` (`test_shares_as_of_returns_shares()`, `test_deposited_value_between_calculates_purchases()`). The test name appears in:

- Local `php artisan test` output.
- CI logs.
- The generated HTML / Markdown report (§11.4).
- The traceability table (§11.5).

**Rule:** read the method name out loud (drop the `test_` prefix). If what remains isn't a complete English sentence describing a behavior, rename it.

✅ `test_outbound_blocks_when_operating_cash_below_floor()`
❌ `testOutboundBlock()`, `test_cash_floor()`, `test_outbound_works()`

For browser tests, the name is the user journey:

✅ `test_operator_opens_credit_line_and_sees_aggregate_panel_update()`
❌ `test_dusk_account()`

### 11.2 One test class per UC-* / MF-* — PHPUnit grouping convention

PHPUnit has no `describe()` block, so the grouping happens at the **class level**: one test class per UC-* / MF- (or per closely-related cluster). The class name encodes the tracker id, and `--testdox` output reads like a spec:

```php
// tests/Feature/CreditLines/UC29AmbiguousRepTest.php
class UC29AmbiguousRepTest extends TestCase
{
    use DatabaseTransactions;

    public function test_flags_transaction_when_two_lines_have_same_shares_due() { ... }
    public function test_shows_mismatch_banner_on_account_page() { ... }
    public function test_sends_mismatch_alert_email_separate_from_received_email() { ... }
    public function test_does_not_advance_any_lines_schedule_until_resolved() { ... }
}
```

Run output via `php artisan test --testdox` reads as:

```
UC29 Ambiguous Rep (Tests\Feature\CreditLines\UC29AmbiguousRep)
  ✓ Flags transaction when two lines have same shares due
  ✓ Shows mismatch banner on account page
  ✓ Sends mismatch alert email separate from received email
  ✓ Does not advance any lines schedule until resolved
```

A reviewer who knows nothing about the code can read the class + test method names and verify the behavior is what they expected. The `UC29` / `MF38` prefix in the class name is what the traceability generator (§11.5) scans for.

### 11.3 Dusk browser tests — screenshots, narration, and video

**Default behavior every Dusk test:**

1. **Screenshot on every step.** Override `Browser::click()`, `::press()`, `::visit()` (via a trait or page-object base class) to capture a screenshot before/after each action, named `tests/Browser/screenshots/{test_name}/{step_n}_{action}.png`. Existing Dusk built-in `screenshot()` is the primitive; the trait makes it automatic.
2. **Step narration.** Each call also writes a line to `tests/Browser/screenshots/{test_name}/steps.md`:
   ```
   1. Visit /dev-login/accounts/8                          → step_1_visit.png
   2. Click "New credit line"                              → step_2_click.png
   3. Fill amount = 100 shares, term = 12 months           → step_3_fill.png
   4. Press "Open line"                                    → step_4_press.png
   5. Assert account page shows aggregate panel with 1 line → step_5_assert.png
   ```
3. **Failure capture.** On failure, Dusk's existing `captureFailures()` already saves a screenshot + DOM snapshot. We also save the **console log** and any browser **JS errors** to `tests/Browser/screenshots/{test_name}/console.log`.
4. **Optional video.** Add `php-webdriver`'s screen-recording plugin (or a simple loop screenshotting at 2 fps via a sidecar). Off by default; flip on for hard-to-debug failures via `DUSK_RECORD_VIDEO=1`.

Result: every browser test is reviewable as a numbered photo strip with English narration, even by someone without a development environment. The directory is committed to the test artifacts in CI (GitHub Actions upload step), so reviewers can download the zip and scrub through.

### 11.4 HTML test report from CI

In CI (and optionally locally), `php artisan test` writes a JUnit XML by default. We pipe it into a human-readable HTML report:

**Tool choice (v1):** [**`junit2html`**](https://github.com/inorton/junit2html) — small Python tool, zero config, generates a single self-contained HTML file from JUnit XML. CI step:

```yaml
- run: docker exec familyfund php artisan test --log-junit=storage/test-output/junit.xml
- run: junit2html storage/test-output/junit.xml storage/test-output/report.html
- uses: actions/upload-artifact@v4
  with:
    name: test-report
    path: storage/test-output/report.html
```

The reviewer clicks the artifact in the GitHub Actions run, opens the HTML, sees:

| Suite | Test | Time | Status |
|---|---|---|---|
| `CreditLines\UC29AmbiguousRepTest` | flags transaction when two lines have same shares_due | 0.18s | ✓ |
| `CreditLines\UC29AmbiguousRepTest` | shows mismatch banner on account page | 0.22s | ✓ |
| `CreditLines\UC29AmbiguousRepTest` | sends mismatch-alert email separate from received-email | 0.15s | ✓ |
| ... | | | |

With failures expandable to show the stack trace and the (linked) screenshot.

**Stretch (v2):** Allure or a similar BDD-style reporter with timeline view, retry tracking, and severity tags. Not needed for v1.

### 11.5 UC / MF traceability table

A generated Markdown file at `tests/Traceability.md` shows every UC-* and MF-* and its test status. Generated by a small command that parses test names for `UC-NN` / `MF-NN` tokens and cross-references the trackers.

```
docker exec familyfund php artisan tests:traceability
```

Output (committed alongside test runs):

```markdown
| ID    | Description                          | Tests                                                                 | Last run | Status |
|-------|--------------------------------------|-----------------------------------------------------------------------|----------|--------|
| UC-01 | Open first credit line               | 2 tests (CreditLines\UC01OpenLineTest)                                | 2026-05-14 | ✓ both |
| UC-02 | Open Nth additional line on account  | 1 test  (CreditLines\UC02MultiLineTest)                               | 2026-05-14 | ✓      |
| UC-03 | Attempt to over-borrow               | 1 test  (CreditLines\UC03OverBorrowTest)                              | 2026-05-14 | ✓      |
| UC-04 | Concurrent draws on same account     | — no tests yet —                                                       | —        | ✗ gap  |
| ...   |                                      |                                                                       |          |        |
```

Reviewers (and you) can read this single file to see exactly which planned behaviors have automated proof and which don't. Failing the build when any UC-* / MF-* has zero tests is a future hardening step.

### 11.6 Local-dev reviewability — quick-look outputs

When running locally:

- `php artisan test --testdox` (built-in PHPUnit option) prints behavior-style output:
  ```
  Credit Lines · UC-29 · Ambiguous REP
   ✓ flags the transaction when two lines have the same shares_due
   ✓ shows the mismatch banner on the account page
   ...
  ```
- `php artisan dusk --browse` runs Dusk with a visible browser (not headless) so the developer can watch in real time.
- After a Dusk run, `open tests/Browser/screenshots/{test_name}/` opens the photo strip in the OS file browser.

### 11.7 Definition of "done" for a test

A test is done when:

1. **Name** is a complete English sentence.
2. **It's grouped** in a test class whose name embeds the tracker id, e.g. `UC29AmbiguousRepTest` or `MF38SweepJobTest` (§11.2).
3. **For browser tests:** the screenshot strip exists and steps.md narrates the journey.
4. **It runs** in under 3 s for unit, 10 s for integration, 30 s for browser (else flag for refactoring).
5. **It appears** in the traceability table tied to a UC-* / MF-*.
6. **CI output** for the test reads cleanly in the HTML report — no surprise stack traces on green.

---

## 12. Coverage targets and metrics

For **new code only** (the credit-line and money-flow namespaces):

| Metric | Target | Source |
|---|---|---|
| Line coverage | ≥ 80% | `phpunit --coverage-text` filtered to namespaces |
| Method coverage | ≥ 80% | same |
| Class coverage | 100% (every new class has at least one test) | same |
| UC-* / MF-* coverage | 100% (every tracker row has at least one named test) | this doc's tables |
| Mutation coverage *(stretch)* | ≥ 60% on the matcher service | Infection PHP, run weekly |

The existing codebase coverage tracking in `test_plan.md` remains its own metric — this doc doesn't try to raise it.

---

## 13. What to write first (test-driven sequencing)

When the implementation starts, the first PR for each subsystem should land its **scaffolding tests** before the implementation:

**Credit lines first PR:**
1. `AccountCreditLineFactory` + repository test (UC-01 minimal).
2. Unit test for `Amortization::splitEvenly()` (UC-01, UC-05).
3. Feature test for the open-line flow (UC-01).

**Money flow first PR (after KYB clears):**
1. `BankingApiClient` interface + Fake stub.
2. Webhook receiver signature-verification test (MF-36).
3. `CheckingDeposit` factory + integration test for webhook → row (MF-37).
4. Arch test for the namespace boundary (MF-16).

After those scaffolds land, every subsequent PR is expected to add tests for the UC-* / MF-* it implements, with the named test from §4 as the acceptance gate.

---

## 14. Open questions

1. **Dusk in CI** — do we have a CI host that can run headless Chrome? The current `docker-compose.dev.yml` does not include one. Likely need to add a `selenium-chrome` service for CI.
2. **Coverage tool** — `phpunit --coverage-text` requires Xdebug or PCOV. Confirm one is enabled in the test container (CLAUDE.md mentions `php artisan test` but not the coverage path explicitly).
3. **Sandbox credentials in CI** — secret management for Wise / Mercury / Relay sandbox tokens. GitHub Actions secrets is the obvious answer; confirm the deployment workflow already has secret support.
4. **Visual regression** — out of scope for v1. Revisit once the UI stabilizes.
5. **Mutation testing** — listed as stretch. Worth piloting on the matcher service only.
