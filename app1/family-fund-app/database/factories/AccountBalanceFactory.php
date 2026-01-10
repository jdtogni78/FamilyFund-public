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
            'account_id' => AccountFactory::new(),
            'transaction_id' => TransactionFactory::new(),
            'type' => $this->faker->randomElement(['OWN', 'BOR']),
            'shares' => $this->faker->randomFloat(4, 0.0001, 999999.9999),
            'start_dt' => $this->faker->date('Y-m-d'),
            'end_dt' => $this->faker->date('Y-m-d'),
        ];
    }
}
