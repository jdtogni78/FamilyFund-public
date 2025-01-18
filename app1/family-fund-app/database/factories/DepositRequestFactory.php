<?php

namespace Database\Factories;

use App\Models\DepositRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DepositRequestExt;

class DepositRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DepositRequest::class;

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
            'status' => DepositRequestExt::STATUS_PENDING,
            'account_id' => null,
            'cash_deposit_id' => null,
            'transaction_id' => null
        ];
    }
}
