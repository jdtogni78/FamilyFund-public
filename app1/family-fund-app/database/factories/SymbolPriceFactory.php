<?php

namespace Database\Factories;

use App\Models\SymbolPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class SymbolPriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SymbolPrice::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
        'type' => $this->faker->word,
        'price' => $this->faker->randomFloat(2, 0.01),
        ];
    }
}
