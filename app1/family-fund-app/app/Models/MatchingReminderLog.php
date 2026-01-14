<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class MatchingReminderLog
 * @package App\Models
 *
 * @property integer $scheduled_job_id
 * @property integer $account_id
 * @property string $sent_at
 * @property array $rule_details
 * @property integer $rules_count
 */
class MatchingReminderLog extends Model
{
    use HasFactory;

    public $table = 'matching_reminder_logs';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'scheduled_job_id',
        'account_id',
        'sent_at',
        'rule_details',
        'rules_count'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'scheduled_job_id' => 'integer',
        'account_id' => 'integer',
        'sent_at' => 'date',
        'rule_details' => 'array',
        'rules_count' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'scheduled_job_id' => 'required|integer',
        'account_id' => 'required|integer',
        'sent_at' => 'required|date',
        'rule_details' => 'nullable|array',
        'rules_count' => 'required|integer'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function scheduledJob()
    {
        return $this->belongsTo(ScheduledJob::class, 'scheduled_job_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
