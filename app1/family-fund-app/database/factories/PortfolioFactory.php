<?php

namespace Database\Factories;

use App\Models\PortfolioExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class PortfolioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PortfolioExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'fund_id' => FundFactory::new(),
            'source' => $this->faker->word,
        ];
    }
}
