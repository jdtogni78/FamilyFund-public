<?php

namespace Database\Seeders;

use App\Models\ScheduleExt;
use App\Models\ScheduledJobExt;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HolidaySyncScheduleSeeder extends Seeder
{
    /**
     * Seed the application's database with holiday sync scheduled job.
     *
     * @return void
     */
    public function run()
    {
        // Create monthly schedule (1st of month)
        $schedule = ScheduleExt::firstOrCreate(
            [
                'type' => ScheduleExt::TYPE_DAY_OF_MONTH,
                'value' => '1',
            ],
            [
                'descr' => 'Monthly - 1st of Month',
            ]
        );

        // Create scheduled job for NYSE holidays
        ScheduledJobExt::firstOrCreate(
            [
                'entity_descr' => ScheduledJobExt::ENTITY_HOLIDAYS_SYNC,
                'entity_id' => 1, // Can be used for exchange config ID in future
            ],
            [
                'schedule_id' => $schedule->id,
                'start_dt' => Carbon::now(),
                'end_dt' => Carbon::now()->addYears(10),
            ]
        );

        $this->command->info('Holiday sync schedule created successfully');
    }
}
