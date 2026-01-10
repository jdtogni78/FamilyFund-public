<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\FundExt;
use App\Models\Portfolio;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FundExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'goal' => $this->faker->word,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (FundExt $fund) {
            // Every fund must have a portfolio - create one if it doesn't exist
            if ($fund->portfolios()->count() === 0) {
                Portfolio::factory()->create(['fund_id' => $fund->id]);
            }
            // Every fund must have an account with no user_id (fund account)
            if ($fund->accounts()->whereNull('user_id')->count() === 0) {
                Account::factory()->create([
                    'fund_id' => $fund->id,
                    'user_id' => null,
                    'code' => 'F' . $fund->id,
                    'nickname' => 'Fund',
                ]);
            }
        });
    }
}
