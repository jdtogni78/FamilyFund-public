<?php

namespace Database\Factories;

use App\Models\FundReportExt;
use App\Models\TransactionExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FundReportExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Create fund - FundFactory creates the fund account (user_id = null) automatically
        $fund = FundFactory::new()->create();

        // Get the fund account (created by FundFactory's afterCreating)
        $fundAccount = $fund->accounts()->whereNull('user_id')->first();

        // Create a transaction on the FUND account (required for report generation)
        TransactionFactory::new()->create([
            'account_id' => $fundAccount->id,
            'status' => TransactionExt::STATUS_CLEARED,
        ]);

        // Also create a user account with email for email validation
        AccountFactory::new()->create([
            'fund_id' => $fund->id,
            'email_cc' => 'test@example.com',
        ]);

        return [
            'fund_id' => $fund->id,
            'type' => $this->faker->randomElement(['ALL', 'ADM']),
            'as_of' => $this->faker->date('Y-m-d'),
        ];
    }
}
