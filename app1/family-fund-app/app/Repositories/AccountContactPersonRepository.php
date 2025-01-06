<?php

namespace App\Repositories;

use App\Models\AccountContactPerson;
use App\Repositories\BaseRepository;

class AccountContactPersonRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'account_id',
        'person_id'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return AccountContactPerson::class;
    }
} 