<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * En la tarifa se leia «free» entre «Basico», «Pro» y «Empresarial».
 *
 * Era el unico en minusculas, en una tabla que se ensena. Se queda en «Free»
 * y no en «Gratis» porque en planes de API es el nombre de toda la vida —Free,
 * Pro, Enterprise— y asi se lee como nombre propio y no como un adjetivo
 * suelto entre los demas.
 *
 * Cambia solo el nombre; el slug es el identificador y no se ve. Al plan
 * gratuito se le encuentra por precio, no por nombre ni por slug, asi que las
 * llaves de sandbox no se enteran.
 *
 * Se busca por nombre y no por slug: el slug de este plan es «gratis», no
 * «free», y filtrar por el no encontraba nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Por slug y no por nombre: el nombre es justo lo que se esta
        // cambiando, y buscarlo por el fallaba en cuanto la migracion se
        // ejecutaba dos veces o el plan ya venia renombrado a mano.
        DB::table('api_planes')->where('slug', 'gratis')->update(['nombre' => 'Free']);
    }

    public function down(): void
    {
        DB::table('api_planes')->where('slug', 'gratis')->update(['nombre' => 'free']);
    }
};
