<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Residence>
 */
class ResidenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $communes = [
            'Cocody' => ['Riviera', '2 Plateaux', 'Angré', 'Blockauss'],
            'Plateau' => ['Centre', 'Commerce', 'Indénié'],
            'Marcory' => ['Zone 4', 'Biétry', 'Anoumabo'],
            'Yopougon' => ['Maroc', 'Sicogi', 'Niangon'],
            'Adjamé' => ['Liberté', '220 Logements', 'Williamsville'],
            'Koumassi' => ['Sicogi', 'Remblais', 'Grand Campement'],
            'Treichville' => ['Avenue 12', 'Belleville', 'France-Amérique'],
        ];

        $commune = $this->faker->randomElement(array_keys($communes));
        $quartier = $this->faker->randomElement($communes[$commune]);

        $name = $this->faker->randomElement([
            'Belle Villa',
            'Appartement Moderne',
            'Résidence de Luxe',
            'Maison Familiale',
            'Studio Cosy',
        ]).' '.$commune;

        return [
            'owner_id' => User::factory()->state(['role' => 'owner']),
            'name' => $name,
            'description' => $this->faker->paragraphs(3, true),
            'address' => $this->faker->streetAddress(),
            'country_code' => 'CI',
            'city' => 'Abidjan',
            'commune' => $commune,
            'quartier' => $quartier,

            // Coordonnées dans la zone d'Abidjan
            'latitude' => $this->faker->latitude(5.28, 5.42),
            'longitude' => $this->faker->longitude(-4.10, -3.88),

            // Prix
            'price_per_month' => $this->faker->randomElement([100000, 150000, 200000, 250000, 300000, 400000, 500000]),
            'price_per_week' => null,
            'price_per_day' => null,

            // Détails
            'bedrooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->numberBetween(1, 3),
            'type' => $this->faker->randomElement(['studio', 'apartment', 'house', 'villa', 'duplex']),
            'surface_area' => $this->faker->randomElement([25, 40, 60, 80, 100, 150, 200]),
            'max_guests' => $this->faker->randomElement([2, 4, 4, 6, 6, 8, 10]),

            // Statut
            'status' => 'active',
            'is_available' => $this->faker->boolean(90),

            // Statistiques
            'views_count' => $this->faker->numberBetween(0, 500),
            'contacts_count' => $this->faker->numberBetween(0, 50),
        ];
    }

    /**
     * État: En attente d'approbation
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * État: Rejetée
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * État: Non disponible
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    /**
     * État: Résidence au Burkina Faso (Ouagadougou)
     */
    public function inBurkinaFaso(): static
    {
        $communes = ['Bogodogo', 'Baskuy', 'Boulmiougou', 'Nongremassom', 'Sig-Nonghin'];

        return $this->state(fn (array $attributes) => [
            'country_code' => 'BF',
            'city' => 'Ouagadougou',
            'commune' => $this->faker->randomElement($communes),
            'quartier' => $this->faker->randomElement(['Zone 1', 'Cité', 'Centre']),
            'latitude' => $this->faker->latitude(12.30, 12.42),
            'longitude' => $this->faker->longitude(-1.58, -1.48),
        ]);
    }
}
