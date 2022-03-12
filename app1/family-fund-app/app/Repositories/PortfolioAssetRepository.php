<?php

namespace App\Repositories;

use App\Models\PortfolioAsset;
use App\Repositories\BaseRepository;

/**
 * Class PortfolioAssetRepository
 * @package App\Repositories
 * @version January 14, 2022, 4:54 am UTC
*/

class PortfolioAssetRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'portfolio_id',
        'asset_id',
        'position',
        'start_dt',
        'end_dt'
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
        return PortfolioAsset::class;
    }
}
