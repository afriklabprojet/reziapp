<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class PhotoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photoUrl = \storage_url($this->path);

        return [
            'id' => $this->id,
            'url' => $photoUrl,
            'full_url' => str_starts_with($photoUrl, 'http://') || str_starts_with($photoUrl, 'https://') ? $photoUrl : url($photoUrl),
            'is_primary' => (bool) $this->is_primary,
            'order' => $this->order,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
