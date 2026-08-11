<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomImage extends Model
{
    protected $table = 'room_images';

    protected $fillable = [
        'chambre_id',
        'chemin',
        'est_principale',
        'ordre',
    ];

    // Sans $appends, l'accesseur "url" n'est calculé qu'à la demande en PHP
    // et n'apparaît jamais dans la réponse JSON envoyée au frontend Angular.
    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'est_principale' => 'boolean',
            'ordre' => 'integer',
        ];
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class);
    }

    /**
     * URL publique de l'image (disque "public", accessible via /storage/...).
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->chemin);
    }
}
