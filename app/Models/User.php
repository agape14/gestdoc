<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'allowed_menus',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'allowed_menus' => 'array',
        ];
    }

    /**
     * Indica si el usuario puede ver un menú (por clave, ej: 'licitaciones').
     * Administrador ve todo. Null/empty allowed_menus = todos los menús.
     */
    public function canAccessMenu(string $menuKey): bool
    {
        if ($this->role === 'Administrador') {
            return true;
        }
        if (empty($this->allowed_menus)) {
            return true;
        }
        return in_array($menuKey, $this->allowed_menus, true);
    }
}
