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
            'descr' => $this->faker->word,
            'type' => $this->faker->word,
            'value' => $this->faker->word,
            'created_at' => $this->faker->date('Y-m-d H:i:s'),
            'updated_at' => $this->faker->date('Y-m-d H:i:s'),
//            'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
