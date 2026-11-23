<?php

namespace Tests\Feature;

use App\Models\Aviso;
use App\Models\Contacto;
use App\Models\Distrito;
use App\Models\Guia;
use App\Models\User;
use App\Notifications\AvisoPublicado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Crear, leer, actualizar y eliminar en las cinco entidades, con validación del servidor. */
class CrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_de_avisos_y_notificacion_unica_al_publicar(): void
    {
        Notification::fake();
        $operador = $this->usuario('operador');
        $d = $this->distrito();
        $vecino = $this->usuario('ciudadano', ['distrito_id' => $d->id]);
        $otro = $this->usuario('ciudadano', ['distrito_id' => $this->distrito()->id]);

        // Crear (publicado) -> notifica solo a los suscritos al distrito
        $this->actingAs($operador)->post('/panel/avisos', $this->datosAviso(['distrito_id' => $d->id]))->assertRedirect();
        $aviso = Aviso::firstOrFail();
        $this->assertSame($operador->id, $aviso->user_id);
        $this->assertSame('manual', $aviso->origen);
        Notification::assertSentTo($vecino, AvisoPublicado::class);
        Notification::assertNotSentTo($otro, AvisoPublicado::class);

        // Leer
        $this->actingAs($operador)->get("/panel/avisos/{$aviso->id}")->assertOk()->assertSee('Aviso de prueba');
        $this->get("/avisos/{$aviso->id}")->assertOk()->assertSee('Mensaje oficial');

        // Actualizar -> no vuelve a notificar
        $this->actingAs($operador)->put("/panel/avisos/{$aviso->id}", $this->datosAviso(['distrito_id' => $d->id, 'titulo' => 'Título corregido']))->assertRedirect();
        $this->assertSame('Título corregido', $aviso->fresh()->titulo);
        Notification::assertSentToTimes($vecino, AvisoPublicado::class, 1);

        // Eliminar
        $this->actingAs($operador)->delete("/panel/avisos/{$aviso->id}")->assertRedirect(route('panel.avisos.index'));
        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_validacion_de_avisos_en_el_servidor(): void
    {
        $operador = $this->usuario('operador');
        $this->actingAs($operador)->post('/panel/avisos', $this->datosAviso([
            'fuente_url' => 'javascript:alert(1)',          // solo https
            'vence_en' => now()->subDays(2)->format('Y-m-d H:i'), // vence antes de emitirse
            'nivel' => 9,                                    // fuera de 1..4
            'mensaje' => 'corto',                            // mínimo 15
            'distrito_id' => 9999,                           // distrito inexistente
        ]))->assertSessionHasErrors(['fuente_url', 'vence_en', 'nivel', 'mensaje', 'distrito_id']);
        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_los_avisos_automaticos_no_se_editan_a_mano(): void
    {
        $operador = $this->usuario('operador');
        $aviso = new Aviso($this->datosAviso());
        $aviso->forceFill(['origen' => 'automatico'])->save();

        $this->actingAs($operador)->get("/panel/avisos/{$aviso->id}/edit")->assertForbidden();
        $this->actingAs($operador)->delete("/panel/avisos/{$aviso->id}")->assertForbidden();
    }

    public function test_un_borrador_no_es_publico(): void
    {
        $aviso = Aviso::create($this->datosAviso(['estado' => 'borrador']));
        $this->get("/avisos/{$aviso->id}")->assertNotFound();
        $this->get('/avisos')->assertDontSee('Aviso de prueba');
    }

    public function test_crud_de_guias(): void
    {
        $o = $this->usuario('operador');
        $datos = ['titulo' => 'Mochila', 'categoria' => 'mochila', 'contenido' => 'Agua, linterna, radio y botiquín.', 'orden' => 1, 'publicado' => '1'];

        $this->actingAs($o)->post('/panel/guias', $datos)->assertRedirect();
        $g = Guia::firstOrFail();
        $this->get('/guias')->assertSee('Mochila');
        $this->actingAs($o)->put("/panel/guias/{$g->id}", ['publicado' => null] + $datos)->assertRedirect();
        $this->assertFalse($g->fresh()->publicado);
        $this->get('/guias')->assertDontSee('Agua, linterna');
        $this->actingAs($o)->post('/panel/guias', ['categoria' => 'otra'] + $datos)->assertSessionHasErrors('categoria');
        $this->actingAs($o)->delete("/panel/guias/{$g->id}")->assertRedirect();
        $this->assertDatabaseCount('guias', 0);
    }

    public function test_crud_de_contactos(): void
    {
        $o = $this->usuario('operador');
        $datos = ['nombre' => 'Serenazgo', 'telefono' => '(054) 123456', 'tipo' => 'municipal', 'fuente_url' => 'https://www.gob.pe/'];

        $this->actingAs($o)->post('/panel/contactos', $datos)->assertRedirect();
        $c = Contacto::firstOrFail();
        $this->get('/contactos')->assertSee('Serenazgo');
        $this->actingAs($o)->put("/panel/contactos/{$c->id}", ['telefono' => '054-999'] + $datos)->assertRedirect();
        $this->assertSame('054-999', $c->fresh()->telefono);
        $this->actingAs($o)->post('/panel/contactos', ['telefono' => 'llamar ya'] + $datos)->assertSessionHasErrors('telefono');
        $this->actingAs($o)->delete("/panel/contactos/{$c->id}")->assertRedirect();
        $this->assertDatabaseCount('contactos', 0);
    }

    public function test_crud_de_distritos_y_proteccion_de_integridad(): void
    {
        $a = $this->usuario('administrador');
        $datos = ['nombre' => 'Quequeña', 'provincia' => 'Arequipa', 'latitud' => -16.55, 'longitud' => -71.45];

        $this->actingAs($a)->post('/panel/distritos', $datos)->assertRedirect();
        $d = Distrito::where('slug', 'quequena')->firstOrFail();
        $this->actingAs($a)->post('/panel/distritos', $datos)->assertSessionHasErrors('nombre');           // único
        $this->actingAs($a)->post('/panel/distritos', ['latitud' => 10] + $datos)->assertSessionHasErrors('latitud'); // fuera de Arequipa
        $this->actingAs($a)->put("/panel/distritos/{$d->slug}", ['es_piloto' => '1'] + $datos)->assertRedirect();
        $this->assertTrue($d->fresh()->es_piloto);

        // Con avisos asociados no se elimina
        Aviso::create($this->datosAviso(['distrito_id' => $d->id]));
        $this->actingAs($a)->delete("/panel/distritos/{$d->slug}")->assertSessionHas('error');
        $this->assertDatabaseHas('distritos', ['id' => $d->id]);

        Aviso::query()->delete();
        $this->actingAs($a)->delete("/panel/distritos/{$d->slug}")->assertRedirect(route('panel.distritos.index'));
        $this->assertDatabaseMissing('distritos', ['id' => $d->id]);
    }

    public function test_crud_de_usuarios_y_roles(): void
    {
        $a = $this->usuario('administrador');
        $datos = ['name' => 'Op Uno', 'email' => 'op@example.test', 'rol' => 'operador',
            'password' => 'ClaveSegura2026', 'password_confirmation' => 'ClaveSegura2026'];

        $this->actingAs($a)->post('/panel/usuarios', $datos)->assertRedirect();
        $op = User::where('email', 'op@example.test')->firstOrFail();
        $this->assertSame('operador', $op->rol);
        $this->assertNotSame('ClaveSegura2026', $op->password);

        $this->actingAs($a)->put("/panel/usuarios/{$op->id}", ['rol' => 'ciudadano', 'password' => '', 'password_confirmation' => ''] + $datos)->assertRedirect();
        $this->assertSame('ciudadano', $op->fresh()->rol);

        // El administrador no puede quitarse su rol ni borrarse a sí mismo
        $this->actingAs($a)->put("/panel/usuarios/{$a->id}", ['name' => $a->name, 'email' => $a->email, 'rol' => 'ciudadano'])->assertSessionHas('error');
        $this->assertSame('administrador', $a->fresh()->rol);
        $this->actingAs($a)->delete("/panel/usuarios/{$a->id}")->assertSessionHas('error');

        $this->actingAs($a)->delete("/panel/usuarios/{$op->id}")->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $op->id]);
    }

    public function test_preferencias_y_bandeja_del_ciudadano(): void
    {
        $d = $this->distrito();
        $c = $this->usuario('ciudadano');
        $this->actingAs($c)->put('/cuenta', ['name' => 'Nuevo nombre', 'distrito_id' => $d->id, 'recibir_alertas' => '1'])->assertRedirect();
        $this->assertSame($d->id, $c->fresh()->distrito_id);

        $aviso = Aviso::create($this->datosAviso(['distrito_id' => $d->id]));
        $c->fresh()->notify(new AvisoPublicado($aviso));
        $this->actingAs($c)->get('/bandeja')->assertOk()->assertSee('Aviso de prueba');

        $id = $c->notifications()->first()->id;
        $this->actingAs($c)->post("/bandeja/{$id}/leida")->assertRedirect();
        $this->assertNotNull($c->notifications()->first()->read_at);
    }
}
