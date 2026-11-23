<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    private function datosRegistro(array $cambios = []): array
    {
        return $cambios + [
            'name' => 'Ana Pérez', 'email' => 'ana@example.test',
            'password' => 'ClaveSegura2026', 'password_confirmation' => 'ClaveSegura2026',
            'acepta_privacidad' => '1',
        ];
    }

    public function test_registro_guarda_la_contrasena_como_hash_y_crea_un_ciudadano(): void
    {
        $this->post('/registro', $this->datosRegistro())->assertRedirect(route('inicio'));

        $u = User::where('email', 'ana@example.test')->firstOrFail();
        $this->assertNotSame('ClaveSegura2026', $u->password);
        $this->assertTrue(Hash::check('ClaveSegura2026', $u->password));
        $this->assertStringStartsWith('$2y$', $u->password); // bcrypt
        $this->assertSame('ciudadano', $u->rol);
        $this->assertAuthenticatedAs($u);
    }

    public function test_el_registro_ignora_un_rol_enviado_por_el_cliente(): void
    {
        $this->post('/registro', $this->datosRegistro(['rol' => 'administrador']));

        $this->assertSame('ciudadano', User::where('email', 'ana@example.test')->value('rol'));
    }

    public function test_el_registro_rechaza_contrasenas_debiles_y_sin_aceptar_privacidad(): void
    {
        $this->post('/registro', $this->datosRegistro(['password' => 'corta', 'password_confirmation' => 'corta', 'acepta_privacidad' => null]))
            ->assertSessionHasErrors(['password', 'acepta_privacidad']);
        $this->assertGuest();
    }

    public function test_ingreso_correcto_e_incorrecto(): void
    {
        $u = $this->usuario('ciudadano', ['email' => 'luis@example.test', 'password' => 'ClaveSegura2026']);

        $this->post('/ingresar', ['email' => 'luis@example.test', 'password' => 'otra'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/ingresar', ['email' => 'luis@example.test', 'password' => 'ClaveSegura2026'])->assertRedirect(route('inicio'));
        $this->assertAuthenticatedAs($u);
    }

    public function test_se_bloquea_tras_cinco_intentos_por_minuto(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/ingresar', ['email' => 'x@example.test', 'password' => 'mala']);
        }
        $this->post('/ingresar', ['email' => 'x@example.test', 'password' => 'mala'])->assertStatus(429);
    }

    public function test_cerrar_sesion(): void
    {
        $this->actingAs($this->usuario())->post('/salir')->assertRedirect(route('inicio'));
        $this->assertGuest();
    }
}
