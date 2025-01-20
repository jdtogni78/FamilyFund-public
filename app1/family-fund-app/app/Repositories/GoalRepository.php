<?php

namespace App\Repositories;

use App\Models\Goal;
use App\Repositories\BaseRepository;

/**
 * Class GoalRepository
 * @package App\Repositories
 * @version January 20, 2025, 10:51 pm UTC
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
        'pct4',
        'account_id'
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
        return Goal::class;
    }
}
