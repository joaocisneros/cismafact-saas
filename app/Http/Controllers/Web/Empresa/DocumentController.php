<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Services\FileService;
use App\Services\PdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    private const LIST_COLUMNS = [
        'id',
        'company_id',
        'client_id',
        'serie',
        'numero_completo',
        'fecha_emision',
        'estado_sunat',
        'xml_path',
        'cdr_path',
        'pdf_path',
        'created_at',
    ];

    private const DOCUMENT_TYPES = [
        'factura' => [Invoice::class, 'Factura'],
        'boleta' => [Boleta::class, 'Boleta'],
        'nota_credito' => [CreditNote::class, 'Nota Credito'],
        'nota_debito' => [DebitNote::class, 'Nota Debito'],
        'guia_remision' => [DispatchGuide::class, 'Guia Remision'],
    ];

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $type = $request->input('type', 'all');
        $filters = [
            'serie' => $request->input('serie'),
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'dateFrom' => $request->input('date_from'),
            'dateTo' => $request->input('date_to'),
        ];

        $documents = collect();

        foreach (self::DOCUMENT_TYPES as $documentType => [$modelClass, $label]) {
            if ($type !== 'all' && $type !== $documentType) {
                continue;
            }

            $documents = $documents->merge(
                $this->getDocumentsForList($modelClass::query(), $companyId, $filters, $documentType, $label)
            );
        }

        $documents = $documents->sortByDesc('created_at')->take(100)->values();

        return view('empresa.documents.index', compact('documents', 'type'));
    }

    private function getDocumentsForList(Builder $query, int $companyId, array $filters, string $type, string $label)
    {
        $columns = self::LIST_COLUMNS;
        if ($type !== 'guia_remision') {
            $columns[] = 'mto_imp_venta';
        }

        $query->select($columns)
            ->where('company_id', $companyId)
            ->when($filters['serie'], fn($q, $serie) => $q->where('serie', 'like', "{$serie}%"))
            ->when($filters['status'], fn($q, $status) => $q->where('estado_sunat', $status))
            ->when($filters['dateFrom'], fn($q, $dateFrom) => $q->where('fecha_emision', '>=', $dateFrom))
            ->when($filters['dateTo'], fn($q, $dateTo) => $q->where('fecha_emision', '<=', $dateTo));

        if ($filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('serie', 'like', "%{$search}%")
                    ->orWhere('numero_completo', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('razon_social', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest()
            ->limit(50)
            ->get()
            ->map(fn($document) => [
                'id' => $document->id,
                'type' => $type,
                'type_label' => $label,
                'serie' => $document->serie,
                'numero_completo' => $document->numero_completo,
                'fecha_emision' => $document->fecha_emision,
                'mto_imp_venta' => $document->getAttribute('mto_imp_venta') ?? 0,
                'estado_sunat' => $document->estado_sunat,
                'xml_path' => $document->xml_path,
                'cdr_path' => $document->cdr_path,
                'pdf_path' => $document->pdf_path,
                'created_at' => $document->created_at,
            ]);
    }

    public function download(string $type, int $id, string $file)
    {
        $document = $this->findCompanyDocument($type, $id);
        $pathField = $this->pathFieldFor($file);
        $path = $document->$pathField;

        if ($file === 'pdf' && (!$path || !Storage::disk('public')->exists($path))) {
            $path = $this->generatePdf($document);
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            return back()->with('error', 'Archivo no encontrado.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = "{$type}_{$document->numero_completo}.{$extension}";

        return Storage::disk('public')->download($path, $filename);
    }

    public function view(string $type, int $id, string $file)
    {
        $document = $this->findCompanyDocument($type, $id);
        $pathField = $this->pathFieldFor($file);
        $path = $document->$pathField;

        if (!$path || !Storage::disk('public')->exists($path)) {
            return back()->with('error', 'Archivo no encontrado.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mimeType = match($extension) {
            'pdf' => 'application/pdf',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
            'cdr' => 'application/xml',
            default => 'application/octet-stream',
        };

        $contents = Storage::disk('public')->get($path);

        return response($contents)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $document->numero_completo . '.' . $extension . '"');
    }

    private function findCompanyDocument(string $type, int $id)
    {
        if (!isset(self::DOCUMENT_TYPES[$type])) {
            abort(404);
        }

        [$modelClass] = self::DOCUMENT_TYPES[$type];

        return $modelClass::where('company_id', Auth::user()->company_id)->findOrFail($id);
    }

    private function pathFieldFor(string $file): string
    {
        return match($file) {
            'xml' => 'xml_path',
            'cdr' => 'cdr_path',
            'pdf' => 'pdf_path',
            default => abort(404),
        };
    }

    private function generatePdf($document): string
    {
        $document->loadMissing(['company', 'branch', 'client']);
        $pdfService = app(PdfService::class);

        $content = match(class_basename($document)) {
            'Invoice' => $pdfService->generateInvoicePdf($document),
            'Boleta' => $pdfService->generateBoletaPdf($document),
            'CreditNote' => $pdfService->generateCreditNotePdf($document),
            'DebitNote' => $pdfService->generateDebitNotePdf($document),
            'DispatchGuide' => $pdfService->generateDispatchGuidePdf($document),
        };

        $path = app(FileService::class)->savePdf($document, $content);
        $document->update(['pdf_path' => $path]);

        return $path;
    }
}
