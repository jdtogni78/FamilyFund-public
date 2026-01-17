<?php

namespace Database\Factories;

use App\Models\ExchangeHoliday;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeHolidayFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExchangeHoliday::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'exchange_code' => 'NYSE',
            'holiday_date' => $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = '+1 years'),
            'holiday_name' => $this->faker->words(3, true),
            'early_close_time' => null,
            'source' => 'test',
            'is_active' => true,
        ];
    }
}
