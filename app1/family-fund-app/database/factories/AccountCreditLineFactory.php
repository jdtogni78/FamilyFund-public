<?php

namespace Database\Factories;

use App\Models\AccountCreditLineExt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountCreditLineFactory extends Factory
{
    protected $model = AccountCreditLineExt::class;

    public function definition(): array
    {
        $origination = Carbon::parse($this->faker->dateTimeBetween('-2 years', 'now'));
        $termMonths  = $this->faker->randomElement([12, 24, 36]);
        $maturity    = $origination->copy()->addMonths($termMonths);
        $principal   = $this->faker->randomFloat(4, 1.0, 100.0);

        return [
            'account_id'             => AccountFactory::new(),
            'principal_shares'       => $principal,
            'outstanding_shares'     => $principal,
            'term_months'            => $termMonths,
            'origination_date'       => $origination->toDateString(),
            'maturity_date'          => $maturity->toDateString(),
            'payment_frequency'      => AccountCreditLineExt::FREQUENCY_MONTHLY,
            'status'                 => AccountCreditLineExt::STATUS_ACTIVE,
            'descr'                  => $this->faker->sentence(),
            'imputed_interest_rate'  => null,
        ];
    }
}
