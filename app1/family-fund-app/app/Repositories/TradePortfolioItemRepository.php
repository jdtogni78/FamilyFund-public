<?php

namespace App\Repositories;

use App\Models\TradePortfolioItem;
use App\Repositories\BaseRepository;

/**
 * Class TradePortfolioItemRepository
 * @package App\Repositories
 * @version February 23, 2024, 8:47 am UTC
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
        'deviation_trigger',
        'display_category'
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
