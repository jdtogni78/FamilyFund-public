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
            // Don't auto-create fund_id here - let it be set by the caller or relationship
            // This prevents creating an extra fund when using Fund::factory()->has(Portfolio::factory())
            'fund_id' => null,
            'source' => $this->faker->unique()->word . '_' . $this->faker->unique()->randomNumber(5),
        ];
    }

    /**
     * State to associate with a specific fund
     */
    public function forFund($fund): static
    {
        return $this->state(fn (array $attributes) => [
            'fund_id' => is_object($fund) ? $fund->id : $fund,
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (PortfolioExt $portfolio) {
            // Sync fund to pivot table if fund_id is set
            if ($portfolio->fund_id) {
                $portfolio->funds()->syncWithoutDetaching([$portfolio->fund_id]);
            }
        });
    }
}
