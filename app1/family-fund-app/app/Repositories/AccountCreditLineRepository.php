<?php

namespace App\Repositories;

use App\Models\AccountCreditLineExt;
use App\Repositories\BaseRepository;

class AccountCreditLineRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'account_id',
        'status',
        'payment_frequency',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return AccountCreditLineExt::class;
    }
}
