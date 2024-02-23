<?php

namespace Database\Factories;

use App\Models\TradePortfolioItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradePortfolioItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TradePortfolioItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //'trade_portfolio_id' => $this->faker->word,
            'symbol' => $this->faker->word,
            'type' => $this->faker->word,
            'target_share' => $this->faker->randomNumber(2)/100,
            'deviation_trigger' => $this->faker->randomNumber(2)/100,
            //'updated_at' => $this->faker->date('Y-m-d H:i:s'),
            //'created_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
