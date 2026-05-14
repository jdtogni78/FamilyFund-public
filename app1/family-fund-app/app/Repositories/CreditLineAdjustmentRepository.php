<?php

namespace App\Repositories;

use App\Models\CreditLineAdjustment;
use App\Repositories\BaseRepository;

class CreditLineAdjustmentRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'account_credit_line_id',
        'adjusted_by_user_id',
        'adjusted_at',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return CreditLineAdjustment::class;
    }
}
