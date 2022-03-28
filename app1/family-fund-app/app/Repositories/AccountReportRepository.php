<?php

namespace App\Repositories;

use App\Models\AccountReport;
use App\Repositories\BaseRepository;

/**
 * Class AccountReportRepository
 * @package App\Repositories
 * @version March 28, 2022, 2:48 am UTC
*/

class AccountReportRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'account_id',
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
        return AccountReport::class;
    }
}
