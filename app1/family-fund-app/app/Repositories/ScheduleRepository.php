<?php

namespace App\Repositories;

use App\Models\ScheduleExt;
use App\Repositories\BaseRepository;

/**
 * Class ScheduleRepository
 * @package App\Repositories
 * @version March 2, 2024, 5:09 am UTC
*/

class ScheduleRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'descr',
        'type',
        'value'
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
        return ScheduleExt::class;
    }
}
