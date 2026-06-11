<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            $table->string('proveedor_tipo_doc', 2);
            $table->string('proveedor_num_doc', 15);
            $table->string('proveedor_razon_social', 200);
            
            $table->string('serie', 4);
            $table->string('correlativo', 8);
            $table->string('numero_completo', 15);
            
            $table->date('fecha_emision');
            $table->string('regimen', 10);
            $table->decimal('tasa', 5, 2);
            $table->text('observacion')->nullable();
            $table->decimal('imp_retenido', 12, 2);
            $table->decimal('imp_pagado', 12, 2);
            $table->string('moneda', 3)->default('PEN');
            
            $table->json('detalles');
            
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('pdf_path')->nullable();
            
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
        Schema::dropIfExists('retentions');
    }
};
