<?php

namespace App\Repositories;

use App\Models\FundReport;
use App\Repositories\BaseRepository;

/**
 * Class FundReportRepository
 * @package App\Repositories
 * @version February 28, 2022, 6:36 am UTC
*/

class FundReportRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'fund_id',
        'type',
        'file',
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
        return FundReport::class;
    }
}
