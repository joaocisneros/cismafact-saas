<?php

use App\Models\ConsultaLlave;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * El secreto de las llaves de consultas pasa a hash.
 *
 * Se guardaba cifrado con la APP_KEY para poder enseñarlo con el boton
 * «Mostrar». Eso deja el secreto de cada cliente al alcance de quien alcance
 * la base y el .env, que viven en el mismo servidor; un hash no se revierte
 * ni teniendo las dos cosas. Las de facturacion ya iban asi.
 *
 * Los secretos existentes se pueden leer todavia —de eso se trata—, asi que se
 * hashean en el sitio y las llaves siguen funcionando sin tocar nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (ConsultaLlave::all() as $llave) {
            $enClaro = $llave->secreto;

            // Ya hasheado (se corrio dos veces, o vino de una llave nueva).
            if (! $enClaro || str_starts_with($enClaro, '$2y$')) {
                continue;
            }

            DB::table('consulta_llaves')
                ->where('id', $llave->id)
                ->update(['secreto' => Hash::make($enClaro)]);
        }
    }

    public function down(): void
    {
        // No hay vuelta: de un hash no sale el secreto que lo genero. Quien
        // necesite el anterior tiene que regenerarlo.
    }
};
