<?php

namespace App\Repositories;

use App\Models\SymbolPrice;
use App\Repositories\BaseRepository;

/**
 * Class SymbolPriceRepository
 * @package App\Repositories
 * @version March 5, 2022, 8:29 pm UTC
*/

class SymbolPriceRepository extends BaseRepository
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
        return SymbolPrice::class;
    }
}
