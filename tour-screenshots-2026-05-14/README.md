# Credit-Line UI Tour Screenshots — 2026-05-14

23 screenshots captured by `tests/Browser/CreditLineUITourTest.php` walking the full credit-line / borrowing flow on the live dev app (`http://localhost:3000`).

Test user: `claude@test.local` (admin). Target account: id 7 (Acct7, ~5391 OWN shares before the tour).

This directory is **temporary** — to be removed in a follow-up commit once reviewed.

> Note: these screenshots were captured *before* the trajectory-math fix in commit `9fbe11b`. The "Original plan" line on the trajectory chart shows 27.0833 increments instead of 20 — that bug is now fixed. A re-run will show the corrected chart. (See `credit_lines_bugs_found.md` #2.)

---

## 01 — Account page before any credit line
![](01_account_page_before.png)

## 01b — Same, widened to 1920px
![](01b_account_page_before_wide.png)

## 02 — Credit-lines index (empty for Acct7)
![](02_credit_lines_index_empty.png)

## 03 — "New credit line" form, blank
![](03_create_form_empty.png)

## 04 — Form filled (120 shares, 6 months, monthly)
![](04_create_form_filled.png)

## 05 — Show page right after open
![](05_show_page_just_opened.png)

## 06 — Show page scrolled to schedule
![](06_show_page_schedule_visible.png)

## 07 — Show page scrolled to adjustment history (empty until readjust)
![](07_show_page_adjustment_history.png)

## 08 — Account page after the line exists
![](08_account_page_after_open.png)

## 09 — Show page after first repayment (20 shares)
![](09_show_after_first_repay.png)

## 10 — Show page after second repayment (15 shares, partial)
![](10_show_after_second_repay.png)

## 11 — Schedule table after two repays
![](11_schedule_after_two_repays.png)

## 12 — Show page after readjust (6 → 12 months)
![](12_show_after_readjust.png)

## 13 — Schedule after readjust: cancelled + new rows by due_date
![](13_schedule_after_readjust.png)

## 14 — Adjustment timeline entry with old → new diff
![](14_adjustment_timeline_with_entry.png)

## 15 — Schedule snapshot modal (Phase 4 wiring)
![](15_schedule_snapshot_modal.png)

## 16 — Trajectory-at-point modal (Phase 4 wiring)
![](16_trajectory_at_point_modal.png)

## 17 — Show page after reversing the partial REP
![](17_show_after_reverse.png)

## 18 — Trajectory chart close-up
![](18_trajectory_chart_closeup.png)
*Pre-fix: notice "Original plan" steps by 27 (bug #2). After commit `9fbe11b` it should step by 20.*

## 19 — Fund show page, top
![](19_fund_show_top.png)

## 20 — Fund show page, bottom (admin "Cash position" panel)
![](20_fund_show_bottom_admin_panel.png)

## 21 — Account quarterly PDF (today)
![](21_account_quarterly_pdf.png)

## 22 — Fund quarterly PDF (today)
![](22_fund_quarterly_pdf.png)

---

## Bugs spotted during this tour

See `credit_lines_bugs_found.md` (repo root) for the full catalog with statuses and regression tests.
