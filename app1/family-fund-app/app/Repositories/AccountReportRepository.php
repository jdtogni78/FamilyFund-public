<?php

namespace App\Repositories;

use App\Models\AccountReport;
use App\Repositories\BaseRepository;

/**
 * Class AccountReportRepository
 * @package App\Repositories
 * @version February 28, 2022, 6:36 am UTC
*/

class AccountReportRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'account_id',
        'type',
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
        return AccountReport::class;
    }
}
