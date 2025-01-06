<?php

namespace App\Repositories;

use App\Models\IdDocument;
use App\Repositories\BaseRepository;

class IdDocumentRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'type',
        'number',
        'person_id'
    ];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return IdDocument::class;
    }
} 