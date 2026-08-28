<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FileService
{
    /** Disco privado donde viven XML, CDR y PDF. Ver config/filesystems.php. */
    private const DISCO = 'comprobantes';

    public function saveXml($document, string $xmlContent): string
    {
        $this->ensureDirectoryExists($document, 'xml');
        $path = $this->generatePath($document, 'xml');
        Storage::disk(self::DISCO)->put($path, $xmlContent);
        return $path;
    }

    public function saveCdr($document, string $cdrContent): string
    {
        $this->ensureDirectoryExists($document, 'zip');
        $path = $this->generatePath($document, 'zip');
        Storage::disk(self::DISCO)->put($path, $cdrContent);
        return $path;
    }

    public function savePdf($document, string $pdfContent, string $format = 'A4'): string
    {
        $this->ensureDirectoryExists($document, 'pdf');
        $path = $this->generatePath($document, 'pdf', $format);
        Storage::disk(self::DISCO)->put($path, $pdfContent);
        return $path;
    }

    protected function generatePath($document, string $extension, string $format = 'A4'): string
    {
        $date = Carbon::parse($this->fechaDelDocumento($document));
        $dateFolder = $date->format('dmY'); // Formato: 02092025

        $fileName = $this->nombreDelDocumento($document, $date);
        
        // Obtener tipo de comprobante
        $tipoComprobante = $this->getDocumentTypeName($document);
        
        // Determinar el tipo de archivo (xml, cdr o pdf)
        $tipoArchivo = $extension === 'zip' ? 'cdr' : $extension;
        
        // Crear estructura: TIPO_COMPROBANTE/TIPO_ARCHIVO/DDMMYYYY/
        $directory = "{$tipoComprobante}/{$tipoArchivo}/{$dateFolder}";
        
        // Prefijo según tipo de archivo
        $prefix = '';
        if ($extension === 'zip') {
            $prefix = 'R-'; // CDR
        }
        
        // Para PDFs, agregar el formato al nombre del archivo si no es A4
        if ($extension === 'pdf' && $format !== 'A4') {
            $fileName .= "_{$format}";
        }
        
        return "{$directory}/{$prefix}{$fileName}.{$extension}";
    }

    /**
     * Fecha con la que se archiva el documento.
     *
     * Los comprobantes la llaman fecha_emision. Las comunicaciones de baja y
     * los resumenes diarios no tienen esa columna: usan fecha_generacion.
     */
    protected function fechaDelDocumento($document)
    {
        return $document->fecha_emision
            ?? $document->fecha_generacion
            ?? now();
    }

    /**
     * Nombre del archivo, sin extension.
     *
     * Un comprobante ya lo trae armado en numero_completo (F001-000001). Una
     * comunicacion de baja o un resumen diario no: SUNAT los nombra
     * RUC-RA-AAAAMMDD-correlativo y RUC-RC-AAAAMMDD-correlativo. Sin esto el
     * archivo se guardaba como ".xml", y el del dia siguiente lo pisaba.
     */
    protected function nombreDelDocumento($document, Carbon $date): string
    {
        if (! empty($document->numero_completo)) {
            return $document->numero_completo;
        }

        $tipo = match (class_basename($document)) {
            'VoidedDocument' => 'RA',
            'DailySummary' => 'RC',
            default => null,
        };

        if ($tipo === null) {
            return 'documento-' . $document->getKey();
        }

        $ruc = $document->company->ruc ?? '';
        $correlativo = str_pad((string) $document->correlativo, 3, '0', STR_PAD_LEFT);

        return trim("{$ruc}-{$tipo}-{$date->format('Ymd')}-{$correlativo}", '-');
    }

    protected function getDocumentTypeName($document): string
    {
        // Determinar el nombre de la carpeta según el tipo de documento
        // Verificar si es un modelo Eloquent con el atributo tipo_documento
        if (isset($document->tipo_documento)) {
            return match($document->tipo_documento) {
                '01' => 'facturas',
                '03' => 'boletas',
                '07' => 'notas-credito',
                '08' => 'notas-debito',
                '09' => 'guias-remision',
                '20' => 'percepciones',
                '21' => 'retenciones',
                default => 'otros-comprobantes'
            };
        }
        
        // Fallback basado en el nombre de la clase del modelo
        $className = class_basename($document);
        return match($className) {
            'Invoice' => 'facturas',  // Corregido: Invoice en lugar de Factura
            'Boleta' => 'boletas',
            'CreditNote' => 'notas-credito',
            'DebitNote' => 'notas-debito', 
            'DispatchGuide' => 'guias-remision',
            'Percepcion' => 'percepciones',
            'Retencion' => 'retenciones',
            'DailySummary' => 'resumenes-diarios',
            'VoidedDocument' => 'comunicaciones-baja',
            default => 'otros-comprobantes'
        };
    }

    public function getXmlPath($document): ?string
    {
        if (!$document->xml_path) {
            return null;
        }
        
        return Storage::disk(self::DISCO)->exists($document->xml_path) 
            ? Storage::disk(self::DISCO)->path($document->xml_path)
            : null;
    }

    public function getCdrPath($document): ?string
    {
        if (!$document->cdr_path) {
            return null;
        }
        
        return Storage::disk(self::DISCO)->exists($document->cdr_path)
            ? Storage::disk(self::DISCO)->path($document->cdr_path)
            : null;
    }

    public function getPdfPath($document): ?string
    {
        if (!$document->pdf_path) {
            return null;
        }
        
        return Storage::disk(self::DISCO)->exists($document->pdf_path)
            ? Storage::disk(self::DISCO)->path($document->pdf_path)
            : null;
    }

    public function downloadXml($document)
    {
        if (!$document->xml_path || !Storage::disk(self::DISCO)->exists($document->xml_path)) {
            return null;
        }
        
        return Storage::disk(self::DISCO)->download(
            $document->xml_path,
            $document->numero_completo . '.xml'
        );
    }

    public function downloadCdr($document)
    {
        if (!$document->cdr_path || !Storage::disk(self::DISCO)->exists($document->cdr_path)) {
            return null;
        }
        
        return Storage::disk(self::DISCO)->download(
            $document->cdr_path,
            'R-' . $document->numero_completo . '.zip'
        );
    }

    public function downloadPdf($document)
    {
        if (!$document->pdf_path || !Storage::disk(self::DISCO)->exists($document->pdf_path)) {
            return null;
        }
        
        return Storage::disk(self::DISCO)->download(
            $document->pdf_path,
            $document->numero_completo . '.pdf'
        );
    }

    /**
     * Descarga el PDF de un formato concreto (A4, 80mm, 58mm...).
     *
     * Cada formato se guarda en su propio archivo: generatePath le pone el
     * sufijo del formato a todo lo que no sea A4. downloadPdf() en cambio mira
     * la columna pdf_path, que solo recuerda uno, y por eso devolvia el mismo
     * PDF para cualquier formato que se pidiera.
     */
    public function downloadPdfEnFormato($document, string $format = 'A4')
    {
        $ruta = $this->generatePath($document, 'pdf', $format);

        if (! Storage::disk(self::DISCO)->exists($ruta)) {
            return null;
        }

        $sufijo = $format === 'A4' ? '' : '_' . $format;

        return Storage::disk(self::DISCO)->download(
            $ruta,
            $document->numero_completo . $sufijo . '.pdf'
        );
    }

    public function createDirectoryStructure(): void
    {
        // Tipos de comprobantes
        $tiposComprobantes = [
            'facturas',
            'boletas', 
            'notas-credito',
            'notas-debito',
            'guias-remision',
            'percepciones',
            'retenciones',
            'resumenes-diarios',
            'otros-comprobantes'
        ];
        
        // Tipos de archivos
        $tiposArchivos = ['xml', 'cdr', 'pdf'];
        
        // Crear estructura de directorios base
        foreach ($tiposComprobantes as $tipoComprobante) {
            foreach ($tiposArchivos as $tipoArchivo) {
                $directory = "{$tipoComprobante}/{$tipoArchivo}";
                Storage::disk(self::DISCO)->makeDirectory($directory);
            }
        }
    }

    public function ensureDirectoryExists($document, string $extension): void
    {
        $date = Carbon::parse($document->fecha_emision);
        $dateFolder = $date->format('dmY');
        
        $tipoComprobante = $this->getDocumentTypeName($document);
        $tipoArchivo = $extension === 'zip' ? 'cdr' : $extension;
        
        $directory = "{$tipoComprobante}/{$tipoArchivo}/{$dateFolder}";
        
        if (!Storage::disk(self::DISCO)->exists($directory)) {
            Storage::disk(self::DISCO)->makeDirectory($directory);
        }
    }
}