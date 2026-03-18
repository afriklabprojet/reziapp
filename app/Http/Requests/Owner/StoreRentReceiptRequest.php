<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentReceiptRequest extends FormRequest
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
            'period_start'       => ['required', 'date'],
            'period_end'         => ['required', 'date', 'after_or_equal:period_start'],
            'rent_amount'        => ['required', 'numeric', 'min:0'],
            'charges_amount'     => ['nullable', 'numeric', 'min:0'],
            'payment_method'     => ['nullable', 'string', 'in:mobile_money,bank,cash,other'],
            'payment_reference'  => ['nullable', 'string', 'max:100'],
            'payment_date'       => ['required', 'date', 'before_or_equal:today'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'sent_by_email'      => ['boolean'],
            'sent_by_whatsapp'   => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required'    => 'Sélectionnez un locataire.',
            'residence_id.required' => 'Sélectionnez une résidence.',
            'period_start.required' => 'La date de début de période est obligatoire.',
            'period_end.required'   => 'La date de fin de période est obligatoire.',
            'period_end.after_or_equal' => 'La fin de période doit être après le début.',
            'rent_amount.required'  => 'Le montant du loyer est obligatoire.',
            'payment_date.required' => 'La date de paiement est obligatoire.',
            'payment_date.before_or_equal' => 'La date de paiement ne peut pas être dans le futur.',
        ];
    }
}
