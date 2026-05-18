<?php

namespace App\Repositories;

use App\Models\AccountCreditLineExt;
use App\Repositories\BaseRepository;
use App\Repositories\Traits\AuthorizesQueries;
use Illuminate\Database\Eloquent\Builder;

class AccountCreditLineRepository extends BaseRepository
{
    use AuthorizesQueries;
    protected $fieldSearchable = [
        'account_id',
        'nickname',
        'status',
        'payment_frequency',
    ];

    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    public function model()
    {
        return AccountCreditLineExt::class;
    }

    /**
     * Apply authorization scope to filter credit lines.
     */
    protected function applyAuthorizationScope(Builder $query): Builder
    {
        $authService = $this->getAuthorizationService();

        if (!$authService) {
            return $query;
        }

        return $authService->scopeCreditLinesQuery($query);
    }
}
