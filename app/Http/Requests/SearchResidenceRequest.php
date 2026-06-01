<?php

namespace App\Http\Requests;

use App\Services\GeolocationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest pour la recherche publique de résidences.
 * Centralise la validation utilisée dans ResidenceController::search().
 */
class SearchResidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Recherche publique
    }

    public function rules(): array
    {
        return [
            // Géolocalisation
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', Rule::in(GeolocationService::getAllowedRadii())],

            // Localisation textuelle
            'country_code' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'quartier' => ['nullable', 'string', 'max:100'],

            // Prix
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],

            // Type de logement
            'type' => ['nullable', 'string', 'in:studio,apartment,house,villa,duplex,other'],

            // Caractéristiques
            'bedrooms' => ['nullable', 'integer', 'min:1', 'max:10'],
            'bathrooms' => ['nullable', 'integer', 'min:1', 'max:10'],
            'max_guests' => ['nullable', 'integer', 'min:1', 'max:20'],

            // Équipements
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['numeric', 'exists:amenities,id'],

            // Note minimale
            'min_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],

            // Politique d'annulation
            'cancellation_policy' => ['nullable', 'integer', 'exists:cancellation_policies,id'],

            // Options spéciales
            'instant_book' => ['nullable', 'boolean'],
            'has_promotion' => ['nullable', 'boolean'],
            'is_accessible' => ['nullable', 'boolean'],
            'available_now' => ['nullable', 'boolean'],
            'is_work_travel_ready' => ['nullable', 'boolean'],
            'is_eco' => ['nullable', 'boolean'],

            // Dates de séjour
            'check_in' => ['nullable', 'date', 'after_or_equal:today'],
            'check_out' => ['nullable', 'date', 'after:check_in'],
            'flex_dates' => ['nullable', 'boolean'],
            'flex_type' => ['nullable', 'string', 'in:weekend,week,month,flexible_3,flexible_7'],
            // Sprint 2 — fenêtre ± N jours autour des dates exactes (Airbnb-style)
            'flex_window' => ['nullable', 'integer', 'in:0,1,3,7'],

            // Sprint 2 — Catégorie visuelle (slug)
            'category' => ['nullable', 'string', 'exists:categories,slug'],

            // Tri et pagination
            'sort' => ['nullable', 'string', 'in:price_asc,price_desc,rating,newest,distance'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',
            'radius.min' => 'Le rayon de recherche doit être d\'au moins 100 m.',
            'radius.max' => 'Le rayon de recherche ne peut pas dépasser 50 km.',
            'min_price.min' => 'Le prix minimum doit être positif.',
            'max_price.min' => 'Le prix maximum doit être positif.',
            'type.in' => 'Le type de logement sélectionné n\'est pas valide.',
            'sort.in' => 'L\'option de tri sélectionnée n\'est pas valide.',
            'amenities.*.exists' => 'L\'équipement sélectionné n\'existe pas.',
            'per_page.max' => 'Vous ne pouvez pas afficher plus de 100 résultats par page.',
        ];
    }
}
