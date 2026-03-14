<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OwnerDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class OwnerDocumentService
{
    public function getDocuments(User $owner, array $filters = []): LengthAwarePaginator
    {
        $query = OwnerDocument::forOwner($owner->id)->with('residence');

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['residence_id'])) {
            $query->where('residence_id', $filters['residence_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function upload(User $owner, array $data, UploadedFile $file): OwnerDocument
    {
        $path = $file->store("owner-documents/{$owner->id}", 'private');

        return OwnerDocument::create([
            'owner_id'     => $owner->id,
            'residence_id' => $data['residence_id'] ?? null,
            'category'     => $data['category'],
            'name'         => $data['name'] ?? $file->getClientOriginalName(),
            'file_path'    => $path,
            'file_type'    => $file->getClientMimeType(),
            'file_size'    => $file->getSize(),
            'expiry_date'  => $data['expiry_date'] ?? null,
            'notes'        => $data['notes'] ?? null,
        ]);
    }

    public function update(OwnerDocument $document, array $data): OwnerDocument
    {
        $document->update($data);
        return $document->fresh();
    }

    public function delete(OwnerDocument $document): void
    {
        if ($document->file_path) {
            Storage::disk('private')->delete($document->file_path);
        }
        $document->delete();
    }

    public function getExpiringDocuments(User $owner, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return OwnerDocument::forOwner($owner->id)
            ->expiring($days)
            ->with('residence')
            ->get();
    }

    public function getExpiredDocuments(User $owner): \Illuminate\Database\Eloquent\Collection
    {
        return OwnerDocument::forOwner($owner->id)
            ->expired()
            ->with('residence')
            ->get();
    }
}
