# Findings from the credit-line scenarios sweep (2026-05-20)

Three issues surfaced while writing tests/Feature/Scenarios/* and tests/Browser/Scenarios/*. Each section below is self-contained and ends with a **fresh-thread prompt** you can paste into a new Claude Code session to address it in isolation.

Pool slot for verification (if still leased): http://localhost:3024 — release with `~/.familyfund-pool/pool.sh release pool4`.

---

## Bug 1 — TransactionObserver never fires in production (HIGH impact) — ✅ FIXED 2026-05-20

Resolved on the `bridge-cse` worktree. `AppServiceProvider::boot()` now registers
`TransactionObserver` on `TransactionExt` (was on the base `Transaction`), so the
detection pipeline fires on every prod save. The S2 test workaround was removed,
and a latent secondary bug in `MatchResult::noOp` (which had been clobbering
MANUAL → AUTO_MATCHED whenever the observer re-traversed a manually-resolved REP)
was fixed by adding an `isNoOp` flag and skipping persistence in
`CreditLineClassifier::mapAndPersist`. Verified via tinker + 214 credit-line
tests pass (32 scenarios + 182 others).

### Original report (for context)

### Symptom

The credit-line detection pipeline (`CreditLineClassifier` → `CreditLineMatcher`) is registered to run on every `Transaction` save, but **never actually runs** when a transaction is saved via the normal production paths.

### Root cause

`app1/family-fund-app/app/Providers/AppServiceProvider.php:75`:

```php
Transaction::observe(TransactionObserver::class);
```

Laravel dispatches eloquent events keyed on the **concrete class name**. Every production code path saves via `TransactionExt` (e.g., `TransactionExt::create([...])` in `DrawService.php:98`, `RepayService`, etc.), which emits `eloquent.saved: App\Models\TransactionExt` — but the observer is subscribed to `eloquent.saved: App\Models\Transaction`. The two are different event keys; the observer is dead for every real save.

### Evidence

Verified via tinker on pool4 (2026-05-20):

```bash
docker exec familyfund-pool4 php artisan tinker --execute='
use App\Models\TransactionExt;
$d = TransactionExt::getEventDispatcher();
echo "Transaction saved listeners: " . count($d->getListeners("eloquent.saved: App\Models\Transaction")) . "\n";
echo "TransactionExt saved listeners: " . count($d->getListeners("eloquent.saved: App\Models\TransactionExt")) . "\n";
'
# Output:
#   Transaction saved listeners: 1
#   TransactionExt saved listeners: 0
```

And the supporting symptom — zero REPs in the dev DB carry a non-null `credit_line_match_status`, because every REP that flows through `RepayService` has its FK pre-set (which short-circuits the matcher's "FK already set" guard anyway, so the bug has been **invisible** in practice until externally-sourced REPs are introduced):

```bash
docker exec familyfund-pool4 php artisan tinker --execute='
use App\Models\TransactionExt;
echo TransactionExt::where("type","REP")->whereNotNull("credit_line_match_status")->count() . "\n";
'
# Output: 0
```

### Test scaffolding that documents this

`tests/Feature/Scenarios/S2RepRoutingMatrixTest.php` (lines 32-44 docblock) describes the wiring gap and uses `app(CreditLineClassifier::class)->classify($rep)` directly to exercise the matcher. Once the observer is fixed, the explicit `classify()` call in `S2RepRoutingMatrixTest::makeUnassignedRep` can be removed.

### Fix options

- **Preferred:** change line 75 to `TransactionExt::observe(TransactionObserver::class)` — the observer's `saved(Transaction $tran)` signature still accepts a `TransactionExt` (covariant), and the existing reentrancy guard already handles its `saveQuietly()` chain.
- **Alternative:** register the listener on both classes (`Transaction::observe(...)` AND `TransactionExt::observe(...)`) for defense-in-depth.
- After fix: drop the explicit classifier call from S2 and verify all 32 scenario tests still pass.

### Memory

`project_transaction_observer_wiring_gap` already exists with this story — update it once fixed.

### Fresh-thread prompt

```
Fix the TransactionObserver wiring bug in app1/family-fund-app/app/Providers/AppServiceProvider.php:75.

Symptom: `Transaction::observe(TransactionObserver::class)` is registered, but every production code path saves via `TransactionExt` (e.g. `DrawService.php:98` uses `TransactionExt::create([...])`). Laravel emits eloquent events keyed on the concrete class, so the observer's `saved` handler never fires in practice. The detection pipeline (TransactionDetectionService → CreditLineClassifier → CreditLineMatcher) is dead for every real save. Confirmed via tinker (2026-05-20): `TransactionExt::getEventDispatcher()->getListeners('eloquent.saved: App\Models\TransactionExt')` returns 0; zero REPs in dev DB carry a non-null `credit_line_match_status`.

Task:
1. Change line 75 to `TransactionExt::observe(TransactionObserver::class)` (or register on both Transaction + TransactionExt — pick the smaller diff).
2. Verify the matcher fires on a freshly-saved REP via a tinker probe:
   docker exec familyfund-<container> php artisan tinker --execute='/* create active line, create REP with null FK, assert credit_line_match_status is auto_matched */'
3. Run tests/Feature/Scenarios/ — all 32 tests should still pass.
4. In tests/Feature/Scenarios/S2RepRoutingMatrixTest.php::makeUnassignedRep, remove the explicit `app(CreditLineClassifier::class)->classify($tran);` line (now redundant) and confirm S2 still passes.
5. Update memory `project_transaction_observer_wiring_gap` to reflect the fix.

Do NOT rename the underlying tables/classes/routes from `credit_line` to `loan_share` — that's a separate, intentionally-deferred rename (see memory `share-loan-rename`).
```

---

## Bug 2 — `delay_notification_enabled` is not in `AccountCreditLine::$fillable` (LOW impact)

### Symptom

The column `delay_notification_enabled` exists on `account_credit_lines`, is read by `LineNotificationSettings::delayNotificationEnabled()` (`app/Services/CreditLine/Settings/LineNotificationSettings.php:65`), and is part of the credit-line settings UI per the docblock at line 60-64, but is **missing from the model's `$fillable` array**. Calling `$line->delay_notification_enabled = false; $line->save();` silently no-ops on the column.

### Evidence

`app1/family-fund-app/app/Models/AccountCreditLine.php:38-57` — `$fillable` has six other notification fields but not `delay_notification_enabled`:

```
'reminder_lead_days',
'reminder_enabled',
'delay_notification_grace_days',
'delay_notification_repeat_days',
'delay_notification_max_repeats',
'transaction_email_enabled',
'mismatch_alert_enabled',
// <— delay_notification_enabled missing here
```

But the column exists:

```bash
docker exec familyfund-pool4 php artisan tinker --execute='use Illuminate\Support\Facades\Schema; foreach (Schema::getColumnListing("account_credit_lines") as $c) if (str_contains($c,"delay")) echo $c."\n";'
# Output:
#   delay_notification_grace_days
#   delay_notification_enabled        <— exists but not fillable
#   delay_notification_repeat_days
#   delay_notification_max_repeats
```

### Workaround currently used in tests

`tests/Feature/Scenarios/S9DelayNotificationDedupTest.php` (the `disabling_delay_notifications` test) uses `AccountCreditLine::where('id', $line->id)->update(['delay_notification_enabled' => false])` to bypass the fillable guard.

### Fix

Add `'delay_notification_enabled'` to `$fillable` in `app/Models/AccountCreditLine.php` (probably between `reminder_enabled` and `delay_notification_grace_days` to keep the grouping). Confirm any form requests / controllers that accept the field are validating it.

### Fresh-thread prompt

```
Add `delay_notification_enabled` to AccountCreditLine::$fillable.

Context: the column exists on account_credit_lines and is read by LineNotificationSettings::delayNotificationEnabled() at app/Services/CreditLine/Settings/LineNotificationSettings.php:65, but it's missing from $fillable in app/Models/AccountCreditLine.php:38-57. Setting `$line->delay_notification_enabled = false; $line->save();` silently no-ops.

Task:
1. Add 'delay_notification_enabled' to the $fillable array in app/Models/AccountCreditLine.php (group with the other delay_notification_* fields).
2. Add the corresponding cast in $casts (=> 'boolean').
3. Check whether any controller / form request needs to accept the field as input. Look at WebV1/AccountCreditLineControllerExt + UpdateAccountCreditLineRequest.
4. In tests/Feature/Scenarios/S9DelayNotificationDedupTest.php, the `test_disabling_delay_notifications_on_line_suppresses_all_sends` test currently uses a raw ::update([...]) call to work around this. Once fixed, change it back to `$line->delay_notification_enabled = false; $line->save();` to confirm the fix.
5. Run the S9 suite to confirm: docker exec <container> php artisan test tests/Feature/Scenarios/S9DelayNotificationDedupTest.php
```

---

## Tracking item — Domain rename half-complete (informational)

### Status

Commit c79126a6 (2026-05-20) renamed user-facing labels from "Credit Line" → "**Loan Share**" / "**Loan Shares**". Scope was **Blade templates, breadcrumbs, page titles, button text, and mail copy only**. The following intentionally still use the old names:

- DB tables: `account_credit_lines`, `credit_line_payments`, `credit_line_payment_allocations`, `credit_line_adjustments`, `account_credit_line_balances`
- FK columns: `account_credit_line_id`, `credit_line_match_status`
- PHP namespaces: `App\Services\CreditLine\*`, `App\Models\AccountCreditLine`, `CreditLinePayment`, etc.
- Route names: `credit_lines.*`
- Test class names: `CreditLineFlowTest`, `CreditLineMessyHistoryTest`, etc.

### Why this is here

Not a bug. Recorded so a future session can decide whether to finish the rename in a dedicated PR (table renames + class renames + route renames + a backfill migration). The current half-state is intentional and stable; mixing forms is acceptable.

### Memory

`share-loan-rename` documents this. Update it when/if the deeper rename happens.

### Fresh-thread prompt (only if you actually want to do this)

```
Finish the credit_line → loan_share rename in app1/family-fund-app.

Current state (per memory share-loan-rename): only user-facing labels were renamed in commit c79126a6 (2026-05-20). The DB tables, FK columns, PHP namespaces, route names, and test class names still use credit_line. Mixing forms is intentional and stable; this is a follow-up to make the codebase fully self-consistent.

Scope:
- Rename DB tables: account_credit_lines → account_loan_shares, credit_line_payments → loan_share_payments, credit_line_payment_allocations → loan_share_payment_allocations, credit_line_adjustments → loan_share_adjustments, account_credit_line_balances → account_loan_share_balances, credit_line_delay_notifications → loan_share_delay_notifications
- Rename FK columns: account_credit_line_id → account_loan_share_id, credit_line_match_status → loan_share_match_status
- Rename namespaces App\Services\CreditLine\* → App\Services\LoanShare\*
- Rename models AccountCreditLine → AccountLoanShare, CreditLinePayment → LoanSharePayment, etc.
- Rename route names credit_lines.* → loan_shares.*
- Rename test class names to match

Steps:
1. Write a migration that renames the tables + the FK columns.
2. Use the IDE's project-wide refactor for the PHP namespace / class rename.
3. Update route names + every Blade `route('credit_lines.*')` reference.
4. Land it in ONE big PR — don't rebrand piecemeal in unrelated branches.
5. Run the full test suite + smoke the pool preview env after.
6. Update memory `share-loan-rename` once shipped.

Do NOT do this alongside any other feature work. It's a wide diff with low individual risk per file but high coordination risk.
```

---

## Notes for the next session

- Run `git log --oneline HEAD~5..HEAD` in the worktree to see the scenario commits (`7e3d97a6`, `173a1abf` merge, `dff18515` expansion).
- All 32 scenario tests pass against the post-merge code: `docker exec familyfund-pool4 php artisan test tests/Feature/Scenarios`.
- 156 tests in the broader credit-line suite also pass (no regression).
- Pool slot pool4 (http://localhost:3024) is still leased to this worktree — release when done.
- Memory files updated: `project_transaction_observer_wiring_gap`, `share-loan-rename` (corrected from "Share Loan" to "Loan Share").
