<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Distrito de Arequipa metropolitana con su punto de referencia. */
class Distrito extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'slug', 'provincia', 'latitud', 'longitud', 'limita_rio', 'es_piloto'];

    protected function casts(): array
    {
        return [
            'latitud' => 'float',
            'longitud' => 'float',
            'limita_rio' => 'boolean',
            'es_piloto' => 'boolean',
        ];
    }

    public function avisos(): HasMany
    {
        return $this->hasMany(Aviso::class);
    }

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(LecturaSenamhi::class);
    }

    /** Lectura más reciente del aviso por lluvias de SENAMHI para este distrito. */
    public function ultimaLectura(): HasOne
    {
        return $this->hasOne(LecturaSenamhi::class)->latestOfMany('fecha');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
