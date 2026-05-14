# Credit Lines — Completion Audit (vs. master plan §2 + §10.5)

Plan defines **50 use cases** across the borrowing feature. This audit
cross-references each against what shipped in commits `19ca645` (Phases 0-3),
`a73b3a8` (Phase 4), `6c84eeb` (Phase 5 Dusk), `9fbe11b` (bug fixes), and
`ac67cd4` (screenshots).

## Score

**43 of 50 done.** 5 real gaps, 1 explicitly deferred, 1 out-of-scope.

## Per-UC status

| UC    | Title                                            | Status | Where shipped                                       |
|-------|--------------------------------------------------|--------|-----------------------------------------------------|
| UC-01 | Open first credit line                           | ✅     | Wave 1a `DrawService` + Phase 2 controller/route    |
| UC-02 | Open Nth additional line on same account         | ✅     | Wave 1a draw-cap accumulates across active lines    |
| UC-03 | Reject over-borrow                               | ✅     | Wave 1a `OverBorrowException`                       |
| UC-04 | Concurrent-draw safety                           | ✅     | Wave 1a `SELECT … FOR UPDATE` on account row        |
| UC-05 | On-time payment                                  | ✅     | Wave 1a `RepayService`                              |
| UC-06 | Partial payment                                  | ✅     | Wave 1a partial-state handling                      |
| UC-07 | Extra / payoff payment                           | ✅     | Wave 1a cascade logic                               |
| UC-08 | Missed payment → `late` status                   | ✅     | Wave 1d `ScanLatePaymentsJob`                       |
| UC-09 | Readjust term (extend)                           | ✅     | Wave 1c `ReadjustService`                           |
| UC-10 | Readjust term (shorten)                          | ✅     | Same path                                           |
| UC-11 | Repay line picker / default on multi-line acct   | ⚠️ Partial | Matcher handles it (1b); **no dedicated form**  |
| UC-12 | Cancel an unused line                            | ✅     | Wave 1a `CancelService`                             |
| UC-13 | Payoff completion                                | ✅     | Wave 1a settlement logic                            |
| UC-14 | Account-page aggregate + per-line view           | ✅     | Phase 3 blade + loans summary card                  |
| UC-15 | Fund-page exposure view                          | ✅     | Phase 3 `_credit_line_exposure.blade.php`           |
| UC-16 | Quarterly report — account section               | ✅     | Phase 3 PDF template extension                      |
| UC-17 | Quarterly report — fund section                  | ✅     | Phase 3 PDF template extension                      |
| UC-18 | Reminder before due date                         | ✅     | Wave 1d `ScanRemindersJob` + `ReminderMail`         |
| UC-19 | Delay notification after missed payment          | ✅     | Wave 1d `ScanLatePaymentsJob` + cap                 |
| UC-20 | Configure reminders                              | ❌ **MISSING** | Settings columns exist (Phase 2 migration); **no edit-form UI** to set them per-line |
| UC-21 | Historical (as-of) view                          | ⚠️ Partial | Phase 4 `account_credit_line_balances` covers fund-level NAV; per-account historical reconstruction works only forward of Phase 4 (pre-existing lines have single origination row) |
| UC-22 | Fund NAV during life of a line                   | ✅     | Phase 3 receivable-as-asset + Phase 4 historical    |
| UC-23 | Person with multiple accounts                    | ✅     | Per-account services compose naturally              |
| UC-24 | `AccountExt::sharesAsOf()` discounts BOR          | ✅     | Phase 0                                             |
| UC-25 | Single-line auto-match                           | ✅     | Wave 1b `CreditLineMatcher`                         |
| UC-26 | Shares match                                     | ✅     | Same                                                |
| UC-27 | Cash fallback (feature-flagged OFF)              | ✅     | Same                                                |
| UC-28 | Outstanding-match payoff                         | ✅     | Same                                                |
| UC-29 | Ambiguous flagging                               | ✅     | Same                                                |
| UC-30 | Unmatched flagging                               | ✅     | Same                                                |
| UC-31 | Resolve flagged transaction                      | ✅     | Wave 1b `MatchResolutionService` + Phase 2 controller |
| UC-32 | Email on transaction received                    | ✅     | Wave 1d `TransactionReceivedMail`                   |
| UC-33 | Email on transaction detected                    | ✅     | Wave 1d `TransactionDetectedMail`                   |
| UC-34 | Email setting opt-out (data layer)               | ✅     | Wave 1d `LineNotificationSettings` reads DB columns |
| UC-35 | Unified ingestion (`TransactionDetectionService`)| ✅     | Wave 1d + Phase 2 observer wiring                   |
| UC-36 | Credit-line classifier routes BOR/REP            | ✅     | Wave 1d `CreditLineClassifier`                      |
| UC-37 | Contribution classifier still handles PUR        | ⚠️ Stub | Phase 1d wrote a placeholder; existing `TransactionMatching` flow runs alongside but isn't wired *through* the new pipeline |
| UC-38 | Email dedup across retries                       | ✅     | Wave 1d `EmailDedup` (cache-based)                  |
| UC-39 | Future external source (CSV / bank feed)         | ⛔ Out of scope | Plan explicitly says v2                       |
| UC-40 | View adjustment timeline                         | ✅     | Wave 1c `AdjustmentHistoryBuilder` + Phase 3 blade  |
| UC-41 | View schedule snapshot at an adjustment          | ✅     | Wave 1c builder + Phase 4 modal wiring              |
| UC-42 | Multi-generation trajectory chart                | ✅     | Phase 3 + Phase 4 per-gen colors + Phase 5b math fix |
| UC-43 | Adjustment audit row written                     | ✅     | Wave 1c                                             |
| UC-44 | Quarterly report lists adjustments               | ✅     | Phase 3 PDF section                                 |
| UC-45 | Admin reverses an erroneous REP                  | ✅     | Wave 1e `ReverseService` + Phase 2 controller       |
| UC-46 | Admin creates transaction from scratch (backdated) | ❌ **MISSING** | No admin "create transaction with arbitrary timestamp" UI built |
| UC-47 | Account closure blocked when lines are active    | ❌ **MISSING** | No closure-guard check in `AccountController::destroy` (or wherever closure lives) |
| UC-48 | Admin-only writes (auth gate)                    | ✅     | Phase 2 form requests' `authorize()` checks `is_admin()` |
| UC-49 | Loans summary card                               | ✅     | Phase 3 `_loans_summary_card.blade.php`             |
| UC-50 | Share-value clarification copy                   | ✅     | Phase 3 inline copy near every shares balance       |

## Remaining work

### Gaps to close (5 items)

1. **UC-11** — Add a "Repay" form that doesn't require a line ID up front. Either:
   - A top-level form at `/accounts/{id}/credit-lines/repay` that lists active lines as radio options with the next-due row pre-selected.
   - Or: just document that the matcher handles ambiguous REPs and rely on `MatchResolutionService` for human disambiguation. Easier; less to build.
2. **UC-20** — Surface the 7 notification-settings columns on the line's edit form (`account_credit_lines/edit.blade.php`). Currently the schema is in place but the only way to change them is via `tinker`. ~30 min of Blade work.
3. **UC-37** — Wire the existing `TransactionMatching`-based contribution flow through `TransactionDetectionService::ingest()` so all transaction types go through one pipeline. Today the old flow still runs (no regression) but the new ingestor stubs the contribution branch.
4. **UC-46** — Admin "create transaction with arbitrary timestamp" UI. Useful for backfilling test data or correcting historical record. Could be a thin extension of the existing transaction CRUD that doesn't reject `timestamp != now()`.
5. **UC-47** — Closure-guard in account delete/close path: if `account.creditLines()->where('status', 'active')->exists()` → return error "Cannot close: N active credit lines remain."

### Soft gaps

- **UC-21 (partial)** — Historical receivable reconstruction only works correctly for lines created after Phase 4 ships. Pre-Phase-4 lines have a single seeded `account_credit_line_balances` row at origination_date; querying their state at intermediate dates returns the *final* outstanding. Two ways to fix:
  - Replay BOR/REP transaction history into the balance table for each pre-existing line.
  - Accept the limitation and document it (current state).

### Test scope gaps

- No browser test for UC-47 (closure block).
- No unit test for UC-37 round-trip through the unified pipeline.
- The screenshot tour exercises only one happy path; ambiguous/unmatched UI (banners, resolve screen) hasn't been visually verified.

## Suggested ordering for a Phase 6

If you want to close the gaps, sequence:

1. UC-20 (settings UI) — easiest, blade-only.
2. UC-47 (closure guard) — single controller method + test.
3. UC-46 (admin create-tx with timestamp) — new form + route.
4. UC-37 (contribution classifier round-trip) — wiring + regression test.
5. UC-11 (default line picker) — UI decision needed; either drop the requirement or build the form.

Estimated: 1-2 focused sessions.
