<?php

namespace App\Http\Requests\Empresa\Concerns;

use App\Models\Correlative;

/**
 * Rellena branch_id a partir de la serie elegida.
 *
 * Cada serie pertenece a una sola sucursal (indice unico company_id +
 * tipo_documento + serie), asi que el formulario no necesita un selector de
 * sucursal y no se depende de un campo oculto que podria venir manipulado.
 *
 * Tiene que ejecutarse en prepareForValidation(): validated() congela los datos
 * y es de ahi de donde los leen los servicios que crean el comprobante.
 */
trait DeduceSucursalDeLaSerie
{
    /**
     * Devuelve los datos a fusionar. Si la serie no es de esta empresa, no se
     * toca branch_id y la validacion normal se encarga de rechazarlo.
     *
     * @return array<string, int>
     */
    protected function sucursalSegunSerie(int $companyId, string $tipoDocumento): array
    {
        $serie = strtoupper(trim((string) $this->input('serie')));

        if ($serie === '') {
            return [];
        }

        $branchId = Correlative::where('company_id', $companyId)
            ->where('tipo_documento', $tipoDocumento)
            ->where('serie', $serie)
            ->value('branch_id');

        return $branchId ? ['branch_id' => $branchId] : [];
    }
}
