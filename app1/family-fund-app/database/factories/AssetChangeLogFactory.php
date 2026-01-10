<?php

namespace Database\Factories;

use App\Models\AssetChangeLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetChangeLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AssetChangeLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'asset_id' => AssetFactory::new(),
            'action' => $this->faker->word,
            'field' => $this->faker->text,
            'content' => $this->faker->text,
            'datetime' => $this->faker->date('Y-m-d H:i:s'),
        ];
    }
}
