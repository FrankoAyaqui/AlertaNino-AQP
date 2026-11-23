<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Evento de seguridad (trazabilidad defensiva): solo registra. No ejecuta
 * ninguna acción contra el origen de la solicitud.
 */
class RegistroSeguridad extends Model
{
    public $timestamps = false;

    protected $table = 'registros_seguridad';

    protected $fillable = ['ip', 'metodo', 'ruta', 'patron', 'severidad', 'estado_http', 'user_agent', 'user_id'];

    protected $casts = ['created_at' => 'datetime'];

    /** Anota un evento a partir de la solicitud actual. Nunca lanza una excepción. */
    public static function anota(Request $request, string $severidad, ?string $patron = null, ?int $estadoHttp = null): void
    {
        try {
            static::create([
                'ip' => $request->ip(),
                'metodo' => $request->method(),
                'ruta' => Str::limit($request->path(), 490, ''),
                'patron' => $patron,
                'severidad' => $severidad,
                'estado_http' => $estadoHttp,
                'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
                'user_id' => $request->user()?->id,
            ]);
        } catch (\Throwable) {
            // La seguridad del registro nunca debe tumbar la aplicación.
        }
    }
}
