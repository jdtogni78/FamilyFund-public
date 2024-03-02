<?php

namespace Database\Factories;

use App\Models\ScheduledJobExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledJobFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledJobExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'schedule_id' => $this->faker->word,
            'entity_descr' => $this->faker->word,
            'entity_id' => $this->faker->word,
            'start_dt' => $this->faker->date('Y-m-d'),
            'end_dt' => $this->faker->date('Y-m-d'),
            'updated_at' => $this->faker->date('Y-m-d H:i:s'),
            'created_at' => $this->faker->date('Y-m-d H:i:s'),
            'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
