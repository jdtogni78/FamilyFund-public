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
            'action' => $this->faker->word,
        //'asset_id' => $this->faker->word,
        'field' => $this->faker->text,
        'content' => $this->faker->text,
        'datetime' => $this->faker->date('Y-m-d H:i:s'),
        //'updated_at' => $this->faker->date('Y-m-d H:i:s'),
        //'created_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
