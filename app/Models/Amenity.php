<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    /**
     * Residences that have this amenity
     */
    public function residences()
    {
        return $this->belongsToMany(Residence::class, 'residence_amenity');
    }
}
