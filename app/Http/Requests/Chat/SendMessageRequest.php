<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by policy
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
            'template_id' => 'nullable|exists:message_templates,id',
            'reply_to_id' => 'nullable|integer|exists:messages,id',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Le message ne peut pas être vide.',
            'content.max' => 'Le message ne doit pas dépasser 5000 caractères.',
        ];
    }
}
