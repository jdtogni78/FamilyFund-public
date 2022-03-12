<?php

namespace Database\Factories;

use App\Models\PositionUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionUpdateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PositionUpdate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'source' => $this->faker->word,
        'timestamp' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
