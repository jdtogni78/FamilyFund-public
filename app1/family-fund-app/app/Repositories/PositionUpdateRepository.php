<?php

namespace App\Repositories;

use App\Models\PositionUpdate;
use App\Repositories\BaseRepository;

/**
 * Class PositionUpdateRepository
 * @package App\Repositories
 * @version March 7, 2022, 3:07 am UTC
*/

class PositionUpdateRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        
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
        return PositionUpdate::class;
    }
}
