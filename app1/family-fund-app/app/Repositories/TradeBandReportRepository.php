<?php

namespace App\Repositories;

use App\Models\TradeBandReport;
use App\Repositories\BaseRepository;

/**
 * Class TradeBandReportRepository
 * @package App\Repositories
 */
class TradeBandReportRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'fund_id',
        'as_of',
        'scheduled_job_id'
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
        return TradeBandReport::class;
    }
}
