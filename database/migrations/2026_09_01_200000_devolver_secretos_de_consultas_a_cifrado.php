<?php

use App\Models\ConsultaLlave;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Vuelve el secreto de las llaves de consultas a cifrado, como estaba.
 *
 * La migracion anterior los paso a hash para que no se pudieran leer, y eso
 * dejo sin efecto el boton «Mostrar» de este modulo, que es con lo que se le
 * entrega el secreto a quien pide una llave. Este modulo va aparte del de
 * facturacion y ahi ese boton hace falta.
 *
 * De un hash no sale el secreto que lo genero, asi que no se puede deshacer
 * dato a dato: a cada llave se le pone uno nuevo, ya cifrado. La clave ck_ no
 * cambia, pero el secreto que tuviera puesto un cliente deja de valer y hay
 * que pasarle el nuevo, que vuelve a poder mirarse en su ficha.
 *
 * Se trabaja con la tabla y no con el modelo: al leer por el modelo, el cast
 * intenta descifrar lo que ahora es un hash y revienta.
 */
return new class extends Migration
{
    public function up(): void
    {
        $filas = DB::table('consulta_llaves')->select('id', 'secreto')->get();

        foreach ($filas as $fila) {
            // Ya cifrado: se deja como esta.
            if (! str_starts_with((string) $fila->secreto, '$2y$')) {
                continue;
            }

            $nuevo = ConsultaLlave::nuevasCredenciales()['secreto'];

            DB::table('consulta_llaves')->where('id', $fila->id)->update([
                // Igual que el cast 'encrypted' del modelo.
                'secreto' => Crypt::encryptString($nuevo),
                'secreto_pista' => substr($nuevo, -6),
            ]);
        }
    }

    public function down(): void
    {
        // Nada que deshacer: el secreto anterior ya no existe en ningun sitio.
    }
};
