<?php

namespace Database\Factories;

use App\Models\MatchingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchingRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MatchingRule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'dollar_range_start' => $this->faker->numberBetween(0,100),
            'dollar_range_end' => $this->faker->numberBetween(101,500),
            'date_start' => $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now'),
            'date_end' => $this->faker->dateTimeBetween($startDate = '+1 months', $endDate = '+3 months'),
            'match_percent' => $this->faker->numberBetween(1,300),
        //'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        //'created_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
