<?php

namespace App\Repositories;

use App\Models\FundReportSchedule;
use App\Repositories\BaseRepository;

/**
 * Class FundReportScheduleRepository
 * @package App\Repositories
 * @version January 17, 2023, 6:26 am UTC
*/

class FundReportScheduleRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'template_fund_report_id',
        'day_of_month',
        'frequency'
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
