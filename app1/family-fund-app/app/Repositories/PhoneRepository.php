<?php

namespace App\Repositories;

use App\Models\Phone;
use App\Repositories\BaseRepository;

/**
 * Class PhoneRepository
 * @package App\Repositories
 * @version January 6, 2025, 1:02 am UTC
*/

class PhoneRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'person_id',
        'number',
        'type',
        'is_primary'
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
        return Phone::class;
    }
}
