<?php

namespace Database\Factories;

use App\Models\TransactionExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TransactionExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'source' => $this->faker->randomElement(['SPO', 'DIR', 'MAT']),
            'type' => $this->faker->randomElement(['PUR','SAL','BOR','REP']),
            'value' => $this->faker->randomFloat(2, $min = 0.01, $max = 999999.99),
            // 'shares' => null,
        // 'account_id' => $this->faker->numberBetween(1, 12),
        //'matching_rule_id' => $this->faker->word,
        //'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        //'created_at' => $this->faker->date('Y-m-d H:i:s'),
        //'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
