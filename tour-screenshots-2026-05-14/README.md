# Credit-Line UI Tour Screenshots — 2026-05-14

23 screenshots captured by `tests/Browser/CreditLineUITourTest.php` walking the full credit-line / borrowing flow on the live dev app (`http://localhost:3000`).

Test user: `claude@test.local` (admin). Target account: id 7 (Acct7, ~5391 OWN shares before the tour).

This directory is **temporary** — to be removed in a follow-up commit once reviewed.

> Re-captured after Phase 6 (`1af2c92`). Includes the post-fix trajectory chart (Original plan now steps by 20 as expected), and reflects all UC-20 / UC-37 / UC-46 / UC-47 wiring from Phase 6.

> **See also: [negative/](negative/)** — UI screenshots of all 8 error / authorization paths (non-admin access, validation errors, over-borrow, cancel-blocked, account-closure-blocked, no-change readjust, double-reverse).

---

## 01 — Account page before any credit line
<img src="01_account_page_before.png" width="380">

## 01b — Same, widened to 1920px
<img src="01b_account_page_before_wide.png" width="380">

## 02 — Credit-lines index (empty for Acct7)
<img src="02_credit_lines_index_empty.png" width="380">

## 03 — "New credit line" form, blank
<img src="03_create_form_empty.png" width="380">

## 04 — Form filled (120 shares, 6 months, monthly)
<img src="04_create_form_filled.png" width="380">

## 05 — Show page right after open
<img src="05_show_page_just_opened.png" width="380">

## 06 — Show page scrolled to schedule
<img src="06_show_page_schedule_visible.png" width="380">

## 07 — Show page scrolled to adjustment history (empty until readjust)
<img src="07_show_page_adjustment_history.png" width="380">

## 08 — Account page after the line exists
<img src="08_account_page_after_open.png" width="380">

## 09 — Show page after first repayment (20 shares)
<img src="09_show_after_first_repay.png" width="380">

## 10 — Show page after second repayment (15 shares, partial)
<img src="10_show_after_second_repay.png" width="380">

## 11 — Schedule table after two repays
<img src="11_schedule_after_two_repays.png" width="380">

## 12 — Show page after readjust (6 → 12 months)
<img src="12_show_after_readjust.png" width="380">

## 13 — Schedule after readjust: cancelled + new rows by due_date
<img src="13_schedule_after_readjust.png" width="380">

## 14 — Adjustment timeline entry with old → new diff
<img src="14_adjustment_timeline_with_entry.png" width="380">

## 15 — Schedule snapshot modal (Phase 4 wiring)
<img src="15_schedule_snapshot_modal.png" width="380">

## 16 — Trajectory-at-point modal (Phase 4 wiring)
<img src="16_trajectory_at_point_modal.png" width="380">

## 17 — Show page after reversing the partial REP
<img src="17_show_after_reverse.png" width="380">

## 18 — Trajectory chart close-up
<img src="18_trajectory_chart_closeup.png" width="380">
*Post-fix: "Original plan" now steps by 20 (correct), reaching 120 by month 6.*

## 19 — Fund show page, top
<img src="19_fund_show_top.png" width="380">

## 20 — Fund show page, bottom (admin "Cash position" panel)
<img src="20_fund_show_bottom_admin_panel.png" width="380">

## 21 — Account quarterly PDF (today)
<img src="21_account_quarterly_pdf.png" width="380">

## 22 — Fund quarterly PDF (today)
<img src="22_fund_quarterly_pdf.png" width="380">

---

## Bugs spotted during this tour

See `credit_lines_bugs_found.md` (repo root) for the full catalog with statuses and regression tests.
