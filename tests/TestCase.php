<?php

namespace Tests;

use App\Models\Distrito;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Crea un usuario con el rol indicado (el rol se asigna fuera de la asignación masiva). */
    protected function usuario(string $rol = 'ciudadano', array $datos = []): User
    {
        $u = User::factory()->create($datos);
        $u->rol = $rol;
        $u->save();

        return $u;
    }

    protected function distrito(array $datos = []): Distrito
    {
        static $n = 0;
        $n++;

        return Distrito::create($datos + [
            'nombre' => "Distrito de prueba {$n}", 'slug' => "distrito-prueba-{$n}", 'provincia' => 'Arequipa',
            'latitud' => -16.40, 'longitud' => -71.53, 'limita_rio' => false, 'es_piloto' => false,
        ]);
    }

    /** Datos válidos de un aviso para formularios. */
    protected function datosAviso(array $cambios = []): array
    {
        return $cambios + [
            'titulo' => 'Aviso de prueba',
            'distrito_id' => null,
            'fuente' => 'SENAMHI',
            'tipo' => 'lluvias',
            'nivel' => 3,
            'mensaje' => 'Mensaje oficial copiado de la fuente para la prueba.',
            'recomendacion' => 'Seguir las indicaciones de las autoridades.',
            'fuente_url' => 'https://www.senamhi.gob.pe/',
            'emitido_en' => now()->subHour()->format('Y-m-d H:i'),
            'vence_en' => now()->addDay()->format('Y-m-d H:i'),
            'estado' => 'publicado',
        ];
    }
}
