<?php

namespace Database\Factories;

use App\Models\AccountMatchingRuleExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountMatchingRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountMatchingRuleExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => AccountFactory::new(),
            'matching_rule_id' => MatchingRuleFactory::new(),
        ];
    }
}
