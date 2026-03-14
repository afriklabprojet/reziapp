<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'residence_id',
        'owner_id',
        'phone',
        'message',
        'status',
        'viewed_at',
        'responded_at',
        'user_latitude',
        'user_longitude',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'responded_at' => 'datetime',
        'user_latitude' => 'decimal:8',
        'user_longitude' => 'decimal:8',
    ];

    /**
     * L'utilisateur qui a fait la demande de contact
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La résidence concernée
     */
    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Le propriétaire de la résidence
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Scope pour les contacts en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour les contacts d'un propriétaire
     */
    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Marquer comme vu
     */
    public function markAsViewed(): void
    {
        $this->update([
            'status' => 'viewed',
            'viewed_at' => now(),
        ]);
    }

    /**
     * Marquer comme répondu
     */
    public function markAsResponded(): void
    {
        $this->update([
            'status' => 'responded',
            'responded_at' => now(),
        ]);
    }
}
