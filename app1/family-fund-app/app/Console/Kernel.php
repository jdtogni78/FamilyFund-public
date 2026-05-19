<?php

namespace App\Console;

use App\Jobs\CreditLine\ScanLatePaymentsJob;
use App\Jobs\CreditLine\ScanRemindersJob;
use App\Jobs\CreditLine\SendStatusUpdateJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Credit-line: daily reminder + late-payment scans (Phase 2 wiring).
        $schedule->call(fn () => ScanRemindersJob::dispatch())
            ->dailyAt('07:00')
            ->name('credit_lines.scan_reminders')
            ->withoutOverlapping();

        $schedule->call(fn () => ScanLatePaymentsJob::dispatch())
            ->dailyAt('07:15')
            ->name('credit_lines.scan_late_payments')
            ->withoutOverlapping();

        // Credit-line: quarterly per-account status digest + trajectory forecast.
        $schedule->call(fn () => SendStatusUpdateJob::dispatch())
            ->quarterly()
            ->at('07:30')
            ->name('credit_lines.send_status_update')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
