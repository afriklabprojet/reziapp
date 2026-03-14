<?php

declare(strict_types=1);

namespace App\Http\Requests\Owner;

use App\Models\IcalFeed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIcalFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'residence_id' => ['required', 'integer', 'exists:residences,id'],
            'platform'     => ['required', Rule::in(array_keys(IcalFeed::PLATFORMS))],
            'name'         => ['required', 'string', 'max:255'],
            'import_url'   => ['required', 'url', 'max:1000'],
            'auto_sync'    => ['boolean'],
            'sync_interval_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ];
    }

    public function messages(): array
    {
        return [
            'residence_id.required' => 'Sélectionnez une résidence.',
            'platform.required'     => 'Sélectionnez la plateforme.',
            'import_url.required'   => 'L\'URL iCal est obligatoire.',
            'import_url.url'        => 'L\'URL n\'est pas valide.',
        ];
    }
}
