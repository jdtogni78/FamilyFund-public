<?php

namespace Database\Factories;

use App\Models\AccountReport;
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
        return [
            'account_id' => AccountFactory::new(),
            'type' => 'ALL',
            'as_of' => $this->faker->date('Y-m-d'),
        ];
    }
}
