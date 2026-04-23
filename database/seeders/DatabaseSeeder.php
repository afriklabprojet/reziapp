<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Favorite;
use App\Models\Photo;
use App\Models\Residence;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding REZI database...');

        // 1. Créer les catégories
        $this->command->info('Creating categories...');
        $categories = $this->createCategories();

        // 2. Créer les amenities de base
        $this->command->info('Creating amenities...');
        $amenities = $this->createAmenities();

        // 2b. Créer les plans d'abonnement
        $this->command->info('Creating subscription plans...');
        $this->call(SubscriptionPlanSeeder::class);

        // 3. Créer un admin
        $this->command->info('Creating admin user...');
        $admin = User::factory()->create([
            'name' => 'Admin REZI',
            'email' => 'admin@rezi.ci',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // 3. Créer des propriétaires
        $this->command->info('Creating owners...');
        $owners = User::factory(5)->create([
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        // 4. Créer des utilisateurs (locataires)
        $this->command->info('Creating users...');
        $users = User::factory(10)->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // Ajouter un utilisateur de test
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // 5. Créer des résidences pour chaque propriétaire
        $this->command->info('Creating residences with photos...');

        // Images de résidences réalistes (Unsplash)
        $residenceImages = [
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800',
            'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=800',
            'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=800',
            'https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=800',
            'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800',
            'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=800',
            'https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6?w=800',
            'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=800',
        ];

        $interiorImages = [
            'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800',
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800',
            'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=800',
            'https://images.unsplash.com/photo-1560185127-6ed189bf02f4?w=800',
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800',
        ];

        foreach ($owners as $owner) {
            // Chaque propriétaire a 3-5 résidences
            $residenceCount = rand(3, 5);

            for ($i = 0; $i < $residenceCount; $i++) {
                $residence = Residence::factory()->create([
                    'owner_id' => $owner->id,
                    'category_id' => $categories->random()->id,
                    'status' => 'active',
                    'is_available' => rand(1, 10) > 2, // 80% disponibles
                ]);

                // Ajouter 3-5 photos par résidence
                $photoCount = rand(3, 5);
                for ($j = 0; $j < $photoCount; $j++) {
                    Photo::create([
                        'residence_id' => $residence->id,
                        'path' => $j === 0
                            ? $residenceImages[array_rand($residenceImages)]
                            : $interiorImages[array_rand($interiorImages)],
                        'is_primary' => $j === 0,
                        'order' => $j,
                    ]);
                }

                // Attacher 3-6 amenities aléatoires
                $residence->amenities()->attach(
                    $amenities->random(rand(3, 6))->pluck('id'),
                );
            }
        }

        // 6. Créer des favoris
        $this->command->info('Creating favorites...');
        $allResidences = Residence::all();

        foreach ($users as $user) {
            // Chaque utilisateur a 2-5 favoris
            $favoriteResidences = $allResidences->random(rand(2, min(5, $allResidences->count())));
            foreach ($favoriteResidences as $residence) {
                Favorite::create([
                    'user_id' => $user->id,
                    'residence_id' => $residence->id,
                ]);
            }
        }

        // 7. Créer des contacts/demandes
        $this->command->info('Creating contacts...');
        foreach ($allResidences->random(min(15, $allResidences->count())) as $residence) {
            Contact::create([
                'user_id' => $users->random()->id,
                'residence_id' => $residence->id,
                'owner_id' => $residence->owner_id,
                'message' => 'Bonjour, je suis intéressé(e) par cette résidence. Est-elle toujours disponible ?',
                'phone' => '+225 07 '.rand(10, 99).' '.rand(10, 99).' '.rand(10, 99).' '.rand(10, 99),
                'status' => 'pending',
            ]);

            // Incrémenter le compteur de contacts
            $residence->increment('contacts_count');
        }

        // 8. Créer des avis
        $this->command->info('Creating reviews...');
        if (class_exists(Review::class)) {
            foreach ($allResidences->random(min(10, $allResidences->count())) as $residence) {
                try {
                    Review::create([
                        'user_id' => $users->random()->id,
                        'residence_id' => $residence->id,
                        'rating' => rand(3, 5),
                        'comment' => 'Très belle résidence, bien située et propriétaire agréable.',
                        'is_approved' => true,
                    ]);
                } catch (\Exception $e) {
                    // Skip if Review model doesn't exist or has issues
                }
            }
        }

        // 9. Dashboard admin — réservations, paiements, tickets, etc.
        $this->command->info('Creating admin dashboard data...');
        $this->call(AdminDashboardSeeder::class);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('   - Admin: admin@rezi.ci / password');
        $this->command->info('   - Test User: test@example.com / password');
        $this->command->info('   - Owners: '.$owners->count());
        $this->command->info('   - Users: '.($users->count() + 1));
        $this->command->info('   - Categories: '.Category::count());
        $this->command->info('   - Residences: '.Residence::count());
        $this->command->info('   - Photos: '.Photo::count());
    }

    /**
     * Créer les catégories de résidences
     */
    private function createCategories(): \Illuminate\Database\Eloquent\Collection
    {
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

        return Category::all();
    }

    /**
     * Créer les amenities de base
     */
    private function createAmenities(): \Illuminate\Database\Eloquent\Collection
    {
        $amenitiesData = [
            ['name' => 'WiFi', 'icon' => 'wifi'],
            ['name' => 'Climatisation', 'icon' => 'snowflake'],
            ['name' => 'Parking', 'icon' => 'car'],
            ['name' => 'Piscine', 'icon' => 'swimmer'],
            ['name' => 'Sécurité 24h/24', 'icon' => 'shield'],
            ['name' => 'Cuisine équipée', 'icon' => 'utensils'],
            ['name' => 'Machine à laver', 'icon' => 'tshirt'],
            ['name' => 'Télévision', 'icon' => 'tv'],
            ['name' => 'Eau chaude', 'icon' => 'fire'],
            ['name' => 'Balcon', 'icon' => 'home'],
            ['name' => 'Groupe électrogène', 'icon' => 'bolt'],
            ['name' => 'Gardien', 'icon' => 'user-shield'],
        ];

        foreach ($amenitiesData as $data) {
            Amenity::firstOrCreate(
                ['name' => $data['name']],
                $data,
            );
        }

        return Amenity::all();
    }
}
