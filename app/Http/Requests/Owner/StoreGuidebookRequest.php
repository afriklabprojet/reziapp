<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuidebookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'residence_id'  => ['required', 'integer', 'exists:residences,id'],
            'title'         => ['required', 'string', 'max:255'],
            'welcome_message' => ['nullable', 'string', 'max:2000'],
            'wifi_name'     => ['nullable', 'string', 'max:100'],
            'wifi_password' => ['nullable', 'string', 'max:100'],
            'house_rules'   => ['nullable', 'string', 'max:5000'],
            'parking_info'  => ['nullable', 'string', 'max:1000'],
            'emergency_contacts' => ['nullable', 'array'],
            'transport_info' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'residence_id.required' => 'Sélectionnez une résidence.',
            'title.required'        => 'Le titre du guide est obligatoire.',
        ];
    }
}
