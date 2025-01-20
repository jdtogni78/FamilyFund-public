<?php

namespace App\Repositories;

use App\Models\DepositRequestExt;
use App\Repositories\BaseRepository;

/**
 * Class DepositRequestRepository
 * @package App\Repositories
 * @version January 14, 2025, 5:03 am UTC
*/

class DepositRequestRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'description',
        'status',
        'account_id',
        'cash_deposit_id',
        'transaction_id'
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
        return DepositRequestExt::class;
    }
}
