<?php

namespace Database\Factories;

use App\Models\TradePortfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradePortfolioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TradePortfolio::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_name' => $this->faker->word,
        'cash_target' => $this->faker->word,
        'cash_reserve_target' => $this->faker->word,
        'max_single_order' => $this->faker->word,
        'minimum_order' => $this->faker->word,
        'rebalance_period' => $this->faker->randomDigitNotNull,
        //'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        //'created_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
