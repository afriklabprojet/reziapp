<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaseContractRequest extends FormRequest
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
            'start_date'         => ['required', 'date'],
            'end_date'           => ['nullable', 'date', 'after:start_date'],
            'lease_type'         => ['required', 'in:short_term,monthly,fixed_term'],
            'monthly_rent'       => ['required', 'numeric', 'min:0'],
            'deposit_amount'     => ['nullable', 'numeric', 'min:0'],
            'charges_amount'     => ['nullable', 'numeric', 'min:0'],
            'payment_day'        => ['nullable', 'integer', 'min:1', 'max:28'],
            'special_clauses'    => ['nullable', 'string', 'max:5000'],
            'included_services'  => ['nullable', 'array'],
            'included_services.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required'   => 'Sélectionnez un locataire.',
            'tenant_id.exists'     => 'Ce locataire n\'existe pas dans notre système.',
            'residence_id.required' => 'Sélectionnez une résidence.',
            'start_date.required'  => 'La date de début est obligatoire.',
            'start_date.after_or_equal' => 'La date de début doit être aujourd\'hui ou ultérieure.',
            'end_date.after'       => 'La date de fin doit être après la date de début.',
            'lease_type.required'  => 'Le type de bail est obligatoire.',
            'monthly_rent.required' => 'Le montant du loyer est obligatoire.',
            'monthly_rent.min'     => 'Le loyer ne peut pas être négatif.',
            'payment_day.min'      => 'Le jour de paiement doit être entre 1 et 28.',
            'payment_day.max'      => 'Le jour de paiement doit être entre 1 et 28.',
        ];
    }
}
