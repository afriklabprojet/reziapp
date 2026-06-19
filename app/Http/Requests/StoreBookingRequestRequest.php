<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation pour la création d'une demande de réservation.
 *
 * Extrait depuis BookingController::storeRequest() pour garder
 * le contrôleur mince et réutiliser la logique de validation.
 */
class StoreBookingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'guests'   => (int) $this->input('guests', 1),
            'adults'   => (int) $this->input('adults', 1),
            'children' => (int) $this->input('children', 0),
            'infants'  => (int) $this->input('infants', 0),
        ]);
    }

    public function rules(): array
    {
        $residence = $this->route('residence');
        $maxGuests = $residence?->max_guests ?? 20;

        return [
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => "required|integer|min:1|max:{$maxGuests}",
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'infants' => 'nullable|integer|min:0',
            'message' => 'nullable|string|max:1000',
            'special_requests' => 'nullable|array',
            'promo_code' => 'nullable|string',
            'coupon_code' => 'nullable|string',
            'payment_method' => 'required|string|in:wave,orange,mtn,moov,djamo',
            'payment_split'       => 'nullable|boolean',
            'use_wallet_credit'   => 'nullable|boolean',
            'use_referral_credit' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.after' => 'La date d\'arrivée doit être dans le futur.',
            'check_out.after' => 'La date de départ doit être après la date d\'arrivée.',
            'guests.max' => 'Cette résidence accepte :max voyageurs maximum.',
        ];
    }
}
