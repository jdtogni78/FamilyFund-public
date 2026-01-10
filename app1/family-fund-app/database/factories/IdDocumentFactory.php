<?php

namespace Database\Factories;

use App\Models\IdDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class IdDocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IdDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'person_id' => PersonFactory::new(),
            'type' => $this->faker->randomElement(['CPF', 'RG', 'CNH', 'Passport', 'SSN', 'other']),
            'number' => $this->faker->numerify('###-##-####'),
        ];
    }
}
