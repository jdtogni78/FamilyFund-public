<?php

namespace Database\Factories;

use App\Models\CashDeposit;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'date' => $this->faker->word,
        'description' => $this->faker->word,
        'value' => $this->faker->word
        ];
    }
}
