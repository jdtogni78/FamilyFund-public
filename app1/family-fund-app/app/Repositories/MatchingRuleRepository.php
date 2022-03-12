<?php

namespace App\Repositories;

use App\Models\MatchingRule;
use App\Repositories\BaseRepository;

/**
 * Class MatchingRuleRepository
 * @package App\Repositories
 * @version January 14, 2022, 4:54 am UTC
*/

class MatchingRuleRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'dollar_range_start',
        'dollar_range_end',
        'date_start',
        'date_end',
        'match_percent'
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
        return MatchingRule::class;
    }
}
