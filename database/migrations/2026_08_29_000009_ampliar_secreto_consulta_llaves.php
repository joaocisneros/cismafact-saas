<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El secreto cifrado no cabia en 255.
 *
 * Se guarda cifrado, y el cifrado de Laravel envuelve el valor en un JSON con
 * el vector y la firma: 64 caracteres de secreto acaban ocupando unos 280. La
 * columna era la varchar(255) por defecto, asi que crear una llave reventaba
 * con "Data too long".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consulta_llaves', function (Blueprint $table) {
            $table->text('secreto')->change();
        });
    }

    public function down(): void
    {
        Schema::table('consulta_llaves', function (Blueprint $table) {
            $table->string('secreto')->change();
        });
    }
};
