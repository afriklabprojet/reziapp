<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdditionalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'pricing_type',
        'price',
        'requires_quantity',
        'max_quantity',
        'category',
        'requires_advance_booking',
        'advance_hours',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'requires_quantity' => 'boolean',
        'requires_advance_booking' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Les résidences qui proposent ce service
     */
    public function residences(): BelongsToMany
    {
        return $this->belongsToMany(Residence::class, 'residence_additional_services')
            ->withPivot(['custom_price', 'is_available', 'custom_description'])
            ->withTimestamps();
    }

    /**
     * Les commandes de ce service
     */
    public function bookingServices(): HasMany
    {
        return $this->hasMany(BookingAdditionalService::class);
    }

    /**
     * Services actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Par catégorie
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Triés
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Calculer le prix total
     */
    public function calculatePrice(int $quantity = 1, int $nights = 1, int $guests = 1): float
    {
        $price = $this->price;

        return match ($this->pricing_type) {
            'fixed' => $price,
            'per_night' => $price * $nights,
            'per_guest' => $price * $guests,
            'per_item' => $price * $quantity,
            default => $price,
        };
    }

    /**
     * Obtenir le label du type de tarification
     */
    public function getPricingLabel(): string
    {
        return match ($this->pricing_type) {
            'fixed' => 'Prix fixe',
            'per_night' => 'Par nuit',
            'per_guest' => 'Par voyageur',
            'per_item' => 'Par unité',
            default => '',
        };
    }

    /**
     * Catégories disponibles
     */
    public static function getCategories(): array
    {
        return [
            'transport' => 'Transport',
            'food' => 'Restauration',
            'cleaning' => 'Ménage',
            'experience' => 'Expériences',
            'equipment' => 'Équipements',
            'concierge' => 'Conciergerie',
            'wellness' => 'Bien-être',
            'other' => 'Autres',
        ];
    }

    /**
     * Services par défaut
     */
    public static function getDefaultServices(): array
    {
        return [
            // Transport
            [
                'name' => 'Transfert aéroport',
                'slug' => 'airport-transfer',
                'description' => 'Service de transfert depuis/vers l\'aéroport FHB',
                'icon' => 'heroicon-o-paper-airplane',
                'pricing_type' => 'fixed',
                'price' => 25000,
                'category' => 'transport',
                'requires_advance_booking' => true,
                'advance_hours' => 24,
                'sort_order' => 1,
            ],
            [
                'name' => 'Location de voiture',
                'slug' => 'car-rental',
                'description' => 'Véhicule avec chauffeur à disposition',
                'icon' => 'heroicon-o-truck',
                'pricing_type' => 'per_night',
                'price' => 35000,
                'category' => 'transport',
                'requires_advance_booking' => true,
                'advance_hours' => 48,
                'sort_order' => 2,
            ],
            // Restauration
            [
                'name' => 'Petit-déjeuner',
                'slug' => 'breakfast',
                'description' => 'Petit-déjeuner continental livré chaque matin',
                'icon' => 'heroicon-o-cake',
                'pricing_type' => 'per_guest',
                'price' => 5000,
                'category' => 'food',
                'requires_advance_booking' => true,
                'advance_hours' => 12,
                'sort_order' => 3,
            ],
            [
                'name' => 'Chef à domicile',
                'slug' => 'private-chef',
                'description' => 'Chef cuisinier pour préparer vos repas',
                'icon' => 'heroicon-o-fire',
                'pricing_type' => 'fixed',
                'price' => 45000,
                'category' => 'food',
                'requires_advance_booking' => true,
                'advance_hours' => 48,
                'sort_order' => 4,
            ],
            // Ménage
            [
                'name' => 'Ménage quotidien',
                'slug' => 'daily-cleaning',
                'description' => 'Service de ménage quotidien',
                'icon' => 'heroicon-o-sparkles',
                'pricing_type' => 'per_night',
                'price' => 8000,
                'category' => 'cleaning',
                'sort_order' => 5,
            ],
            [
                'name' => 'Blanchisserie',
                'slug' => 'laundry',
                'description' => 'Service de lavage et repassage',
                'icon' => 'heroicon-o-rectangle-stack',
                'pricing_type' => 'per_item',
                'price' => 2500,
                'requires_quantity' => true,
                'max_quantity' => 20,
                'category' => 'cleaning',
                'sort_order' => 6,
            ],
            // Expériences
            [
                'name' => 'Guide touristique',
                'slug' => 'tour-guide',
                'description' => 'Guide pour découvrir Abidjan',
                'icon' => 'heroicon-o-map',
                'pricing_type' => 'fixed',
                'price' => 30000,
                'category' => 'experience',
                'requires_advance_booking' => true,
                'advance_hours' => 24,
                'sort_order' => 7,
            ],
            // Bien-être
            [
                'name' => 'Massage à domicile',
                'slug' => 'in-home-massage',
                'description' => 'Séance de massage par un professionnel',
                'icon' => 'heroicon-o-hand-raised',
                'pricing_type' => 'fixed',
                'price' => 25000,
                'category' => 'wellness',
                'requires_advance_booking' => true,
                'advance_hours' => 24,
                'sort_order' => 8,
            ],
            // Équipements
            [
                'name' => 'Lit bébé',
                'slug' => 'baby-crib',
                'description' => 'Lit pour bébé (0-2 ans)',
                'icon' => 'heroicon-o-moon',
                'pricing_type' => 'fixed',
                'price' => 5000,
                'category' => 'equipment',
                'requires_advance_booking' => true,
                'advance_hours' => 24,
                'sort_order' => 9,
            ],
            [
                'name' => 'Chaise haute',
                'slug' => 'high-chair',
                'description' => 'Chaise haute pour enfant',
                'icon' => 'heroicon-o-user',
                'pricing_type' => 'fixed',
                'price' => 2500,
                'category' => 'equipment',
                'sort_order' => 10,
            ],
        ];
    }
}
