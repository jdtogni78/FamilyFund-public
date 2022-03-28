<?php

namespace App\Repositories;

use App\Models\FundReport;
use App\Repositories\BaseRepository;

/**
 * Class FundReportRepository
 * @package App\Repositories
 * @version March 28, 2022, 2:48 am UTC
*/

class FundReportRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'fund_id',
        'type',
        'as_of'
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
