<?php

namespace App\Repositories;

use App\Models\TradePortfolioExt;
use App\Repositories\BaseRepository;

/**
 * Class TradePortfolioRepository
 * @package App\Repositories
 * @version January 18, 2024, 2:10 am UTC
*/

class TradePortfolioRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'account_name',
        'portfolio_id',
        'tws_query_id',
        'tws_token',
        'cash_target',
        'cash_reserve_target',
        'max_single_order',
        'minimum_order',
        'rebalance_period',
        'mode',
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
