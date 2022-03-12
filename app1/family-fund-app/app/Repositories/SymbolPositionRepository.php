<?php

namespace App\Repositories;

use App\Models\SymbolPosition;
use App\Repositories\BaseRepository;

/**
 * Class SymbolPositionRepository
 * @package App\Repositories
 * @version March 7, 2022, 3:07 am UTC
*/

class SymbolPositionRepository extends BaseRepository
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
        return SymbolPosition::class;
    }
}
