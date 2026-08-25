<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Boleta;
use App\Models\Branch;
use App\Models\DailySummary;
use App\Models\VoidedDocument;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Anulación de comprobantes, en un solo sitio.
 *
 * SUNAT usa dos trámites distintos según el tipo:
 *
 *   - Facturas, notas de crédito y de débito -> Comunicación de Baja (RA).
 *   - Boletas -> Resumen diario con estado 3.
 *
 * Eso es una tecnicidad suya, no algo que el usuario tenga que saber: él solo
 * quiere anular un comprobante. Antes vivían en dos módulos con nombres
 * distintos ("Anulaciones" y "Resumen Boletas") y nadie encontraba el segundo.
 * Aquí se listan todos juntos y el trámite se elige solo.
 */
class AnulacionController extends Controller
{
    use \App\Traits\FormatsSunatError;

    /** Días que da SUNAT para dar de baja un comprobante. */
    private const DIAS_DE_PLAZO = 7;

    public function __construct(private DocumentService $documentService)
    {
    }

    public function index()
    {
        $companyId = Auth::user()->company_id;

        // Las dos vías, juntas y ordenadas por fecha: para el usuario son lo
        // mismo, así que su historial también debe serlo.
        $bajas = VoidedDocument::where('company_id', $companyId)->latest('id')->limit(100)->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'ruta' => 'empresa.anulaciones.show',
                'referencia' => 'RA-' . $b->fecha_generacion?->format('Ymd') . '-' . $b->correlativo,
                'via' => 'Comunicación de baja',
                'comprobantes' => collect((array) $b->detalles)->count(),
                'fecha' => $b->created_at,
                'estado' => $b->estado_sunat,
            ]);

        $resumenes = DailySummary::where('company_id', $companyId)->latest('id')->limit(100)->get()
            ->filter(fn ($r) => collect((array) $r->detalles)->contains(fn ($d) => ($d['estado'] ?? '1') === '3'))
            ->map(fn ($r) => [
                'id' => $r->id,
                'ruta' => 'empresa.resumenes.show',
                'referencia' => 'RC-' . ($r->fecha_generacion?->format('Ymd') ?? '') . '-' . $r->correlativo,
                'via' => 'Resumen de boletas',
                'comprobantes' => collect((array) $r->detalles)->where('estado', '3')->count(),
                'fecha' => $r->created_at,
                'estado' => $r->estado_sunat,
            ]);

        $anulaciones = collect()->merge($bajas)->merge($resumenes)
            ->sortByDesc('fecha')
            ->values();

        return view('empresa.anulaciones.index', compact('anulaciones'));
    }

    public function create(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $fecha = $request->input('fecha');
        $documentos = [];

        if ($fecha) {
            // Todas las sucursales: antes miraba solo la primera y lo emitido
            // por las demás no aparecía, sin forma de anularlo.
            foreach (Branch::where('company_id', $companyId)->orderBy('id')->get() as $sucursal) {
                foreach ($this->documentService->getDocumentsForVoiding($companyId, $sucursal->id, $fecha) as $documento) {
                    $documento['sucursal'] = $sucursal->nombre;
                    $documento['branch_id'] = $sucursal->id;
                    $documento['via'] = $documento['tipo_documento'] === '03'
                        ? 'Resumen de boletas'
                        : 'Comunicación de baja';

                    $documentos[] = $documento;
                }
            }
        }

        $datos = [
            'fecha' => $fecha,
            'documentos' => $documentos,
            'diasDePlazo' => self::DIAS_DE_PLAZO,
        ];

        if (request()->ajax() || request()->boolean('modal')) {
            return view('empresa.anulaciones._form_modal', $datos);
        }

        return view('empresa.anulaciones.create', $datos);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha_referencia' => ['required', 'date'],
            'motivo' => ['required', 'string', 'min:3', 'max:250'],
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*.tipo_documento' => ['required', 'string', 'in:01,03,07,08'],
            'documentos.*.serie' => ['required', 'string', 'max:4'],
            'documentos.*.correlativo' => ['required', 'string', 'max:8'],
        ], [], ['documentos' => 'comprobantes']);

        if ($aviso = $this->fueraDePlazo($data['fecha_referencia'])) {
            return back()->withInput()->with('error', $aviso);
        }

        $companyId = Auth::user()->company_id;
        $seleccion = collect($data['documentos']);

        // Cada grupo va por su trámite. El usuario no elige: se deduce del tipo.
        $paraResumen = $seleccion->where('tipo_documento', '03');
        $paraBaja = $seleccion->where('tipo_documento', '!=', '03');

        $hechos = [];
        $fallos = [];

        try {
            if ($paraBaja->isNotEmpty()) {
                [$ok, $mensaje] = $this->comunicacionDeBaja($companyId, $data, $paraBaja);
                $ok ? $hechos[] = $mensaje : $fallos[] = $mensaje;
            }

            if ($paraResumen->isNotEmpty()) {
                [$ok, $mensaje] = $this->resumenDeBoletas($companyId, $data, $paraResumen);
                $ok ? $hechos[] = $mensaje : $fallos[] = $mensaje;
            }
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo anular: ' . $e->getMessage());
        }

        $respuesta = redirect()->route('empresa.anulaciones.index');

        if ($fallos) {
            return $respuesta->with('error', implode(' ', $fallos));
        }

        return $respuesta->with('success', implode(' ', $hechos)
            . ' Usa «Consultar estado» para obtener el CDR: la anulación solo es efectiva cuando SUNAT la acepta.');
    }

    public function show(int $id)
    {
        $anulacion = VoidedDocument::where('company_id', Auth::user()->company_id)->findOrFail($id);

        if (request()->ajax() || request()->boolean('modal')) {
            return view('empresa.anulaciones._detail_modal', compact('anulacion'));
        }

        return view('empresa.anulaciones.show', compact('anulacion'));
    }

    public function checkStatus(int $id)
    {
        $anulacion = VoidedDocument::where('company_id', Auth::user()->company_id)->findOrFail($id);

        if (empty($anulacion->ticket)) {
            return back()->with('error', 'Esta anulación aún no tiene ticket. Primero envíala a SUNAT.');
        }

        if ($anulacion->estado_sunat === 'ACEPTADO') {
            return back()->with('error', 'La anulación ya fue aceptada por SUNAT.');
        }

        $result = $this->documentService->checkVoidedDocumentStatus($anulacion);

        return $result['success'] && ($result['document']->estado_sunat ?? '') === 'ACEPTADO'
            ? back()->with('success', 'Anulación aceptada por SUNAT. Los comprobantes quedaron anulados.')
            : back()->with('error', 'SUNAT respondió: ' . $this->errorText($result['error'] ?? $result['document']->respuesta_sunat ?? null));
    }

    /** Facturas y notas: Comunicación de Baja. */
    private function comunicacionDeBaja(int $companyId, array $data, $documentos): array
    {
        $branch = Branch::where('company_id', $companyId)->orderBy('id')->firstOrFail();

        $voided = $this->documentService->createVoidedDocument([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'fecha_referencia' => $data['fecha_referencia'],
            'motivo_baja' => $data['motivo'],
            'detalles' => $documentos->map(fn ($d) => [
                'tipo_documento' => $d['tipo_documento'],
                'serie' => $d['serie'],
                'correlativo' => $d['correlativo'],
                'motivo_especifico' => $data['motivo'],
            ])->values()->all(),
            'usuario_creacion' => Auth::user()->name,
        ]);

        $resultado = $this->documentService->sendVoidedDocumentToSunat($voided);
        $cuantos = $documentos->count();

        return $resultado['success']
            ? [true, "Comunicación de baja enviada por {$cuantos} comprobante(s)."]
            : [false, "La comunicación de baja falló: " . $this->errorText($resultado['error'] ?? null)];
    }

    /** Boletas: Resumen diario con estado 3. */
    private function resumenDeBoletas(int $companyId, array $data, $documentos): array
    {
        $numeros = $documentos->map(fn ($d) => $d['serie'] . '-' . $d['correlativo'])->all();

        $boletas = Boleta::with('client:id,tipo_documento,numero_documento')
            ->where('company_id', $companyId)
            ->whereIn('numero_completo', $numeros)
            ->where('estado_sunat', 'ACEPTADO')
            ->whereNull('anulado_en')
            ->get();

        if ($boletas->isEmpty()) {
            return [false, 'Las boletas seleccionadas ya estaban anuladas o aún no las aceptó SUNAT.'];
        }

        $branch = Branch::where('company_id', $companyId)->orderBy('id')->firstOrFail();

        $summary = $this->documentService->createDailySummary([
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'fecha_resumen' => $data['fecha_referencia'],
            'fecha_generacion' => now()->toDateString(),
            'moneda' => 'PEN',
            'detalles' => $boletas->map(fn ($b) => [
                'tipo_documento' => '03',
                'serie_numero' => $b->numero_completo,
                'estado' => '3',   // 3 = anulación
                'cliente_tipo' => $b->client->tipo_documento ?? '1',
                'cliente_numero' => $b->client->numero_documento ?? '00000000',
                'total' => $b->mto_imp_venta,
                'mto_oper_gravadas' => $b->mto_oper_gravadas,
                'mto_oper_exoneradas' => $b->mto_oper_exoneradas,
                'mto_oper_inafectas' => $b->mto_oper_inafectas,
                'mto_oper_gratuitas' => $b->mto_oper_gratuitas,
                'mto_igv' => $b->mto_igv,
                'mto_isc' => $b->mto_isc ?? 0,
                'mto_icbper' => $b->mto_icbper ?? 0,
            ])->values()->all(),
            'usuario_creacion' => Auth::user()->name,
        ]);

        $resultado = $this->documentService->sendDailySummaryToSunat($summary);
        $cuantas = $boletas->count();

        return $resultado['success']
            ? [true, "Resumen de baja enviado por {$cuantas} boleta(s)."]
            : [false, 'El resumen de boletas falló: ' . $this->errorText($resultado['error'] ?? null)];
    }

    /**
     * SUNAT solo acepta la baja dentro del plazo. Comprobarlo antes evita que
     * el usuario envíe y se entere por el rechazo.
     */
    private function fueraDePlazo(string $fechaReferencia): ?string
    {
        $dias = (int) Carbon::parse($fechaReferencia)->startOfDay()->diffInDays(now()->startOfDay());

        if ($dias <= self::DIAS_DE_PLAZO) {
            return null;
        }

        return 'SUNAT solo acepta la anulación dentro de los ' . self::DIAS_DE_PLAZO
            . " días de emitido el comprobante, y esa fecha tiene {$dias} días. "
            . 'Para anular uno más antiguo, emite una Nota de Crédito.';
    }
}
