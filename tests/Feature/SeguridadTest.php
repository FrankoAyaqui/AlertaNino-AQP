<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Notifications\AvisoPublicado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_texto_con_script_se_muestra_escapado(): void
    {
        $aviso = Aviso::create($this->datosAviso(['titulo' => '<script>alert("x")</script>Aviso']));

        $this->get("/avisos/{$aviso->id}")
            ->assertOk()
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_la_busqueda_no_permite_inyeccion_sql(): void
    {
        Aviso::create($this->datosAviso(['titulo' => 'Lluvias en Socabaya']));

        // Si la consulta concatenara texto, esto devolvería todos los avisos.
        $this->get('/avisos?q='.urlencode("' OR '1'='1"))->assertOk()->assertDontSee('Lluvias en Socabaya');
        $this->get('/avisos?q=Socabaya')->assertOk()->assertSee('Lluvias en Socabaya');
    }

    public function test_cabeceras_de_seguridad(): void
    {
        $r = $this->get('/');
        $r->assertHeader('X-Frame-Options', 'DENY');
        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString("script-src 'self' https://cdnjs.cloudflare.com", $r->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", $r->headers->get('Content-Security-Policy'));
    }

    public function test_no_se_puede_marcar_una_notificacion_ajena(): void
    {
        $aviso = Aviso::create($this->datosAviso());
        $duena = $this->usuario();
        $duena->notify(new AvisoPublicado($aviso));
        $id = $duena->notifications()->first()->id;

        $this->actingAs($this->usuario())->post("/bandeja/{$id}/leida")->assertNotFound();
        $this->assertNull($duena->notifications()->first()->read_at);
    }

    public function test_la_api_publica_responde_json(): void
    {
        $this->distrito(['nombre' => 'Socabaya', 'slug' => 'socabaya']);
        $this->getJson('/api/estado')
            ->assertOk()
            ->assertJsonStructure(['generado', 'distritos' => [['slug', 'nombre', 'latitud', 'longitud', 'senamhi', 'avisos_vigentes']], 'enfen', 'emergencias']);
    }

    public function test_las_paginas_publicas_cargan(): void
    {
        $this->distrito(['nombre' => 'Arequipa (Cercado)', 'slug' => 'cercado']);
        foreach (['/', '/avisos', '/guias', '/contactos', '/fuentes', '/privacidad', '/ingresar', '/registro'] as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }
}
