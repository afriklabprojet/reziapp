<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Salutation
            [
                'name' => 'Bienvenue standard',
                'content' => "Bonjour {user_name},\n\nMerci de votre intérêt pour {residence_name}.\n\nJe suis disponible pour répondre à toutes vos questions et organiser une visite si vous le souhaitez.\n\nBien cordialement,\n{owner_name}",
                'category' => 'greeting',
                'shortcut' => 'bienvenue',
            ],
            [
                'name' => 'Réponse rapide',
                'content' => "Bonjour {user_name},\n\nMerci pour votre message. Je reviens vers vous très rapidement.\n\nCordialement,\n{owner_name}",
                'category' => 'greeting',
                'shortcut' => 'rapide',
            ],

            // Disponibilité
            [
                'name' => 'Disponibilité immédiate',
                'content' => "Bonjour,\n\nLe logement est actuellement disponible et prêt à accueillir un locataire dès maintenant.\n\nSouhaitez-vous planifier une visite ?",
                'category' => 'availability',
                'shortcut' => 'dispo',
            ],
            [
                'name' => 'Non disponible',
                'content' => "Bonjour,\n\nMerci de votre intérêt. Malheureusement, ce logement n'est plus disponible actuellement.\n\nJe vous invite à consulter mes autres annonces sur ReziApp.\n\nCordialement",
                'category' => 'availability',
                'shortcut' => 'indispo',
            ],
            [
                'name' => 'Disponibilité future',
                'content' => "Bonjour,\n\nLe logement sera disponible à partir de [DATE].\n\nSi vous êtes intéressé(e), nous pouvons d'ores et déjà organiser une visite et discuter des modalités.\n\nCordialement",
                'category' => 'availability',
                'shortcut' => 'dispofutur',
            ],

            // Tarification
            [
                'name' => 'Détails tarifs',
                'content' => "Bonjour,\n\nVoici le détail des tarifs pour {residence_name} :\n\n- Loyer mensuel : [PRIX] FCFA\n- Charges incluses : Eau, électricité, internet\n- Caution : [CAUTION] FCFA\n\nPour toute question supplémentaire, je reste à votre disposition.",
                'category' => 'pricing',
                'shortcut' => 'tarifs',
            ],
            [
                'name' => 'Négociation',
                'content' => "Bonjour,\n\nJe comprends votre demande concernant le tarif. Je suis ouvert à la discussion pour un séjour de longue durée.\n\nPourriez-vous me préciser la durée souhaitée ?\n\nCordialement",
                'category' => 'pricing',
                'shortcut' => 'nego',
            ],

            // Règlement
            [
                'name' => 'Règlement intérieur',
                'content' => "Bonjour,\n\nVoici les principales règles de la résidence :\n\n✓ Respect des voisins et du calme\n✓ Propreté des espaces communs\n✓ Pas d'animaux sans accord préalable\n✓ Interdiction de fumer à l'intérieur\n\nLe règlement complet vous sera remis lors de votre arrivée.",
                'category' => 'rules',
                'shortcut' => 'reglement',
            ],
            [
                'name' => 'Check-in/Check-out',
                'content' => "Bonjour,\n\nHoraires d'arrivée et de départ :\n\n📍 Check-in : à partir de 14h\n📍 Check-out : avant 11h\n\nSi vous avez besoin d'horaires différents, merci de me contacter en avance.\n\nCordialement",
                'category' => 'rules',
                'shortcut' => 'horaires',
            ],

            // Itinéraire
            [
                'name' => 'Itinéraire',
                'content' => "Bonjour,\n\nVoici comment accéder au logement :\n\n📍 Adresse : [ADRESSE]\n🚗 En voiture : [INSTRUCTIONS]\n🚌 En transport : [INSTRUCTIONS]\n\nJe vous enverrai le plan détaillé avant votre arrivée.",
                'category' => 'directions',
                'shortcut' => 'itineraire',
            ],
            [
                'name' => 'Point de repère',
                'content' => "Le logement se situe à côté de [REPÈRE].\n\nVous ne pouvez pas le manquer !",
                'category' => 'directions',
                'shortcut' => 'repere',
            ],

            // Remerciement
            [
                'name' => 'Merci visite',
                'content' => "Bonjour {user_name},\n\nMerci d'avoir visité {residence_name}.\n\nJ'espère que le logement vous a plu. N'hésitez pas à me faire part de votre décision.\n\nBien cordialement,\n{owner_name}",
                'category' => 'thank_you',
                'shortcut' => 'mercivisite',
            ],
            [
                'name' => 'Merci réservation',
                'content' => "Bonjour {user_name},\n\nMerci pour votre réservation ! Je suis ravi de vous accueillir bientôt.\n\nJe vous enverrai les informations d'accès quelques jours avant votre arrivée.\n\nÀ très bientôt !",
                'category' => 'thank_you',
                'shortcut' => 'mercireserv',
            ],
            [
                'name' => 'Merci séjour',
                'content' => "Bonjour {user_name},\n\nMerci pour votre séjour chez moi !\n\nJ'espère que vous avez passé un agréable moment. Si vous avez apprécié votre expérience, je vous serais reconnaissant de laisser un avis sur ReziApp.\n\nÀ bientôt peut-être !",
                'category' => 'thank_you',
                'shortcut' => 'mercisejour',
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::firstOrCreate(
                [
                    'name' => $template['name'],
                    'is_system' => true,
                ],
                [
                    'user_id' => null,
                    'content' => $template['content'],
                    'category' => $template['category'],
                    'shortcut' => $template['shortcut'],
                    'variables' => $this->extractVariables($template['content']),
                    'language' => 'fr',
                    'is_active' => true,
                    'is_system' => true,
                ],
            );
        }

        $this->command->info('Templates de messages système créés avec succès !');
    }

    /**
     * Extraire les variables d'un contenu
     */
    protected function extractVariables(string $content): array
    {
        preg_match_all('/\{([a-zA-Z_]+)\}/', $content, $matches);

        return array_unique($matches[1] ?? []);
    }
}
