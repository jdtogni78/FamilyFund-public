<?php

namespace App\Repositories;

use App\Models\Person;
use App\Repositories\BaseRepository;

class PersonRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'first_name',
        'last_name',
        'email',
        'birthday'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return Person::class;
    }
} 