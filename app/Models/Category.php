<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'color',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Résidences de cette catégorie
     */
    public function residences()
    {
        return $this->hasMany(Residence::class);
    }

    /**
     * Résidences disponibles de cette catégorie
     */
    public function availableResidences()
    {
        return $this->hasMany(Residence::class)
            ->where('status', 'active')
            ->where('is_available', true);
    }

    /**
     * Scope pour les catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour le tri par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Nombre de résidences disponibles dans la catégorie
     * Utilise le withCount si déjà chargé, sinon compte les résidences approved + available
     */
    public function getResidencesCountAttribute($value)
    {
        // Si withCount a déjà fourni la valeur, la retourner directement
        if ($value !== null) {
            return $value;
        }

        return $this->availableResidences()->count();
    }

    /**
     * URL de l'image ou placeholder
     */
    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : asset('images/categories/default.jpg');
    }
}
