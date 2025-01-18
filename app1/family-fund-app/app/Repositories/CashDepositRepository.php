<?php

namespace App\Repositories;

use App\Models\CashDeposit;
use App\Repositories\BaseRepository;

/**
 * Class CashDepositRepository
 * @package App\Repositories
 * @version January 14, 2025, 5:04 am UTC
*/

class CashDepositRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'description',
        'value',
        'status',
        'account_id',
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
        return CashDeposit::class;
    }
}
