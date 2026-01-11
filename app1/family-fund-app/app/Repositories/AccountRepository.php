<?php

namespace App\Repositories;

use App\Models\AccountExt;
use App\Repositories\BaseRepository;
use App\Repositories\Traits\AuthorizesQueries;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class AccountRepository
 * @package App\Repositories
 * @version January 20, 2025, 11:18 pm UTC
*/

class AccountRepository extends BaseRepository
{
    use AuthorizesQueries;

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'code',
        'nickname',
        'email_cc',
        'user_id',
        'fund_id'
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
        return AccountExt::class;
    }

    /**
     * Apply authorization scope to filter accounts.
     */
    protected function applyAuthorizationScope(Builder $query): Builder
    {
        $authService = $this->getAuthorizationService();

        if (!$authService) {
            return $query;
        }

        return $authService->scopeAccountsQuery($query);
    }
}
