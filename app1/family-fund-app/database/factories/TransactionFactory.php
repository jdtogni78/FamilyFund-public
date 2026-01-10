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
            'account_id' => AccountFactory::new(),
            'type' => $this->faker->randomElement([
                TransactionExt::TYPE_PURCHASE,
                TransactionExt::TYPE_SALE,
                TransactionExt::TYPE_MATCHING,
                TransactionExt::TYPE_BORROW,
                TransactionExt::TYPE_REPAY,
            ]),
            'status' => $this->faker->randomElement([
                TransactionExt::STATUS_PENDING,
                TransactionExt::STATUS_CLEARED,
            ]),
            'value' => $this->faker->randomFloat(2, 0.01, 999999.99),
            'shares' => $this->faker->randomFloat(4, 0.0001, 9999.9999),
            'timestamp' => $this->faker->date('Y-m-d'),
            'descr' => $this->faker->word,
            'flags' => $this->faker->randomElement([
                TransactionExt::FLAGS_ADD_CASH,
                TransactionExt::FLAGS_CASH_ADDED,
                TransactionExt::FLAGS_NO_MATCH,
                null,
            ]),
        ];
    }
}
