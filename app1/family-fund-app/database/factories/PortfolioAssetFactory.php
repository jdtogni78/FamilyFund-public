<?php

namespace Database\Factories;

use App\Models\PortfolioAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioAssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PortfolioAsset::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //'portfolio_id' => $this->faker->word,
        //'asset_id' => $this->faker->word,
        'position' => $this->faker->randomFloat(4, 0.0001),
        'start_dt' => $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now'),
//        'end_dt' => $this->faker->dateTimeBetween($startDate = '+1 months', $endDate = '+3 months'),
        //'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        //'created_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
