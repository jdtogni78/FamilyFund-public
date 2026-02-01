<?php

namespace App\Repositories;

use App\Models\MatchingReminderLog;

class MatchingReminderLogRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'scheduled_job_id',
        'account_id',
        'sent_at',
        'rules_count'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return MatchingReminderLog::class;
    }
}
