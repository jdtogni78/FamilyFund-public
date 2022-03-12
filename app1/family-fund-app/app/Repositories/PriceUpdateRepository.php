<?php

namespace App\Repositories;

use App\Models\PriceUpdate;
use App\Repositories\BaseRepository;

/**
 * Class PriceUpdateRepository
 * @package App\Repositories
 * @version March 5, 2022, 8:29 pm UTC
*/

class PriceUpdateRepository extends BaseRepository
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
        return PriceUpdate::class;
    }
}
