<?php

namespace App\Repositories;

use App\Models\AccountGoal;
use App\Repositories\BaseRepository;

/**
 * Class AccountGoalRepository
 * @package App\Repositories
 * @version January 20, 2025, 11:18 pm UTC
*/

class AccountGoalRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'account_id',
        'goal_id'
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
        return AccountGoal::class;
    }
}
