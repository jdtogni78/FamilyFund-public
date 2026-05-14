# Credit Lines — Phase 1d: Transaction Detection + Notifications

**Status:** Complete  
**Branch:** `claude/elated-pare-eac2e9`  
**Plan refs:** §8.6 (notifications), §10 (unified detection pipeline), §5 rule 10 (mismatch flagging)

---

## Scope

Implements the unified transaction ingestion pipeline and all email notification
infrastructure for credit lines. No existing files were modified.

---

## Use Cases Covered

| UC    | Description                                             | Status   |
|-------|---------------------------------------------------------|----------|
| UC-18 | Reminder before due date                                | Done     |
| UC-19 | Delay notification after missed payment                 | Done     |
| UC-32 | Email on transaction received                           | Done     |
| UC-33 | Email on transaction detected (system-generated badge)  | Done     |
| UC-34 | Email setting opt-out — settings declared, defaults set | Done     |
| UC-35 | Unified ingestion: TransactionDetectionService          | Done     |
| UC-36 | Credit-line classifier routes BOR/REP                   | Done     |
| UC-37 | Contribution classifier stub (PUR) — wraps, not replaces| Done     |
| UC-38 | Email dedup across retries (cache-backed, 24h TTL)      | Done     |

---

## File List (all new)

### Services / Detection
- `app/Services/Detection/Contracts/Classifier.php` — interface
- `app/Services/Detection/DetectionResult.php` — value object (status/targetCreditLineId/notes)
- `app/Services/Detection/TransactionDetectionService.php` — entry point; routes by type; dispatches emails
- `app/Services/Detection/CreditLineClassifier.php` — delegates to `CreditLineMatcher` (wave 1b); persists match status
- `app/Services/Detection/ContributionClassifier.php` — PUR stub; Phase 2 wires existing TransactionMatching flow
- `app/Services/Detection/EmailDedup.php` — cache-backed dedup; `wasSent` / `markSent`

### Settings Helper
- `app/Services/CreditLine/Settings/LineNotificationSettings.php` — returns hard-coded defaults; Phase 2 reads DB columns

### Mail
- `app/Mail/CreditLine/TransactionReceivedMail.php` — UC-32
- `app/Mail/CreditLine/TransactionDetectedMail.php` — UC-33
- `app/Mail/CreditLine/MismatchAlertMail.php` — UC-29/30; placeholder resolve URL
- `app/Mail/CreditLine/ReminderMail.php` — UC-18
- `app/Mail/CreditLine/DelayNotificationMail.php` — UC-19

### Jobs
- `app/Jobs/CreditLine/ScanRemindersJob.php` — UC-18; accepts injectable Carbon today for tests
- `app/Jobs/CreditLine/ScanLatePaymentsJob.php` — UC-19; flips status to `late`; respects repeat + hard cap

### Blade Views (`resources/views/emails/credit_lines/`)
- `transaction_received.blade.php`
- `transaction_detected.blade.php` — "SYSTEM GENERATED" badge
- `mismatch_alert.blade.php` — resolve CTA with placeholder URL
- `reminder.blade.php`
- `delay_notification.blade.php`

### Tests
- `tests/Unit/Services/Detection/TransactionDetectionServiceTest.php`
- `tests/Unit/Services/Detection/ScanJobsTest.php`
- `tests/Unit/Mail/CreditLine/MailableRenderTest.php`

---

## Phase 2 Schema Dependency — Columns Needed on `account_credit_lines`

Add in a new migration (after Phase 1 merges):

| Column                          | Type            | Default | Notes                                       |
|---------------------------------|-----------------|---------|---------------------------------------------|
| `reminder_enabled`              | boolean         | true    | Toggle for pre-due reminders (UC-18/UC-20)  |
| `reminder_lead_days`            | unsigned tinyint| 7       | Days before due_date to send reminder       |
| `delay_notification_enabled`    | boolean         | true    | Toggle for late-payment emails (UC-19/UC-20)|
| `delay_notification_grace_days` | unsigned tinyint| 3       | Days past due before first alert            |
| `delay_notification_repeat_days`| unsigned tinyint| 14      | Days between repeat delay alerts            |
| `delay_notification_max`        | unsigned tinyint| 6       | Hard cap on repeat notifications            |
| `transaction_email_enabled`     | boolean         | true    | Toggle for received/detected emails (UC-34) |

Once these columns exist, update `LineNotificationSettings` to read from `$this->line->{column}`.

---

## Phase 2 Wiring Required

1. **Transaction::saved observer** — wire `TransactionDetectionService::ingest()`:
   ```php
   // In EventServiceProvider or a dedicated Observer:
   Transaction::saved(function (Transaction $tran) {
       app(\App\Services\Detection\TransactionDetectionService::class)
           ->ingest(TransactionExt::find($tran->id));
   });
   ```
   Do NOT add this until Phase 2 — the service is callable but not auto-invoked.

2. **Mismatch resolution route** — mount route so the placeholder URL works:
   ```php
   Route::get('/credit-lines/resolve/{transaction}', [CreditLineResolveController::class, 'show']);
   Route::post('/credit-lines/resolve/{transaction}', [CreditLineResolveController::class, 'store']);
   ```

3. **Scheduler entries** — register jobs in `app/Console/Kernel.php`:
   ```php
   $schedule->job(new \App\Jobs\CreditLine\ScanRemindersJob)->dailyAt('08:00');
   $schedule->job(new \App\Jobs\CreditLine\ScanLatePaymentsJob)->dailyAt('08:05');
   ```

4. **ContributionClassifier** — wire existing `TransactionMatching` flow in Phase 2 without replacing `TransactionExt::createMatching()`. The classify method currently no-ops and logs.

5. **EmailDedup persistence** — replace cache-based dedup with a DB-backed log table if audit trail is needed for compliance.

6. **`created_by` actor** — `TransactionDetectionService::isSystemGenerated()` currently returns `false`. Phase 2 should add a `created_by` column to transactions and use it to distinguish user-submitted vs system-generated.

---

## Test List

| Test file | Test name | Covers |
|---|---|---|
| `TransactionDetectionServiceTest` | `test_detection_result_factories` | DetectionResult value object factories |
| `TransactionDetectionServiceTest` | `test_email_dedup_marks_and_checks` | EmailDedup cache logic |
| `TransactionDetectionServiceTest` | `test_ingest_bor_dispatches_credit_line_classifier_and_sends_email` | UC-35/UC-36/UC-32 |
| `TransactionDetectionServiceTest` | `test_ingest_bor_twice_sends_only_one_email` | UC-38 dedup |
| `TransactionDetectionServiceTest` | `test_ingest_rep_with_ambiguous_result_sends_mismatch_alert` | UC-29 mismatch alert |
| `TransactionDetectionServiceTest` | `test_ingest_pur_calls_contribution_classifier_but_no_credit_line_email` | UC-37 PUR isolation |
| `ScanJobsTest` | `test_scan_reminders_sends_reminder_when_lead_days_match` | UC-18 lead-day match |
| `ScanJobsTest` | `test_scan_reminders_does_not_send_when_not_lead_day` | UC-18 no false positive |
| `ScanJobsTest` | `test_scan_late_payments_flips_status_to_late_and_sends_notification` | UC-19 status flip |
| `ScanJobsTest` | `test_scan_late_payments_respects_repeat_cap` | UC-19 hard cap |
| `ScanJobsTest` | `test_scan_late_payments_increments_notification_count` | UC-19 count tracking |
| `MailableRenderTest` | `test_transaction_received_mail_renders` | UC-32 smoke |
| `MailableRenderTest` | `test_transaction_detected_mail_renders` | UC-33 smoke |
| `MailableRenderTest` | `test_mismatch_alert_mail_renders` | UC-29/30 smoke |
| `MailableRenderTest` | `test_reminder_mail_renders` | UC-18 smoke |
| `MailableRenderTest` | `test_delay_notification_mail_renders` | UC-19 smoke |
