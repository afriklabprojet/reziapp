<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'residence_id' => ['required', 'integer', 'exists:residences,id'],
            'category'     => ['required', Rule::in(array_keys(MaintenanceRequest::CATEGORIES))],
            'priority'     => ['required', Rule::in(array_keys(MaintenanceRequest::PRIORITIES))],
            'title'        => ['required', 'string', 'max:200'],
            'description'  => ['required', 'string', 'max:2000'],
            'photos'       => ['nullable', 'array', 'max:5'],
            'photos.*'     => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'assigned_to'  => ['nullable', 'integer', 'exists:users,id'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'residence_id.required' => 'Sélectionnez une résidence.',
            'category.required'     => 'La catégorie est obligatoire.',
            'priority.required'     => 'La priorité est obligatoire.',
            'title.required'        => 'Le titre est obligatoire.',
            'description.required'  => 'La description est obligatoire.',
        ];
    }
}
