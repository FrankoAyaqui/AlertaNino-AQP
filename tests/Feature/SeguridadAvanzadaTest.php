<?php

namespace Tests\Feature;

use App\Models\RegistroSeguridad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Vigilancia, bloqueo, trazabilidad y cabeceras avanzadas de seguridad. */
class SeguridadAvanzadaTest extends TestCase
{
    use RefreshDatabase;

    public static function solicitudesDeAtaque(): array
    {
        return [
            'traversal' => ['/?archivo=../../../../etc/passwd'],
            'xss en url' => ['/avisos?q=<script>alert(1)</script>'],
            'sql injection' => ['/avisos?q=1 UNION SELECT password FROM users'],
            'busca .env' => ['/.env'],
            'busca git' => ['/.git/config'],
            'escaner wordpress' => ['/wp-login.php'],
        ];
    }

    #[DataProvider('solicitudesDeAtaque')]
    public function test_bloquea_y_registra_solicitudes_de_ataque(string $ruta): void
    {
        $this->get($ruta)->assertForbidden();
        $this->assertDatabaseHas('registros_seguridad', ['severidad' => 'critico']);
    }

    public function test_una_peticion_normal_pasa_sin_registro(): void
    {
        $this->distrito(['nombre' => 'Arequipa (Cercado)', 'slug' => 'cercado']);
        $this->get('/avisos?q=lluvia')->assertOk();
        $this->assertDatabaseCount('registros_seguridad', 0);
    }

    public function test_tras_varios_intentos_se_bloquea_la_ip(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->get('/?x=../../etc/passwd');
        }
        // Ya bloqueada: hasta una ruta válida responde 429.
        $this->get('/')->assertStatus(429);
        $this->assertDatabaseHas('registros_seguridad', ['severidad' => 'bloqueado']);
    }

    public function test_el_login_fallido_queda_registrado_con_su_ip(): void
    {
        $this->usuario('ciudadano', ['email' => 'real@example.test', 'password' => 'ClaveSegura2026']);
        $this->post('/ingresar', ['email' => 'real@example.test', 'password' => 'incorrecta'])->assertSessionHasErrors('email');
        $this->assertDatabaseHas('registros_seguridad', ['patron' => 'login-fallido']);
    }

    public function test_cabeceras_hsts_y_csp_en_conexion_segura(): void
    {
        $this->distrito(['nombre' => 'Arequipa (Cercado)', 'slug' => 'cercado']);
        $r = $this->get('https://localhost/');
        $r->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $r->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertStringContainsString("object-src 'none'", $r->headers->get('Content-Security-Policy'));
    }

    public function test_el_registro_de_seguridad_es_solo_para_administradores(): void
    {
        RegistroSeguridad::create(['ip' => '203.0.113.9', 'metodo' => 'GET', 'ruta' => '/.env', 'severidad' => 'critico']);
        $this->actingAs($this->usuario('operador'))->get('/panel/seguridad')->assertForbidden();
        $this->actingAs($this->usuario('administrador'))->get('/panel/seguridad')->assertOk()->assertSee('203.0.113.9');
    }

    public function test_cors_restringido_a_la_api_de_solo_lectura(): void
    {
        $this->assertSame(['api/*'], config('cors.paths'));
        $this->assertSame(['GET'], config('cors.allowed_methods'));
        $this->assertFalse(config('cors.supports_credentials'));
    }
}
