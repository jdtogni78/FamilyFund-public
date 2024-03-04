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
            'value' => $this->faker->randomFloat(2, $min = 0.01, $max = 999999.99),
//        'shares' => $this->faker->word,
//        'timestamp' => $this->faker->date('Y-m-d H:i:s'),
//        'account_id' => $this->faker->word,
            'descr' => $this->faker->word,
            'flags' => $this->faker->randomElement([
                TransactionExt::FLAGS_ADD_CASH,
                TransactionExt::FLAGS_CASH_ADDED,
                TransactionExt::FLAGS_NO_MATCH,
                null]),
//        'scheduled_job_id' => $this->faker->word,
//        'updated_at' => $this->faker->date('Y-m-d H:i:s'),
//        'created_at' => $this->faker->date('Y-m-d H:i:s'),
//        'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
