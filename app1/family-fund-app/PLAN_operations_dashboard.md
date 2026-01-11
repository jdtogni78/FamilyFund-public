# Operations Dashboard Plan

## Overview
Create a single "Operations" page to trigger scheduled jobs, process pending items, and manage the queue - consolidating operations that currently require CLI or API calls.

## Current State

### Scheduled Jobs (DB-based, entity schedules)
| ID | Type | Schedule | Description |
|----|------|----------|-------------|
| 1 | fund_report | 5th of month | Quarterly fund reports |
| 3 | fund_report | 15th of quarter | Quarterly fund reports |
| 4 | transaction | 5th of month | Recurring purchase (Acct 15) |
| 5 | transaction | 5th of month | Recurring purchase (Acct 26) |
| 1251 | trade_band_report | 5th of month | Trading bands report |

### Queue Jobs
- `SendAccountReport` - Email account statements
- `SendFundReport` - Email fund reports
- `SendPortfolioReport` - Email portfolio reports
- `SendTradeBandReport` - Email trade band reports
- `FetchDeposits` - Fetch deposit data

### Existing Operations
- `POST /api/schedule_jobs` - Run all due scheduled jobs
- Individual job run/force-run on scheduled job show page
- `POST /transactions/process_all_pending` - Process pending transactions (just added)

---

## Proposed UI: `/operations` page

### Section 1: Quick Actions (Card Grid)
```
+---------------------------+  +---------------------------+  +---------------------------+
|  Run Due Scheduled Jobs   |  |  Process Pending Trans    |  |  Start Queue Worker       |
|  [Run All Due]            |  |  24 pending               |  |  Status: Stopped          |
|  Last run: 2026-01-05     |  |  [Process All]            |  |  [Start] [Stop]           |
+---------------------------+  +---------------------------+  +---------------------------+
```

### Section 2: Scheduled Jobs Status (Table)
| Job | Type | Schedule | Last Run | Next Due | Status | Actions |
|-----|------|----------|----------|----------|--------|---------|
| Fund Report Q4 | fund_report | 15th of quarter | 2025-10-15 | 2026-01-15 | Due in 4 days | [Preview] [Run] [Force] |
| Transaction #4 | transaction | 5th of month | 2026-01-05 | 2026-02-05 | OK | [Preview] [Run] [Force] |
| Trade Bands | trade_band_report | 5th of month | 2026-01-05 | 2026-02-05 | OK | [Preview] [Run] [Force] |

### Section 3: Pending Items Summary
| Type | Count | Oldest | Actions |
|------|-------|--------|---------|
| Pending Transactions | 0 | - | [Process All] |
| Pending Fund Reports | 2 | 2025-Q3 | [Generate] |

### Section 4: Queue Status
```
Queue Worker: [Running] / [Stopped]    [Start] [Stop] [Restart]

Failed Jobs: 9
+------------+---------------------+------------------------+------------------+
| Date       | Job                 | Error                  | Actions          |
+------------+---------------------+------------------------+------------------+
| 2026-01-11 | SendPortfolioReport | Connection timed out   | [Retry] [Delete] |
| 2025-10-15 | SendAccountReport   | Invalid email          | [Retry] [Delete] |
+------------+---------------------+------------------------+------------------+
                                                    [Retry All] [Flush All]
```

---

## Implementation

### Files to Create/Modify

1. **Route**: `routes/web.php`
```php
Route::get('operations', 'OperationsController@index')->name('operations.index');
Route::post('operations/run-due-jobs', 'OperationsController@runDueJobs')->name('operations.run_due_jobs');
Route::post('operations/queue/start', 'OperationsController@startQueue')->name('operations.queue_start');
Route::post('operations/queue/stop', 'OperationsController@stopQueue')->name('operations.queue_stop');
Route::post('operations/queue/retry/{id}', 'OperationsController@retryFailedJob')->name('operations.queue_retry');
Route::post('operations/queue/retry-all', 'OperationsController@retryAllFailedJobs')->name('operations.queue_retry_all');
Route::post('operations/queue/flush', 'OperationsController@flushFailedJobs')->name('operations.queue_flush');
```

2. **Controller**: `app/Http/Controllers/WebV1/OperationsController.php`
   - `index()` - gather all status data
   - `runDueJobs()` - calls `scheduleDueJobs(Carbon::now())`
   - `startQueue()` - starts queue worker (background process)
   - `stopQueue()` - stops queue worker
   - `retryFailedJob($id)` - `Artisan::call('queue:retry', ['id' => $id])`
   - `retryAllFailedJobs()` - `Artisan::call('queue:retry', ['id' => 'all'])`
   - `flushFailedJobs()` - `Artisan::call('queue:flush')`

3. **View**: `resources/views/operations/index.blade.php`
   - Quick actions cards
   - Scheduled jobs table with status
   - Pending items summary
   - Failed queue jobs table

4. **Navigation**: Add "Operations" link to sidebar/nav

### Data to Display

```php
// In OperationsController@index
$data = [
    // Scheduled jobs with status
    'scheduledJobs' => ScheduledJobExt::with('schedule')->get()->map(fn($job) => [
        'job' => $job,
        'lastRun' => $job->lastGeneratedReportDate(),
        'shouldRunBy' => $job->shouldRunBy(Carbon::now()),
    ]),

    // Pending counts
    'pendingTransactions' => TransactionExt::where('status', 'P')->count(),

    // Queue status
    'failedJobs' => DB::table('failed_jobs')->orderByDesc('failed_at')->limit(20)->get(),
    'failedJobsCount' => DB::table('failed_jobs')->count(),

    // Queue worker status (check if process running)
    'queueRunning' => $this->isQueueWorkerRunning(),
];
```

---

## Decisions

1. **Queue worker management**: Yes - start/stop from UI (will use background process)
2. **Permissions**: Admin only (use middleware)
3. **Audit logging**: Yes - show operation history/logs on UI

---

## Section 5: Operations History (New)
| Date | User | Operation | Result |
|------|------|-----------|--------|
| 2026-01-11 10:30 | admin | Run Due Scheduled Jobs | 3 jobs executed |
| 2026-01-11 10:25 | admin | Process All Pending | 24 transactions processed |
| 2026-01-11 10:20 | admin | Retry Failed Job #abc | Success |

**Storage**: New `operation_logs` table
```sql
CREATE TABLE operation_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    operation VARCHAR(100),
    details JSON,
    result VARCHAR(50),
    created_at TIMESTAMP
);
```

---

## Updated Implementation

### Files to Create/Modify

1. **Migration**: `create_operation_logs_table.php`

2. **Model**: `app/Models/OperationLog.php`

3. **Route**: `routes/web.php` (admin middleware)
```php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('operations', 'OperationsController@index')->name('operations.index');
    Route::post('operations/run-due-jobs', 'OperationsController@runDueJobs')->name('operations.run_due_jobs');
    Route::post('operations/process-pending', 'OperationsController@processPending')->name('operations.process_pending');
    Route::post('operations/queue/start', 'OperationsController@startQueue')->name('operations.queue_start');
    Route::post('operations/queue/stop', 'OperationsController@stopQueue')->name('operations.queue_stop');
    Route::post('operations/queue/retry/{id}', 'OperationsController@retryFailedJob')->name('operations.queue_retry');
    Route::post('operations/queue/retry-all', 'OperationsController@retryAllFailedJobs')->name('operations.queue_retry_all');
    Route::post('operations/queue/flush', 'OperationsController@flushFailedJobs')->name('operations.queue_flush');
});
```

4. **Controller**: `app/Http/Controllers/WebV1/OperationsController.php`
   - All methods log to `operation_logs` table
   - Queue start/stop uses `popen()`/process management

5. **View**: `resources/views/operations/index.blade.php`

---

## Complexity Estimate
- Migration + Model: ~30 lines
- Controller: ~250 lines
- View: ~300 lines
- Routes: ~15 lines
- **Total**: ~3-4 hours implementation
