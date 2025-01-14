<?php

namespace App\Repositories;

use App\Models\DepositRequest;
use App\Repositories\BaseRepository;

/**
 * Class DepositRequestRepository
 * @package App\Repositories
 * @version January 14, 2025, 4:25 am UTC
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
        return DepositRequest::class;
    }
}
