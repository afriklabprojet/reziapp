<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionItem extends Model
{
    public const CONDITION_GOOD    = 'good';
    public const CONDITION_FAIR    = 'fair';
    public const CONDITION_DAMAGED = 'damaged';
    public const CONDITION_MISSING = 'missing';

    protected $fillable = [
        'property_inspection_id',
        'room',
        'element',
        'condition',
        'observations',
        'photos',
        'repair_estimate',
        'requires_action',
        'sort_order',
    ];

    protected $casts = [
        'photos'           => 'array',
        'repair_estimate'  => 'decimal:2',
        'requires_action'  => 'boolean',
        'sort_order'       => 'integer',
    ];

    // ===== ACCESSORS =====

    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition) {
            self::CONDITION_GOOD    => 'Bon état',
            self::CONDITION_FAIR    => 'Usage normal',
            self::CONDITION_DAMAGED => 'Endommagé',
            self::CONDITION_MISSING => 'Manquant',
            default                 => 'Inconnu',
        };
    }

    public function getConditionColorAttribute(): string
    {
        return match ($this->condition) {
            self::CONDITION_GOOD    => 'green',
            self::CONDITION_FAIR    => 'blue',
            self::CONDITION_DAMAGED => 'red',
            self::CONDITION_MISSING => 'gray',
            default                 => 'gray',
        };
    }

    public function getConditionIconAttribute(): string
    {
        return match ($this->condition) {
            self::CONDITION_GOOD    => '✅',
            self::CONDITION_FAIR    => '🔵',
            self::CONDITION_DAMAGED => '⚠️',
            self::CONDITION_MISSING => '❌',
            default                 => '❓',
        };
    }

    // ===== RELATIONSHIPS =====

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(PropertyInspection::class, 'property_inspection_id');
    }

    // ===== CLASS HELPERS =====

    /**
     * Pièces type d'un logement ivoirien
     */
    public static function defaultRooms(): array
    {
        return [
            'Salon/Séjour',
            'Chambre principale',
            'Chambre 2',
            'Chambre 3',
            'Cuisine',
            'Salle de bain',
            'WC',
            'Couloir/Entrée',
            'Balcon/Terrasse',
            'Garage',
            'Dépendance',
        ];
    }

    /**
     * Éléments type par pièce
     */
    public static function defaultElements(): array
    {
        return [
            'Sol', 'Murs', 'Plafond',
            'Fenêtres / Volets', 'Portes', 'Serrures',
            'Éclairage', 'Prises électriques', 'Interrupteurs',
            'Climatiseur', 'Ventilateur', 'Chauffe-eau',
            'Mobilier', 'Équipements cuisine', 'Sanitaires',
            'Robinetterie', 'Peinture',
        ];
    }
}
