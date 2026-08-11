<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'prix',
        'est_actif',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'est_actif' => 'boolean',
        ];
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'service_reservations')
            ->withPivot(['quantite', 'prix'])
            ->withTimestamps();
    }
}
