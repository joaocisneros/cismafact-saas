<?php

namespace App\Http\Requests\Empresa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->company_id !== null;
    }

    /**
     * Inyecta company_id y usuario desde la sesión (nunca desde el formulario).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_id' => Auth::user()->company_id,
            'usuario_creacion' => Auth::user()->name,
        ]);
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id'  => ['required', 'exists:branches,id'],
            'serie'      => ['required', 'string', 'size:4', 'regex:/^F[A-Z0-9]{3}$/'],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_emision'],
            'moneda' => ['required', 'string', 'in:PEN,USD'],
            'tipo_operacion' => ['nullable', 'string', 'max:4'],
            'forma_pago_tipo' => ['required', 'string', 'in:Contado,Credito'],

            // Cliente (en factura el RUC es obligatorio: tipo 6)
            'client' => ['required', 'array'],
            'client.tipo_documento' => ['required', 'string', 'in:6'],
            'client.numero_documento' => ['required', 'string', 'regex:/^\d{11}$/'],
            'client.razon_social' => ['required', 'string', 'max:255'],
            'client.direccion' => ['nullable', 'string', 'max:255'],
            'client.email' => ['nullable', 'email', 'max:100'],

            // Detalles (items)
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.codigo' => ['required', 'string', 'max:50'],
            'detalles.*.descripcion' => ['required', 'string', 'max:500'],
            'detalles.*.unidad' => ['required', 'string', 'max:3'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.001'],
            'detalles.*.mto_valor_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.tip_afe_igv' => ['required', 'string', 'in:10,20,30,40'],
            'detalles.*.porcentaje_igv' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'observacion' => ['nullable', 'string', 'max:500'],
            'usuario_creacion' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'La sucursal es requerida.',
            'serie.required' => 'La serie es requerida.',
            'serie.regex' => 'La serie de factura debe empezar con F (ej. F001).',
            'fecha_emision.required' => 'La fecha de emisión es requerida.',
            'client.tipo_documento.in' => 'Una factura solo puede emitirse a un cliente con RUC. Para clientes con DNI usa una Boleta.',
            'client.numero_documento.required' => 'El RUC del cliente es requerido.',
            'client.numero_documento.regex' => 'El RUC del cliente debe tener 11 dígitos.',
            'client.razon_social.required' => 'La razón social del cliente es requerida.',
            'detalles.required' => 'Agrega al menos un ítem a la factura.',
            'detalles.min' => 'Agrega al menos un ítem a la factura.',
            'detalles.*.descripcion.required' => 'La descripción del ítem es requerida.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
            'detalles.*.mto_valor_unitario.required' => 'El valor unitario es requerido.',
        ];
    }

    /**
     * Arma el arreglo exacto que espera DocumentService::createInvoice().
     */
    public function toServiceData(): array
    {
        $v = $this->validated();

        return [
            'company_id' => $v['company_id'],
            'branch_id' => $v['branch_id'],
            'serie' => $v['serie'],
            'fecha_emision' => $v['fecha_emision'],
            'fecha_vencimiento' => $v['fecha_vencimiento'] ?? null,
            'moneda' => $v['moneda'],
            'tipo_operacion' => $v['tipo_operacion'] ?? '0101',
            'forma_pago_tipo' => $v['forma_pago_tipo'],
            'client' => $v['client'],
            'detalles' => array_map(function ($d) {
                return [
                    'codigo' => $d['codigo'],
                    'descripcion' => $d['descripcion'],
                    'unidad' => $d['unidad'],
                    'cantidad' => (float) $d['cantidad'],
                    'mto_valor_unitario' => (float) $d['mto_valor_unitario'],
                    'tip_afe_igv' => $d['tip_afe_igv'],
                    'porcentaje_igv' => $d['tip_afe_igv'] === '10' ? (float) ($d['porcentaje_igv'] ?? 18) : 0,
                ];
            }, $v['detalles']),
            'datos_adicionales' => ['observacion' => $v['observacion'] ?? null],
            'usuario_creacion' => $v['usuario_creacion'] ?? null,
        ];
    }
}
