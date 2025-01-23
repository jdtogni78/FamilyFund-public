<?php

namespace App\Repositories;

use App\Models\GoalExt;
use App\Repositories\BaseRepository;

/**
 * Class GoalRepository
 * @package App\Repositories
 * @version January 20, 2025, 11:17 pm UTC
*/

class GoalRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'description',
        'start_dt',
        'end_dt',
        'target_type',
        'target_amount',
        'target_pct'
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
        return GoalExt::class;
    }
}
