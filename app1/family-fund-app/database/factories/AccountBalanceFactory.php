<?php

namespace Database\Factories;

use App\Models\AccountBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountBalanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountBalance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['OWN', 'BOR']),
        'shares' => $this->faker->randomFloat(4, $min = 0.0001, $max = 999999.9999),
        //'account_id' => $this->faker->word,
        //'transaction_id' => $this->faker->word,
        'start_dt' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
        'end_dt' => $this->faker->date($format = 'Y-m-d', $max = 'now'),
        //'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        //'created_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
