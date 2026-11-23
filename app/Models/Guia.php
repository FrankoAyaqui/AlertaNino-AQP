<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Orientación preventiva (antes, durante y después de lluvias intensas). */
class Guia extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'categoria', 'contenido', 'fuente_url', 'orden', 'publicado'];

    protected function casts(): array
    {
        return ['publicado' => 'boolean', 'orden' => 'integer'];
    }

    public function nombreCategoria(): string
    {
        return config('alerta.categorias_guia.'.$this->categoria, $this->categoria);
    }
}
