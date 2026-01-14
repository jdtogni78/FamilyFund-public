<?php

namespace Database\Factories;

use App\Models\TradeBandReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradeBandReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TradeBandReport::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'fund_id' => FundFactory::new(),
            'as_of' => $this->faker->date('Y-m-d'),
            'scheduled_job_id' => null,
        ];
    }

    /**
     * Indicate that the report is a template.
     */
    public function template()
    {
        return $this->state(function (array $attributes) {
            return [
                'as_of' => '9999-12-31',
            ];
        });
    }
}
