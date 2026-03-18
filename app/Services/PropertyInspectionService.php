<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\InspectionItem;
use App\Models\LeaseContract;
use App\Models\PropertyInspection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Service de gestion des états des lieux.
 *
 * Responsabilités :
 *  - Création (entrée / sortie / périodique)
 *  - Ajout et mise à jour des éléments inspectés (pièces + photos)
 *  - Génération du rapport PDF
 *  - Workflow de signature
 *  - Comparaison entrée/sortie pour évaluation des dommages
 */
class PropertyInspectionService
{
    // ===== CRÉATION =====

    public function create(array $data): PropertyInspection
    {
        return PropertyInspection::create(array_merge($data, [
            'status' => PropertyInspection::STATUS_DRAFT,
        ]));
    }

    public function createFromBooking(Booking $booking, string $type): PropertyInspection
    {
        $residence = $booking->residence;

        $inspection = PropertyInspection::create([
            'owner_id'    => $residence->owner_id,
            'tenant_id'   => $booking->user_id,
            'residence_id' => $booking->residence_id,
            'booking_id'  => $booking->id,
            'type'        => $type,
            'status'      => PropertyInspection::STATUS_DRAFT,
            'inspection_date' => match ($type) {
                PropertyInspection::TYPE_CHECK_IN  => $booking->check_in->startOfDay(),
                PropertyInspection::TYPE_CHECK_OUT => $booking->check_out->startOfDay(),
                default                            => now(),
            },
        ]);

        // Pré-remplir les pièces selon la résidence
        $this->prefillItems($inspection, $residence);

        return $inspection;
    }

    public function createFromContract(LeaseContract $contract, string $type): PropertyInspection
    {
        return PropertyInspection::create([
            'owner_id'          => $contract->owner_id,
            'tenant_id'         => $contract->tenant_id,
            'residence_id'      => $contract->residence_id,
            'lease_contract_id' => $contract->id,
            'type'              => $type,
            'status'            => PropertyInspection::STATUS_DRAFT,
            'inspection_date'   => now(),
        ]);
    }

    // ===== ÉLEMENTS =====

    /**
     * Pré-remplir les éléments de l'état des lieux depuis les pièces de la résidence.
     */
    private function prefillItems(PropertyInspection $inspection, $residence): void
    {
        $rooms    = InspectionItem::defaultRooms();
        $elements = InspectionItem::defaultElements();

        $items = [];
        $order = 0;

        foreach (['Salon/Séjour', 'Cuisine', 'Salle de bain', 'WC', 'Couloir/Entrée'] as $room) {
            foreach (['Sol', 'Murs', 'Plafond', 'Fenêtres / Volets', 'Portes', 'Éclairage'] as $element) {
                $items[] = [
                    'property_inspection_id' => $inspection->id,
                    'room'                   => $room,
                    'element'                => $element,
                    'condition'              => InspectionItem::CONDITION_GOOD,
                    'sort_order'             => $order++,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
            }
        }

        // Ajouter les chambres selon capacité
        $bedrooms = min((int) ($residence->bedrooms ?? 1), 4);
        for ($i = 1; $i <= $bedrooms; $i++) {
            $room = $bedrooms === 1 ? 'Chambre principale' : "Chambre {$i}";
            foreach (['Sol', 'Murs', 'Plafond', 'Fenêtres / Volets', 'Portes', 'Éclairage', 'Climatiseur'] as $element) {
                $items[] = [
                    'property_inspection_id' => $inspection->id,
                    'room'                   => $room,
                    'element'                => $element,
                    'condition'              => InspectionItem::CONDITION_GOOD,
                    'sort_order'             => $order++,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ];
            }
        }

        InspectionItem::insert($items);
    }

    /**
     * Mettre à jour un élément (condition + observations + photos).
     */
    public function updateItem(InspectionItem $item, array $data): InspectionItem
    {
        $item->update($data);

        return $item;
    }

    /**
     * Ajouter des photos à un élément de l'état des lieux.
     */
    public function addPhotosToItem(
        InspectionItem $item,
        array $uploadedFiles, // UploadedFile[]
    ): InspectionItem {
        $existingPhotos = $item->photos ?? [];
        $newPhotos      = [];

        foreach ($uploadedFiles as $file) {
            if (! ($file instanceof UploadedFile)) {
                continue;
            }

            $path = $file->store(
                "inspections/{$item->property_inspection_id}/items/{$item->id}",
                's3',
            );

            $newPhotos[] = $path;
        }

        $item->update(['photos' => array_merge($existingPhotos, $newPhotos)]);

        return $item;
    }

    // ===== GÉNÉRATION PDF =====

    public function generatePdf(PropertyInspection $inspection): PropertyInspection
    {
        $inspection->load(['owner', 'tenant', 'residence', 'items']);

        $pdf = Pdf::loadView('pdf.property-inspection', ['inspection' => $inspection])
            ->setPaper('A4', 'portrait');

        $path = "property-inspections/{$inspection->reference}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $inspection->update([
            'pdf_path'          => $path,
            'pdf_generated_at'  => now(),
        ]);

        return $inspection->fresh();
    }

    public function downloadPdf(PropertyInspection $inspection): string
    {
        if (! $inspection->pdf_path || ! Storage::disk('local')->exists($inspection->pdf_path)) {
            $this->generatePdf($inspection);
            $inspection->refresh();
        }

        return Storage::disk('local')->get($inspection->pdf_path);
    }

    // ===== SIGNATURE =====

    public function sign(PropertyInspection $inspection, string $role, string $ip): PropertyInspection
    {
        $inspection->sign($role, $ip);

        if ($inspection->fresh()->is_fully_signed) {
            $this->generatePdf($inspection->fresh());
        }

        return $inspection->fresh();
    }

    // ===== COMPARAISON ENTRÉE / SORTIE =====

    /**
     * Comparer l'état des lieux de sortie à l'état des lieux d'entrée.
     * Retourne les éléments dégradés avec les coûts estimés.
     */
    public function compareCheckInOut(PropertyInspection $checkIn, PropertyInspection $checkOut): array
    {
        $inItems  = $checkIn->items->keyBy(fn ($i) => "{$i->room}|{$i->element}");
        $outItems = $checkOut->items->keyBy(fn ($i) => "{$i->room}|{$i->element}");

        $degraded = [];
        $totalCost = 0;

        foreach ($outItems as $key => $outItem) {
            $inItem = $inItems[$key] ?? null;

            if (! $inItem) {
                continue;
            }

            $conditionOrder = ['good' => 0, 'fair' => 1, 'damaged' => 2, 'missing' => 3];
            $inOrder        = $conditionOrder[$inItem->condition] ?? 0;
            $outOrder       = $conditionOrder[$outItem->condition] ?? 0;

            if ($outOrder > $inOrder) {
                $cost     = (float) ($outItem->repair_estimate ?? 0);
                $totalCost += $cost;

                $degraded[] = [
                    'room'          => $outItem->room,
                    'element'       => $outItem->element,
                    'condition_in'  => $inItem->condition_label,
                    'condition_out' => $outItem->condition_label,
                    'observations'  => $outItem->observations,
                    'repair_cost'   => $cost,
                    'photos'        => $outItem->photos ?? [],
                ];
            }
        }

        return [
            'degraded_items' => $degraded,
            'total_repair_cost' => $totalCost,
            'has_degradation' => count($degraded) > 0,
        ];
    }
}
