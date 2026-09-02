<?php

use App\Models\ApiKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El secret de facturacion vuelve a poder leerse, como el de RUC y DNI.
 *
 * Estaba como hash, que no se revierte: al entregar una credencial no habia
 * forma de decirle al cliente cual es su secret salvo generarle otro. En este
 * negocio la credencial se entrega a mano, y quien la entrega tiene que poder
 * verla; es como funciona el otro producto.
 *
 * Dos cosas a la vez:
 *
 *   1  la columna pasa a text, porque cifrado ocupa 312 caracteres y tenia 128
 *   2  cada secret se cambia por uno nuevo, ya cifrado
 *
 * Lo segundo no es opcional: de un hash no sale el secret que lo genero, asi
 * que no hay nada que convertir. Las credenciales en uso dejan de valer con el
 * secret viejo y hay que pasarles el nuevo, que ya se puede mirar en su ficha.
 * La key no cambia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $tabla) {
            $tabla->text('secret')->change();
        });

        // Con la tabla, no con el modelo: el cast cifraria dos veces.
        foreach (DB::table('api_keys')->select('id', 'secret')->get() as $fila) {
            // Ya cifrado (se corrio dos veces): se deja.
            if (! str_starts_with((string) $fila->secret, '$2y$')) {
                continue;
            }

            DB::table('api_keys')->where('id', $fila->id)->update([
                'secret' => Crypt::encryptString(ApiKey::generateSecret()),
            ]);
        }
    }

    public function down(): void
    {
        // No se vuelve: hashear lo que hay dejaria a todos sin secret otra vez.
    }
};
