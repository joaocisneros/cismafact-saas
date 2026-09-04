<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use App\Services\FileService;
use App\Models\DailySummary;
use App\Models\Boleta;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DailySummaryController extends Controller
{
    protected $documentService;
    protected $fileService;

    public function __construct(DocumentService $documentService, FileService $fileService)
    {
        $this->documentService = $documentService;
        $this->fileService = $fileService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = DailySummary::with(['company', 'branch'])
                ->where('company_id', (int) $request->user()->company_id);

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('estado_sunat')) {
                $query->where('estado_sunat', $request->estado_sunat);
            }

            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $query->whereBetween('fecha_resumen', [
                    $request->fecha_desde,
                    $request->fecha_hasta
                ]);
            }

            $perPage = min(max((int) $request->get('per_page', 15), 1), 20);
            $summaries = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $summaries,
                'message' => 'Resúmenes diarios obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los resúmenes diarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        // La validacion va fuera del try: dentro, el catch de mas abajo
        // atrapa la excepcion que lanza y la devuelve como 500. Que a la
        // peticion le falte un dato no es que el servidor este roto —es un
        // 422, y lo corrige quien llama.
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'fecha_resumen' => 'required|date',
        ]);

        try {

            $validated['company_id'] = (int) $request->user()->company_id;
            Branch::where('company_id', $validated['company_id'])
                ->findOrFail($validated['branch_id']);
            $summary = $this->documentService->createSummaryFromBoletas($validated);

            return response()->json([
                'success' => true,
                'data' => $summary->load(['company', 'branch', 'boletas']),
                'message' => 'Resumen diario creado correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el resumen diario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $summary = $this->findCompanySummary($request, $id);

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Resumen diario obtenido correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resumen diario no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function sendToSunat($id): JsonResponse
    {
        try {
            $summary = $this->findCompanySummary(request(), $id);

            if ($summary->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'El resumen ya fue aceptado por SUNAT'
                ], 400);
            }

            $result = $this->documentService->sendDailySummaryToSunat($summary);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch', 'boletas']),
                    'ticket' => $result['ticket'],
                    'message' => 'Resumen enviado correctamente a SUNAT'
                ]);
            }

            return response()->json([
                'success' => false,
                'data' => $result['document']->load(['company', 'branch', 'boletas']),
                'message' => 'Error al enviar resumen a SUNAT',
                'error' => $result['error']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno al enviar resumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkStatus($id): JsonResponse
    {
        try {
            $summary = $this->findCompanySummary(request(), $id);
            $result = $this->documentService->checkSummaryStatus($summary);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch', 'boletas']),
                    'message' => 'Estado del resumen consultado correctamente'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado: ' . ($result['error'] ?? 'Error desconocido')
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado del resumen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /** Boletas que aun se pueden anular en una fecha. */
    public function boletasForVoiding(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer',
            'fecha_emision' => 'nullable|date',
        ]);

        try {

            $companyId = (int) $request->user()->company_id;

            $branchQuery = Branch::where('company_id', $companyId);
            $branch = isset($validated['branch_id'])
                ? $branchQuery->findOrFail($validated['branch_id'])
                : $branchQuery->orderBy('id')->firstOrFail();

            $boletas = $this->documentService->getBoletasForVoiding(
                $companyId,
                (int) $branch->id,
                $validated['fecha_emision'] ?? now()->toDateString()
            );

            return response()->json([
                'success' => true,
                'data' => $boletas,
                'total' => count($boletas),
                'message' => 'Boletas disponibles para anular',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las boletas anulables',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Anula boletas.
     *
     * Va por resumen diario con estado 3, no por comunicacion de baja: SUNAT
     * rechaza una baja que lleve boletas dentro. Devuelve un ticket; el
     * resultado se recoge luego en POST /resumenes/{id}/estado.
     */
    public function voidBoletas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer',
            'fecha_referencia' => 'required|date',
            'boletas' => 'required|array|min:1',
            'boletas.*' => 'required|string|max:20',
        ]);

        try {

            $companyId = (int) $request->user()->company_id;

            $branchQuery = Branch::where('company_id', $companyId);
            $branch = isset($validated['branch_id'])
                ? $branchQuery->findOrFail($validated['branch_id'])
                : $branchQuery->orderBy('id')->firstOrFail();

            $summary = $this->documentService->createSummaryToVoidBoletas(
                $companyId,
                (int) $branch->id,
                $validated['fecha_referencia'],
                $validated['boletas'],
                $request->user()->name ?? null
            );

            $resultado = $this->documentService->sendDailySummaryToSunat($summary);

            if (! ($resultado['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'data' => $summary->fresh(),
                    'message' => 'El resumen de baja no se pudo enviar a SUNAT',
                    'error' => $resultado['error'] ?? null,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $summary->fresh()->load(['company', 'branch']),
                'message' => 'Resumen de baja enviado. Consulta el estado con el ticket.',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular las boletas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function pendingBoletas(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'fecha_emision' => 'required|date',
        ]);

        try {

            $boletas = Boleta::with(['company', 'branch', 'client'])
                ->where('company_id', (int) $request->user()->company_id)
                ->where('branch_id', $request->branch_id)
                ->whereDate('fecha_emision', $request->fecha_emision)
                ->where('estado_sunat', 'PENDIENTE')
                ->whereNull('daily_summary_id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $boletas,
                'total' => $boletas->count(),
                'message' => 'Boletas pendientes obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener boletas pendientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadXml(Request $request, $id)
    {
        $summary = $this->findCompanySummary($request, $id);

        return $this->fileService->downloadXml($summary)
            ?? response()->json(['success' => false, 'message' => 'XML no encontrado'], 404);
    }

    public function downloadCdr(Request $request, $id)
    {
        $summary = $this->findCompanySummary($request, $id);

        return $this->fileService->downloadCdr($summary)
            ?? response()->json(['success' => false, 'message' => 'CDR no encontrado'], 404);
    }

    private function findCompanySummary(Request $request, $id): DailySummary
    {
        return DailySummary::with(['company', 'branch', 'boletas'])
            ->where('company_id', (int) $request->user()->company_id)
            ->findOrFail($id);
    }
}
