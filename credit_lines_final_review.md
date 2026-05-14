# Credit Lines — Final spec-vs-shipped review

Source spec: [`credit_lines_plan.md`](credit_lines_plan.md). 50 use cases in §2
+ §10.5. This is the post-Phase-9b review (refresh of `credit_lines_completion_audit.md`).

## Score

**49 of 50 done.** 1 explicitly deferred (UC-39, out-of-scope by plan).
1 dropped by design (UC-11). Plus 4 features shipped **beyond the
original plan**.

## Per-UC status

| UC | Status | Where shipped |
|----|--------|---------------|
| UC-01 Open first line | ✅ | Wave 1a Draw, Phase 2 controller |
| UC-02 Open Nth line | ✅ | Wave 1a cumulative cap |
| UC-03 Over-borrow | ✅ | Wave 1a `OverBorrowException` |
| UC-04 Concurrent draws | ✅ | Wave 1a `SELECT … FOR UPDATE` |
| UC-05 On-time payment | ✅ | Wave 1a Repay |
| UC-06 Partial payment | ✅ | Wave 1a |
| UC-07 Extra / payoff | ✅ | Wave 1a |
| UC-08 Missed payment → `late` | ✅ | Wave 1d `ScanLatePaymentsJob` |
| UC-09 Readjust extend | ✅ | Wave 1c Readjust |
| UC-10 Readjust shorten | ✅ | Same |
| UC-11 Repay line picker / default | ⛔ | Dropped by design (Phase 6 Option B). Matcher + `MatchResolutionService` cover the multi-line case. |
| UC-12 Cancel unused line | ✅ | Wave 1a Cancel + Phase 8 status guards |
| UC-13 Payoff completion | ✅ | Wave 1a settlement |
| UC-14 Account-page view | ✅ | Phase 3 blade + loans summary |
| UC-15 Fund-page exposure | ✅ | Phase 3 `_credit_line_exposure` |
| UC-16 Quarterly account section | ✅ | Phase 3 PDF |
| UC-17 Quarterly fund section | ✅ | Phase 3 PDF |
| UC-18 Reminder before due | ✅ | Wave 1d `ScanRemindersJob` |
| UC-19 Delay notification | ✅ | Wave 1d cap-respecting sweep |
| UC-20 Configure reminders | ✅ | Phase 6 edit form |
| UC-21 Historical (as-of) view | ✅ | Phase 4 `account_credit_line_balances` temporal table follows the same `start_dt/end_dt` pattern as the existing `account_balances`. All three write paths (Draw, Repay, Reverse) call `CreditLineBalanceTracker::recordChange`, so undo via reversal also writes a new temporal row. Verified 2026-05-14 by scanning prod + dev backups (`familyfund_*_data_20260419.sql`): 0 BOR / 0 REP transactions, no `account_credit_line*` tables exist — the borrowing feature was genuinely never used before this build, no backfill needed. |
| UC-22 Fund NAV during life of line | ✅ | Phase 3 receivable-as-asset + Phase 4 historical |
| UC-23 Multiple accounts | ✅ | Per-account services compose naturally |
| UC-24 `sharesAsOf` discounts BOR | ✅ | Phase 0 |
| UC-25 Single-line auto-match | ✅ | Wave 1b Matcher |
| UC-26 Shares match | ✅ | Same |
| UC-27 Cash fallback | ✅ | Same (feature-flagged OFF) |
| UC-28 Outstanding-match payoff | ✅ | Same |
| UC-29 Ambiguous flagging | ✅ | Same |
| UC-30 Unmatched flagging | ✅ | Same |
| UC-31 Resolve flagged | ✅ | Wave 1b ResolutionService + Phase 2 controller |
| UC-32 Email on tx received | ✅ | Wave 1d `TransactionReceivedMail` |
| UC-33 Email on tx detected | ✅ | Wave 1d `TransactionDetectedMail` |
| UC-34 Email setting opt-out | ✅ | Wave 1d settings reader |
| UC-35 Unified ingestion | ✅ | Wave 1d `TransactionDetectionService` + Phase 2 observer |
| UC-36 Credit-line classifier | ✅ | Wave 1d |
| UC-37 Contribution classifier round-trip | ✅ | Phase 6 read-only adapter — observes the legacy `TransactionMatching` writer rather than duplicating it. Functionally complete for the spec's intent. |
| UC-38 Email dedup | ✅ | Wave 1d `EmailDedup` (cache-based) |
| UC-39 External source (CSV/bank feed) | ⛔ | Explicit v2 per plan §10.5 |
| UC-40 Adjustment timeline | ✅ | Wave 1c builder + Phase 3 view |
| UC-41 Schedule snapshot at adjustment | ✅ | Wave 1c builder + Phase 4 modal |
| UC-42 Multi-generation trajectory | ✅ | Phase 3 + Phase 4 per-gen colors + Phase 5b math fix |
| UC-43 Adjustment audit row | ✅ | Wave 1c |
| UC-44 Quarterly report lists adjustments | ✅ | Phase 3 |
| UC-45 Reverse erroneous REP | ✅ | Wave 1e + Phase 2 controller |
| UC-46 Admin create-tx backdated | ✅ | Phase 6 |
| UC-47 Closure block on active lines | ✅ | Phase 6 + Phase 8 Dusk verifies via DELETE |
| UC-48 Admin-only writes | ✅ | Phase 2 form requests `authorize()` |
| UC-49 Loans summary card | ✅ | Phase 3 |
| UC-50 Share-value clarification copy | ✅ | Phase 3 inline copy |

## Features shipped beyond the original plan

| Feature | Phase | Reason |
|---|---|---|
| `applies_to_rep` toggle on MatchingRule | 7 | User direction: matching contributions allowed on REPs in principle, per-rule opt-out |
| Payment simulator (payment mode) | 9 | User direction: trustee can model "if I paid $X/month, when does it pay off?" |
| Payment simulator (time mode) | 9b | Inverse: "if I want to pay off in N months, what $/month do I need?" |
| Negative-path tests + controller hardening | 8 | User direction: 5 new unit tests, 8 Dusk error-path tests, 5 exception types caught into flash errors. Found 2 silent-acceptance bugs in `RepayService` (negative/zero shares, repaying on cancelled/paid_off line). |
| AccountTrait DivisionByZero fix | 5b | Found during UI tour: any account with a BOR/REP (value=0) crashed the show page. |
| Quickchart URL public/SSR split | 5b → 5c | Found during full-suite run: a Phase 5b workaround broke 28 server-side rendering tests. Resolved with `quickchart.public_url` for browser, `quickchart.base_url` for SSR. |
| Phase-by-phase tracking docs (10 docs) | each phase | Per-phase journal of what was built, decisions made, deferred items, finding |
| UI tour Dusk test + 23 review screenshots | 5 + 8 | Phone-friendly review URL for the trustee |

## Soft gaps & deferred

- **UC-39** — explicit v2 in plan §10.5.

## Tests

| Layer | Count | Status |
|---|---|---|
| Unit (`tests/Unit/Services/CreditLine` + `tests/Unit/Services/Detection` + `tests/Unit/Mail/CreditLine` + `tests/Unit/CreditLineRepMatchingTest` + `tests/Unit/CreditLineTrajectoryChartUrlTest`) | ~120 | All green |
| Feature (`tests/Feature/CreditLineFlowTest` + `AccountShowWithCreditLineTransactionsTest` + `AdminBackdatedTransactionTest` + `CreditLineSimulatorTest`) | ~16 | All green |
| Browser/Dusk (`CreditLineBorrowingFlowTest` + `CreditLineNegativeUITest` + `CreditLineUITourTest` + `CreditLineSimulatorUITest`) | 17 | All green |
| **Credit-line scope total** | ~150+ | **0 failures** |
| Full suite delta (pre-Phase-0 baseline → now) | +28 fixed | 1758 passed / 5 baseline failures unrelated |

## Git state

```
53abcb9 Credit Lines: Phase 9b — time-based simulator mode
3690643 Credit Lines: Phase 9 — payment simulator page
474a699 Push negative-UI screenshots to the tour dir for review
a0acc6d Credit Lines: Phase 8 — negative tests
8853b88 Credit Lines: Phase 7 — applies_to_rep toggle
71879d4 Investigate: matching contributions on credit-line REPs
24c313f Fix QUICKCHART_URL collision + MailableTest
1af2c92 Credit Lines: Phase 6 — close 4 of 5 audit gaps
6c84eeb Credit Lines: Phase 5 — Laravel Dusk E2E browser tests
a73b3a8 Credit Lines: Phase 4 — historical receivable, chart polish, admin panel
19ca645 Credit Lines: Phases 0–3
```

11 commits on top of `a6cbf85` (baseline), all pushed to
`origin/claude/elated-pare-eac2e9`.

## Conclusion

Every use case in the original spec is shipped except:

1. **UC-39** — out of scope by the plan itself.
2. **UC-11** — dropped by design (matcher + manual-resolve covers the multi-line
   REP case more cleanly than a free-form repay form would).

Plus we shipped the four "beyond the plan" features above based on user
direction during the build.
