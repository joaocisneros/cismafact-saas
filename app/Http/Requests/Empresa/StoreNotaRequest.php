<?php

namespace App\Http\Requests\Empresa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Support\CatalogoSunat;

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
            'moneda' => ['required', 'string', CatalogoSunat::paraRegla(CatalogoSunat::MONEDAS)],

            // Documento afectado
            'tipo_doc_afectado' => ['required', 'string', 'in:01,03'],
            'num_doc_afectado'  => ['required', 'string', 'max:13', 'regex:/^[BF][A-Z0-9]{3}-\d+$/'],
            'cod_motivo' => ['required', 'string', 'in:' . implode(',', $this->motivosValidos())],
            'des_motivo' => ['required', 'string', 'max:250'],

            // Cliente
            'client' => ['required', 'array'],
            'client.tipo_documento' => ['required', 'string', CatalogoSunat::paraRegla(CatalogoSunat::DOCUMENTOS_IDENTIDAD)],
            // Un DNI son ocho digitos y un RUC once. Con «max:15» se podia
            // dejar el tipo en RUC y el numero de un DNI, y eso lo devuelve
            // SUNAT mucho despues de darle a emitir.
            'client.numero_documento' => CatalogoSunat::reglaNumeroDocumento($this->input('client.tipo_documento')),
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
            'detalles.*.tip_afe_igv' => ['required', 'string', CatalogoSunat::paraRegla(CatalogoSunat::AFECTACIONES_IGV)],
            'detalles.*.porcentaje_igv' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'usuario_creacion' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(fn ($v) => $this->elAfectadoTieneQueSerTuyo($v));
    }

    /**
     * El comprobante que se modifica tiene que existir, ser de esta empresa y
     * estar aceptado.
     *
     * El campo se escribe a mano y solo se miraba que tuviera pinta de
     * numero. Se podia emitir una nota contra un comprobante inventado o de
     * otra empresa: SUNAT la rechaza, pero el correlativo ya se ha gastado y
     * hay que dar de baja la nota para recuperarlo.
     */
    private function elAfectadoTieneQueSerTuyo(\Illuminate\Validation\Validator $validator): void
    {
        $numero = (string) $this->input('num_doc_afectado');
        $tipo = (string) $this->input('tipo_doc_afectado');

        if ($numero === '' || $tipo === '') {
            return;   // de eso ya avisan las reglas de arriba
        }

        $modelo = $tipo === '01' ? \App\Models\Invoice::class : \App\Models\Boleta::class;

        $afectado = $modelo::where('company_id', Auth::user()->company_id)
            ->where('numero_completo', $numero)
            ->first(['id', 'estado_sunat']);

        if (! $afectado) {
            $validator->errors()->add(
                'num_doc_afectado',
                "No tienes ningún comprobante {$numero}. Revisa el número o elígelo de la lista."
            );

            return;
        }

        if ($afectado->estado_sunat !== 'ACEPTADO') {
            $validator->errors()->add(
                'num_doc_afectado',
                "{$numero} todavía no está aceptado por SUNAT, así que no se puede modificar."
            );
        }
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
                // Los retiros y bonificaciones gravadas tampoco se cobran, pero pagan
                // IGV sobre su valor referencial; con 0 habrian salido sin impuesto.
                'porcentaje_igv' => CatalogoSunat::llevaIgv($d['tip_afe_igv']) ? (float) ($d['porcentaje_igv'] ?? 18) : 0,
            ], $v['detalles']),
            'usuario_creacion' => $v['usuario_creacion'] ?? null,
        ];
    }
}
