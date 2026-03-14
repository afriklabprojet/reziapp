<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\DamageReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDamageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'residence_id'   => ['required', 'integer', 'exists:residences,id'],
            'booking_id'     => ['nullable', 'integer', 'exists:bookings,id'],
            'category'       => ['required', Rule::in(array_keys(DamageReport::CATEGORIES))],
            'severity'       => ['required', Rule::in(array_keys(DamageReport::SEVERITIES))],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string', 'max:5000'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'photos'         => ['nullable', 'array', 'max:10'],
            'photos.*'       => ['image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'residence_id.required' => 'Sélectionnez une résidence.',
            'category.required'     => 'La catégorie est obligatoire.',
            'severity.required'     => 'La gravité est obligatoire.',
            'title.required'        => 'Le titre est obligatoire.',
            'description.required'  => 'La description est obligatoire.',
            'photos.*.max'          => 'Chaque photo ne doit pas dépasser 5 Mo.',
        ];
    }
}
