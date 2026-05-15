<?php

namespace App\Repositories;

use App\Models\TransactionReversal;
use App\Repositories\BaseRepository;

class TransactionReversalRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'transaction_id',
        'reversed_by_user_id',
        'original_target_credit_line_id',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return TransactionReversal::class;
    }
}
