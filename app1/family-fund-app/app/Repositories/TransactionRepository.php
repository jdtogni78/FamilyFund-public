<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Repositories\BaseRepository;

/**
 * Class TransactionRepository
 * @package App\Repositories
 * @version March 2, 2024, 10:21 am UTC
*/

class TransactionRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'type',
        'status',
        'value',
        'shares',
        'timestamp',
        'account_id',
        'descr',
        'flags',
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
        return TransactionExt::class;
    }
}
