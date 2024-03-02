<?php

namespace App\Repositories;

use App\Models\FundReportExt;
use App\Repositories\BaseRepository;

/**
 * Class FundReportRepository
 * @package App\Repositories
 * @version February 11, 2024, 11:55 pm UTC
*/

class FundReportRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'fund_id',
        'type',
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
        return FundReportExt::class;
    }
}
