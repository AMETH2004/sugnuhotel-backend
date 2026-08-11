<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomAmenity extends Model
{
    protected $table = 'room_amenities';

    protected $fillable = [
        'chambre_id',
        'amenity_name',
    ];

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class);
    }
}
