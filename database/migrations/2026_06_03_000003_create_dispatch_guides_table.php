<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            $table->string('tipo_documento', 2)->default('09');
            $table->string('serie', 4);
            $table->string('correlativo', 8);
            $table->string('numero_completo', 15);
            
            $table->date('fecha_emision');
            $table->string('version', 4)->default('2022');
            
            $table->string('cod_traslado', 2);
            $table->text('des_traslado')->nullable();
            $table->string('mod_traslado', 2);
            $table->date('fecha_traslado');
            $table->decimal('peso_total', 10, 2)->default(0);
            $table->string('und_peso_total', 10)->default('KGM');
            $table->integer('num_bultos')->nullable();
            
            $table->json('partida');
            $table->json('llegada');
            $table->json('transportista')->nullable();
            $table->json('indicadores')->nullable();
            $table->json('vehiculo')->nullable();
            $table->json('conductor')->nullable();
            
            $table->json('detalles');
            $table->json('observaciones')->nullable();
            
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('ticket')->nullable();
            
            $table->string('estado_sunat', 20)->default('PENDIENTE');
            $table->text('respuesta_sunat')->nullable();
            $table->string('codigo_hash')->nullable();
            
            $table->string('usuario_creacion')->nullable();
            $table->timestamps();
            
            $table->unique(['company_id', 'serie', 'correlativo']);
            $table->index(['company_id', 'branch_id']);
            $table->index(['fecha_emision']);
            $table->index(['estado_sunat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_guides');
    }
};
