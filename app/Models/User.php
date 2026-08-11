<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * Les attributs assignables.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
    ];

    /**
     * Les attributs masqués dans les réponses JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts des attributs.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Le hachage est fait explicitement via Hash::make() dans AuthController,
            // pas de cast 'hashed' ici pour éviter un double hachage.
        ];
    }

    /**
     * Toutes les réservations passées par ce client.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
