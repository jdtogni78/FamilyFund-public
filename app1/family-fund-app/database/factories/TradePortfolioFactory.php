<?php

namespace Database\Factories;

use App\Models\Fund;
use App\Models\TradePortfolioExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradePortfolioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TradePortfolioExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Create a fund first, which will auto-create a portfolio
        // This ensures the portfolio has a proper fund association
        $fund = Fund::factory()->create();
        $portfolio = $fund->portfolio();

        return [
            'portfolio_id' => $portfolio->id,
            'account_name' => $this->faker->word,
            'tws_query_id' => $this->faker->word,
            'tws_token' => $this->faker->word,
            'cash_target' => $this->faker->randomNumber(2)/100,
            'cash_reserve_target' => $this->faker->randomNumber(2)/100,
            'max_single_order' => $this->faker->randomNumber(2)/100,
            'minimum_order' => $this->faker->randomNumber(5)/100,
            'rebalance_period' => $this->faker->randomNumber(2),
            'mode' => $this->faker->randomElement(['STD', 'MAX']),
            'start_dt' => $this->faker->date('Y-m-d'),
            'end_dt' => $this->faker->date('Y-m-d'),
        ];
    }
}
