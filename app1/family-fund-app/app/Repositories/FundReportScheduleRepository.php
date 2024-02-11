<?php

namespace App\Repositories;

use App\Models\FundReportSchedule;
use App\Repositories\BaseRepository;

/**
 * Class FundReportScheduleRepository
 * @package App\Repositories
 * @version February 11, 2024, 7:23 pm UTC
*/

class FundReportScheduleRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'fund_report_id',
        'schedule_id',
        'start_dt',
        'end_dt'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return FundReportSchedule::class;
    }
}
