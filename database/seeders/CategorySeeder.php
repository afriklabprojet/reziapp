<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Residence;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the categories table.
     */
    public function run(): void
    {
        $this->command->info('🏠 Seeding categories...');

        $categoriesData = [
            [
                'name' => 'Appartement',
                'slug' => 'appartement',
                'description' => 'Appartements modernes et confortables en ville',
                'icon' => 'building',
                'color' => '#3b82f6',
                'sort_order' => 1,
            ],
            [
                'name' => 'Villa',
                'slug' => 'villa',
                'description' => 'Villas spacieuses avec jardin et piscine',
                'icon' => 'home',
                'color' => '#10b981',
                'sort_order' => 2,
            ],
            [
                'name' => 'Studio',
                'slug' => 'studio',
                'description' => 'Studios pratiques pour une personne ou un couple',
                'icon' => 'door-open',
                'color' => '#f97316',
                'sort_order' => 3,
            ],
            [
                'name' => 'Duplex',
                'slug' => 'duplex',
                'description' => 'Duplex sur deux niveaux avec grand espace',
                'icon' => 'layer-group',
                'color' => '#8b5cf6',
                'sort_order' => 4,
            ],
            [
                'name' => 'Chambre meublée',
                'slug' => 'chambre-meublee',
                'description' => 'Chambres meublées dans une colocation ou résidence',
                'icon' => 'bed',
                'color' => '#ec4899',
                'sort_order' => 5,
            ],
            [
                'name' => 'Maison',
                'slug' => 'maison',
                'description' => 'Maisons familiales avec plusieurs chambres',
                'icon' => 'house-user',
                'color' => '#14b8a6',
                'sort_order' => 6,
            ],
            [
                'name' => 'Penthouse',
                'slug' => 'penthouse',
                'description' => 'Appartements de luxe au dernier étage',
                'icon' => 'crown',
                'color' => '#eab308',
                'sort_order' => 7,
            ],
            [
                'name' => 'Résidence hôtelière',
                'slug' => 'residence-hoteliere',
                'description' => 'Résidences avec services hôteliers inclus',
                'icon' => 'concierge-bell',
                'color' => '#6366f1',
                'sort_order' => 8,
            ],
        ];

        foreach ($categoriesData as $data) {
            Category::firstOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }

        $this->command->info('✅ '.Category::count().' categories created!');

        // Assigner des catégories aux résidences existantes
        $categories = Category::all();
        $residencesWithoutCategory = Residence::whereNull('category_id')->get();

        if ($residencesWithoutCategory->count() > 0) {
            $this->command->info('🔗 Assigning categories to '.$residencesWithoutCategory->count().' residences...');

            foreach ($residencesWithoutCategory as $residence) {
                $residence->update([
                    'category_id' => $categories->random()->id,
                ]);
            }

            $this->command->info('✅ Categories assigned!');
        }
    }
}
