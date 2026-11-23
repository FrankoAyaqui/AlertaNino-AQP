<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Registro de cada lectura de una fuente oficial (trazabilidad). */
class Sincronizacion extends Model
{
    protected $table = 'sincronizaciones';

    protected $fillable = ['fuente', 'estado', 'registros', 'detalle'];

    /** Última lectura exitosa (directa o por espejo) de una fuente. */
    public static function ultimaExitosa(string $fuente): ?self
    {
        return static::query()->where('fuente', $fuente)->whereIn('estado', ['ok', 'espejo'])->latest('id')->first();
    }
}
