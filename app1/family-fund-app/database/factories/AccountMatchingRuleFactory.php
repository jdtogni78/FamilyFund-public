<?php

namespace Database\Factories;

use App\Models\AccountMatchingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountMatchingRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountMatchingRule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //'account_id' => $this->faker->word,
        //'matching_rule_id' => $this->faker->word,
        //'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        //'created_at' => $this->faker->date('Y-m-d H:i:s'),
        //'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
