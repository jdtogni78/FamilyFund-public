<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\ScheduledJobTrait;
use App\Mail\ScheduledJobFailureMail;
use App\Models\ScheduledJobExt;
use App\Models\ScheduleExt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit coverage for ScheduledJobTrait (Phase 1, issue #22).
 *
 * The handler-dispatch happy paths are exercised through the controller suites;
 * what was missing per clover is the failure handling: the "soft failure" branch
 * of scheduleDueJob() (a due handler that returns null -> failure alert) and
 * forceRunJob()'s "no data" branch. A trade-band-report job pointing at a
 * missing template triggers both, since tradeBandReportScheduleDue() cleanly
 * returns null when the template can't be found.
 */
class ScheduledJobTraitTest extends TestCase
{
    use DatabaseTransactions;

    private object $trait;
    private DataFactory $df;

    protected function setUp(): void
    {
        parent::setUp();

        $this->df = new DataFactory();
        $this->df->createFund(1000, 1000, '2021-01-01');

        // Anonymous host that uses the trait and exposes the private dispatcher.
        $this->trait = new class {
            use ScheduledJobTrait;
            public $verbose = false;

            public function callScheduleDueJob($asOf, $schedule): array
            {
                return $this->scheduleDueJob($asOf, $schedule);
            }
        };

        Mail::fake();
    }

    /**
     * A due scheduled job whose handler returns null is a "soft failure":
     * scheduleDueJob() returns a synthetic error and fires a failure alert.
     */
    public function test_soft_failure_when_due_handler_returns_null()
    {
        $schedule = $this->df->createSchedule(ScheduleExt::TYPE_DAY_OF_MONTH, 1);
        // entity_id points at a trade-band-report template that does not exist.
        $job = $this->df->createScheduledJob(
            $schedule,
            ScheduledJobExt::ENTITY_TRADE_BAND_REPORT,
            999999,
            '2020-01-01'
        );
        $jobExt = ScheduledJobExt::find($job->id);

        [$model, $error, $shouldRunBy] = $this->trait->callScheduleDueJob(Carbon::now(), $jobExt);

        $this->assertNull($model, 'Handler returned null, so no model should be produced');
        $this->assertInstanceOf(\Exception::class, $error, 'Soft failure should surface a synthetic exception');
        $this->assertStringContainsString('null', $error->getMessage());
        $this->assertNotNull($shouldRunBy);

        Mail::assertSent(ScheduledJobFailureMail::class);
    }

    /**
     * forceRunJob() bypasses the schedule check but still reports "no data"
     * when the handler produces nothing.
     */
    public function test_force_run_reports_no_data_when_handler_returns_null()
    {
        $schedule = $this->df->createSchedule(ScheduleExt::TYPE_DAY_OF_MONTH, 1);
        $job = $this->df->createScheduledJob(
            $schedule,
            ScheduledJobExt::ENTITY_TRADE_BAND_REPORT,
            999999,
            '2020-01-01'
        );
        $jobExt = ScheduledJobExt::find($job->id);

        [$model, $error] = $this->trait->forceRunJob(Carbon::now(), $jobExt);

        $this->assertNull($model);
        $this->assertInstanceOf(\Exception::class, $error);
        $this->assertStringContainsString('no data', strtolower($error->getMessage()));
    }
}
