<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\UtilityReading;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUtilityReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'residence_id'  => ['required', 'integer', 'exists:residences,id'],
            'utility_type'  => ['required', Rule::in(array_keys(UtilityReading::TYPES))],
            'reading_value' => ['required', 'numeric', 'min:0'],
            'reading_date'  => ['required', 'date', 'before_or_equal:today'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'residence_id.required'  => 'Sélectionnez une résidence.',
            'utility_type.required'  => 'Le type de compteur est obligatoire.',
            'reading_value.required' => 'La valeur du relevé est obligatoire.',
            'reading_date.required'  => 'La date du relevé est obligatoire.',
        ];
    }
}
