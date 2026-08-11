<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReservation extends Model
{
    protected $fillable = [
        'reservation_id',
        'service_id',
        'quantite',
        'prix',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
