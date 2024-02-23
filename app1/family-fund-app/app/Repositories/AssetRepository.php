<?php

namespace App\Repositories;

use App\Models\AssetExt;
use App\Repositories\BaseRepository;

/**
 * Class AssetRepository
 * @package App\Repositories
 * @version February 23, 2024, 9:34 am UTC
*/

class AssetRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'source',
        'name',
        'type',
        'display_group'
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
        return AssetExt::class;
    }
}
