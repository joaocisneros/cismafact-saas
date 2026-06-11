<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Boleta;
use App\Models\Branch;
use App\Models\DailySummary;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Resumen Diario de Boletas (RC) ante SUNAT, usado para ANULAR boletas ya
 * aceptadas (estado 3). Las boletas no se anulan por Comunicación de Baja.
 */
class ResumenController extends Controller
{
    public function __construct(private DocumentService $documentService)
    {
    }

    public function index()
    {
        $resumenes = DailySummary::where('company_id', Auth::user()->company_id)
            ->latest('id')
            ->paginate(20);

        return view('empresa.resumenes.index', compact('resumenes'));
    }

    public function create(Request $request)
    {
        $branch = Branch::where('company_id', Auth::user()->company_id)->orderBy('id')->first();
        $fecha = $request->input('fecha');
        $boletas = collect();

        if ($branch && $fecha) {
            $boletas = Boleta::where('company_id', $branch->company_id)
                ->where('branch_id', $branch->id)
                ->whereDate('fecha_emision', $fecha)
                ->where('estado_sunat', 'ACEPTADO')
                ->orderBy('serie')->orderBy('correlativo')
                ->get(['id', 'serie', 'correlativo', 'numero_completo', 'mto_imp_venta']);
        }

        return view('empresa.resumenes.create', compact('branch', 'fecha', 'boletas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha_resumen' => ['required', 'date'],
            'boletas' => ['required', 'array', 'min:1'],
            'boletas.*' => ['integer'],
        ]);

        $branch = Branch::where('company_id', Auth::user()->company_id)->orderBy('id')->firstOrFail();

        $boletas = Boleta::with('client:id,tipo_documento,numero_documento')
            ->where('company_id', Auth::user()->company_id)
            ->where('branch_id', $branch->id)
            ->whereIn('id', $data['boletas'])
            ->where('estado_sunat', 'ACEPTADO')
            ->get();

        if ($boletas->isEmpty()) {
            return back()->with('error', 'No se encontraron boletas válidas para anular.');
        }

        // Detalles con estado '3' = anulación
        $detalles = $boletas->map(fn ($b) => [
            'tipo_documento' => '03',
            'serie_numero' => $b->numero_completo,
            'estado' => '3',
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
        ])->all();

        try {
            $summary = $this->documentService->createDailySummary([
                'company_id' => Auth::user()->company_id,
                'branch_id' => $branch->id,
                'fecha_resumen' => $data['fecha_resumen'],
                'fecha_generacion' => now()->toDateString(),
                'moneda' => 'PEN',
                'detalles' => $detalles,
                'usuario_creacion' => Auth::user()->name,
            ]);

            $result = $this->documentService->sendDailySummaryToSunat($summary);
            $summary = $result['document'] ?? $summary;

            if ($result['success']) {
                return redirect()->route('empresa.resumenes.show', $summary->id)
                    ->with('success', 'Resumen enviado a SUNAT (ticket ' . ($result['ticket'] ?? '') . '). Usa «Consultar estado» para el CDR.');
            }

            return redirect()->route('empresa.resumenes.show', $summary->id)
                ->with('error', 'Registrado, pero SUNAT respondió con error: ' . $this->errorText($result['error'] ?? null));
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo generar el resumen: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $resumen = DailySummary::where('company_id', Auth::user()->company_id)->findOrFail($id);

        return view('empresa.resumenes.show', compact('resumen'));
    }

    public function checkStatus(int $id)
    {
        $resumen = DailySummary::where('company_id', Auth::user()->company_id)->findOrFail($id);

        if (empty($resumen->ticket)) {
            return back()->with('error', 'Este resumen aún no tiene ticket. Primero envíalo a SUNAT.');
        }
        if ($resumen->estado_sunat === 'ACEPTADO') {
            return back()->with('error', 'El resumen ya fue aceptado por SUNAT.');
        }

        $result = $this->documentService->checkSummaryStatus($resumen);

        if (! empty($result['success']) && ($result['document']->estado_sunat ?? '') === 'ACEPTADO') {
            // Marcar las boletas incluidas como anuladas.
            $series = collect($resumen->detalles ?? [])->pluck('serie_numero')->all();
            Boleta::where('company_id', Auth::user()->company_id)
                ->whereIn('numero_completo', $series)
                ->update(['estado_sunat' => 'ANULADO']);

            return back()->with('success', 'Resumen aceptado por SUNAT. Las boletas quedaron anuladas.');
        }

        return back()->with('error', 'SUNAT respondió: ' . $this->errorText($result['error'] ?? ($result['document']->estado_sunat ?? null)));
    }

    private function errorText($error): string
    {
        if (is_string($error)) {
            return $error;
        }
        if (is_object($error)) {
            return $error->message ?? 'Error desconocido.';
        }

        return 'Error desconocido.';
    }
}
