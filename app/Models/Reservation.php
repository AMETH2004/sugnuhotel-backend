<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory;

    /**
     * Statuts du cycle de vie d'une réservation.
     */
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_CONFIRMEE = 'confirmee';
    public const STATUT_ENREGISTREE = 'enregistree'; // check-in effectué
    public const STATUT_TERMINEE = 'terminee';        // check-out effectué
    public const STATUT_ANNULEE = 'annulee';

    protected $fillable = [
        'numero_reservation',
        'user_id',
        'chambre_id',
        'date_arrivee',
        'date_depart',
        'nombre_adultes',
        'nombre_enfants',
        'prix_total',
        'statut',
        'demandes_speciales',
    ];

    protected function casts(): array
    {
        return [
            'date_arrivee' => 'date',
            'date_depart' => 'date',
            'nombre_adultes' => 'integer',
            'nombre_enfants' => 'integer',
            'prix_total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chambre(): BelongsTo
    {
        return $this->belongsTo(Chambre::class);
    }

    public function serviceReservations(): HasMany
    {
        return $this->hasMany(ServiceReservation::class);
    }

    /**
     * Services additionnels liés à cette réservation (petit-déjeuner, parking, spa...).
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_reservations')
            ->withPivot(['quantite', 'prix'])
            ->withTimestamps();
    }

    /**
     * Nombre de nuits du séjour.
     */
    public function getNombreNuitsAttribute(): int
    {
        return (int) Carbon::parse($this->date_arrivee)->diffInDays(Carbon::parse($this->date_depart));
    }

    // ------------------------------------------------------------------
    // Scopes pour le parcours client : à venir / en cours / passées
    // ------------------------------------------------------------------

    public function scopeAVenir(Builder $query): Builder
    {
        return $query->whereNotIn('statut', [self::STATUT_ANNULEE, self::STATUT_TERMINEE])
            ->whereDate('date_arrivee', '>', Carbon::today());
    }

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->whereNotIn('statut', [self::STATUT_ANNULEE, self::STATUT_TERMINEE])
            ->whereDate('date_arrivee', '<=', Carbon::today())
            ->whereDate('date_depart', '>=', Carbon::today());
    }

    public function scopePassees(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('statut', self::STATUT_TERMINEE)
                ->orWhereDate('date_depart', '<', Carbon::today());
        });
    }

    /**
     * Réservations actives (non annulées) — utilisé pour la détection de conflits.
     */
    public function scopeActives(Builder $query): Builder
    {
        return $query->whereNotIn('statut', [self::STATUT_ANNULEE]);
    }
}
