<?php

namespace App\Repositories;

use App\Models\PortfolioExt;
use App\Repositories\BaseRepository;

/**
 * Class PortfolioRepository
 * @package App\Repositories
 * @version March 7, 2022, 7:17 am UTC
*/

class PortfolioRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'fund_id',
        'source'
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
        return PortfolioExt::class;
    }
}
