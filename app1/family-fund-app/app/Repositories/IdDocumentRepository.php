<?php

namespace App\Repositories;

use App\Models\IdDocument;
use App\Repositories\BaseRepository;

/**
 * Class IdDocumentRepository
 * @package App\Repositories
 * @version January 6, 2025, 1:17 am UTC
*/

class IdDocumentRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'person_id',
        'type',
        'number'
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
        return IdDocument::class;
    }
}
