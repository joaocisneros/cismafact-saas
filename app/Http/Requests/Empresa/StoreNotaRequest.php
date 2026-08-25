<?php

namespace App\Http\Requests\Empresa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Base para Notas de Crédito (07) y Débito (08).
 * Las subclases definen la serie esperada y los motivos válidos.
 */
abstract class StoreNotaRequest extends FormRequest
{
    use \App\Http\Requests\Empresa\Concerns\DeduceSucursalDeLaSerie;

    /** Codigo SUNAT del comprobante (07 credito, 08 debito). */
    abstract protected function tipoDocumento(): string;

    abstract protected function serieRegex(): string;

    abstract protected function motivosValidos(): array;

    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->company_id !== null;
    }

    protected function prepareForValidation(): void
    {
        $companyId = Auth::user()->company_id;

        // La sucursal se deduce de la serie: cada serie pertenece a una sola.
        $this->merge([
            'company_id' => $companyId,
            'usuario_creacion' => Auth::user()->name,
        ] + $this->sucursalSegunSerie($companyId, $this->tipoDocumento()));
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id'  => ['required', 'exists:branches,id'],
            'serie'      => ['required', 'string', 'size:4', 'regex:' . $this->serieRegex()],
            'fecha_emision' => ['required', 'date'],
            'moneda' => ['required', 'string', 'in:PEN,USD'],

            // Documento afectado
            'tipo_doc_afectado' => ['required', 'string', 'in:01,03'],
            'num_doc_afectado'  => ['required', 'string', 'max:13', 'regex:/^[BF][A-Z0-9]{3}-\d+$/'],
            'cod_motivo' => ['required', 'string', 'in:' . implode(',', $this->motivosValidos())],
            'des_motivo' => ['required', 'string', 'max:250'],

            // Cliente
            'client' => ['required', 'array'],
            'client.tipo_documento' => ['required', 'string', 'in:0,1,4,6'],
            'client.numero_documento' => ['required', 'string', 'max:15'],
            'client.razon_social' => ['required', 'string', 'max:255'],
            'client.direccion' => ['nullable', 'string', 'max:255'],
            'client.email' => ['nullable', 'email', 'max:100'],

            // Detalle
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.codigo' => ['required', 'string', 'max:50'],
            'detalles.*.descripcion' => ['required', 'string', 'max:500'],
            'detalles.*.unidad' => ['required', 'string', 'max:3'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.001'],
            'detalles.*.mto_valor_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.tip_afe_igv' => ['required', 'string', 'in:10,20,30,40'],
            'detalles.*.porcentaje_igv' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'usuario_creacion' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'num_doc_afectado.regex' => 'El documento afectado debe tener el formato F001-123 o B001-45.',
            'cod_motivo.required' => 'Selecciona el motivo.',
            'des_motivo.required' => 'Describe el motivo.',
            'detalles.required' => 'Agrega al menos un ítem.',
            'detalles.min' => 'Agrega al menos un ítem.',
        ];
    }

    public function toServiceData(): array
    {
        $v = $this->validated();

        return [
            'company_id' => $v['company_id'],
            'branch_id' => $v['branch_id'],
            'serie' => $v['serie'],
            'fecha_emision' => $v['fecha_emision'],
            'moneda' => $v['moneda'],
            'tipo_doc_afectado' => $v['tipo_doc_afectado'],
            'num_doc_afectado' => $v['num_doc_afectado'],
            'cod_motivo' => $v['cod_motivo'],
            'des_motivo' => $v['des_motivo'],
            'client' => $v['client'],
            'detalles' => array_map(fn ($d) => [
                'codigo' => $d['codigo'],
                'descripcion' => $d['descripcion'],
                'unidad' => $d['unidad'],
                'cantidad' => (float) $d['cantidad'],
                'mto_valor_unitario' => (float) $d['mto_valor_unitario'],
                'tip_afe_igv' => $d['tip_afe_igv'],
                'porcentaje_igv' => $d['tip_afe_igv'] === '10' ? (float) ($d['porcentaje_igv'] ?? 18) : 0,
            ], $v['detalles']),
            'usuario_creacion' => $v['usuario_creacion'] ?? null,
        ];
    }
}
