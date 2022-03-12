<?php

namespace App\Repositories;

use App\Models\AssetChangeLog;
use App\Repositories\BaseRepository;

/**
 * Class AssetChangeLogRepository
 * @package App\Repositories
 * @version January 14, 2022, 4:54 am UTC
*/

class AssetChangeLogRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'action',
        'asset_id',
        'field',
        'content',
        'datetime'
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
        return AssetChangeLog::class;
    }
}
