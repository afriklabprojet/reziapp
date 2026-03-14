<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreSecurityDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'tenant_id'          => ['required', 'integer', 'exists:users,id'],
            'residence_id'       => ['required', 'integer', 'exists:residences,id'],
            'booking_id'         => ['nullable', 'integer', 'exists:bookings,id'],
            'lease_contract_id'  => ['nullable', 'integer', 'exists:lease_contracts,id'],
            'amount'             => ['required', 'numeric', 'min:1'],
            'payment_method'     => ['nullable', 'string', 'in:mobile_money,bank,cash,other'],
            'payment_reference'  => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant de la caution est obligatoire.',
            'amount.min'      => 'Le montant doit être supérieur à 0.',
        ];
    }
}
