<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use App\Services\FileService;
use App\Models\VoidedDocument;
use App\Models\Branch;
use App\Http\Requests\StoreVoidedDocumentRequest;
use App\Http\Requests\IndexVoidedDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VoidedDocumentController extends Controller
{
    protected $documentService;
    protected $fileService;

    public function __construct(DocumentService $documentService, FileService $fileService)
    {
        $this->documentService = $documentService;
        $this->fileService = $fileService;
    }

    public function index(IndexVoidedDocumentRequest $request): JsonResponse
    {
        try {
            $query = VoidedDocument::with(['company', 'branch'])
                ->where('company_id', (int) $request->user()->company_id);

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('estado_sunat')) {
                $query->where('estado_sunat', $request->estado_sunat);
            }

            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $query->whereBetween('fecha_generacion', [
                    $request->fecha_desde,
                    $request->fecha_hasta
                ]);
            }

            $perPage = min(max((int) $request->get('per_page', 15), 1), 20);
            $voidedDocs = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $voidedDocs,
                'message' => 'Comunicaciones de baja obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las comunicaciones de baja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreVoidedDocumentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['company_id'] = (int) $request->user()->company_id;
            Branch::where('company_id', $validated['company_id'])
                ->findOrFail($validated['branch_id']);
            $voidedDoc = $this->documentService->createVoidedDocument($validated);

            return response()->json([
                'success' => true,
                'data' => $voidedDoc->load(['company', 'branch']),
                'message' => 'Comunicación de baja creada correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la comunicación de baja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $voidedDoc = $this->findCompanyVoidedDocument($request, $id);

            return response()->json([
                'success' => true,
                'data' => $voidedDoc,
                'message' => 'Comunicación de baja obtenida correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comunicación de baja no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function sendToSunat($id): JsonResponse
    {
        try {
            $voidedDoc = $this->findCompanyVoidedDocument(request(), $id);

            if ($voidedDoc->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'La comunicación de baja ya fue enviada y aceptada por SUNAT'
                ], 400);
            }

            $result = $this->documentService->sendVoidedDocumentToSunat($voidedDoc);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document'],
                    'ticket' => $result['ticket'],
                    'message' => 'Comunicación de baja enviada correctamente a SUNAT'
                ]);
            } else {
                $errorCode = 'UNKNOWN';
                $errorMessage = 'Error desconocido';
                
                if (is_object($result['error'])) {
                    if (method_exists($result['error'], 'getCode')) {
                        $errorCode = $result['error']->getCode();
                    } elseif (property_exists($result['error'], 'code')) {
                        $errorCode = $result['error']->code;
                    }
                    
                    if (method_exists($result['error'], 'getMessage')) {
                        $errorMessage = $result['error']->getMessage();
                    } elseif (property_exists($result['error'], 'message')) {
                        $errorMessage = $result['error']->message;
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'data' => $result['document'],
                    'message' => 'Error al enviar a SUNAT: ' . $errorMessage,
                    'error_code' => $errorCode
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el envío a SUNAT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkStatus($id): JsonResponse
    {
        try {
            $voidedDoc = $this->findCompanyVoidedDocument(request(), $id);

            if (empty($voidedDoc->ticket)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay ticket disponible para consultar'
                ], 400);
            }

            $result = $this->documentService->checkVoidedDocumentStatus($voidedDoc);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document'],
                    'message' => 'Estado consultado correctamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al consultar estado',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function documentsForVoiding(Request $request): JsonResponse
    {
        // La validacion va fuera del try: dentro, el catch de mas abajo
        // atrapa la excepcion que lanza y la devuelve como 500. Que a la
        // peticion le falte un dato no es que el servidor este roto —es un
        // 422, y lo corrige quien llama.
        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'fecha_referencia' => ['nullable', 'date'],
            'tipo_documento' => ['nullable', 'string', 'in:01,07,08,09'],
        ]);

        try {
            $companyId = (int) $request->user()->company_id;

            $branchQuery = Branch::where('company_id', $companyId);
            $branch = isset($validated['branch_id'])
                ? $branchQuery->findOrFail($validated['branch_id'])
                : $branchQuery->orderBy('id')->firstOrFail();

            $documents = $this->documentService->getDocumentsForVoiding(
                $companyId,
                (int) $branch->id,
                $validated['fecha_referencia'] ?? now()->toDateString(),
                $validated['tipo_documento'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $documents,
                'message' => 'Documentos disponibles para anular'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener documentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadXml($id)
    {
        try {
            $voidedDoc = $this->findCompanyVoidedDocument(request(), $id);
            $download = $this->fileService->downloadXml($voidedDoc);
            
            if (!$download) {
                return response()->json([
                    'success' => false,
                    'message' => 'XML no encontrado'
                ], 404);
            }
            
            return $download;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar XML',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadCdr($id)
    {
        try {
            $voidedDoc = $this->findCompanyVoidedDocument(request(), $id);
            $download = $this->fileService->downloadCdr($voidedDoc);
            
            if (!$download) {
                return response()->json([
                    'success' => false,
                    'message' => 'CDR no encontrado'
                ], 404);
            }
            
            return $download;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar CDR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function findCompanyVoidedDocument(Request $request, $id): VoidedDocument
    {
        return VoidedDocument::with(['company', 'branch'])
            ->where('company_id', (int) $request->user()->company_id)
            ->findOrFail($id);
    }
}
