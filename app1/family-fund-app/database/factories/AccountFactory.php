<?php

namespace Database\Factories;

use App\Models\AccountExt;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountExt::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => $this->faker->word,
            'nickname' => $this->faker->word,
            'email_cc' => $this->faker->word . '@dstrader.com',
            'user_id' => null,
            'fund_id' => \App\Models\Fund::factory(),
        ];
    }
}
