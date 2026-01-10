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
            'matching_rule_id' => \App\Models\MatchingRule::factory(),
            'transaction_id' => \App\Models\Transaction::factory(),
            'reference_transaction_id' => \App\Models\Transaction::factory(),
        ];
    }
}
