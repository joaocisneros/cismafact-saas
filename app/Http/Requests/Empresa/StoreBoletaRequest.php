<?php

namespace App\Http\Requests\Empresa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Support\CatalogoSunat;

class StoreBoletaRequest extends FormRequest
{
    use \App\Http\Requests\Empresa\Concerns\DeduceSucursalDeLaSerie;

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
        ] + $this->sucursalSegunSerie($companyId, '03'));
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id'  => ['required', 'exists:branches,id'],
            'serie'      => ['required', 'string', 'size:4', 'regex:/^B[A-Z0-9]{3}$/'],
            'fecha_emision' => ['required', 'date'],
            'moneda' => ['required', 'string', CatalogoSunat::paraRegla(CatalogoSunat::MONEDAS)],
            'tipo_operacion' => ['nullable', 'string', 'max:4'],

            // En boleta el cliente puede ser DNI o "sin documento" (0)
            'client' => ['required', 'array'],
            'client.tipo_documento' => ['required', 'string', CatalogoSunat::paraRegla(CatalogoSunat::DOCUMENTOS_IDENTIDAD)],
            'client.numero_documento' => ['required', 'string', 'max:15'],
            'client.razon_social' => ['required', 'string', 'max:255'],
            'client.direccion' => ['nullable', 'string', 'max:255'],
            'client.email' => ['nullable', 'email', 'max:100'],

            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.codigo' => ['required', 'string', 'max:50'],
            'detalles.*.descripcion' => ['required', 'string', 'max:500'],
            'detalles.*.unidad' => ['required', 'string', 'max:3'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.001'],
            'detalles.*.mto_valor_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.tip_afe_igv' => ['required', 'string', CatalogoSunat::paraRegla(CatalogoSunat::AFECTACIONES_IGV)],
            'detalles.*.porcentaje_igv' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'observacion' => ['nullable', 'string', 'max:500'],
            'usuario_creacion' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'serie.regex' => 'La serie de boleta debe empezar con B (ej. B001).',
            'client.numero_documento.required' => 'El documento del cliente es requerido.',
            'client.razon_social.required' => 'El nombre del cliente es requerido.',
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
            'tipo_operacion' => $v['tipo_operacion'] ?? '0101',
            'client' => $v['client'],
            'detalles' => array_map(fn ($d) => [
                'codigo' => $d['codigo'],
                'descripcion' => $d['descripcion'],
                'unidad' => $d['unidad'],
                'cantidad' => (float) $d['cantidad'],
                'mto_valor_unitario' => (float) $d['mto_valor_unitario'],
                'tip_afe_igv' => $d['tip_afe_igv'],
                // Los retiros y bonificaciones gravadas tampoco se cobran, pero pagan
                // IGV sobre su valor referencial; con 0 habrian salido sin impuesto.
                'porcentaje_igv' => CatalogoSunat::llevaIgv($d['tip_afe_igv']) ? (float) ($d['porcentaje_igv'] ?? 18) : 0,
            ], $v['detalles']),
            'datos_adicionales' => ['observacion' => $v['observacion'] ?? null],
            'usuario_creacion' => $v['usuario_creacion'] ?? null,
        ];
    }
}
