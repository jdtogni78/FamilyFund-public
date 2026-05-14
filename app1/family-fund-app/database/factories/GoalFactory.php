<?php

namespace Database\Factories;

use App\Models\GoalExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GoalExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence(6),
            'start_dt' => '2024-01-01',
            'end_dt' => '2024-12-31',
            'target_type' => $this->faker->randomElement([GoalExt::TARGET_TYPE_TOTAL, GoalExt::TARGET_TYPE_4PCT]),
            'target_amount' => $this->faker->numberBetween(1000, 100000),
            'target_pct' => $this->faker->randomFloat(2, 0, 100),
        // 'created_at' => $this->faker->date('Y-m-d H:i:s'),
        // 'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        // 'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
