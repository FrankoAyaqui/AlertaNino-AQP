<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\ComunicadoEnfen;
use App\Models\Emergencia;
use App\Models\LecturaSenamhi;
use App\Models\Sincronizacion;
use App\Notifications\AvisoPublicado;
use App\Notifications\NivelSenamhiElevado;
use App\Services\SincronizadorOficial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Lectura de fuentes oficiales con respuestas simuladas (Http::fake): las
 * pruebas no dependen de que SENAMHI o INDECI estén disponibles.
 */
class SincronizacionTest extends TestCase
{
    use RefreshDatabase;

    /** Zona de aviso cuadrada que cubre Arequipa metropolitana. */
    private function zonaArequipa(string $nivel): array
    {
        return ['type' => 'FeatureCollection', 'features' => [[
            'type' => 'Feature',
            'geometry' => ['type' => 'Polygon', 'coordinates' => [[[-71.7, -16.6], [-71.3, -16.6], [-71.3, -16.2], [-71.7, -16.2], [-71.7, -16.6]]]],
            'properties' => ['nivel' => $nivel, 'fecha' => now()->toDateString().'Z',
                'descripcio' => 'Texto oficial de SENAMHI.', 'recomendac' => 'Recomendación oficial.'],
        ]]];
    }

    private function espejo(): array
    {
        return [
            'generado' => now()->toIso8601String(),
            'senamhi' => ['consultado' => now()->subMinutes(20)->toIso8601String(), 'por_distrito' => [
                'socabaya' => ['lluvia_24h' => ['nivel' => 2, 'fecha' => now()->toDateString(), 'descripcion' => 'Desde espejo', 'recomendacion' => null]],
            ]],
            'enfen' => ['numero' => '20-2026', 'fecha' => '2026-11-13', 'estado' => 'Alerta de El Niño Costero',
                'titular' => 'EL NIÑO COSTERO CONTINUARÍA', 'resumen' => 'Resumen.', 'lluvias' => null,
                'pdf' => 'https://www.senamhi.gob.pe/load/file/02204SENA-226.pdf'],
        ];
    }

    private function fingirFuentes(bool $senamhiCae = false, string $nivel = 'Nivel 3'): void
    {
        Http::fake(function (Request $r) use ($senamhiCae, $nivel) {
            $url = $r->url();
            if (str_contains($url, 'idesep.senamhi.gob.pe')) {
                if ($senamhiCae) {
                    return Http::response('', 504);
                }

                return Http::response(str_contains($url, 'view_aviso24h') ? $this->zonaArequipa($nivel)
                    : ['type' => 'FeatureCollection', 'features' => []]);
            }
            if (str_contains($url, 'www.senamhi.gob.pe')) {
                return Http::response('', 503); // ENFEN no disponible -> se usa el espejo
            }
            if (str_contains($url, 'github.io')) {
                return Http::response($this->espejo());
            }
            if (str_contains($url, 'geosinpad.indeci.gob.pe')) {
                return Http::response(['features' => [['attributes' => ['IDE_SINPAD' => 187898, 'FECHA' => 1705957440000,
                    'DISTRITO' => 'SOCABAYA', 'FENOMENO' => 'LLUVIAS INTENSAS', 'SAFECTA' => 12, 'SDAMNI' => 3, 'SAFECTAVIVI' => 2],
                    'geometry' => ['x' => -71.53, 'y' => -16.47]]]]);
            }

            return Http::response('', 404);
        });
    }

    public function test_lectura_directa_crea_niveles_avisos_y_notifica(): void
    {
        Notification::fake();
        $d = $this->distrito(['nombre' => 'Socabaya', 'slug' => 'socabaya', 'latitud' => -16.468, 'longitud' => -71.529]);
        $vecino = $this->usuario('ciudadano', ['distrito_id' => $d->id]);
        $this->fingirFuentes();

        $r = app(SincronizadorOficial::class)->ejecutar();

        $this->assertSame('ok', $r['senamhi_lluvia']->estado);
        $l = LecturaSenamhi::firstOrFail();
        $this->assertSame(3, $l->nivel);
        $this->assertSame('directo', $l->origen);
        $this->assertSame('Texto oficial de SENAMHI.', $l->descripcion);

        // Desde Nivel 2 se crea un aviso automático con el texto oficial
        $aviso = Aviso::where('origen', 'automatico')->firstOrFail();
        $this->assertSame(3, $aviso->nivel);
        $this->assertTrue($aviso->estaVigente());
        Notification::assertSentTo($vecino, NivelSenamhiElevado::class);
        Notification::assertSentTo($vecino, AvisoPublicado::class);

        // ENFEN por espejo y SINPAD directo
        $this->assertSame('espejo', $r['enfen']->estado);
        $this->assertSame('Alerta de El Niño Costero', ComunicadoEnfen::ultimo()->estado);
        $this->assertSame(1, Emergencia::count());
        $this->assertSame(5, Sincronizacion::count());
    }

    public function test_repetir_la_lectura_no_duplica_ni_vuelve_a_notificar(): void
    {
        Notification::fake();
        $d = $this->distrito(['nombre' => 'Socabaya', 'slug' => 'socabaya', 'latitud' => -16.468, 'longitud' => -71.529]);
        $vecino = $this->usuario('ciudadano', ['distrito_id' => $d->id]);
        $this->fingirFuentes();

        app(SincronizadorOficial::class)->ejecutar();
        $segunda = app(SincronizadorOficial::class)->ejecutar();

        // La segunda lectura del mismo día actualiza (no falla ni duplica).
        $this->assertSame('ok', $segunda['senamhi_lluvia']->estado);
        $this->assertSame(1, LecturaSenamhi::count());
        $this->assertSame(1, Aviso::count());
        Notification::assertSentToTimes($vecino, AvisoPublicado::class, 1);
        Notification::assertSentToTimes($vecino, NivelSenamhiElevado::class, 1);
    }

    public function test_si_senamhi_no_responde_se_usa_el_espejo_publicado_desde_peru(): void
    {
        $this->distrito(['nombre' => 'Socabaya', 'slug' => 'socabaya', 'latitud' => -16.468, 'longitud' => -71.529]);
        $this->fingirFuentes(senamhiCae: true);

        $r = app(SincronizadorOficial::class)->ejecutar();

        $this->assertSame('espejo', $r['senamhi_lluvia']->estado);
        $this->assertSame('error', $r['senamhi_avisos']->estado);
        $l = LecturaSenamhi::firstOrFail();
        $this->assertSame('espejo', $l->origen);
        $this->assertSame(2, $l->nivel);
    }

    public function test_nivel_1_no_crea_aviso_automatico(): void
    {
        $this->distrito(['nombre' => 'Socabaya', 'slug' => 'socabaya', 'latitud' => -16.468, 'longitud' => -71.529]);
        $this->fingirFuentes(nivel: 'Nivel 1');

        app(SincronizadorOficial::class)->ejecutar();

        $this->assertSame(1, LecturaSenamhi::first()->nivel);
        $this->assertSame(0, Aviso::count());
    }

    public function test_el_comando_artisan_ejecuta_la_lectura(): void
    {
        $this->distrito();
        $this->fingirFuentes();
        $this->artisan('alerta:sincronizar')->assertSuccessful();
    }

    public function test_un_operador_puede_ejecutar_la_lectura_desde_el_panel(): void
    {
        $this->distrito();
        $this->fingirFuentes();
        $this->actingAs($this->usuario('operador'))->post('/panel/sincronizar')
            ->assertRedirect(route('panel.inicio'))->assertSessionHas('estado');
        $this->actingAs($this->usuario('ciudadano'))->post('/panel/sincronizar')->assertForbidden();
    }
}
