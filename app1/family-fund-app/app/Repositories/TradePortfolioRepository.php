<?php

namespace App\Repositories;

use App\Models\TradePortfolio;
use App\Repositories\BaseRepository;

/**
 * Class TradePortfolioRepository
 * @package App\Repositories
 * @version July 23, 2022, 12:55 pm UTC
*/

class TradePortfolioRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'account_name',
        'cash_target',
        'cash_reserve_target',
        'max_single_order',
        'minimum_order',
        'rebalance_period'
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
        return TradePortfolio::class;
    }
}
