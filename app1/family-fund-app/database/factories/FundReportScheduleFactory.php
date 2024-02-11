<?php

namespace Database\Factories;

use App\Models\FundReportSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundReportScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FundReportSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
//            'fund_report_id' => $this->faker->word,
        'schedule_id' => $this->faker->word,
        'start_dt' => $this->faker->date('Y-m-d'),
        'end_dt' => $this->faker->date('Y-m-d'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
