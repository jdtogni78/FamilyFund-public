<?php

namespace App\Repositories;

use App\Models\CreditLinePayment;
use App\Repositories\BaseRepository;

class CreditLinePaymentRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'account_credit_line_id',
        'status',
        'due_date',
        'paid_transaction_id',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return CreditLinePayment::class;
    }
}
