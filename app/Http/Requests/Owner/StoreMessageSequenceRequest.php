<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\MessageSequence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'residence_id'  => ['nullable', 'integer', 'exists:residences,id'],
            'trigger_event' => ['required', Rule::in(array_keys(MessageSequence::TRIGGERS))],
            'is_active'     => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Le nom de la séquence est obligatoire.',
            'trigger_event.required' => 'Sélectionnez un événement déclencheur.',
            'trigger_event.in'       => 'Événement déclencheur invalide.',
        ];
    }
}
