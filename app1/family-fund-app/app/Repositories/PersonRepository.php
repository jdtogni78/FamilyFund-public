<?php

namespace App\Repositories;

use App\Models\Person;
use App\Repositories\BaseRepository;

/**
 * Class PersonRepository
 * @package App\Repositories
 * @version January 6, 2025, 1:02 am UTC
*/

class PersonRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'first_name',
        'last_name',
        'email',
        'birthday',
        'legal_guardian_id'
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
        return Person::class;
    }
}
