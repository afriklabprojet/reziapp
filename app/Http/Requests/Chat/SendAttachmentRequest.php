<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by policy
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240', // 10MB
            'caption' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}
