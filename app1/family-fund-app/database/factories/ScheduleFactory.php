<?php

namespace Database\Factories;

use App\Models\ScheduleExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduleExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'descr' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['DOM', 'DOW', 'DOQ', 'DOY']),
            'value' => (string) $this->faker->randomNumber(2),
        ];
    }
}
