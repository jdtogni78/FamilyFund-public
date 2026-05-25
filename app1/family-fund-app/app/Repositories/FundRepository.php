<?php

namespace App\Repositories;

use App\Models\FundExt;
use App\Repositories\BaseRepository;
use App\Repositories\Traits\AuthorizesQueries;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class FundRepository
 * @package App\Repositories
 * @version January 14, 2022, 4:54 am UTC
*/

class FundRepository extends BaseRepository
{
    use AuthorizesQueries;

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'goal'
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
        return FundExt::class;
    }

    /**
     * Apply authorization scope to filter funds (mirrors AccountRepository).
     * Without this override the AuthorizesQueries trait's default is a no-op,
     * so withAuthorization() would silently return every fund.
     */
    protected function applyAuthorizationScope(Builder $query): Builder
    {
        $authService = $this->getAuthorizationService();

        if (!$authService) {
            return $query;
        }

        return $authService->scopeFundsQuery($query);
    }
}
