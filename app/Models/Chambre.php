<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chambre extends Model
{
    use HasFactory;

    /**
     * Statuts possibles d'une chambre.
     */
    public const STATUT_DISPONIBLE = 'disponible';
    public const STATUT_OCCUPEE = 'occupee';
    public const STATUT_MAINTENANCE = 'maintenance';
    public const STATUT_HORS_SERVICE = 'hors_service';

    protected $fillable = [
        'numero_chambre',
        'type_chambre_id',
        'etage',
        'prix_par_nuit',
        'capacite_max',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'prix_par_nuit' => 'decimal:2',
            'etage' => 'integer',
            'capacite_max' => 'integer',
        ];
    }

    public function typeChambre(): BelongsTo
    {
        return $this->belongsTo(TypeChambre::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class)->orderBy('ordre');
    }

    public function amenities(): HasMany
    {
        return $this->hasMany(RoomAmenity::class);
    }

    /**
     * Vérifie si la chambre est libre sur la période [dateArrivee, dateDepart[.
     * Deux séjours se chevauchent si : arrivee < autre_depart ET depart > autre_arrivee.
     * On exclut les réservations annulées et, pour une modification, la réservation courante.
     */
    public function estDisponiblePour(string $dateArrivee, string $dateDepart, ?int $exclureReservationId = null): bool
    {
        return !$this->reservations()
            ->whereNotIn('statut', ['annulee'])
            ->when($exclureReservationId, fn ($query) => $query->where('id', '!=', $exclureReservationId))
            ->where('date_arrivee', '<', $dateDepart)
            ->where('date_depart', '>', $dateArrivee)
            ->exists();
    }
}
