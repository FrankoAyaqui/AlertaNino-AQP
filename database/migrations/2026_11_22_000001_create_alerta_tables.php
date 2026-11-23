<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas del dominio de AlertaNiño AQP.
 *
 * - distritos, avisos, guias, contactos: entidades gestionadas con CRUD.
 * - lecturas_senamhi, comunicados_enfen, emergencias, sincronizaciones:
 *   copia local de los datos oficiales y registro de cada lectura.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distritos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('provincia', 60)->default('Arequipa');
            // Punto de referencia del distrito (no es un límite oficial).
            $table->decimal('latitud', 9, 6);
            $table->decimal('longitud', 9, 6);
            $table->boolean('limita_rio')->default(false);
            $table->boolean('es_piloto')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('ciudadano')->after('password')->index();
            $table->foreignId('distrito_id')->nullable()->after('rol')->constrained('distritos')->nullOnDelete();
            $table->boolean('recibir_alertas')->default(true)->after('distrito_id');
        });

        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            // null = aviso para toda la provincia de Arequipa.
            $table->foreignId('distrito_id')->nullable()->constrained('distritos')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 160);
            $table->string('fuente', 60);
            $table->string('tipo', 30);
            $table->unsignedTinyInteger('nivel');
            $table->text('mensaje');
            $table->text('recomendacion')->nullable();
            $table->string('fuente_url', 500);
            $table->dateTime('emitido_en');
            $table->dateTime('vence_en');
            $table->string('estado', 20)->default('borrador');
            // manual = registrado por un operador; automatico = creado al leer SENAMHI.
            $table->string('origen', 20)->default('manual');
            $table->string('referencia_externa', 160)->nullable()->unique();
            // Evita notificar dos veces el mismo aviso a los suscriptores.
            $table->dateTime('notificado_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'vence_en']);
        });

        Schema::create('guias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 160);
            $table->string('categoria', 20);
            $table->text('contenido');
            $table->string('fuente_url', 500)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });

        Schema::create('contactos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('telefono', 20);
            $table->string('tipo', 20);
            $table->foreignId('distrito_id')->nullable()->constrained('distritos')->nullOnDelete();
            $table->string('descripcion', 255)->nullable();
            $table->string('fuente_url', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('lecturas_senamhi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distrito_id')->constrained('distritos')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedTinyInteger('nivel');
            $table->text('descripcion')->nullable();
            $table->text('recomendacion')->nullable();
            $table->dateTime('consultado_en');
            // directo = leído de SENAMHI por este servidor; espejo = copia publicada desde Perú.
            $table->string('origen', 20)->default('directo');
            $table->timestamps();
            $table->unique(['distrito_id', 'fecha']);
        });

        Schema::create('comunicados_enfen', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->date('fecha');
            $table->string('estado', 80);
            $table->string('titular', 255);
            $table->text('resumen');
            $table->text('lluvias')->nullable();
            $table->string('pdf_url', 500);
            $table->dateTime('consultado_en');
            $table->timestamps();
        });

        Schema::create('emergencias', function (Blueprint $table) {
            $table->id();
            $table->string('ide_sinpad', 30)->unique();
            $table->dateTime('fecha');
            $table->string('distrito', 100);
            $table->string('fenomeno', 120);
            $table->unsignedInteger('afectados')->nullable();
            $table->unsignedInteger('damnificados')->nullable();
            $table->unsignedInteger('viviendas_afectadas')->nullable();
            $table->decimal('latitud', 9, 6)->nullable();
            $table->decimal('longitud', 9, 6)->nullable();
            $table->timestamps();
        });

        Schema::create('sincronizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('fuente', 30);
            $table->string('estado', 20); // ok | espejo | error
            $table->unsignedInteger('registros')->default(0);
            $table->text('detalle')->nullable();
            $table->timestamps();
            $table->index(['fuente', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizaciones');
        Schema::dropIfExists('emergencias');
        Schema::dropIfExists('comunicados_enfen');
        Schema::dropIfExists('lecturas_senamhi');
        Schema::dropIfExists('contactos');
        Schema::dropIfExists('guias');
        Schema::dropIfExists('avisos');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('distrito_id');
            $table->dropColumn(['rol', 'recibir_alertas']);
        });
        Schema::dropIfExists('distritos');
    }
};
