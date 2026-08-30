<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las llaves con las que se entra a las consultas de RUC y DNI.
 *
 * NO SON LAS DE EMISION. Las de api_keys cuelgan de una empresa que factura;
 * estas puede tenerlas alguien que solo compra consultas y no emite nada. Y
 * un mismo titular quiere varias: una por sistema suyo (web, movil, el ERP),
 * para poder cortar una sin dejar sin servicio a las demas.
 *
 * TITULAR. O una empresa del sistema —y entonces cuelga de companies— o
 * alguien de fuera, del que solo se guarda con quien hay que hablar. Se
 * permiten las dos porque un externo no tiene por que existir como empresa
 * solo para comprar consultas.
 *
 * VIGENCIA. Una llave puede nacer con fecha de caducidad: para una prueba,
 * una integracion temporal o un contrato con fin. Vencida deja de responder
 * sin que nadie tenga que acordarse de apagarla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulta_llaves', function (Blueprint $table) {
            $table->id();

            // Lo pone el dueño para saber cual es cual: "Producción - Web".
            $table->string('nombre', 80);

            // De quien es. Una de las dos, nunca las dos a la vez.
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('titular', 120)->nullable();
            $table->string('titular_documento', 20)->nullable();
            $table->string('titular_email', 120)->nullable();

            $table->foreignId('api_plan_id')->nullable()->constrained('api_planes')->nullOnDelete();

            // Sandbox: responde con datos de ejemplo y no gasta cuota. Para
            // integrar sin consumir lo que se paga.
            $table->enum('entorno', ['produccion', 'sandbox'])->default('produccion');

            // A que da acceso. Un array y no dos booleanos: mañana habra otra
            // consulta y no deberia hacer falta migrar por eso.
            $table->json('servicios');

            $table->string('clave', 64)->unique();
            $table->string('secreto');              // cifrado
            $table->string('secreto_pista', 12);    // los ultimos caracteres, para reconocerla

            $table->boolean('activa')->default(true);
            $table->date('expira_en')->nullable();
            $table->timestamp('ultimo_uso_en')->nullable();

            $table->timestamps();

            $table->index(['activa', 'expira_en']);
        });

        // El consumo pasa a colgar de la llave: es lo que se mide en la
        // pantalla ("125 / 1.000" es de esa llave, no del titular entero).
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->foreignId('llave_id')->nullable()->after('company_id')
                ->constrained('consulta_llaves')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultas_consumo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('llave_id');
        });

        Schema::dropIfExists('consulta_llaves');
    }
};
