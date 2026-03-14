<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class PageContent extends Model
{
    protected $fillable = [
        'page_slug',
        'page_title',
        'data',
        'meta_title',
        'meta_description',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get page content by slug with caching
     */
    public static function getBySlug(string $slug): ?self
    {
        return Cache::remember(
            "page_content.{$slug}",
            static::CACHE_TTL,
            fn () => static::where('page_slug', $slug)->where('is_active', true)->first()
        );
    }

    /**
     * Clear cache for this page
     */
    public function clearCache(): void
    {
        Cache::forget("page_content.{$this->page_slug}");
    }

    /**
     * Get a nested data value using dot notation
     */
    public function getSection(string $section, $default = []): mixed
    {
        return data_get($this->data, $section, $default);
    }

    /**
     * Editor relationship
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Boot: clear cache on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function (self $page) {
            $page->clearCache();
        });
    }

    /**
     * Default data structure for 'about' page
     */
    public static function defaultAboutData(): array
    {
        return [
            'hero' => [
                'title' => 'Trouver un logement meublé en Afrique de l\'Ouest,',
                'highlight' => 'simplement.',
                'description' => "REZI est la plateforme de référence pour la recherche géolocalisée de résidences meublées en Côte d'Ivoire. Notre mission : rendre la recherche de logement simple, transparente et accessible à tous.",
                'cta_primary' => 'Explorer les résidences',
                'cta_secondary' => 'Nous contacter',
            ],
            'mission' => [
                'label' => 'Notre Mission',
                'title' => 'Simplifier la recherche de logement meublé en Afrique de l\'Ouest',
                'paragraphs' => [
                    "En Afrique de l'Ouest, trouver un logement meublé de qualité peut être un véritable parcours du combattant. Entre les arnaques, les informations incomplètes et le manque de transparence, les locataires perdent un temps précieux.",
                    "REZI est né de cette frustration. Nous avons créé une plateforme qui met la technologie au service de la recherche immobilière, avec des outils de géolocalisation avancés, des photos vérifiées et un système d'avis transparent.",
                    "Que vous soyez un expatrié, un professionnel en déplacement ou un étudiant, REZI vous accompagne pour trouver le logement qui correspond à vos besoins — rapidement et en toute confiance.",
                ],
                'features' => [
                    ['title' => 'Géolocalisation', 'description' => 'Recherche sur carte interactive avec filtres par zone', 'color' => 'orange'],
                    ['title' => 'Annonces vérifiées', 'description' => 'Chaque résidence est modérée avant publication', 'color' => 'emerald'],
                    ['title' => 'Avis réels', 'description' => 'Retours authentiques des locataires vérifiés', 'color' => 'yellow'],
                    ['title' => 'Contact direct', 'description' => 'Messagerie intégrée avec les propriétaires', 'color' => 'blue'],
                ],
            ],
            'steps' => [
                'title' => 'En 3 étapes simples',
                'subtitle' => "De la recherche à l'emménagement, REZI vous accompagne à chaque étape.",
                'items' => [
                    ['title' => 'Recherchez', 'description' => "Explorez les résidences meublées par commune, budget ou sur la carte interactive."],
                    ['title' => 'Contactez', 'description' => 'Échangez directement avec le propriétaire via notre messagerie sécurisée et planifiez une visite.'],
                    ['title' => 'Emménagez', 'description' => 'Réservez en ligne, payez en toute sécurité et installez-vous dans votre nouveau chez-vous.'],
                ],
            ],
            'values' => [
                'title' => 'Ce qui nous guide au quotidien',
                'items' => [
                    ['title' => 'Transparence', 'description' => "Des informations claires et vérifiées sur chaque résidence. Pas de surprises, pas de frais cachés. Chaque annonce est modérée par notre équipe."],
                    ['title' => 'Communauté', 'description' => "Nous construisons un écosystème de confiance entre propriétaires et locataires. Les avis vérifiés aident chacun à prendre les bonnes décisions."],
                    ['title' => 'Innovation', 'description' => "Carte interactive, notifications en temps réel, paiement sécurisé — nous utilisons la technologie pour transformer l'immobilier en Côte d'Ivoire."],
                ],
            ],
            'why' => [
                'title' => 'Ce qui nous différencie',
                'items' => [
                    ['title' => 'Gain de temps', 'description' => 'Filtres avancés, carte interactive et alertes personnalisées. Trouvez en minutes ce qui prenait des jours.'],
                    ['title' => 'Sécurité maximale', 'description' => 'Paiements sécurisés, identités vérifiées, annonces modérées. Votre tranquillité est notre priorité.'],
                    ['title' => 'Support réactif', 'description' => "Une équipe dédiée disponible par email, téléphone ou via la plateforme."],
                    ['title' => '100% mobile', 'description' => "Application web progressive (PWA) installable, pensée pour le mobile. Cherchez où que vous soyez."],
                ],
            ],
            'cta' => [
                'title' => 'Prêt à trouver votre logement ?',
                'description' => "Rejoignez des centaines d'utilisateurs qui ont déjà trouvé leur résidence idéale sur REZI.",
                'cta_primary' => 'Chercher une résidence',
                'cta_secondary' => 'Créer un compte gratuit',
            ],
        ];
    }

    /**
     * Default data structure for 'contact' page
     */
    public static function defaultContactData(): array
    {
        return [
            'hero' => [
                'title' => 'Une question ?',
                'highlight' => 'Parlons-en.',
                'description' => "Notre équipe est disponible pour vous accompagner dans votre recherche de logement meublé ou pour toute question sur la plateforme.",
            ],
            'cards' => [
                'email' => config('rezi.company.email', 'contact@rezi.ci'),
                'email_subtitle' => 'Réponse sous 24h',
                'phone' => config('rezi.company.phone', '+225 07 00 00 00 00'),
                'phone_raw' => config('rezi.company.phone_raw', '+22507000000000'),
                'phone_subtitle' => 'Lun – Sam · 8h – 18h',
                'whatsapp_number' => str_replace('+', '', config('rezi.company.phone_raw', '22507000000000')),
                'whatsapp_label' => 'Discuter maintenant',
                'whatsapp_subtitle' => 'Réponse rapide',
                'address_title' => 'Bureau',
                'address_line1' => 'Cocody, Riviera Palmeraie',
                'address_line2' => config('rezi.company.address', "Abidjan, Côte d'Ivoire"),
            ],
            'faq' => [
                'title' => 'On répond à vos questions',
                'subtitle' => "Retrouvez les réponses aux questions les plus courantes. Si vous ne trouvez pas votre réponse, n'hésitez pas à nous contacter directement.",
                'items' => [
                    [
                        'question' => 'REZI est-il gratuit pour les locataires ?',
                        'answer' => "Oui ! La recherche et la consultation des annonces sont entièrement gratuites. Vous ne payez que lorsque vous effectuez une réservation.",
                    ],
                    [
                        'question' => 'Comment publier mon logement sur REZI ?',
                        'answer' => "Créez un compte propriétaire, ajoutez les détails de votre résidence (photos, description, prix) et soumettez votre annonce. Notre équipe la vérifie sous 24h avant publication.",
                    ],
                    [
                        'question' => 'Les annonces sont-elles vérifiées ?',
                        'answer' => "Absolument. Chaque annonce passe par un processus de modération avant d'être visible. Nous vérifions les informations, les photos et la conformité du logement.",
                    ],
                    [
                        'question' => 'Quels moyens de paiement acceptez-vous ?',
                        'answer' => 'Nous acceptons le paiement par Mobile Money (Orange Money, MTN Money, Moov Money, Wave) directement via la plateforme, en toute sécurité.',
                    ],
                    [
                        'question' => 'Comment signaler un problème avec un logement ?',
                        'answer' => "Vous pouvez nous contacter par email, téléphone ou WhatsApp. Vous pouvez aussi utiliser le bouton \"Signaler\" présent sur chaque annonce. Notre équipe traite les signalements sous 24h.",
                    ],
                ],
            ],
            'hours' => [
                'title' => 'Quand nous joindre',
                'items' => [
                    ['day' => 'Lundi – Vendredi', 'hours' => '8h00 – 18h00', 'open' => true],
                    ['day' => 'Samedi', 'hours' => '9h00 – 15h00', 'open' => true],
                    ['day' => 'Dimanche', 'hours' => 'Fermé', 'open' => false],
                ],
                'note' => "Les urgences liées à une réservation en cours sont traitées 7j/7 par email.",
            ],
            'cta' => [
                'title' => 'Vous êtes propriétaire ?',
                'description' => "Publiez votre résidence gratuitement et touchez des milliers de locataires potentiels.",
                'cta_primary' => 'Guide propriétaire',
                'cta_secondary' => 'Créer un compte',
            ],
        ];
    }

    /**
     * Default data structure for 'cgu' page
     */
    public static function defaultCguData(): array
    {
        return [
            'title' => "Conditions Générales d'Utilisation",
            'sections' => [
                [
                    'title' => '1. Objet',
                    'content' => "Les présentes Conditions Générales d'Utilisation (ci-après « CGU ») ont pour objet de définir les modalités et conditions d'utilisation de la plateforme REZI (ci-après « la Plateforme »), accessible à l'adresse rezi.ci, ainsi que les droits et obligations des parties dans le cadre de l'utilisation de ses services.\n\nREZI est une plateforme de mise en relation entre propriétaires de résidences meublées et locataires potentiels, opérant principalement à Abidjan, Côte d'Ivoire.",
                ],
                [
                    'title' => '2. Acceptation des CGU',
                    'content' => "L'utilisation de la Plateforme implique l'acceptation pleine et entière des présentes CGU. En vous inscrivant ou en utilisant nos services, vous reconnaissez avoir pris connaissance de ces conditions et les accepter sans réserve.",
                ],
                [
                    'title' => '3. Inscription et Compte Utilisateur',
                    'content' => "Pour accéder à certaines fonctionnalités de la Plateforme, l'utilisateur doit créer un compte en fournissant des informations exactes et complètes. L'utilisateur est responsable de la confidentialité de ses identifiants de connexion et de toute activité effectuée depuis son compte.",
                ],
                [
                    'title' => '4. Services Proposés',
                    'content' => "REZI propose les services suivants :\n• Pour les locataires : Recherche géolocalisée de résidences meublées, consultation des annonces, contact avec les propriétaires, système d'avis.\n• Pour les propriétaires : Publication d'annonces, gestion de résidences, tableau de bord statistique, outils marketing.",
                ],
                [
                    'title' => '5. Obligations des Utilisateurs',
                    'content' => "Les utilisateurs s'engagent à :\n• Ne pas publier de contenu illicite, trompeur ou portant atteinte aux droits de tiers.\n• Respecter les autres utilisateurs de la Plateforme.\n• Fournir des informations exactes concernant les résidences publiées.\n• Ne pas utiliser la Plateforme à des fins frauduleuses.",
                ],
                [
                    'title' => '6. Responsabilité',
                    'content' => "REZI agit en tant qu'intermédiaire technique et n'est pas partie aux transactions entre propriétaires et locataires. REZI ne peut être tenu responsable de la qualité des résidences, de la véracité des annonces ou du comportement des utilisateurs.",
                ],
                [
                    'title' => '7. Propriété Intellectuelle',
                    'content' => "L'ensemble du contenu de la Plateforme (textes, images, logos, code source) est protégé par le droit de la propriété intellectuelle. Toute reproduction, même partielle, est interdite sans autorisation écrite préalable de REZI.",
                ],
                [
                    'title' => '8. Modification des CGU',
                    'content' => "REZI se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs seront informés des modifications par notification sur la Plateforme. La poursuite de l'utilisation vaut acceptation des nouvelles conditions.",
                ],
                [
                    'title' => '9. Droit Applicable',
                    'content' => "Les présentes CGU sont régies par le droit ivoirien. En cas de litige, les tribunaux d'Abidjan seront seuls compétents, après tentative de résolution amiable.",
                ],
            ],
        ];
    }

    /**
     * Default data structure for 'confidentialite' page
     */
    public static function defaultConfidentialiteData(): array
    {
        return [
            'title' => 'Politique de Confidentialité',
            'sections' => [
                [
                    'title' => '1. Collecte des données',
                    'content' => "REZI collecte les données personnelles que vous fournissez lors de votre inscription et utilisation de la plateforme : nom, email, téléphone, adresse, photos de résidences, données de localisation.",
                ],
                [
                    'title' => '2. Utilisation des données',
                    'content' => "Vos données sont utilisées pour :\n• Gérer votre compte utilisateur\n• Permettre la mise en relation entre propriétaires et locataires\n• Améliorer nos services\n• Vous envoyer des notifications relatives à votre compte",
                ],
                [
                    'title' => '3. Partage des données',
                    'content' => "Vos données ne sont jamais vendues à des tiers. Elles peuvent être partagées avec :\n• Les autres utilisateurs de la plateforme (nom, photo pour les échanges)\n• Nos prestataires techniques (hébergement, paiement)\n• Les autorités si la loi l'exige",
                ],
                [
                    'title' => '4. Sécurité',
                    'content' => "Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos données : chiffrement SSL, accès restreint, sauvegardes régulières.",
                ],
                [
                    'title' => '5. Vos droits',
                    'content' => "Conformément à la réglementation, vous disposez d'un droit d'accès, de rectification et de suppression de vos données. Contactez-nous à contact@rezi.ci pour exercer ces droits.",
                ],
                [
                    'title' => '6. Cookies',
                    'content' => "REZI utilise des cookies pour améliorer votre expérience de navigation et analyser l'utilisation du site. Vous pouvez configurer votre navigateur pour refuser les cookies.",
                ],
            ],
        ];
    }

    /**
     * Default data structure for 'mentions-legales' page
     */
    public static function defaultMentionsLegalesData(): array
    {
        return [
            'title' => 'Mentions Légales',
            'sections' => [
                [
                    'title' => 'Éditeur du site',
                    'content' => "REZI SAS\nSiège social : Cocody, Riviera Palmeraie, Abidjan, Côte d'Ivoire\nEmail : contact@rezi.ci\nTéléphone : +225 07 07 07 07 07",
                ],
                [
                    'title' => 'Directeur de la publication',
                    'content' => "Le directeur de la publication est le représentant légal de REZI SAS.",
                ],
                [
                    'title' => 'Hébergement',
                    'content' => "Le site est hébergé par :\nHetzner Online GmbH\nIndustriestr. 25, 91710 Gunzenhausen, Allemagne",
                ],
                [
                    'title' => 'Propriété intellectuelle',
                    'content' => "L'ensemble du contenu de ce site (textes, images, logos, code) est protégé par le droit de la propriété intellectuelle. Toute reproduction non autorisée est interdite.",
                ],
            ],
        ];
    }

    /**
     * Default data structure for 'faq' page
     */
    public static function defaultFaqData(): array
    {
        return [
            'title' => 'Questions Fréquentes',
            'subtitle' => 'Trouvez rapidement les réponses à vos questions sur REZI',
            'categories' => [
                [
                    'name' => 'Pour les Locataires',
                    'icon' => '🏠',
                    'color' => 'orange',
                    'questions' => [
                        ['q' => 'Comment rechercher une résidence sur REZI ?', 'a' => "Utilisez la barre de recherche sur la page d'accueil ou la carte interactive pour trouver des résidences par commune, type de logement ou budget."],
                        ['q' => 'Est-ce que REZI est gratuit pour les locataires ?', 'a' => 'Oui, la recherche et la consultation des annonces sur REZI sont entièrement gratuites.'],
                        ['q' => 'Comment contacter un propriétaire ?', 'a' => 'Sur la page de chaque résidence, cliquez sur le bouton « Contacter le propriétaire ». Un compte est nécessaire pour cette fonctionnalité.'],
                        ['q' => 'Les résidences sont-elles vérifiées ?', 'a' => 'Toutes les annonces publiées sur REZI passent par un processus de modération avant d\'être visibles.'],
                    ],
                ],
                [
                    'name' => 'Pour les Propriétaires',
                    'icon' => '🔑',
                    'color' => 'blue',
                    'questions' => [
                        ['q' => 'Comment publier une annonce sur REZI ?', 'a' => "Créez un compte propriétaire, puis accédez à votre tableau de bord. Cliquez sur « Ajouter une résidence » et suivez l'assistant de publication."],
                        ['q' => 'La publication est-elle gratuite ?', 'a' => 'Oui, la publication d\'annonces est gratuite. Des options payantes de mise en avant sont disponibles.'],
                        ['q' => 'Comment augmenter la visibilité de mon annonce ?', 'a' => 'Ajoutez des photos de qualité, une description détaillée et répondez rapidement aux contacts.'],
                    ],
                ],
                [
                    'name' => 'Paiement & Sécurité',
                    'icon' => '💳',
                    'color' => 'emerald',
                    'questions' => [
                        ['q' => 'Quels moyens de paiement acceptez-vous ?', 'a' => 'Nous acceptons Orange Money, MTN Money, Moov Money et Wave.'],
                        ['q' => 'Les paiements sont-ils sécurisés ?', 'a' => 'Oui, tous les paiements sont traités via des passerelles de paiement sécurisées et certifiées.'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Default data structure for 'guide-proprietaire' page
     */
    public static function defaultGuideProprietaireData(): array
    {
        return [
            'title' => 'Guide Propriétaire',
            'subtitle' => 'Tout ce que vous devez savoir pour publier et gérer vos résidences sur REZI',
            'steps' => [
                [
                    'number' => 1,
                    'title' => 'Créer votre compte propriétaire',
                    'content' => "Inscrivez-vous sur REZI et sélectionnez le rôle « Propriétaire ». Complétez votre profil avec vos informations de contact et une photo. Un profil complet inspire confiance aux locataires potentiels.",
                    'tip' => 'Un profil vérifié avec photo obtient en moyenne 3x plus de contacts.',
                ],
                [
                    'number' => 2,
                    'title' => 'Publier votre première résidence',
                    'content' => "Depuis votre tableau de bord, cliquez sur « Ajouter une résidence ». L'assistant de publication vous guide en 5 étapes simples.",
                    'substeps' => [
                        'Informations générales — Nom, catégorie, description, nombre de pièces.',
                        'Localisation — Commune, quartier et position exacte sur la carte.',
                        'Photos — Ajoutez jusqu\'à 10 photos de qualité.',
                        'Tarification — Prix par jour, semaine et mois.',
                        'Confirmation — Vérifiez et soumettez.',
                    ],
                ],
                [
                    'number' => 3,
                    'title' => 'Optimiser votre annonce',
                    'content' => "Pour maximiser vos contacts et votre visibilité :",
                    'tips' => [
                        'Photos de qualité — Des photos lumineuses augmentent les contacts de 70%.',
                        'Description détaillée — Décrivez les équipements, le quartier, les commodités.',
                        'Prix compétitif — Consultez les résidences similaires.',
                        'Réactivité — Répondez rapidement aux contacts.',
                    ],
                ],
                [
                    'number' => 4,
                    'title' => 'Utiliser les outils marketing',
                    'content' => 'REZI propose plusieurs outils pour booster votre visibilité.',
                    'tools' => [
                        ['name' => 'Sponsoring', 'icon' => '🚀', 'description' => 'Mettez votre résidence en avant dans les résultats de recherche.'],
                        ['name' => 'Promotions', 'icon' => '🎁', 'description' => 'Créez des offres spéciales pour attirer plus de locataires.'],
                        ['name' => 'Statistiques', 'icon' => '📊', 'description' => 'Suivez les performances de vos annonces en temps réel.'],
                    ],
                ],
            ],
        ];
    }
}
