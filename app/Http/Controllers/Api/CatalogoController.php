<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Correlative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Datos auxiliares para las aplicaciones que consumen la API.
 *
 * Quien integra necesita saber que sucursales, que series y que clientes tiene
 * la empresa antes de poder armar un comprobante. Sin esto habia que pedirle al
 * programador que los escribiera a mano y adivinara el branch_id.
 *
 * Todo cuelga de la empresa que autentica la peticion (el middleware api.key la
 * deja en los atributos), asi que nunca se puede leer lo de otra.
 */
class CatalogoController extends Controller
{
    /** Tipos de comprobante con serie propia, tal como los nombra SUNAT. */
    private const TIPOS = [
        '01' => 'Factura',
        '03' => 'Boleta de venta',
        '07' => 'Nota de crédito',
        '08' => 'Nota de débito',
        '09' => 'Guía de remisión',
    ];

    /** Sucursales activas, con su domicilio para el cabezal del comprobante. */
    public function sucursales(Request $request): JsonResponse
    {
        $sucursales = Branch::query()
            ->where('company_id', $this->empresaId($request))
            ->where('activo', true)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'direccion', 'ubigeo', 'distrito', 'provincia', 'departamento'])
            ->map(fn (Branch $b) => [
                'id' => $b->id,
                'codigo' => $b->codigo,
                'nombre' => $b->nombre,
                'direccion' => $b->direccion,
                'ubigeo' => $b->ubigeo,
                'distrito' => $b->distrito,
                'provincia' => $b->provincia,
                'departamento' => $b->departamento,
                // El 0000 es el domicilio fiscal; el resto son anexos.
                'es_domicilio_fiscal' => $b->codigo === '0000',
            ]);

        return response()->json(['success' => true, 'data' => $sucursales]);
    }

    /**
     * Series con el numero que sigue, agrupadas por tipo de comprobante.
     *
     * Se devuelve el siguiente y no el ultimo emitido a proposito: es el dato
     * que la aplicacion necesita mostrar al usuario antes de emitir.
     */
    public function series(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => ['nullable', 'string', 'in:' . implode(',', array_keys(self::TIPOS))],
        ]);

        $series = Correlative::query()
            ->where('correlatives.company_id', $this->empresaId($request))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo_documento', $request->string('tipo')))
            ->leftJoin('branches', 'branches.id', '=', 'correlatives.branch_id')
            ->orderBy('correlatives.tipo_documento')
            ->orderBy('correlatives.serie')
            ->get([
                'correlatives.id',
                'correlatives.serie',
                'correlatives.tipo_documento',
                'correlatives.correlativo_actual',
                'correlatives.branch_id',
                'branches.nombre as sucursal_nombre',
                'branches.codigo as sucursal_codigo',
            ])
            ->map(fn ($s) => [
                'id' => $s->id,
                'serie' => $s->serie,
                'tipo_documento' => $s->tipo_documento,
                'tipo_nombre' => self::TIPOS[$s->tipo_documento] ?? 'Otro',
                'siguiente_numero' => (int) $s->correlativo_actual + 1,
                'branch_id' => $s->branch_id,
                'sucursal' => $s->sucursal_nombre,
                'sucursal_codigo' => $s->sucursal_codigo,
            ]);

        return response()->json(['success' => true, 'data' => $series]);
    }

    /** Clientes ya guardados, filtrados por nombre o numero de documento. */
    public function clientes(Request $request): JsonResponse
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:80'],
            'limite' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $buscar = trim((string) $request->input('buscar'));

        $clientes = Client::query()
            ->where('company_id', $this->empresaId($request))
            ->where('activo', true)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('razon_social', 'like', "%{$buscar}%")
                ->orWhere('numero_documento', 'like', "%{$buscar}%")
                ->orWhere('nombre_comercial', 'like', "%{$buscar}%")))
            ->orderBy('razon_social')
            ->limit((int) $request->input('limite', 25))
            ->get([
                'id', 'tipo_documento', 'numero_documento', 'razon_social',
                'nombre_comercial', 'direccion', 'email', 'telefono',
            ]);

        return response()->json(['success' => true, 'data' => $clientes]);
    }

    /**
     * Busca un cliente por su numero de documento.
     *
     * Solo mira los clientes ya registrados en la empresa: aqui no se consulta
     * el padron de SUNAT ni RENIEC, asi que un documento desconocido responde
     * 404 y la aplicacion pide los datos a mano.
     */
    public function buscarDocumento(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => ['required', 'string', 'in:0,1,4,6,7'],
            'numero' => ['required', 'string', 'max:20'],
        ]);

        $cliente = Client::query()
            ->where('company_id', $this->empresaId($request))
            ->where('tipo_documento', $request->string('tipo'))
            ->where('numero_documento', $request->string('numero'))
            ->where('activo', true)
            ->first(['id', 'tipo_documento', 'numero_documento', 'razon_social', 'direccion', 'email', 'telefono']);

        if (! $cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No hay ningún cliente registrado con ese documento. Escribe sus datos y quedará guardado al emitir.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cliente->id,
                'tipo_documento' => $cliente->tipo_documento,
                'numero_documento' => $cliente->numero_documento,
                'razon_social' => $cliente->razon_social,
                'direccion' => $cliente->direccion,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'fuente' => 'clientes registrados',
            ],
        ]);
    }

    /** La empresa dueña del token; el middleware api.key ya la resolvio. */
    private function empresaId(Request $request): int
    {
        return (int) $request->attributes->get('api_company')->id;
    }
}
