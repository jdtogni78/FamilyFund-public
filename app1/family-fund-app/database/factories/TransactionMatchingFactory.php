<?php

namespace Database\Factories;

use App\Models\TransactionMatching;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionMatchingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TransactionMatching::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'matching_rule_id' => MatchingRuleFactory::new(),
            'transaction_id' => TransactionFactory::new(),
            'reference_transaction_id' => TransactionFactory::new(),
        ];
    }
}
