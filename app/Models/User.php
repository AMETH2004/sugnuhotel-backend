<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
            // Si tu haches déjà avec Hash::make() dans le contrôleur, 
            // vérifie qu'il N'Y A PAS 'password' => 'hashed' ici pour éviter le double-hachage !
        ];
    }
}