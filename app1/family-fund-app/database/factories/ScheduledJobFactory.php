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
            'schedule_id' => ScheduleFactory::new(),
            'entity_descr' => $this->faker->randomElement(['transaction', 'account', 'report']),
            'entity_id' => $this->faker->randomNumber(4),
            'start_dt' => $this->faker->date('Y-m-d'),
            'end_dt' => $this->faker->date('Y-m-d'),
        ];
    }
}
