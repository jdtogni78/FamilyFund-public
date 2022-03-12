<?php

namespace App\Repositories;

use App\Models\AccountBalance;
use App\Repositories\BaseRepository;

/**
 * Class AccountBalanceRepository
 * @package App\Repositories
 * @version January 14, 2022, 4:53 am UTC
*/

class AccountBalanceRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'type',
        'shares',
        'account_id',
        'transaction_id',
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
        return AccountBalance::class;
    }
}
