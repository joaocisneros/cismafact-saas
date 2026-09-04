<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Busca un documento sin salirse de la empresa que hizo la llamada.
 *
 * Los controladores hacian «Modelo::findOrFail($id)» a secas. Con una
 * credencial cualquiera se podia leer el detalle, el PDF y el XML de los
 * comprobantes de otra empresa: bastaba cambiar el numero de la direccion.
 *
 * La comprobacion no se deja al criterio de cada metodo porque son treinta y
 * siete sitios y basta olvidarla en uno. Aqui esta una vez, y quien busque un
 * documento pasa por aqui.
 */
trait BuscaEnSuEmpresa
{
    /**
     * @param  class-string<Model>  $modelo
     * @param  array<int, string>  $con  relaciones a cargar
     */
    protected function deLaEmpresa(string $modelo, string|int $id, array $con = []): Model
    {
        return $modelo::query()
            ->where('company_id', $this->empresaDeLaLlamada())
            ->when($con, fn ($consulta) => $consulta->with($con))
            ->findOrFail($id);
    }

    /**
     * La empresa a la que pertenece la credencial.
     *
     * La pone AuthenticateApiKey en cada peticion. Si no esta, algo va mal en
     * el orden de los middleware y es mejor cortar que servir el documento de
     * quien sea.
     */
    protected function empresaDeLaLlamada(): int
    {
        $empresa = request()->attributes->get('api_company')?->id
            ?? request()->input('company_id');

        abort_if(! $empresa, 401, 'No se pudo determinar la empresa de la credencial.');

        return (int) $empresa;
    }
}
