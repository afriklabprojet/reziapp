<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaticPagesSeoSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'route_name'       => 'home',
                'url_pattern'      => '/',
                'page_type'        => 'home',
                'locale'           => 'fr',
                'meta_title'       => 'ReziApp – Location de résidences meublés à Abidjan',
                'meta_description' => 'Trouvez votre résidence meublée idéale à Abidjan. Studios, appartements, villas disponibles en location courte et longue durée. Réservation en ligne sécurisée.',
                'keywords'         => ['location meublée', 'appartement Abidjan', 'résidence meublée', 'location Côte d\'Ivoire', 'ReziApp'],
                'og_data'          => [
                    'title'       => 'ReziApp – Location de résidences meublés à Abidjan',
                    'description' => 'Trouvez votre résidence meublée idéale à Abidjan. Studios, appartements, villas disponibles.',
                    'og:type'     => 'website',
                    'og:locale'   => 'fr_CI',
                    'og:site_name' => 'ReziApp',
                ],
                'structured_data'  => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'WebSite',
                    'name'     => 'ReziApp',
                    'url'      => 'https://reziapp.ci',
                    'description' => 'Plateforme de location de résidences meublées en Côte d\'Ivoire',
                    'potentialAction' => [
                        '@type'       => 'SearchAction',
                        'target'      => 'https://reziapp.ci/residences?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                'priority'   => 1.0,
                'is_noindex' => false,
                'is_nofollow' => false,
            ],
            [
                'route_name'       => 'residences.index',
                'url_pattern'      => '/residences',
                'page_type'        => 'listing',
                'locale'           => 'fr',
                'meta_title'       => 'Résidences meublées à louer à Abidjan | ReziApp',
                'meta_description' => 'Découvrez toutes nos résidences meublées disponibles à Abidjan. Filtrez par quartier, type de logement, prix et équipements. Réservez en quelques clics.',
                'keywords'         => ['résidences meublées', 'location Abidjan', 'appartements à louer', 'studios meublés', 'villas meublées'],
                'og_data'          => [
                    'title'   => 'Résidences meublées à louer à Abidjan',
                    'og:type' => 'website',
                ],
                'structured_data'  => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'ItemList',
                    'name'     => 'Résidences meublées disponibles',
                    'description' => 'Liste des résidences meublées à louer à Abidjan',
                ],
                'priority'   => 0.9,
                'is_noindex' => false,
                'is_nofollow' => false,
            ],
            [
                'route_name'       => 'residences.map',
                'url_pattern'      => '/residences/carte',
                'page_type'        => 'search',
                'locale'           => 'fr',
                'meta_title'       => 'Carte des résidences meublées à Abidjan | ReziApp',
                'meta_description' => 'Visualisez toutes les résidences meublées disponibles sur la carte d\'Abidjan. Trouvez facilement un logement dans votre quartier préféré.',
                'keywords'         => ['carte résidences', 'carte logements Abidjan', 'géolocalisation résidences'],
                'og_data'          => [
                    'title'   => 'Carte des résidences meublées à Abidjan',
                    'og:type' => 'website',
                ],
                'priority'   => 0.8,
                'is_noindex' => false,
                'is_nofollow' => false,
            ],
            [
                'route_name'       => 'pages.about',
                'url_pattern'      => '/a-propos',
                'page_type'        => 'static',
                'locale'           => 'fr',
                'meta_title'       => 'À propos de ReziApp – Votre plateforme immobilière en Côte d\'Ivoire',
                'meta_description' => 'ReziApp est la plateforme de référence pour la location de résidences meublées en Côte d\'Ivoire. Découvrez notre mission et nos valeurs.',
                'keywords'         => ['à propos ReziApp', 'plateforme immobilière CI', 'location meublée Abidjan'],
                'og_data'          => [
                    'title'   => 'À propos de ReziApp',
                    'og:type' => 'website',
                ],
                'structured_data'  => [
                    '@context'    => 'https://schema.org',
                    '@type'       => 'AboutPage',
                    'name'        => 'À propos de ReziApp',
                    'description' => 'ReziApp est la plateforme de référence pour la location de résidences meublées en Côte d\'Ivoire.',
                    'url'         => 'https://reziapp.ci/a-propos',
                ],
                'priority'   => 0.5,
                'is_noindex' => false,
                'is_nofollow' => false,
            ],
            [
                'route_name'       => 'pages.contact',
                'url_pattern'      => '/contact',
                'page_type'        => 'static',
                'locale'           => 'fr',
                'meta_title'       => 'Contactez ReziApp – Support et assistance',
                'meta_description' => 'Besoin d\'aide ? Contactez l\'équipe ReziApp pour toute question sur votre réservation, votre logement ou votre compte propriétaire.',
                'keywords'         => ['contact ReziApp', 'support ReziApp', 'assistance location'],
                'og_data'          => [
                    'title'   => 'Contactez ReziApp',
                    'og:type' => 'website',
                ],
                'structured_data'  => [
                    '@context'   => 'https://schema.org',
                    '@type'      => 'ContactPage',
                    'name'       => 'Contact ReziApp',
                    'url'        => 'https://reziapp.ci/contact',
                ],
                'priority'   => 0.5,
                'is_noindex' => false,
                'is_nofollow' => false,
            ],
            [
                'route_name'       => 'pages.faq',
                'url_pattern'      => '/faq',
                'page_type'        => 'static',
                'locale'           => 'fr',
                'meta_title'       => 'FAQ – Questions fréquentes sur ReziApp',
                'meta_description' => 'Trouvez les réponses à vos questions sur la réservation, le paiement, l\'annulation et la location de résidences meublées sur ReziApp.',
                'keywords'         => ['FAQ ReziApp', 'questions fréquentes', 'aide location', 'comment réserver'],
                'og_data'          => [
                    'title'   => 'FAQ – Questions fréquentes | ReziApp',
                    'og:type' => 'website',
                ],
                'structured_data'  => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'FAQPage',
                    'name'     => 'Questions fréquentes ReziApp',
                ],
                'priority'   => 0.6,
                'is_noindex' => false,
                'is_nofollow' => false,
            ],
            [
                'route_name'       => 'pages.guide-proprietaire',
                'url_pattern'      => '/guide-proprietaire',
                'page_type'        => 'static',
                'locale'           => 'fr',
                'meta_title'       => 'Guide propriétaire – Mettre en location sur ReziApp',
                'meta_description' => 'Découvrez comment mettre votre résidence en location sur ReziApp. Publiez votre annonce, gérez vos réservations et maximisez vos revenus locatifs en Côte d\'Ivoire.',
                'keywords'         => ['propriétaire ReziApp', 'mettre en location', 'guide propriétaire', 'revenus locatifs Abidjan'],
                'og_data'          => [
                    'title'   => 'Guide propriétaire | ReziApp',
                    'og:type' => 'website',
                ],
                'structured_data'  => [
                    '@context' => 'https://schema.org',
                    '@type'    => 'HowTo',
                    'name'     => 'Comment mettre votre résidence en location sur ReziApp',
                ],
                'priority'   => 0.7,
                'is_noindex' => false,
                'is_nofollow' => false,
            ],
        ];

        foreach ($pages as $i => $page) {
            // Pages statiques : seoable_type='page', seoable_id = index 1..N
            $key = ['route_name' => $page['route_name'], 'locale' => $page['locale']];

            // On utilise updateOrInsert pour contourner la contrainte unique sur (seoable_type, seoable_id, locale)
            $existing = DB::table('seo_data')
                ->where('route_name', $page['route_name'])
                ->where('locale', $page['locale'])
                ->first();

            $data = array_merge($page, [
                'seoable_type'   => 'page',
                'seoable_id'     => $existing->seoable_id ?? ($i + 1),
                'keywords'       => json_encode($page['keywords']),
                'og_data'        => json_encode($page['og_data']),
                'structured_data' => isset($page['structured_data']) ? json_encode($page['structured_data']) : null,
                'updated_at'     => now(),
                'created_at'     => now(),
            ]);

            if ($existing) {
                DB::table('seo_data')->where('id', $existing->id)->update($data);
            } else {
                DB::table('seo_data')->insert($data);
            }
        }

        $this->command->info('✅ SEO données seedées pour '.count($pages).' pages statiques.');
    }
}
