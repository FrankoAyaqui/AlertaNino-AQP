<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aviso oficial mostrado a la ciudadanía.
 *
 * Puede venir de dos orígenes:
 *  - automatico: creado al leer los servicios de SENAMHI (texto copiado sin cambios).
 *  - manual: un operador copia un aviso oficial publicado por otra entidad
 *    (municipalidad, gobierno regional...), siempre con su enlace de origen.
 */
class Aviso extends Model
{
    use HasFactory;

    public const ESTADOS = ['borrador', 'publicado', 'archivado'];

    protected $fillable = [
        'distrito_id', 'titulo', 'fuente', 'tipo', 'nivel', 'mensaje', 'recomendacion',
        'fuente_url', 'emitido_en', 'vence_en', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'emitido_en' => 'datetime',
            'vence_en' => 'datetime',
            'notificado_en' => 'datetime',
            'nivel' => 'integer',
        ];
    }

    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Publicados y todavía dentro de su vigencia. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('estado', 'publicado')
            ->where('emitido_en', '<=', now())
            ->where('vence_en', '>', now());
    }

    /** Avisos que aplican a un distrito: los suyos y los de toda la provincia. */
    public function scopeParaDistrito(Builder $query, ?int $distritoId): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('distrito_id')
            ->when($distritoId, fn (Builder $q) => $q->orWhere('distrito_id', $distritoId)));
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'publicado' && $this->emitido_en <= now() && $this->vence_en > now();
    }

    public function nombreTipo(): string
    {
        return config('alerta.tipos_aviso.'.$this->tipo, $this->tipo);
    }
}
