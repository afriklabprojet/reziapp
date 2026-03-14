<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PhotoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => Storage::url($this->path),
            'full_url' => url(Storage::url($this->path)),
            'is_primary' => (bool) $this->is_primary,
            'order' => $this->order,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
