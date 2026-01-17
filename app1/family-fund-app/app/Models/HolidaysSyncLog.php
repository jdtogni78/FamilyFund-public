<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HolidaysSyncLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'scheduled_job_id',
        'exchange',
        'synced_at',
        'records_synced',
        'source'
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    /**
     * Get the scheduled job that triggered this sync.
     */
    public function scheduledJob()
    {
        return $this->belongsTo(ScheduledJobExt::class, 'scheduled_job_id');
    }
}
