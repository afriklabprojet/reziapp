<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'relationship',
        'email',
        'is_primary',
        'notify_on_emergency',
        'share_location',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'notify_on_emergency' => 'boolean',
        'share_location' => 'boolean',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeNotifiable($query)
    {
        return $query->where('notify_on_emergency', true);
    }

    // ==========================================
    // MÉTHODES
    // ==========================================

    /**
     * Définir comme contact principal
     */
    public function setAsPrimary(): void
    {
        // Retirer le statut principal des autres contacts
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    /**
     * Obtenir le label de la relation
     */
    public function getRelationshipLabel(): string
    {
        return match($this->relationship) {
            'parent' => 'Parent',
            'spouse' => 'Conjoint(e)',
            'sibling' => 'Frère/Sœur',
            'child' => 'Enfant',
            'friend' => 'Ami(e)',
            'colleague' => 'Collègue',
            'other' => 'Autre',
            default => $this->relationship ?? 'Non spécifié',
        };
    }
}
