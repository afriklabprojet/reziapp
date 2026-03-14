<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\PropertyInspection;
use Illuminate\Foundation\Http\FormRequest;

class StorePropertyInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'residence_id'          => ['required', 'integer', 'exists:residences,id'],
            'tenant_id'             => ['nullable', 'integer', 'exists:users,id'],
            'booking_id'            => ['nullable', 'integer', 'exists:bookings,id'],
            'lease_contract_id'     => ['nullable', 'integer', 'exists:lease_contracts,id'],
            'type'                  => ['required', 'in:check_in,check_out,periodic'],
            'inspection_date'       => ['required', 'date'],
            'inspector_name'        => ['nullable', 'string', 'max:150'],
            'tenant_present'        => ['boolean'],
            'electricity_meter'     => ['nullable', 'numeric', 'min:0'],
            'water_meter'           => ['nullable', 'numeric', 'min:0'],
            'gas_meter'             => ['nullable', 'numeric', 'min:0'],
            'keys_count'            => ['nullable', 'integer', 'min:0'],
            'badges_count'          => ['nullable', 'integer', 'min:0'],
            'global_observations'   => ['nullable', 'string', 'max:3000'],
            'estimated_repair_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'residence_id.required'   => 'Sélectionnez une résidence.',
            'type.required'           => 'Indiquez le type d\'état des lieux.',
            'inspection_date.required' => 'La date de l\'état des lieux est obligatoire.',
        ];
    }
}
