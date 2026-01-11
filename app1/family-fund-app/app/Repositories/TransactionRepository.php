<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Repositories\BaseRepository;
use App\Repositories\Traits\AuthorizesQueries;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class TransactionRepository
 * @package App\Repositories
 * @version March 2, 2024, 10:21 am UTC
*/

class TransactionRepository extends BaseRepository
{
    use AuthorizesQueries;

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'type',
        'status',
        'value',
        'shares',
        'timestamp',
        'account_id',
        'descr',
        'flags',
        'scheduled_job_id'
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
        return TransactionExt::class;
    }

    /**
     * Apply authorization scope to filter transactions.
     */
    protected function applyAuthorizationScope(Builder $query): Builder
    {
        $authService = $this->getAuthorizationService();

        if (!$authService) {
            return $query;
        }

        return $authService->scopeTransactionsQuery($query);
    }
}
