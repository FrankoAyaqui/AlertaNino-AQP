<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Comunicado oficial de la Comisión Multisectorial ENFEN. */
class ComunicadoEnfen extends Model
{
    protected $table = 'comunicados_enfen';

    protected $fillable = ['numero', 'fecha', 'estado', 'titular', 'resumen', 'lluvias', 'pdf_url', 'consultado_en'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'consultado_en' => 'datetime'];
    }

    public static function ultimo(): ?self
    {
        return static::query()->orderByDesc('fecha')->orderByDesc('id')->first();
    }
}
