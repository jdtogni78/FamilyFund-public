# Credit-Line Negative-UI Screenshots — 2026-05-14

17 screenshots captured by `tests/Browser/CreditLineNegativeUITest.php`.
Each test exercises a user-error or authorization path through the real
browser and screenshots both the *before* state (form loaded) and the
*after* state (the error / blocked state) so you can see how the UI
reacts.

This directory is **temporary** — to be removed in a follow-up cleanup
commit.

---

## 01 — Non-admin user tries to create a credit line
**Before — non-admin POSTs to create:**
<img src="01a_non_admin_create_attempt.png" width="380">

**After — 403 forbidden:**
<img src="01b_non_admin_result.png" width="380">

## 02 — Negative principal_shares
**Form loaded:**
<img src="02a_create_form_loaded.png" width="380">

**After submit with negative principal — validation error:**
<img src="02b_negative_principal_result.png" width="380">

## 03 — term_months = 0
**Form loaded:**
<img src="03a_create_form_loaded.png" width="380">

**After submit with zero term — validation error:**
<img src="03b_zero_term_result.png" width="380">

## 04 — Over-borrow (more than account's available-to-borrow)
**Form loaded:**
<img src="04a_create_form_loaded.png" width="380">

**After submit — flash "Cannot borrow":**
<img src="04b_over_borrow_result.png" width="380">

## 05 — Cancel active line that still has outstanding shares
**Line is active with outstanding > 0:**
<img src="05a_line_active.png" width="380">

**After cancel attempt — flash error, line stays active:**
<img src="05b_cancel_attempt_result.png" width="380">

## 06 — Delete account that has an active credit line (UC-47)
**Account show page:**
<img src="06a_account_show.png" width="380">

**After DELETE attempt — closure-block error:**
<img src="06b_after_delete_attempt.png" width="380">

## 07 — Readjust with no actual change
**Line before readjust:**
<img src="07a_line_before_readjust.png" width="380">

**After submit same term/frequency — flash "No changes":**
<img src="07b_readjust_no_change_result.png" width="380">

## 08 — Reverse the same REP transaction twice
**After one repay (REP exists):**
<img src="08a_after_repay.png" width="380">

**After first reverse — REP marked reversed:**
<img src="08b_after_first_reverse.png" width="380">

**After second reverse attempt — flash "already been reversed":**
<img src="08c_after_double_reverse.png" width="380">
