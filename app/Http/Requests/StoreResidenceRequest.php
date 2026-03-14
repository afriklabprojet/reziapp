<?php

namespace App\Http\Requests;

use App\Models\Residence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreResidenceRequest extends FormRequest
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
        return $this->user() && in_array($this->user()->role, ['owner', 'admin']);
    }

    /**
     * Hook de validation supplémentaire : détection de doublons.
     * Bloque si le même propriétaire a déjà une annonce à < 100m avec un nom très similaire.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->user();
                $lat  = $this->input('latitude');
                $lng  = $this->input('longitude');
                $name = $this->input('name');

                if (! $user || ! $lat || ! $lng || ! $name) {
                    return;
                }

                // Cherche une résidence du même propriétaire à moins de 100 m
                $duplicate = Residence::where('owner_id', $user->id)
                    ->whereRaw(
                        '(6371000 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < 100',
                        [$lat, $lng, $lat]
                    )
                    ->where(function ($q) use ($name) {
                        // Nom identique ou très proche (SOUNDEX ou LIKE)
                        $q->where('name', $name)
                          ->orWhereRaw('SOUNDEX(name) = SOUNDEX(?)', [$name]);
                    })
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add(
                        'name',
                        'Vous avez déjà une annonce similaire à proximité (< 100 m). Veuillez modifier l\'annonce existante au lieu d\'en créer une nouvelle.'
                    );
                }
            },
            // Cohérence type_location ↔ price_period
            function (Validator $validator) {
                $type   = $this->input('type_location');
                $period = $this->input('price_period');

                if ($type && $period && isset(self::TYPE_PERIOD_MAP[$type])) {
                    $expected = self::TYPE_PERIOD_MAP[$type];
                    if ($period !== $expected) {
                        $labels = [
                            'month' => 'mois (month)',
                            'day'   => 'jour (day)',
                            'night' => 'nuit (night)',
                        ];
                        $validator->errors()->add(
                            'price_period',
                            "Pour le type \"$type\", la période de prix doit être \"" . ($labels[$expected] ?? $expected) . '".',
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50', 'max:5000'],
            'type' => ['required', 'string', 'in:studio,apartment,house,villa,duplex,other'],
            'rental_type' => ['nullable', 'string', 'in:standard,short_term,colocation,corporate,seasonal'],
            'type_location' => ['required', 'string', 'in:apartment,residence_meublee,hotel'],
            'price_period' => ['required', 'string', 'in:day,night,month'],
            
            // Localisation
            'address' => ['required', 'string', 'max:500'],
            'country_code' => ['required', 'string', 'size:2', 'exists:countries,code'],
            'city' => ['required', 'string', 'max:100'],
            'commune' => ['required', 'string', 'max:100'],
            'quartier' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            
            // Tarification — prix conditionnel selon type_location
            'price_per_day' => ['nullable', 'numeric', 'min:0', 'required_if:type_location,residence_meublee,hotel'],
            'price_per_week' => ['nullable', 'numeric', 'min:0'],
            'price_per_month' => ['nullable', 'numeric', 'min:10000', 'required_if:type_location,apartment'],
            'deposit_negotiable' => ['boolean'],
            'deposit_terms' => ['nullable', 'string', 'max:1000'],
            
            // Caractéristiques
            'bedrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['required', 'integer', 'min:1', 'max:10'],
            'max_guests' => ['required', 'integer', 'min:1', 'max:50'],
            'surface_area' => ['nullable', 'integer', 'min:5', 'max:10000'],
            'floor' => ['nullable', 'integer', 'min:-5', 'max:100'],
            'has_elevator' => ['boolean'],
            
            // Disponibilité
            'is_available' => ['boolean'],
            'available_from' => ['nullable', 'date', 'after_or_equal:today'],
            'min_nights' => ['nullable', 'integer', 'min:1', 'max:365'],
            'max_nights' => ['nullable', 'integer', 'min:1', 'max:365', 'gte:min_nights'],
            'instant_book' => ['boolean'],
            
            // Horaires
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            
            // Règles
            'house_rules' => ['nullable', 'string', 'max:2000'],
            'pets_allowed' => ['boolean'],
            'smoking_allowed' => ['boolean'],
            'parties_allowed' => ['boolean'],
            
            // Location
            'lease_type' => ['nullable', 'string', 'in:written,verbal,flexible'],
            'target_tenants' => ['nullable', 'array'],
            'target_tenants.*' => ['string', 'in:students,families,professionals,couples,tourists'],
            
            // Accessibilité
            'is_accessible' => ['boolean'],
            'accessibility_features' => ['nullable', 'array'],
            'accessibility_features.*' => ['string', 'max:100'],
            
            // Média
            'virtual_tour_url' => ['nullable', 'url', 'max:500'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            
            // Équipements
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['numeric', 'exists:amenities,id'],
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
            'name.required' => 'Le nom de la résidence est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins 50 caractères.',
            'description.max' => 'La description ne peut pas dépasser 5000 caractères.',
            'type.required' => 'Le type de résidence est obligatoire.',
            'type.in' => 'Le type de résidence sélectionné n\'est pas valide.',
            'rental_type.in' => 'Le type de location sélectionné n\'est pas valide.',
            'type_location.required' => 'Le type de location est obligatoire.',
            'type_location.in' => 'Le type de location sélectionné n\'est pas valide.',
            'price_period.required' => 'La période de prix est obligatoire.',
            'price_period.in' => 'La période de prix sélectionnée n\'est pas valide.',
            
            // Localisation
            'address.required' => 'L\'adresse est obligatoire.',
            'country_code.required' => 'Le pays est obligatoire.',
            'country_code.exists' => 'Le pays sélectionné n\'est pas valide.',
            'city.required' => 'La ville est obligatoire.',
            'commune.required' => 'La commune est obligatoire.',
            'quartier.required' => 'Le quartier est obligatoire.',
            'latitude.required' => 'Veuillez positionner votre résidence sur la carte.',
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'longitude.required' => 'Veuillez positionner votre résidence sur la carte.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',
            
            // Tarification
            'price_per_month.required' => 'Le prix mensuel est obligatoire.',
            'price_per_month.required_if' => 'Le prix mensuel est obligatoire pour un appartement.',
            'price_per_month.min' => 'Le prix mensuel doit être d\'au moins 10 000 FCFA.',
            'price_per_day.min' => 'Le prix journalier doit être positif.',
            'price_per_day.required_if' => 'Le prix journalier est obligatoire pour ce type de location.',
            'price_per_week.min' => 'Le prix hebdomadaire doit être positif.',
            'deposit_terms.max' => 'Les conditions de caution ne peuvent pas dépasser 1000 caractères.',
            
            // Caractéristiques
            'bedrooms.required' => 'Le nombre de chambres est obligatoire.',
            'bedrooms.min' => 'Le nombre de chambres doit être au moins 0.',
            'bedrooms.max' => 'Le nombre de chambres ne peut pas dépasser 20.',
            'bathrooms.required' => 'Le nombre de salles de bain est obligatoire.',
            'bathrooms.min' => 'Il doit y avoir au moins 1 salle de bain.',
            'bathrooms.max' => 'Le nombre de salles de bain ne peut pas dépasser 10.',
            'max_guests.required' => 'La capacité d\'accueil est obligatoire.',
            'max_guests.min' => 'La capacité doit être d\'au moins 1 personne.',
            'max_guests.max' => 'La capacité ne peut pas dépasser 50 personnes.',
            'surface_area.min' => 'La surface doit être d\'au moins 5 m².',
            'surface_area.max' => 'La surface ne peut pas dépasser 10 000 m².',
            'floor.min' => 'L\'étage ne peut pas être inférieur à -5.',
            'floor.max' => 'L\'étage ne peut pas dépasser 100.',
            
            // Disponibilité
            'available_from.date' => 'La date de disponibilité n\'est pas valide.',
            'available_from.after_or_equal' => 'La date de disponibilité doit être aujourd\'hui ou ultérieure.',
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
            'type_location' => 'type de location',
            'price_period' => 'période de prix',
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
        ];
    }
}
