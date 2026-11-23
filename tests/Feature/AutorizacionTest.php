<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutorizacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_es_redirigido_al_ingreso(): void
    {
        $this->get('/panel')->assertRedirect(route('ingresar'));
        $this->get('/bandeja')->assertRedirect(route('ingresar'));
    }

    public function test_un_ciudadano_no_entra_al_panel(): void
    {
        $c = $this->usuario('ciudadano');
        $this->actingAs($c)->get('/panel')->assertForbidden();
        $this->actingAs($c)->get('/panel/avisos/create')->assertForbidden();
        $this->actingAs($c)->post('/panel/avisos', $this->datosAviso())->assertForbidden();
    }

    public function test_un_operador_gestiona_contenido_pero_no_distritos_ni_usuarios(): void
    {
        $o = $this->usuario('operador');
        $this->actingAs($o)->get('/panel')->assertOk();
        $this->actingAs($o)->get('/panel/avisos')->assertOk();
        $this->actingAs($o)->get('/panel/guias')->assertOk();
        $this->actingAs($o)->get('/panel/contactos')->assertOk();
        $this->actingAs($o)->get('/panel/distritos')->assertForbidden();
        $this->actingAs($o)->get('/panel/usuarios')->assertForbidden();
    }

    public function test_un_administrador_accede_a_todo(): void
    {
        $a = $this->usuario('administrador');
        foreach (['/panel', '/panel/avisos', '/panel/guias', '/panel/contactos', '/panel/distritos', '/panel/usuarios'] as $ruta) {
            $this->actingAs($a)->get($ruta)->assertOk();
        }
    }
}
