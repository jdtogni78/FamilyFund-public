<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'person_id' => $this->faker->word,
        'type' => $this->faker->word,
        'is_primary' => $this->faker->word,
        'street' => $this->faker->word,
        'number' => $this->faker->word,
        'complement' => $this->faker->word,
        'neighborhood' => $this->faker->word,
        'city' => $this->faker->word,
        'state' => $this->faker->word,
        'zip_code' => $this->faker->word,
        'country' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
