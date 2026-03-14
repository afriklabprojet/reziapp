<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Amenity>
 */
class AmenityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amenity = $this->faker->randomElement([
            ['name' => 'WiFi', 'icon' => '📶'],
            ['name' => 'Climatisation', 'icon' => '❄️'],
            ['name' => 'Piscine', 'icon' => '🏊'],
            ['name' => 'Parking', 'icon' => '🚗'],
            ['name' => 'Sécurité 24/7', 'icon' => '🔒'],
            ['name' => 'Cuisine équipée', 'icon' => '🍳'],
            ['name' => 'Balcon', 'icon' => '🏡'],
            ['name' => 'Jardin', 'icon' => '🌳'],
            ['name' => 'Ascenseur', 'icon' => '🛗'],
            ['name' => 'Lave-linge', 'icon' => '🧺'],
        ]);

        return [
            'name' => $amenity['name'],
            'icon' => $amenity['icon'],
        ];
    }
}
