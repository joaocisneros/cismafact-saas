<?php

namespace App\Http\Controllers\Traits;

use App\Services\FileService;
use App\Services\PdfService;
use Illuminate\Support\Facades\Storage;

/**
 * El PDF de un comprobante no se guarda siempre al emitir: muchos quedan con
 * pdf_path vacio. Antes eso se traducia en "Archivo no encontrado" al pulsar
 * Ver, aunque el comprobante estuviera perfectamente aceptado por SUNAT.
 *
 * El PDF se puede rehacer en cualquier momento a partir de los datos guardados,
 * asi que se genera al vuelo la primera vez que alguien lo pide y se guarda para
 * las siguientes.
 */
trait GeneraPdfDelComprobante
{
    /** Ruta del PDF, generandolo si aun no existe. */
    protected function rutaDelPdf($document, string $formato = 'A4', bool $regenerar = false): ?string
    {
        $formato = PdfService::normalizarFormato($formato);

        if ($formato === 'A4' && ! $regenerar && $document->pdf_path && Storage::disk('comprobantes')->exists($document->pdf_path)) {
            return $document->pdf_path;
        }

        $fileService = app(FileService::class);
        if ($formato !== 'A4' && ! $regenerar) {
            $rutaExistente = $fileService->getPdfFormatPath($document, $formato);
            if ($rutaExistente !== null) {
                return $rutaExistente;
            }
        }

        $document->loadMissing(['company', 'branch', 'client']);
        $pdf = app(PdfService::class);

        $contenido = match (class_basename($document)) {
            'Invoice' => $pdf->generateInvoicePdf($document, $formato),
            'Boleta' => $pdf->generateBoletaPdf($document, $formato),
            'CreditNote' => $pdf->generateCreditNotePdf($document, $formato),
            'DebitNote' => $pdf->generateDebitNotePdf($document, $formato),
            'DispatchGuide' => $pdf->generateDispatchGuidePdf($document, $formato),
            default => null,
        };

        if ($contenido === null) {
            return null;
        }

        $ruta = $fileService->savePdf($document, $contenido, $formato);
        if ($formato === 'A4') {
            $document->update(['pdf_path' => $ruta]);
        }

        return $ruta;
    }

    /**
     * Explica por que no hay archivo. Un comprobante que nunca se envio no
     * tiene XML ni CDR, y decir solo "no encontrado" hace pensar en un fallo.
     */
    protected function motivoArchivoAusente($document, string $file): string
    {
        $etiquetas = ['xml' => 'XML', 'cdr' => 'CDR', 'pdf' => 'PDF'];
        $etiqueta = $etiquetas[$file] ?? strtoupper($file);

        if (in_array($file, ['xml', 'cdr'], true) && $document->estado_sunat !== 'ACEPTADO') {
            return "Este comprobante todavía no fue aceptado por SUNAT (estado: {$document->estado_sunat}), "
                . "por eso aún no tiene {$etiqueta}. Envíalo a SUNAT para generarlo.";
        }

        return "No se encontró el {$etiqueta} de este comprobante.";
    }
}
