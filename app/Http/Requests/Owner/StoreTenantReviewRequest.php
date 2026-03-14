<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\TenantReview;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'tenant_id'       => ['required', 'integer', 'exists:users,id'],
            'booking_id'      => ['nullable', 'integer', 'exists:bookings,id'],
            'residence_id'    => ['required', 'integer', 'exists:residences,id'],
            'cleanliness'     => ['required', 'integer', 'min:1', 'max:5'],
            'respect_rules'   => ['required', 'integer', 'min:1', 'max:5'],
            'communication'   => ['required', 'integer', 'min:1', 'max:5'],
            'payment'         => ['required', 'integer', 'min:1', 'max:5'],
            'overall'         => ['required', 'integer', 'min:1', 'max:5'],
            'comment'         => ['nullable', 'string', 'max:2000'],
            'would_rent_again' => ['boolean'],
            'is_public'       => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required'     => 'Sélectionnez un locataire.',
            'residence_id.required'  => 'Sélectionnez une résidence.',
            'cleanliness.required'   => 'La note propreté est obligatoire.',
            'respect_rules.required' => 'La note respect des règles est obligatoire.',
            'communication.required' => 'La note communication est obligatoire.',
            'payment.required'       => 'La note paiement est obligatoire.',
            'overall.required'       => 'La note globale est obligatoire.',
        ];
    }
}
