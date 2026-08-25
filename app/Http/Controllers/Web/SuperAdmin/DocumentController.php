<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
use App\Models\Company;
use App\Services\ConsultaCpeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    use \App\Http\Controllers\Traits\GeneraPdfDelComprobante;

    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'type' => ['nullable', Rule::in(['factura', 'boleta', 'nc', 'nd', 'guia'])],
            'status' => ['nullable', Rule::in(['PENDIENTE', 'PROCESANDO', 'ENVIADO', 'ACEPTADO', 'RECHAZADO', 'ERROR'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $documents = $this->documentsQuery($request)
            ->orderByDesc('created_at')
            ->simplePaginate(20)
            ->withQueryString();

        $companies = Cache::remember('super_admin_company_filter_options', now()->addMinutes(5), fn () => Company::orderBy('razon_social')
            ->limit(300)
            ->get(['id', 'razon_social']));

        return view('super-admin.documents', compact('documents', 'companies'));
    }

    public function show($type, $id)
    {
        $modelMap = [
            'factura' => Invoice::class,
            'boleta' => Boleta::class,
            'nc' => CreditNote::class,
            'nd' => DebitNote::class,
            'guia' => DispatchGuide::class,
        ];

        if (!isset($modelMap[$type])) {
            abort(404);
        }

        $doc = $modelMap[$type]::with('company')->findOrFail($id);

        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.documents._detail_modal', compact('doc', 'type'));
        }

        return view('super-admin.documents.show', compact('doc', 'type'));
    }

    public function download($type, $id, $file)
    {
        $modelMap = [
            'factura' => Invoice::class,
            'boleta' => Boleta::class,
            'nc' => CreditNote::class,
            'nd' => DebitNote::class,
            'guia' => DispatchGuide::class,
        ];

        if (!isset($modelMap[$type])) {
            abort(404);
        }

        $doc = $modelMap[$type]::findOrFail($id);

        $pathField = match($file) {
            'xml' => 'xml_path',
            'cdr' => 'cdr_path',
            'pdf' => 'pdf_path',
            default => abort(404),
        };

        $path = $doc->$pathField;

        // El PDF se rehace si falta, igual que en el panel de la empresa.
        if ($file === 'pdf') {
            $path = $this->rutaDelPdf($doc);
        }

        if (!$path || !Storage::disk('comprobantes')->exists($path)) {
            abort(404, $this->motivoArchivoAusente($doc, $file));
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = "{$type}_{$doc->numero_completo}.{$extension}";

        return Storage::disk('comprobantes')->download($path, $filename);
    }

    public function view($type, $id, $file)
    {
        $modelMap = [
            'factura' => Invoice::class,
            'boleta' => Boleta::class,
            'nc' => CreditNote::class,
            'nd' => DebitNote::class,
            'guia' => DispatchGuide::class,
        ];

        if (!isset($modelMap[$type])) {
            abort(404);
        }

        $doc = $modelMap[$type]::findOrFail($id);

        $pathField = match($file) {
            'xml' => 'xml_path',
            'cdr' => 'cdr_path',
            'pdf' => 'pdf_path',
            default => abort(404),
        };

        $path = $doc->$pathField;

        // El PDF se rehace si falta, igual que en el panel de la empresa.
        if ($file === 'pdf') {
            $path = $this->rutaDelPdf($doc);
        }

        if (!$path || !Storage::disk('comprobantes')->exists($path)) {
            abort(404, $this->motivoArchivoAusente($doc, $file));
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $mimeType = match($extension) {
            'pdf' => 'application/pdf',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
            'cdr' => 'application/xml',
            default => 'application/octet-stream',
        };

        $contents = Storage::disk('comprobantes')->get($path);
        $filename = $doc->numero_completo . '.' . $extension;

        return response($contents)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function consult($type, $id)
    {
        $modelMap = [
            'factura' => Invoice::class,
            'boleta' => Boleta::class,
            'nc' => CreditNote::class,
            'nd' => DebitNote::class,
        ];

        if (! isset($modelMap[$type])) {
            return back()->with('error', 'La consulta CPE directa no está disponible para este tipo de documento.');
        }

        $document = $modelMap[$type]::with('company')->findOrFail($id);
        $result = (new ConsultaCpeService($document->company))->consultarComprobante($document);

        if (! ($result['success'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'SUNAT no pudo responder la consulta.');
        }

        return back()->with('success', 'Estado SUNAT actualizado correctamente.');
    }

    private function documentsQuery(Request $request)
    {
        $type = $request->input('type');
        $queries = collect([
            'factura' => $this->documentQuery('invoices', 'factura', 'Factura', true, $request),
            'boleta' => $this->documentQuery('boletas', 'boleta', 'Boleta', true, $request),
            'nc' => $this->documentQuery('credit_notes', 'nc', 'Nota Credito', true, $request),
            'nd' => $this->documentQuery('debit_notes', 'nd', 'Nota Debito', true, $request),
            'guia' => $this->documentQuery('dispatch_guides', 'guia', 'Guia Remision', false, $request),
        ]);

        $selected = $type ? $queries->only($type) : $queries;
        $union = $selected->values()->reduce(fn ($query, $next) => $query ? $query->unionAll($next) : $next);

        return DB::query()->fromSub($union, 'documents');
    }

    private function documentQuery(string $table, string $type, string $label, bool $hasAmount, Request $request)
    {
        $query = DB::table($table)
            ->leftJoin('companies', "{$table}.company_id", '=', 'companies.id')
            ->selectRaw('? as type_key, ? as type_label', [$type, $label])
            ->addSelect([
                "{$table}.id",
                "{$table}.company_id",
                "{$table}.serie",
                "{$table}.correlativo",
                "{$table}.numero_completo",
                "{$table}.estado_sunat",
                "{$table}.xml_path",
                "{$table}.cdr_path",
                "{$table}.pdf_path",
                "{$table}.created_at",
                'companies.razon_social as empresa',
            ])
            ->selectRaw($hasAmount ? "{$table}.mto_imp_venta as total" : '0 as total');

        if ($request->filled('company_id')) {
            $query->where("{$table}.company_id", $request->integer('company_id'));
        }

        if ($request->filled('status')) {
            $query->where("{$table}.estado_sunat", $request->input('status'));
        }

        if ($request->filled('search')) {
            $query->where("{$table}.numero_completo", 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate("{$table}.created_at", '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate("{$table}.created_at", '<=', $request->input('date_to'));
        }

        return $query;
    }
}
