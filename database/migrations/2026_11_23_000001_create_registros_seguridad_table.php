<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de seguridad (trazabilidad defensiva).
 *
 * Guarda cada solicitud sospechosa o rechazada con su IP, navegador y qué
 * intentó, para detectar ataques, bloquear al origen y conservar evidencia
 * que pueda entregarse a la autoridad. No se usa para atacar de vuelta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_seguridad', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();          // IPv4 o IPv6
            $table->string('metodo', 10);
            $table->string('ruta', 500);
            $table->string('patron', 60)->nullable();    // qué regla se activó
            $table->string('severidad', 20)->default('advertencia'); // advertencia | critico | bloqueado
            $table->unsignedSmallInteger('estado_http')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_seguridad');
    }
};
