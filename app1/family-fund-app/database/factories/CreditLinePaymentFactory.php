<?php

namespace Database\Factories;

use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditLinePaymentFactory extends Factory
{
    protected $model = CreditLinePayment::class;

    public function definition(): array
    {
        return [
            'account_credit_line_id' => AccountCreditLineFactory::new(),
            'due_date'               => Carbon::now()->addMonth()->toDateString(),
            'shares_due'             => $this->faker->randomFloat(4, 0.1, 10.0),
            'status'                 => CreditLinePayment::STATUS_SCHEDULED,
            'paid_transaction_id'    => null,
        ];
    }
}
