<?php

namespace Database\Factories;

use App\Models\SymbolPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

class SymbolPositionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SymbolPosition::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word . "_" . $this->faker->randomNumber(5),
        'type' => $this->faker->randomElement(['STK', 'ETF', 'BND', 'CRY']),
//            $table->decimal('position', 21, 8);
        // TODO: php max dig is 14?!?!
        'position' => $this->faker->randomFloat(4, 0.0001),
        ];
    }
}
