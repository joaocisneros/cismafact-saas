<?php

namespace App\Http\Requests\Empresa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreGuiaRequest extends FormRequest
{
    use \App\Http\Requests\Empresa\Concerns\DeduceSucursalDeLaSerie;

    // Catálogo 20 - Motivo de traslado
    public const MOTIVOS = [
        '01' => 'Venta',
        '02' => 'Compra',
        '04' => 'Traslado entre establecimientos de la misma empresa',
        '08' => 'Importación',
        '09' => 'Exportación',
        '13' => 'Otros',
        '14' => 'Venta sujeta a confirmación del comprador',
        '18' => 'Traslado emisor itinerante CP',
        '19' => 'Traslado a zona primaria',
    ];

    // Catálogo 18 - Modalidad de traslado
    public const MODALIDADES = [
        '01' => 'Transporte público (con transportista)',
        '02' => 'Transporte privado (vehículo propio)',
    ];

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
        ] + $this->sucursalSegunSerie($companyId, '09'));
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id'  => ['required', 'exists:branches,id'],
            'serie'      => ['required', 'string', 'size:4', 'regex:/^T[A-Z0-9]{3}$/'],
            'fecha_emision' => ['required', 'date'],

            // Destinatario
            'destinatario' => ['required', 'array'],
            'destinatario.tipo_documento' => ['required', 'string', 'in:0,1,4,6'],
            'destinatario.numero_documento' => ['required', 'string', 'max:15'],
            'destinatario.razon_social' => ['required', 'string', 'max:255'],
            'destinatario.direccion' => ['nullable', 'string', 'max:255'],
            'destinatario.email' => ['nullable', 'email', 'max:100'],

            // Datos del traslado
            'cod_traslado' => ['required', 'string', 'in:' . implode(',', array_keys(self::MOTIVOS))],
            'mod_traslado' => ['required', 'string', 'in:01,02'],
            'fecha_traslado' => ['required', 'date'],
            'peso_total' => ['required', 'numeric', 'min:0.001'],
            'und_peso_total' => ['required', 'string', 'in:KGM,TNE'],
            'num_bultos' => ['nullable', 'integer', 'min:0'],

            // Punto de partida y llegada
            'partida_ubigeo' => ['required', 'string', 'size:6'],
            'partida_direccion' => ['required', 'string', 'max:255'],
            'llegada_ubigeo' => ['required', 'string', 'size:6'],
            'llegada_direccion' => ['required', 'string', 'max:255'],

            // Transporte público (mod 01): transportista
            'transportista_tipo_doc' => ['required_if:mod_traslado,01', 'nullable', 'string', 'in:6'],
            'transportista_num_doc' => ['required_if:mod_traslado,01', 'nullable', 'string', 'size:11'],
            'transportista_razon_social' => ['required_if:mod_traslado,01', 'nullable', 'string', 'max:255'],
            'transportista_nro_mtc' => ['nullable', 'string', 'max:20'],

            // Transporte privado (mod 02): vehículo + conductor
            'vehiculo_placa' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:8'],
            'conductor_tipo_doc' => ['required_if:mod_traslado,02', 'nullable', 'string', 'in:1,4,6,7'],
            'conductor_num_doc' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:15'],
            'conductor_licencia' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:20'],
            'conductor_nombres' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:100'],
            'conductor_apellidos' => ['required_if:mod_traslado,02', 'nullable', 'string', 'max:100'],

            // Detalle (bienes a trasladar)
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.codigo' => ['required', 'string', 'max:50'],
            'detalles.*.descripcion' => ['required', 'string', 'max:500'],
            'detalles.*.unidad' => ['required', 'string', 'max:3'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.001'],

            'observaciones' => ['nullable', 'string', 'max:250'],
            'usuario_creacion' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'serie.regex' => 'La serie de guía debe empezar con T (ej. T001).',
            'transportista_num_doc.required_if' => 'El RUC del transportista es obligatorio en transporte público.',
            'vehiculo_placa.required_if' => 'La placa del vehículo es obligatoria en transporte privado.',
            'conductor_num_doc.required_if' => 'El documento del conductor es obligatorio en transporte privado.',
            'detalles.required' => 'Agrega al menos un bien a trasladar.',
        ];
    }

    public function toServiceData(): array
    {
        $v = $this->validated();

        $data = [
            'company_id' => $v['company_id'],
            'branch_id' => $v['branch_id'],
            'serie' => $v['serie'],
            'fecha_emision' => $v['fecha_emision'],
            'destinatario' => $v['destinatario'],
            'cod_traslado' => $v['cod_traslado'],
            'des_traslado' => self::MOTIVOS[$v['cod_traslado']] ?? null,
            'mod_traslado' => $v['mod_traslado'],
            'fecha_traslado' => $v['fecha_traslado'],
            'peso_total' => (float) $v['peso_total'],
            'und_peso_total' => $v['und_peso_total'],
            'num_bultos' => $v['num_bultos'] ?? null,
            'partida_ubigeo' => $v['partida_ubigeo'],
            'partida_direccion' => $v['partida_direccion'],
            'llegada_ubigeo' => $v['llegada_ubigeo'],
            'llegada_direccion' => $v['llegada_direccion'],
            'detalles' => array_map(fn ($d) => [
                'codigo' => $d['codigo'],
                'descripcion' => $d['descripcion'],
                'unidad' => $d['unidad'],
                'cantidad' => (float) $d['cantidad'],
            ], $v['detalles']),
            'observaciones' => $v['observaciones'] ?? null,
            'usuario_creacion' => $v['usuario_creacion'] ?? null,
        ];

        if ($v['mod_traslado'] === '01') {
            $data += [
                'transportista_tipo_doc' => $v['transportista_tipo_doc'] ?? '6',
                'transportista_num_doc' => $v['transportista_num_doc'] ?? null,
                'transportista_razon_social' => $v['transportista_razon_social'] ?? null,
                'transportista_nro_mtc' => $v['transportista_nro_mtc'] ?? null,
            ];
        } else {
            $data += [
                'vehiculo_placa' => $v['vehiculo_placa'] ?? null,
                'conductor_tipo' => 'Principal',
                'conductor_tipo_doc' => $v['conductor_tipo_doc'] ?? null,
                'conductor_num_doc' => $v['conductor_num_doc'] ?? null,
                'conductor_licencia' => $v['conductor_licencia'] ?? null,
                'conductor_nombres' => $v['conductor_nombres'] ?? null,
                'conductor_apellidos' => $v['conductor_apellidos'] ?? null,
            ];
        }

        return $data;
    }
}
