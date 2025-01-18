<?php

namespace Database\Factories;

use App\Models\CashDeposit;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\CashDepositExt;

class CashDepositFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CashDeposit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'date' => $this->faker->dateTime(),
            'description' => $this->faker->word,
            'amount' => $this->faker->numberBetween(1, 1000),
            'status' => CashDepositExt::STATUS_PENDING,
            'account_id' => null,
            'transaction_id' => null
        ];
    }
}
