<?php

namespace Database\Factories;

use App\Models\Residence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Photo>
 */
class PhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'residence_id' => Residence::factory(),
            'path' => 'residences/'.$this->faker->uuid().'.jpg',
            'is_primary' => false,
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
            'order' => 1,
        ]);
    }
}
