<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesPdfGeneration;
use App\Services\DocumentService;
use App\Services\FileService;
use App\Models\DebitNote;
use App\Http\Requests\StoreDebitNoteRequest;
use App\Http\Requests\IndexDebitNoteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DebitNoteController extends Controller
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

    public function index(IndexDebitNoteRequest $request): JsonResponse
    {
        try {
            $query = DebitNote::with(['company', 'branch', 'client']);

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
            $notes = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $notes,
                'message' => 'Notas de débito obtenidas correctamente'
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
                'message' => 'Error al obtener las notas de débito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreDebitNoteRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $debitNote = $this->documentService->createDebitNote($validated);
            $sendResult = $this->documentService->sendToSunat($debitNote, 'debit_note');
            $debitNote = $sendResult['document'];

            return response()->json([
                'success' => $sendResult['success'],
                'data' => $debitNote->load(['company', 'branch', 'client']),
                'message' => $sendResult['success']
                    ? 'Nota de debito creada y enviada a SUNAT correctamente'
                    : 'Nota de debito creada, pero SUNAT respondio con error',
                'sunat_error' => $sendResult['success'] ? null : $sendResult['error'],
            ], $sendResult['success'] ? 201 : 422);

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
                'message' => 'Error al crear la nota de débito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $debitNote = $this->deLaEmpresa(DebitNote::class, $id, ['company', 'branch', 'client']);

            return response()->json([
                'success' => true,
                'data' => $debitNote,
                'message' => 'Nota de débito obtenida correctamente'
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
                'message' => 'Nota de débito no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function sendToSunat($id): JsonResponse
    {
        try {
            $debitNote = $this->deLaEmpresa(DebitNote::class, $id, ['company', 'branch', 'client']);

            if ($debitNote->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'La nota de débito ya fue enviada y aceptada por SUNAT'
                ], 400);
            }

            $result = $this->documentService->sendToSunat($debitNote, 'debit_note');

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document'],
                    'message' => 'Nota de débito enviada correctamente a SUNAT'
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
            $debitNote = $this->deLaEmpresa(DebitNote::class, $id);
            $download = $this->fileService->downloadXml($debitNote);
            
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
            $debitNote = $this->deLaEmpresa(DebitNote::class, $id);
            $download = $this->fileService->downloadCdr($debitNote);
            
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
        $debitNote = $this->deLaEmpresa(DebitNote::class, $id);
        return $this->downloadDocumentPdf($debitNote, $request);
    }

    public function generatePdf($id, Request $request)
    {
        $debitNote = $this->deLaEmpresa(DebitNote::class, $id, ['company', 'branch', 'client']);
        return $this->generateDocumentPdf($debitNote, 'debit_note', $request);
    }
}
