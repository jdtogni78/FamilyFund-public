<?php

namespace Database\Factories;

use App\Models\AccountReport;
use App\Models\TransactionExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountReport::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Create account with a transaction (required for report generation)
        $account = AccountFactory::new()->create();
        TransactionFactory::new()->create([
            'account_id' => $account->id,
            'status' => TransactionExt::STATUS_CLEARED,
        ]);

        return [
            'account_id' => $account->id,
            'type' => 'ALL',
            'as_of' => $this->faker->date('Y-m-d'),
        ];
    }
}
