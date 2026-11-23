<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Emergencia registrada por INDECI en el SINPAD (provincia de Arequipa). */
class Emergencia extends Model
{
    protected $fillable = ['ide_sinpad', 'fecha', 'distrito', 'fenomeno', 'afectados', 'damnificados',
        'viviendas_afectadas', 'latitud', 'longitud'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime', 'latitud' => 'float', 'longitud' => 'float'];
    }
}
