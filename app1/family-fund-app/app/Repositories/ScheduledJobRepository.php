<?php

namespace App\Repositories;

use App\Models\ScheduledJobExt;
use App\Repositories\BaseRepository;

/**
 * Class ScheduledJobRepository
 * @package App\Repositories
 * @version March 2, 2024, 5:09 am UTC
*/

class ScheduledJobRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'schedule_id',
        'entity_descr',
        'entity_id',
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
        return ScheduledJobExt::class;
    }
}
