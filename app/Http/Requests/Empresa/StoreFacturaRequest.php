<?php

namespace App\Http\Requests\Empresa;

use App\Support\CatalogoSunat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class StoreFacturaRequest extends FormRequest
{
    use \App\Http\Requests\Empresa\Concerns\DeduceSucursalDeLaSerie;

    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->company_id !== null;
    }

    /**
     * Inyecta company_id y usuario desde la sesión (nunca desde el formulario).
     *
     * La sucursal se deduce de la serie: cada serie pertenece a una sola
     * sucursal, asi que el formulario no necesita elegirla y no se depende de
     * un campo oculto. Tiene que hacerse ANTES de validar, porque validated()
     * congela los datos y createInvoice() los lee de ahi.
     */
    protected function prepareForValidation(): void
    {
        $companyId = Auth::user()->company_id;

        $this->merge([
            'company_id' => $companyId,
            'usuario_creacion' => Auth::user()->name,
        ] + $this->sucursalSegunSerie($companyId, '01'));
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id'  => ['required', 'exists:branches,id'],
            'serie'      => ['required', 'string', 'size:4', 'regex:/^F[A-Z0-9]{3}$/'],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_emision'],
            'moneda' => ['required', 'string', CatalogoSunat::paraRegla(CatalogoSunat::MONEDAS)],
            'tipo_operacion' => ['nullable', 'string', 'max:4'],
            'forma_pago_tipo' => ['required', 'string', 'in:Contado,Credito'],

            // Cliente. La factura pide RUC, salvo cuando se exporta: al que no
            // vive aqui se le identifica con su pasaporte o con su numero
            // tributario de fuera, porque RUC no tiene. De que sea de verdad una
            // exportacion se encarga clienteSinRucSoloAlExportar().
            'client' => ['required', 'array'],
            'client.tipo_documento' => ['required', 'string', CatalogoSunat::paraRegla(CatalogoSunat::DOCUMENTOS_FACTURA)],
            'client.numero_documento' => $this->esCliente('6')
                ? ['required', 'string', 'regex:/^\d{11}$/']
                : ['required', 'string', 'max:15'],
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
            'detalles.*.tip_afe_igv' => ['required', 'string', CatalogoSunat::paraRegla(CatalogoSunat::AFECTACIONES_IGV)],
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
            'client.tipo_documento.in' => 'Ese tipo de documento no vale para una factura. Con DNI usa una Boleta; sin RUC solo se puede facturar una exportación.',
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

    /** Si el cliente lleva ese tipo de documento. */
    private function esCliente(string $tipo): bool
    {
        return (string) $this->input('client.tipo_documento') === $tipo;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $v) => $this->clienteSinRucSoloAlExportar($v));
    }

    /**
     * Sin RUC solo se factura una exportacion.
     *
     * SUNAT admite pasaporte y documentos extranjeros en la factura, pero solo
     * cuando lo que se vende sale del pais. Una venta interna a alguien sin RUC
     * se documenta con boleta, y aceptarla aqui seria emitir algo que SUNAT
     * devuelve.
     */
    private function clienteSinRucSoloAlExportar(Validator $validator): void
    {
        if ($this->esCliente('6')) {
            return;
        }

        $lineas = (array) $this->input('detalles', []);
        $todoExportacion = $lineas !== [] && ! collect($lineas)
            ->contains(fn ($linea) => ($linea['tip_afe_igv'] ?? null) !== CatalogoSunat::AFECTACION_EXPORTACION);

        if (! $todoExportacion) {
            $validator->errors()->add(
                'client.tipo_documento',
                'Sin RUC solo se puede facturar una exportación. Marca todos los ítems como exportación o usa una Boleta.'
            );
        }
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
                    // Los retiros y bonificaciones gravadas tampoco se cobran, pero pagan
                    // IGV sobre su valor referencial; con 0 habrian salido sin impuesto.
                    'porcentaje_igv' => CatalogoSunat::llevaIgv($d['tip_afe_igv']) ? (float) ($d['porcentaje_igv'] ?? 18) : 0,
                ];
            }, $v['detalles']),
            'datos_adicionales' => ['observacion' => $v['observacion'] ?? null],
            'usuario_creacion' => $v['usuario_creacion'] ?? null,
        ];
    }
}
