<?php

namespace App\Repositories;

use App\Models\AssetPrice;
use App\Repositories\BaseRepository;

/**
 * Class AssetPriceRepository
 * @package App\Repositories
 * @version January 14, 2022, 4:54 am UTC
*/

class AssetPriceRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'asset_id',
        'price',
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
        return AssetPrice::class;
    }
}
