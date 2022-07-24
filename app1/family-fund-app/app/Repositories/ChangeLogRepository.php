<?php

namespace App\Repositories;

use App\Models\ChangeLog;
use App\Repositories\BaseRepository;

/**
 * Class ChangeLogRepository
 * @package App\Repositories
 * @version July 23, 2022, 12:55 pm UTC
*/

class ChangeLogRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'object',
        'content'
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
        return ChangeLog::class;
    }
}
