<?php

namespace App\Http\Controllers\Traits;

use App\Models\Correlative;
use Illuminate\Support\Collection;

/**
 * Las series de una empresa pueden estar repartidas entre varias sucursales, y
 * cada serie pertenece a una sola (asi lo garantiza el indice unico
 * company_id + tipo_documento + serie).
 *
 * Antes los formularios de emision cogian siempre la PRIMERA sucursal y solo
 * mostraban sus series: con dos sedes, la segunda no podia emitir nunca. Aqui
 * se listan todas las series de la empresa y se deduce la sucursal a partir de
 * la serie elegida, sin necesidad de un selector aparte.
 */
trait ResuelveSucursalPorSerie
{
    /**
     * Todas las series de la empresa para ese tipo de documento, con el nombre
     * de su sucursal para mostrarlo en el desplegable.
     */
    protected function seriesDeLaEmpresa(int $companyId, string $tipoDocumento): Collection
    {
        return Correlative::query()
            ->where('correlatives.company_id', $companyId)
            ->where('correlatives.tipo_documento', $tipoDocumento)
            ->join('branches', 'branches.id', '=', 'correlatives.branch_id')
            ->orderBy('branches.codigo')
            ->orderBy('correlatives.serie')
            ->get([
                'correlatives.serie',
                'correlatives.correlativo_actual',
                'correlatives.branch_id',
                'branches.nombre as sucursal_nombre',
                'branches.codigo as sucursal_codigo',
                'branches.activo as sucursal_activa',
            ]);
    }

    /**
     * Sucursal a la que pertenece una serie. Devuelve null si la serie no es de
     * esta empresa, para que la validacion lo rechace.
     */
    protected function sucursalDeLaSerie(int $companyId, string $tipoDocumento, ?string $serie): ?int
    {
        if (! $serie) {
            return null;
        }

        return Correlative::where('company_id', $companyId)
            ->where('tipo_documento', $tipoDocumento)
            ->where('serie', strtoupper($serie))
            ->value('branch_id');
    }
}
