<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\VoidedDocument;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Anulación de comprobantes vía Comunicación de Baja (RA) ante SUNAT.
 * Aplica a Facturas y Notas de Crédito/Débito. Las Boletas se anulan por
 * Resumen Diario (otro módulo).
 */
class AnulacionController extends Controller
{
    public function __construct(private DocumentService $documentService)
    {
    }

    public function index()
    {
        $anulaciones = VoidedDocument::where('company_id', Auth::user()->company_id)
            ->latest('id')
            ->paginate(20);

        return view('empresa.anulaciones.index', compact('anulaciones'));
    }

    public function create(Request $request)
    {
        $branch = Branch::where('company_id', Auth::user()->company_id)->orderBy('id')->first();
        $fecha = $request->input('fecha');
        $documentos = [];

        if ($branch && $fecha) {
            // Solo facturas (01) y notas (07); las boletas se anulan por Resumen.
            $documentos = collect($this->documentService->getDocumentsForVoiding($branch->company_id, $branch->id, $fecha))
                ->reject(fn ($d) => $d['tipo_documento'] === '03')
                ->values()
                ->all();
        }

        return view('empresa.anulaciones.create', [
            'branch' => $branch,
            'fecha' => $fecha,
            'documentos' => $documentos,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha_referencia' => ['required', 'date'],
            'motivo' => ['required', 'string', 'min:3', 'max:250'],
            'documentos' => ['required', 'array', 'min:1'],
            'documentos.*.tipo_documento' => ['required', 'string', 'in:01,07,08'],
            'documentos.*.serie' => ['required', 'string', 'max:4'],
            'documentos.*.correlativo' => ['required', 'string', 'max:8'],
        ]);

        $branch = Branch::where('company_id', Auth::user()->company_id)->orderBy('id')->firstOrFail();

        $detalles = array_map(fn ($d) => [
            'tipo_documento' => $d['tipo_documento'],
            'serie' => $d['serie'],
            'correlativo' => $d['correlativo'],
            'motivo_especifico' => $data['motivo'],
        ], $data['documentos']);

        try {
            $voided = $this->documentService->createVoidedDocument([
                'company_id' => Auth::user()->company_id,
                'branch_id' => $branch->id,
                'fecha_referencia' => $data['fecha_referencia'],
                'motivo_baja' => $data['motivo'],
                'detalles' => $detalles,
                'usuario_creacion' => Auth::user()->name,
            ]);

            $result = $this->documentService->sendVoidedDocumentToSunat($voided);
            $voided = $result['document'] ?? $voided;

            if ($result['success']) {
                return redirect()->route('empresa.anulaciones.show', $voided->id)
                    ->with('success', 'Comunicación de baja enviada a SUNAT (ticket ' . ($result['ticket'] ?? '') . '). Usa «Consultar estado» para el CDR.');
            }

            return redirect()->route('empresa.anulaciones.show', $voided->id)
                ->with('error', 'Registrada, pero SUNAT respondió con error: ' . $this->errorText($result['error'] ?? null));
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo anular: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $anulacion = VoidedDocument::where('company_id', Auth::user()->company_id)->findOrFail($id);

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
            : back()->with('error', 'SUNAT respondió: ' . $this->errorText($result['error'] ?? ($result['document']->estado_sunat ?? null)));
    }

    private function errorText($error): string
    {
        if (is_string($error)) {
            return $error;
        }
        if (is_object($error)) {
            return $error->message ?? ($error->getMessage ?? 'Error desconocido.');
        }

        return 'Error desconocido.';
    }
}
