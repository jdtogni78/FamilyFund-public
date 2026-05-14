# Credit Lines — Phase 1c: Readjustment

**Status:** complete
**Date:** 2026-05-13
**Branch:** `claude/elated-pare-eac2e9`

---

## Scope

Wave 1c implements the credit-line readjustment subsystem.

---

## Use Cases Covered

| ID    | Description                                         | Status |
|-------|-----------------------------------------------------|--------|
| UC-09 | Readjust term (extend)                              | done   |
| UC-10 | Readjust term (shorten)                             | done   |
| UC-40 | View adjustment timeline (data builder)             | done   |
| UC-41 | View schedule snapshot at an adjustment             | done   |
| UC-43 | Adjustment audit row written on every readjust      | done   |

---

## File List (all new — no existing files modified)

| File | Description |
|------|-------------|
| `app/Services/CreditLine/Exceptions/NoChangeException.php` | Thrown when neither term nor frequency changed — prevents empty audit rows. |
| `app/Services/CreditLine/Adjust/ReadjustService.php` | Main orchestrator; wraps all 7 steps in `DB::transaction()`. |
| `app/Services/CreditLine/Adjust/AdjustmentHistoryBuilder.php` | Returns timeline entries (origination + adjustments) newest-first for UC-40. |
| `app/Services/CreditLine/Adjust/ScheduleSnapshotBuilder.php` | Returns `CreditLinePayment` rows `created_at <= asOf` for UC-41. |
| `tests/Unit/Services/CreditLine/Adjust/ReadjustServiceTest.php` | 11 test methods covering all UCs above. |

---

## API Surface

### `ReadjustService::readjust()`

```php
public function readjust(
    AccountCreditLine $line,
    ?int $newTermMonths,      // null = keep current
    ?string $newFrequency,    // null = keep current
    ?UserExt $adminUser,      // null = system-initiated
    ?string $reason = null
): CreditLineAdjustment
```

Throws `NoChangeException` if neither value changes. Otherwise runs atomically:
1. Guard no-op.
2. Capture old values + compute `oldPlannedPayoffDate` (max `due_date` of `scheduled` rows, or `maturity_date`).
3. Recompute `outstanding_shares` via `OutstandingCalculator::recomputeForLine()`.
4. Apply new `term_months`, `payment_frequency`, `maturity_date` (= today + new term) to line.
5. `UPDATE … SET status='cancelled'` on all `scheduled` payment rows — rows are preserved, not deleted.
6. Build fresh schedule via `AmortizationScheduleBuilder::build()` anchored at `Carbon::today()`.
7. Insert `CreditLineAdjustment` row with full snapshot. Return it.

### `AdjustmentHistoryBuilder::build(AccountCreditLine $line): array`

Returns `array<['date' => Carbon, 'kind' => 'origination'|'adjustment', 'data' => array]>` newest-first. Last entry is always the synthetic origination card.

### `ScheduleSnapshotBuilder::snapshotAt(AccountCreditLine $line, Carbon $asOf): Collection`

Returns `CreditLinePayment` rows where `created_at <= $asOf`, ordered by `due_date` ascending.

---

## Phase 2 Dependencies

| Dependency | Description |
|------------|-------------|
| **Dedup with wave 1a** | `ReadjustService` depends on `AmortizationScheduleBuilder` and `OutstandingCalculator` (both already in `app/Services/CreditLine/Support/`). No duplication — 1c reuses 1a's classes directly. Phase 2 can remove the spec note about intentional duplication. |
| **Authorization** | `ReadjustService` is callable from any context. The admin-only gate (§5 rule 12) must be enforced in the Phase 2 controller (`ReadjustCreditLineRequest` + policy). |
| **View rendering** | `AdjustmentHistoryBuilder` and `ScheduleSnapshotBuilder` produce data structures; actual Blade rendering is Phase 5 (§8.5 timeline view). |
| **Trajectory chart overlay** | UC-42 multi-generation chart requires a Phase 5 chart service consuming `CreditLineAdjustment` rows; the data is already written by UC-43. |
| **Quarterly report** | UC-44 scoped adjustment list (`adjusted_at` IN quarter) is Phase 5 reporting; the rows exist after Phase 1c. |

---

## Test List

| Test method | What it verifies |
|-------------|-----------------|
| `test_extend_term_generates_new_schedule_and_cancels_old` | UC-09: 12→24 months; 12 cancelled, 24 scheduled, sum=100, audit row correct. |
| `test_shorten_term_generates_new_schedule_and_cancels_old` | UC-10: 24→12 months; correct row counts and sum. |
| `test_readjust_after_partial_repayment_sums_to_outstanding` | Outstanding=70 after 30 repaid; new schedule sums to 70. |
| `test_no_change_throws_exception_and_writes_nothing` | Same term+freq → `NoChangeException`, no audit row. |
| `test_system_readjust_has_null_adjusted_by` | `adminUser=null` → `adjusted_by_user_id` is NULL. |
| `test_frequency_only_change_creates_adjustment` | Monthly→Quarterly with same term; 4 payments sum=120. |
| `test_old_scheduled_rows_are_preserved_as_cancelled` | Row count = old+new (no deletion). |
| `test_maturity_date_updated_on_line_after_readjust` | `maturity_date` = today + new term months. |
| `test_history_builder_returns_origination_only_when_no_adjustments` | 0 adjustments → 1 entry (origination). |
| `test_history_builder_returns_n_plus_one_entries_newest_first` | 2 adjustments → 3 entries, newest-first order. |
| `test_history_builder_diff_contains_changed_fields` | `diff` array lists `term_months` and `payment_frequency`. |
| `test_snapshot_builder_filters_by_created_at` | Before readjust: 12 rows; after: 36 rows. |
| `test_snapshot_builder_orders_by_due_date` | Rows ascending by `due_date`. |
