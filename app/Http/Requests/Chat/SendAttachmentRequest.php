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
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,mp4,mov,avi,mp3,m4a,ogg,wav',
            ],
            'caption' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Veuillez sélectionner un fichier.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 Mo.',
            'file.mimes' => 'Type de fichier non autorisé. Formats acceptés : images, PDF, documents Office, vidéos et audio.',
        ];
    }
}
