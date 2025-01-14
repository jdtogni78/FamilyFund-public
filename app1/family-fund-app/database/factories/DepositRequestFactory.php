<?php

namespace Database\Factories;

use App\Models\DepositRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'date' => $this->faker->word,
        'description' => $this->faker->word,
        'status' => $this->faker->word,
        'account_id' => $this->faker->word,
        'cash_deposit_id' => $this->faker->word,
        'transaction_id' => $this->faker->word
        ];
    }
}
