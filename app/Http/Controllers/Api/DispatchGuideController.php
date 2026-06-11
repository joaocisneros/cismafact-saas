<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesPdfGeneration;
use App\Services\DocumentService;
use App\Services\FileService;
use App\Models\DispatchGuide;
use App\Http\Requests\StoreDispatchGuideRequest;
use App\Http\Requests\IndexDispatchGuideRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DispatchGuideController extends Controller
{
    use HandlesPdfGeneration;
    protected $documentService;
    protected $fileService;

    public function __construct(DocumentService $documentService, FileService $fileService)
    {
        $this->documentService = $documentService;
        $this->fileService = $fileService;
    }

    public function index(IndexDispatchGuideRequest $request): JsonResponse
    {
        try {
            $query = DispatchGuide::with(['company', 'branch', 'client']);

            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('estado_sunat')) {
                $query->where('estado_sunat', $request->estado_sunat);
            }

            if ($request->has('fecha_desde') && $request->has('fecha_hasta')) {
                $query->whereBetween('fecha_emision', [
                    $request->fecha_desde,
                    $request->fecha_hasta
                ]);
            }

            $perPage = min(max((int) $request->get('per_page', 15), 1), 20);
            $guides = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $guides,
                'message' => 'Guías de remisión obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las guías de remisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreDispatchGuideRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $guide = $this->documentService->createDispatchGuide($validated);
            $sendResult = $this->documentService->sendDispatchGuideToSunat($guide);
            $guide = $sendResult['document'];

            return response()->json([
                'success' => $sendResult['success'],
                'data' => $guide->load(['company', 'branch', 'client']),
                'message' => $sendResult['success']
                    ? 'Guia de remision creada y enviada a SUNAT correctamente'
                    : 'Guia creada, pero SUNAT respondio con error',
                'sunat_error' => $sendResult['success'] ? null : $sendResult['error'],
            ], $sendResult['success'] ? 201 : 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la guía de remisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $guide = DispatchGuide::with(['company', 'branch', 'client'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $guide,
                'message' => 'Guía de remisión obtenida correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Guía de remisión no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function sendToSunat($id): JsonResponse
    {
        try {
            $guide = DispatchGuide::with(['company', 'branch', 'client'])->findOrFail($id);

            if ($guide->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'La guía de remisión ya fue enviada y aceptada por SUNAT'
                ], 400);
            }

            $result = $this->documentService->sendDispatchGuideToSunat($guide);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document'],
                    'message' => 'Guía de remisión enviada correctamente a SUNAT'
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
            $guide = DispatchGuide::findOrFail($id);

            if (empty($guide->ticket)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay ticket disponible para consultar'
                ], 400);
            }

            $result = $this->documentService->checkDispatchGuideStatus($guide);

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

    public function downloadXml($id)
    {
        try {
            $guide = DispatchGuide::findOrFail($id);
            $download = $this->fileService->downloadXml($guide);
            
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
            $guide = DispatchGuide::findOrFail($id);
            $download = $this->fileService->downloadCdr($guide);
            
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

    public function downloadPdf($id, Request $request)
    {
        $guide = DispatchGuide::findOrFail($id);
        return $this->downloadDocumentPdf($guide, $request);
    }

    public function generatePdf($id, Request $request)
    {
        $guide = DispatchGuide::with(['company', 'branch', 'client'])->findOrFail($id);
        return $this->generateDocumentPdf($guide, 'dispatch_guide', $request);
    }
}
