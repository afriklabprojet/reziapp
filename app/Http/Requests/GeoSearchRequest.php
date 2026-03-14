<?php

namespace App\Http\Requests;

use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validation pour la recherche géolocalisée
 *
 * Cœur de REZI : validation stricte des coordonnées et du rayon
 * Supporte CI + Burkina Faso (limites dynamiques depuis la BDD)
 */
class GeoSearchRequest extends FormRequest
{
    /**
     * Rayon par défaut (mètres)
     */
    public const DEFAULT_RADIUS = 5000;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Route publique
    }

    /**
     * Limites géographiques dynamiques (CI + BF combinées)
     */
    public static function geoBounds(): array
    {
        return Country::globalBounds();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bounds = self::geoBounds();

        return [
            // Coordonnées (obligatoires)
            'latitude' => [
                'required',
                'numeric',
                'between:'.$bounds['min_lat'].','.$bounds['max_lat'],
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:'.$bounds['min_lng'].','.$bounds['max_lng'],
            ],

            // Rayon de recherche
            'radius' => [
                'sometimes',
                'integer',
                Rule::in(config('rezi.search.allowed_radii')),
            ],

            // Filtres de prix
            'min_price' => ['sometimes', 'integer', 'min:0', 'max:10000000'],
            'max_price' => ['sometimes', 'integer', 'min:0', 'max:10000000', 'gte:min_price'],

            // Filtres de localisation
            'country_code' => ['sometimes', 'string', 'size:2'],
            'city' => ['sometimes', 'string', 'max:100'],
            'commune' => [
                'sometimes',
                'string',
                'max:100',
            ],

            // Équipements
            'amenities' => ['sometimes', 'array', 'max:10'],
            'amenities.*' => ['numeric', 'exists:amenities,id'],

            // Tri et pagination
            'sort' => [
                'sometimes',
                'string',
                Rule::in(['distance', 'price_asc', 'price_desc', 'newest']),
            ],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'La latitude est obligatoire pour la recherche.',
            'latitude.numeric' => 'La latitude doit être un nombre.',
            'latitude.between' => 'La latitude doit être dans les limites de la zone couverte (CI / BF).',

            'longitude.required' => 'La longitude est obligatoire pour la recherche.',
            'longitude.numeric' => 'La longitude doit être un nombre.',
            'longitude.between' => 'La longitude doit être dans les limites de la zone couverte (CI / BF).',

            'radius.in' => 'Le rayon sélectionné n\'est pas autorisé.',

            'min_price.integer' => 'Le prix minimum doit être un nombre entier.',
            'max_price.gte' => 'Le prix maximum doit être supérieur ou égal au prix minimum.',

            'commune.in' => 'Cette commune n\'est pas reconnue.',

            'amenities.max' => 'Vous pouvez sélectionner maximum 10 équipements.',
            'amenities.*.exists' => 'Un des équipements sélectionnés n\'existe pas.',

            'sort.in' => 'Le tri doit être: distance, price_asc, price_desc, newest ou area.',
            'per_page.max' => 'Maximum 50 résultats par page.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Valeurs par défaut
        $this->merge([
            'radius' => $this->input('radius', self::DEFAULT_RADIUS),
            'sort' => $this->input('sort', 'distance'),
            'per_page' => $this->input('per_page', 15),
        ]);

        // Normaliser la commune (lowercase, trim)
        if ($this->has('commune') && $this->input('commune')) {
            $this->merge([
                'commune' => $this->normalizeCommune($this->input('commune')),
            ]);
        }
    }

    /**
     * Normalise le nom de commune
     */
    protected function normalizeCommune(string $commune): string
    {
        return strtolower(trim($commune));
    }

    /**
     * Get validated data with defaults
     */
    public function validatedWithDefaults(): array
    {
        return array_merge([
            'radius' => self::DEFAULT_RADIUS,
            'sort' => 'distance',
            'per_page' => 15,
        ], $this->validated());
    }
}
