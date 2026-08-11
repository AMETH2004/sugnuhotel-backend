<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeChambre extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'prix_de_base',
        'capacite_max',
        'image',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'prix_de_base' => 'decimal:2',
            'capacite_max' => 'integer',
        ];
    }

    /**
     * Les chambres physiques rattachées à ce type (Standard, Deluxe, Suite...).
     */
    public function chambres(): HasMany
    {
        return $this->hasMany(Chambre::class);
    }

    /**
     * URL publique de l'image (disque "public", accessible via /storage/...).
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}
