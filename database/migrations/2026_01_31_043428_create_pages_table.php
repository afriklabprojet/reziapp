<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('type')->default('page'); // page, faq, legal
            $table->boolean('is_published')->default(false);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // Pages par défaut
        $pages = [
            [
                'title' => 'Conditions Générales d\'Utilisation',
                'slug' => 'cgu',
                'content' => '<h2>1. Objet</h2><p>Les présentes conditions générales d\'utilisation régissent l\'accès et l\'utilisation de la plateforme REZI.</p><h2>2. Inscription</h2><p>Pour utiliser nos services, vous devez créer un compte...</p>',
                'excerpt' => 'Conditions générales d\'utilisation de la plateforme REZI',
                'type' => 'legal',
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Politique de Confidentialité',
                'slug' => 'confidentialite',
                'content' => '<h2>Protection de vos données</h2><p>REZI s\'engage à protéger vos données personnelles...</p>',
                'excerpt' => 'Comment nous protégeons vos données personnelles',
                'type' => 'legal',
                'is_published' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Politique d\'Annulation',
                'slug' => 'annulation',
                'content' => '<h2>Conditions d\'annulation</h2><p>Les conditions d\'annulation varient selon les propriétaires...</p>',
                'excerpt' => 'Nos politiques d\'annulation et de remboursement',
                'type' => 'legal',
                'is_published' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Foire Aux Questions',
                'slug' => 'faq',
                'content' => '<div class="faq-item"><h3>Comment réserver un logement ?</h3><p>Recherchez un logement, sélectionnez vos dates et procédez au paiement.</p></div><div class="faq-item"><h3>Comment devenir propriétaire ?</h3><p>Créez un compte, vérifiez votre identité et publiez votre première annonce.</p></div>',
                'excerpt' => 'Réponses aux questions fréquentes',
                'type' => 'faq',
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'À propos de REZI',
                'slug' => 'a-propos',
                'content' => '<h2>Notre mission</h2><p>REZI est la première plateforme de location de résidences meublées en Côte d\'Ivoire...</p>',
                'excerpt' => 'Découvrez l\'histoire et la mission de REZI',
                'type' => 'page',
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'content' => '<h2>Contactez-nous</h2><p>Email: contact@rezi.ci</p><p>Téléphone: +225 07 00 00 00 00</p><p>Adresse: Abidjan, Côte d\'Ivoire</p>',
                'excerpt' => 'Nos coordonnées de contact',
                'type' => 'page',
                'is_published' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($pages as $page) {
            DB::table('pages')->insert(array_merge($page, [
                'created_at' => now(),
                'updated_at' => now(),
                'published_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
