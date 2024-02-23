<?php

namespace App\Repositories;

use App\Models\TradePortfolioItem;
use App\Repositories\BaseRepository;

/**
 * Class TradePortfolioItemRepository
 * @package App\Repositories
 * @version July 23, 2022, 12:55 pm UTC
*/

class TradePortfolioItemRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'trade_portfolio_id',
        'symbol',
        'type',
        'target_share',
        'deviation_trigger'
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
        return TradePortfolioItem::class;
    }
}
