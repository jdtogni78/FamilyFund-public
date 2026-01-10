<?php

namespace Database\Factories;

use App\Models\AssetPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetPriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AssetPrice::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'asset_id' => AssetFactory::new(),
            'price' => $this->faker->randomFloat(2),
            'start_dt' => $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now'),
        ];
    }
}
