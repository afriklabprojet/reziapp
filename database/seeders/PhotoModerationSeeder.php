<?php

namespace Database\Seeders;

use App\Models\Photo;
use Illuminate\Database\Seeder;

/**
 * Peuple la table photos avec des statuts de modération variés
 * pour tester l'interface admin PhotoModeration.
 *
 * Ne crée pas de nouvelles photos : met à jour les photos existantes.
 */
class PhotoModerationSeeder extends Seeder
{
    public function run(): void
    {
        $photos = Photo::orderBy('id')->get();

        if ($photos->isEmpty()) {
            $this->command->warn('Aucune photo en base — seeder ignoré.');

            return;
        }

        $total = $photos->count();
        $this->command->info("Mise à jour de {$total} photos avec des statuts variés...");

        // Répartition des statuts sur l'ensemble des photos
        $scenarios = [
            // approved (40%) — photos qui ont passé l'analyse
            'approved' => [
                'moderation_reason' => null,
                'quality_score'     => 85,
                'quality_issues'    => null,
                'is_property_photo' => true,
                'room_type'         => 'Salon',
                'tags'              => ['Salon', 'Meublé'],
                'safe_search_data'  => ['adult' => 'VERY_UNLIKELY', 'violence' => 'VERY_UNLIKELY', 'racy' => 'VERY_UNLIKELY', 'adult_score' => 1, 'violence_score' => 1, 'racy_score' => 1],
                'labels_data'       => [['label' => 'living room', 'score' => 0.95], ['label' => 'furniture', 'score' => 0.88]],
                'is_optimized'      => true,
            ],
            // skipped (25%) — Vision API non configurée
            'skipped' => [
                'moderation_reason' => 'Service Cloud Vision non configuré',
                'quality_score'     => null,
                'quality_issues'    => null,
                'is_property_photo' => true,
                'room_type'         => null,
                'tags'              => null,
                'safe_search_data'  => null,
                'labels_data'       => null,
                'is_optimized'      => true,
            ],
            // review (20%) — à vérifier manuellement
            'review' => [
                'moderation_reason' => 'Qualité photo insuffisante: Photo trop sombre',
                'quality_score'     => 22,
                'quality_issues'    => ['Photo trop sombre'],
                'is_property_photo' => true,
                'room_type'         => 'Chambre',
                'tags'              => ['Chambre'],
                'safe_search_data'  => ['adult' => 'POSSIBLE', 'violence' => 'VERY_UNLIKELY', 'racy' => 'UNLIKELY', 'adult_score' => 3, 'violence_score' => 1, 'racy_score' => 2],
                'labels_data'       => [['label' => 'bedroom', 'score' => 0.72]],
                'is_optimized'      => true,
            ],
            // rejected (10%) — contenu inapproprié ou non-immobilier
            'rejected' => [
                'moderation_reason' => 'La photo ne semble pas montrer un bien immobilier',
                'quality_score'     => 60,
                'quality_issues'    => null,
                'is_property_photo' => false,
                'room_type'         => null,
                'tags'              => null,
                'safe_search_data'  => ['adult' => 'VERY_UNLIKELY', 'violence' => 'VERY_UNLIKELY', 'racy' => 'VERY_UNLIKELY', 'adult_score' => 1, 'violence_score' => 1, 'racy_score' => 1],
                'labels_data'       => [['label' => 'person', 'score' => 0.91], ['label' => 'portrait', 'score' => 0.85]],
                'is_optimized'      => true,
            ],
            // pending (5%) — pas encore traité
            'pending' => [
                'moderation_reason' => null,
                'quality_score'     => null,
                'quality_issues'    => null,
                'is_property_photo' => true,
                'room_type'         => null,
                'tags'              => null,
                'safe_search_data'  => null,
                'labels_data'       => null,
                'is_optimized'      => false,
            ],
        ];

        // Distribution par photos (boucle circulaire sur les scénarios)
        $distribution = [
            'approved', 'approved', 'approved', 'approved',  // 40%
            'skipped',  'skipped',  'skipped',               // ~25%
            'review',   'review',                            // 20%
            'rejected',                                       // 10%
            'pending',                                        // 5%
        ];
        $distCount = count($distribution);
        $counts = array_fill_keys(array_keys($scenarios), 0);

        foreach ($photos as $index => $photo) {
            $status = $distribution[$index % $distCount];
            $data   = $scenarios[$status];

            $photo->update(array_merge(['moderation_status' => $status], $data));
            $counts[$status]++;
        }

        $this->command->info('✅ photos moderation seeded:');
        foreach ($counts as $status => $count) {
            $this->command->line("   → {$status}: {$count}");
        }
    }
}
