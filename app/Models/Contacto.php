<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Teléfono de emergencia o de orientación, general o de un distrito. */
class Contacto extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'telefono', 'tipo', 'distrito_id', 'descripcion', 'fuente_url'];

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class);
    }

    public function nombreTipo(): string
    {
        return config('alerta.tipos_contacto.'.$this->tipo, $this->tipo);
    }
}
