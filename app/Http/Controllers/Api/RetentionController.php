<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesPdfGeneration;
use App\Services\DocumentService;
use App\Services\FileService;
use App\Models\Retention;
use App\Http\Requests\StoreRetentionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RetentionController extends Controller
{
    use \App\Http\Controllers\Api\Concerns\BuscaEnSuEmpresa;

    use HandlesPdfGeneration;
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
            $query = Retention::with(['company', 'branch']);

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
            $retentions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $retentions,
                'message' => 'Retenciones obtenidas correctamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // No aparece, o es de otra empresa: al que pregunta se le responde
            // lo mismo en los dos casos. Antes salia un 500 con el mensaje de
            // Eloquent, que dice la clase y el id que se probo.
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el documento.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las retenciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreRetentionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $retention = $this->documentService->createRetention($validated);

            return response()->json([
                'success' => true,
                'data' => $retention->load(['company', 'branch']),
                'message' => 'Retención creada correctamente'
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // No aparece, o es de otra empresa: al que pregunta se le responde
            // lo mismo en los dos casos. Antes salia un 500 con el mensaje de
            // Eloquent, que dice la clase y el id que se probo.
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el documento.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la retención',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $retention = $this->deLaEmpresa(Retention::class, $id, ['company', 'branch']);

            return response()->json([
                'success' => true,
                'data' => $retention,
                'message' => 'Retención obtenida correctamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // No aparece, o es de otra empresa: al que pregunta se le responde
            // lo mismo en los dos casos. Antes salia un 500 con el mensaje de
            // Eloquent, que dice la clase y el id que se probo.
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el documento.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Retención no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function sendToSunat($id): JsonResponse
    {
        try {
            $retention = $this->deLaEmpresa(Retention::class, $id, ['company', 'branch']);

            if ($retention->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'La retención ya fue enviada y aceptada por SUNAT'
                ], 400);
            }

            $result = $this->documentService->sendRetentionToSunat($retention);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document'],
                    'message' => 'Retención enviada correctamente a SUNAT'
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

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // No aparece, o es de otra empresa: al que pregunta se le responde
            // lo mismo en los dos casos. Antes salia un 500 con el mensaje de
            // Eloquent, que dice la clase y el id que se probo.
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el documento.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el envío a SUNAT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadXml($id)
    {
        try {
            $retention = $this->deLaEmpresa(Retention::class, $id);
            $download = $this->fileService->downloadXml($retention);
            
            if (!$download) {
                return response()->json([
                    'success' => false,
                    'message' => 'XML no encontrado'
                ], 404);
            }
            
            return $download;

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // No aparece, o es de otra empresa: al que pregunta se le responde
            // lo mismo en los dos casos. Antes salia un 500 con el mensaje de
            // Eloquent, que dice la clase y el id que se probo.
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el documento.',
            ], 404);
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
            $retention = $this->deLaEmpresa(Retention::class, $id);
            $download = $this->fileService->downloadCdr($retention);
            
            if (!$download) {
                return response()->json([
                    'success' => false,
                    'message' => 'CDR no encontrado'
                ], 404);
            }
            
            return $download;

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // No aparece, o es de otra empresa: al que pregunta se le responde
            // lo mismo en los dos casos. Antes salia un 500 con el mensaje de
            // Eloquent, que dice la clase y el id que se probo.
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el documento.',
            ], 404);
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
        $retention = $this->deLaEmpresa(Retention::class, $id);
        return $this->downloadDocumentPdf($retention, $request);
    }

    public function generatePdf($id, Request $request)
    {
        $retention = $this->deLaEmpresa(Retention::class, $id, ['company', 'branch']);
        return $this->generateDocumentPdf($retention, 'retention', $request);
    }
}
