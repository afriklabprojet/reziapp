<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\SmartLock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSmartLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'residence_id' => ['required', 'integer', 'exists:residences,id'],
            'device_name'  => ['required', 'string', 'max:255'],
            'provider'     => ['required', Rule::in(array_keys(SmartLock::PROVIDERS))],
            'device_id'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'residence_id.required' => 'Sélectionnez une résidence.',
            'device_name.required'  => 'Le nom de la serrure est obligatoire.',
            'provider.required'     => 'Sélectionnez un fournisseur.',
        ];
    }
}
