<?php

namespace Database\Seeders;

use App\Models\PageContent;
use Illuminate\Database\Seeder;

class PageContentSeeder extends Seeder
{
    public function run(): void
    {
        PageContent::updateOrCreate(
            ['page_slug' => 'about'],
            [
                'page_title' => 'À propos de ReziApp',
                'data' => PageContent::defaultAboutData(),
                'meta_title' => 'À propos de ReziApp – Location meublée à Abidjan',
                'meta_description' => "Découvrez ReziApp, la plateforme de référence pour la location de résidences meublées à Abidjan. Notre mission : simplifier la recherche de logement en Côte d'Ivoire.",
                'is_active' => true,
            ],
        );

        PageContent::updateOrCreate(
            ['page_slug' => 'contact'],
            [
                'page_title' => 'Nous contacter',
                'data' => PageContent::defaultContactData(),
                'meta_title' => 'Nous Contacter – ReziApp',
                'meta_description' => "Contactez l'équipe ReziApp par email, téléphone, WhatsApp ou réseaux sociaux. Nous sommes à votre écoute pour toute question sur la location meublée à Abidjan.",
                'is_active' => true,
            ],
        );

        PageContent::updateOrCreate(
            ['page_slug' => 'cgu'],
            [
                'page_title' => "Conditions Générales d'Utilisation",
                'data' => PageContent::defaultCguData(),
                'meta_title' => "CGU – Conditions Générales d'Utilisation – ReziApp",
                'meta_description' => "Consultez les conditions générales d'utilisation de la plateforme ReziApp pour la location de résidences meublées.",
                'is_active' => true,
            ],
        );

        PageContent::updateOrCreate(
            ['page_slug' => 'confidentialite'],
            [
                'page_title' => 'Politique de Confidentialité',
                'data' => PageContent::defaultConfidentialiteData(),
                'meta_title' => 'Politique de Confidentialité – ReziApp',
                'meta_description' => 'Découvrez comment ReziApp collecte, utilise et protège vos données personnelles.',
                'is_active' => true,
            ],
        );

        PageContent::updateOrCreate(
            ['page_slug' => 'mentions-legales'],
            [
                'page_title' => 'Mentions Légales',
                'data' => PageContent::defaultMentionsLegalesData(),
                'meta_title' => 'Mentions Légales – ReziApp',
                'meta_description' => 'Mentions légales de la plateforme ReziApp : éditeur, hébergement, propriété intellectuelle.',
                'is_active' => true,
            ],
        );

        PageContent::updateOrCreate(
            ['page_slug' => 'faq'],
            [
                'page_title' => 'Foire Aux Questions',
                'data' => PageContent::defaultFaqData(),
                'meta_title' => 'FAQ – Questions Fréquentes – ReziApp',
                'meta_description' => "Trouvez les réponses à vos questions sur ReziApp : réservation, paiement, publication d'annonces et plus encore.",
                'is_active' => true,
            ],
        );

        PageContent::updateOrCreate(
            ['page_slug' => 'guide-proprietaire'],
            [
                'page_title' => 'Guide Propriétaire',
                'data' => PageContent::defaultGuideProprietaireData(),
                'meta_title' => 'Guide Propriétaire – ReziApp',
                'meta_description' => 'Guide complet pour publier et gérer votre résidence sur ReziApp. Optimisez votre annonce et augmentez vos réservations.',
                'is_active' => true,
            ],
        );

        PageContent::updateOrCreate(
            ['page_slug' => 'tarifs'],
            [
                'page_title' => 'Tarifs',
                'data' => PageContent::defaultTarifsData(),
                'meta_title' => 'Tarifs ReziApp – Publication gratuite, 0 commission',
                'meta_description' => 'Publiez votre résidence meublée gratuitement sur ReziApp. Aucune commission sur la mise en location, options de visibilité disponibles.',
                'is_active' => true,
            ],
        );
    }
}
