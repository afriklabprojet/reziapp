<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateResidenceRequest extends FormRequest
{
    /**
     * Mapping type_location → price_period autorisé.
     */
    private const TYPE_PERIOD_MAP = [
        'apartment'         => 'month',
        'residence_meublee' => 'day',
        'hotel'             => 'night',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $residence = $this->route('residence');

        // L'utilisateur doit être le propriétaire de la résidence ou admin
        return $this->user() && (
            (int) $this->user()->id === (int) $residence->owner_id ||
            $this->user()->role === 'admin'
        );
    }

    /**
     * Cohérence type_location ↔ price_period.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $type   = $this->input('type_location');
                $period = $this->input('price_period');

                if ($type && $period && isset(self::TYPE_PERIOD_MAP[$type])) {
                    $expected = self::TYPE_PERIOD_MAP[$type];
                    if ($period !== $expected) {
                        $validator->errors()->add(
                            'price_period',
                            "Pour le type \"$type\", la période de prix doit être \"$expected\".",
                        );
                    }
                }
            },
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Informations générales
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'min:10', 'max:5000'],
            'type' => ['sometimes', 'string', 'in:studio,apartment,house,villa,duplex,other'],
            'rental_type' => ['nullable', 'string', 'in:standard,short_term,colocation,corporate,seasonal'],
            'type_location' => ['sometimes', 'string', 'in:apartment,residence_meublee,hotel'],
            'price_period' => ['sometimes', 'string', 'in:day,night,month'],

            // Localisation
            'address' => ['sometimes', 'string', 'max:500'],
            'country_code' => ['sometimes', 'string', 'size:2', 'exists:countries,code'],
            'city' => ['sometimes', 'string', 'max:100'],
            'commune' => ['sometimes', 'string', 'max:100'],
            'quartier' => ['sometimes', 'string', 'max:100'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],

            // Tarification — prix conditionnel selon type_location
            'price_per_day' => ['nullable', 'numeric', 'min:0'],
            'price_per_week' => ['nullable', 'numeric', 'min:0'],
            'price_per_month' => ['sometimes', 'numeric', 'min:0'],
            'deposit_negotiable' => ['sometimes', 'boolean'],
            'deposit_terms' => ['nullable', 'string', 'max:1000'],

            // Caractéristiques
            'bedrooms' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'max_guests' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'surface_area' => ['nullable', 'integer', 'min:5', 'max:10000'],
            'floor' => ['nullable', 'integer', 'min:-5', 'max:100'],
            'has_elevator' => ['sometimes', 'boolean'],

            // Disponibilité
            'is_available' => ['sometimes', 'boolean'],
            'available_from' => ['nullable', 'date'],
            'min_nights' => ['nullable', 'integer', 'min:1', 'max:365'],
            'max_nights' => ['nullable', 'integer', 'min:1', 'max:365', 'gte:min_nights'],
            'instant_book' => ['sometimes', 'boolean'],

            // Nouveaux champs Airbnb
            'cleaning_fee' => ['nullable', 'numeric', 'min:0'],
            'is_work_travel_ready' => ['sometimes', 'boolean'],
            'sustainability_score' => ['nullable', 'integer', 'min:0', 'max:100'],

            // Horaires
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],

            // Règles
            'house_rules' => ['nullable', 'string', 'max:2000'],
            'pets_allowed' => ['sometimes', 'boolean'],
            'smoking_allowed' => ['sometimes', 'boolean'],
            'parties_allowed' => ['sometimes', 'boolean'],

            // Location
            'lease_type' => ['nullable', 'string', 'in:written,verbal,flexible'],
            'target_tenants' => ['nullable', 'array'],
            'target_tenants.*' => ['string', 'in:students,families,professionals,couples,tourists'],

            // Accessibilité
            'is_accessible' => ['sometimes', 'boolean'],
            'accessibility_features' => ['nullable', 'array'],
            'accessibility_features.*' => ['string', 'max:100'],

            // Média
            'virtual_tour_url' => ['nullable', 'url', 'max:500'],
            'photos' => ['sometimes', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],

            // Équipements
            'amenities' => ['sometimes', 'array'],
            'amenities.*' => ['numeric', 'exists:amenities,id'],

            // Catégorie
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],

            // Politique d'annulation
            'cancellation_policy_id' => ['nullable', 'integer', 'exists:cancellation_policies,id'],
        ];
    }

    /**
     * Get custom messages for validator errors (French).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Informations générales
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'description.min' => 'La description doit contenir au moins 50 caractères.',
            'description.max' => 'La description ne peut pas dépasser 5000 caractères.',
            'type.in' => 'Le type de résidence sélectionné n\'est pas valide.',
            'rental_type.in' => 'Le type de location sélectionné n\'est pas valide.',

            // Localisation
            'country_code.exists' => 'Le pays sélectionné n\'est pas valide.',
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',

            // Tarification
            'price_per_month.min' => 'Le prix doit être positif.',
            'price_per_day.min' => 'Le prix journalier doit être positif.',
            'price_per_week.min' => 'Le prix hebdomadaire doit être positif.',
            'deposit_terms.max' => 'Les conditions de caution ne peuvent pas dépasser 1000 caractères.',

            // Caractéristiques
            'bedrooms.min' => 'Le nombre de chambres doit être au moins 0.',
            'bedrooms.max' => 'Le nombre de chambres ne peut pas dépasser 20.',
            'bathrooms.min' => 'Il doit y avoir au moins 1 salle de bain.',
            'bathrooms.max' => 'Le nombre de salles de bain ne peut pas dépasser 10.',
            'max_guests.min' => 'La capacité doit être d\'au moins 1 personne.',
            'max_guests.max' => 'La capacité ne peut pas dépasser 50 personnes.',
            'surface_area.min' => 'La surface doit être d\'au moins 5 m².',
            'surface_area.max' => 'La surface ne peut pas dépasser 10 000 m².',
            'floor.min' => 'L\'étage ne peut pas être inférieur à -5.',
            'floor.max' => 'L\'étage ne peut pas dépasser 100.',

            // Disponibilité
            'available_from.date' => 'La date de disponibilité n\'est pas valide.',
            'min_nights.min' => 'Le séjour minimum doit être d\'au moins 1 nuit.',
            'min_nights.max' => 'Le séjour minimum ne peut pas dépasser 365 nuits.',
            'max_nights.min' => 'Le séjour maximum doit être d\'au moins 1 nuit.',
            'max_nights.max' => 'Le séjour maximum ne peut pas dépasser 365 nuits.',
            'max_nights.gte' => 'Le séjour maximum doit être supérieur ou égal au minimum.',

            // Horaires
            'check_in_time.date_format' => 'L\'heure d\'arrivée doit être au format HH:MM.',
            'check_out_time.date_format' => 'L\'heure de départ doit être au format HH:MM.',

            // Règles
            'house_rules.max' => 'Les règles ne peuvent pas dépasser 2000 caractères.',

            // Location
            'lease_type.in' => 'Le type de bail sélectionné n\'est pas valide.',
            'target_tenants.*.in' => 'Le profil de locataire sélectionné n\'est pas valide.',

            // Accessibilité
            'accessibility_features.*.max' => 'Chaque caractéristique d\'accessibilité ne peut pas dépasser 100 caractères.',

            // Média
            'virtual_tour_url.url' => 'Le lien de visite virtuelle doit être une URL valide.',
            'virtual_tour_url.max' => 'Le lien de visite virtuelle ne peut pas dépasser 500 caractères.',
            'photos.max' => 'Vous ne pouvez pas ajouter plus de 10 photos.',
            'photos.*.image' => 'Le fichier doit être une image.',
            'photos.*.mimes' => 'Les photos doivent être au format JPEG, JPG, PNG ou WEBP.',
            'photos.*.max' => 'Chaque photo ne peut pas dépasser 5 MB.',

            // Équipements
            'amenities.*.exists' => 'L\'équipement sélectionné n\'existe pas.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'description' => 'description',
            'type' => 'type de résidence',
            'rental_type' => 'type de location',
            'address' => 'adresse',
            'country_code' => 'pays',
            'city' => 'ville',
            'commune' => 'commune',
            'quartier' => 'quartier',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'price_per_day' => 'prix journalier',
            'price_per_week' => 'prix hebdomadaire',
            'price_per_month' => 'prix mensuel',
            'deposit_negotiable' => 'caution négociable',
            'deposit_terms' => 'conditions de caution',
            'bedrooms' => 'chambres',
            'bathrooms' => 'salles de bain',
            'max_guests' => 'capacité',
            'surface_area' => 'surface',
            'floor' => 'étage',
            'has_elevator' => 'ascenseur',
            'is_available' => 'disponibilité',
            'available_from' => 'disponible à partir de',
            'min_nights' => 'séjour minimum',
            'max_nights' => 'séjour maximum',
            'instant_book' => 'réservation instantanée',
            'check_in_time' => 'heure d\'arrivée',
            'check_out_time' => 'heure de départ',
            'house_rules' => 'règles',
            'pets_allowed' => 'animaux autorisés',
            'smoking_allowed' => 'fumeurs autorisés',
            'parties_allowed' => 'fêtes autorisées',
            'lease_type' => 'type de bail',
            'target_tenants' => 'locataires cibles',
            'is_accessible' => 'accessibilité PMR',
            'accessibility_features' => 'équipements accessibilité',
            'virtual_tour_url' => 'visite virtuelle',
            'photos' => 'photos',
            'amenities' => 'équipements',
            'category_id' => 'catégorie',
            'cancellation_policy_id' => 'politique d\'annulation',
        ];
    }
}
