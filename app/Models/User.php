<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del sistema. Tiene uno de tres roles:
 *  - ciudadano: consulta, elige su distrito y recibe avisos en su bandeja.
 *  - operador: además gestiona avisos, guías y contactos y ejecuta la lectura oficial.
 *  - administrador: además gestiona distritos y usuarios.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['ciudadano', 'operador', 'administrador'];

    /**
     * Campos asignables en masa. El rol NO está aquí: solo lo cambia un
     * administrador de forma explícita, así un formulario no puede escalar privilegios.
     */
    protected $fillable = ['name', 'email', 'password', 'distrito_id', 'recibir_alertas'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // se guarda siempre con bcrypt
            'recibir_alertas' => 'boolean',
        ];
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class);
    }

    public function avisos(): HasMany
    {
        return $this->hasMany(Aviso::class);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    /** Operadores y administradores pueden gestionar contenido. */
    public function esPersonal(): bool
    {
        return in_array($this->rol, ['operador', 'administrador'], true);
    }
}
