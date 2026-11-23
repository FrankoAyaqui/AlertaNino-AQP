<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Nivel del aviso por lluvias de 24 h de SENAMHI para un distrito y una fecha. */
class LecturaSenamhi extends Model
{
    protected $table = 'lecturas_senamhi';

    protected $fillable = ['distrito_id', 'fecha', 'nivel', 'descripcion', 'recomendacion', 'consultado_en', 'origen'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'consultado_en' => 'datetime', 'nivel' => 'integer'];
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class);
    }

    /** Una lectura antigua no se presenta como vigente. */
    public function estaVerificada(): bool
    {
        return $this->consultado_en->gt(now()->subHours(config('alerta.horas_vigencia_lectura')));
    }
}
