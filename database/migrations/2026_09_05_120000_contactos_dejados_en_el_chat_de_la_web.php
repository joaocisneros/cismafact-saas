<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quien pregunta de noche y no escribe por WhatsApp.
 *
 * El chat resolvia la duda y ahi se acababa: el visitante leia los precios a
 * las once y se iba sin que nadie supiera que habia estado. Escribir a un
 * numero desconocido cuesta mas que dejar el propio, asi que ahora se le puede
 * dejar y le escribes tu.
 *
 * Se guarda lo justo para devolver la llamada: como se llama, por donde
 * localizarle y que estaba mirando. Nada mas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contactos_web', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 120);
            $table->string('telefono', 30);
            $table->string('mensaje', 500)->nullable();

            // De que rama del chat salio: dice si viene por facturacion o por
            // consultas sin tener que preguntarselo otra vez al llamar.
            $table->string('interes', 40)->nullable();

            // Atendido o no. Sin esto, con veinte contactos no se sabe a quien
            // se llamo ya y a quien no.
            $table->timestamp('atendido_en')->nullable();
            $table->foreignId('atendido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nota', 500)->nullable();

            // Para no repetir el aviso de un mismo visitante insistente.
            $table->string('ip', 45)->nullable();

            $table->timestamps();

            $table->index(['atendido_en', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos_web');
    }
};
