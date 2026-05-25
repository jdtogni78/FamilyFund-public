<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| This Laravel 11 app uses the minimal bootstrap/app.php, so the scheduler
| is defined here (the legacy App\Console\Kernel::schedule() is NOT wired —
| `php artisan schedule:list` is the source of truth).
|
*/

// dstrader service-token safety net (#82): the actual rotation runs as an
// external cron in dstrader-docker (it must distribute the new token to
// dstrader). Here we only warn if a token is near expiry — so a failed
// rotation can't silently lapse and break price feeds — and prune dead rows.
// See docs/runbooks/ff-service-token-rotation.md.
Schedule::command('security:service-token --check-expiry --warn-days=14')
    ->dailyAt('06:30')
    ->name('service_token.check_expiry')
    ->withoutOverlapping();

Schedule::command('security:service-token --prune-expired')
    ->weeklyOn(1, '06:35')
    ->name('service_token.prune_expired')
    ->withoutOverlapping();
