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
        'description' => $this->faker->word,
        'start_dt' => $this->faker->word,
        'end_dt' => $this->faker->word,
        'target_type' => $this->faker->word,
        'target_amount' => $this->faker->word,
        'target_pct' => $this->faker->word,
        // 'created_at' => $this->faker->date('Y-m-d H:i:s'),
        // 'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        // 'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
