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
            'fund_id' => $this->faker->word,
            'type' => $this->faker->word,
            'as_of' => $this->faker->word,
//            'scheduled_job_id' => $this->faker->word,
            'created_at' => $this->faker->date('Y-m-d H:i:s'),
            'updated_at' => $this->faker->date('Y-m-d H:i:s'),
//            'deleted_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
