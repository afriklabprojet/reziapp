<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreCleaningTaskRequest extends FormRequest
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
            'assigned_to'    => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'cost'           => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'checklist'      => ['nullable', 'array'],
            'checklist.*.item' => ['required_with:checklist', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'residence_id.required'   => 'Sélectionnez une résidence.',
            'scheduled_date.required' => 'La date est obligatoire.',
            'scheduled_date.after_or_equal' => 'La date doit être aujourd\'hui ou après.',
        ];
    }
}
