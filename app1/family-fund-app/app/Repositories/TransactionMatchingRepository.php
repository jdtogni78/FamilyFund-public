<?php

namespace App\Repositories;

use App\Models\TransactionMatching;
use App\Repositories\BaseRepository;

/**
 * Class TransactionMatchingRepository
 * @package App\Repositories
 * @version January 19, 2022, 1:19 am UTC
*/

class TransactionMatchingRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'matching_rule_id',
        'transaction_id',
        'reference_transaction_id'
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
        return TransactionMatching::class;
    }
}
