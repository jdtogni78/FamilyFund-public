<?php

namespace App\Repositories;

use App\Models\ReportSchedule;
use App\Repositories\BaseRepository;

/**
 * Class ReportScheduleRepository
 * @package App\Repositories
 * @version February 11, 2024, 6:45 pm UTC
*/

class ReportScheduleRepository extends BaseRepository
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
        return ReportSchedule::class;
    }
}
