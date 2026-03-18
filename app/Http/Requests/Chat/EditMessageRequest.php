<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class EditMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $message = $this->route('message');

        // Seul l'expéditeur peut modifier
        if ($message->sender_id !== $this->user()->id) {
            return false;
        }

        // Modifiable dans les 15 minutes seulement
        return $message->created_at->diffInMinutes(now()) <= 15;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Le contenu ne peut pas être vide.',
            'content.max' => 'Le message ne doit pas dépasser 5000 caractères.',
        ];
    }
}
