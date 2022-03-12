<?php

namespace App\Repositories;

use App\Models\AccountMatchingRule;
use App\Repositories\BaseRepository;

/**
 * Class AccountMatchingRuleRepository
 * @package App\Repositories
 * @version January 14, 2022, 4:53 am UTC
*/

class AccountMatchingRuleRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'account_id',
        'matching_rule_id'
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
        return AccountMatchingRule::class;
    }
}
