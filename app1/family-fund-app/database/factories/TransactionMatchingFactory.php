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
//             'matching_rule_id' => $this->faker->word,
//         'transaction_id' => $this->faker->word,
//         'reference_transaction_id' => $this->faker->word,
//         'updated_at' => $this->faker->date('Y-m-d H:i:s'),
//         'created_at' => $this->faker->date('Y-m-d H:i:s'),
//         'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
