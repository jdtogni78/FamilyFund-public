<?php

namespace Database\Factories;

use App\Models\FundReportExt;
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
        return [
            'fund_id' => FundFactory::new(),
            'type' => $this->faker->randomElement(['ALL', 'ADM']),
            'as_of' => $this->faker->date('Y-m-d'),
        ];
    }
}
