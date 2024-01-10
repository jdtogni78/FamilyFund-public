<?php

namespace App\Repositories;

use App\Models\TradePortfolioExt;
use App\Repositories\BaseRepository;

/**
 * Class TradePortfolioRepository
 * @package App\Repositories
 * @version January 3, 2024, 3:56 pm UTC
*/

class TradePortfolioRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'account_name',
        'fund_id',
        'cash_target',
        'cash_reserve_target',
        'max_single_order',
        'minimum_order',
        'rebalance_period',
        'start_dt',
        'end_dt'
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
        return TradePortfolioExt::class;
    }
}
