<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\EnforcesPlanLimits;
use App\Http\Requests\Empresa\StoreFacturaRequest;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Correlative;
use App\Models\Invoice;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FacturaController extends Controller
{
    use EnforcesPlanLimits;
    use \App\Http\Controllers\Traits\ResuelveSucursalPorSerie;
    use \App\Traits\FormatsSunatError;

    public function __construct(private DocumentService $documentService)
    {
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $facturas = Invoice::where('company_id', $companyId)
            ->with('client:id,razon_social,numero_documento')
            ->when($request->string('search')->trim()->toString(), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('numero_completo', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($c) => $c->where('razon_social', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('status'), fn ($q, $status) => $q->where('estado_sunat', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('empresa.facturas.index', compact('facturas'));
    }

    public function create()
    {
        [$branch, $series, $clients] = $this->formData();

        if (! $branch || $series->isEmpty()) {
            return redirect()->route('empresa.correlatives.index')
                ->with('error', 'Primero registra una serie de factura (F001) en Correlativos.');
        }

        return view('empresa.facturas.create', compact('branch', 'series', 'clients'));
    }

    public function store(StoreFacturaRequest $request)
    {
        // Tope del plan: si la empresa ya alcanzó su límite mensual, no emite.
        if ($limitResponse = $this->documentLimitReachedResponse()) {
            return $limitResponse;
        }

        // La sucursal ya viene resuelta desde la serie en el FormRequest. Aqui
        // solo se comprueba que sea de esta empresa.
        $this->assertBranchBelongsToCompany($request->input('branch_id'));

        try {
            $invoice = $this->documentService->createInvoice($request->toServiceData());
            $result = $this->documentService->sendToSunat($invoice, 'invoice');
            $invoice = $result['document'] ?? $invoice;

            // Se vuelve al listado y alli se abre el detalle en modal, en vez de
            // mandar al usuario a una pantalla aparte: es lo mismo que ve al
            // pulsar "Ver" en la tabla, y no pierde el contexto.
            $abrir = ['abrir_documento' => $invoice->id, 'abrir_titulo' => "Factura {$invoice->numero_completo}"];

            if ($result['success']) {
                return redirect()->route('empresa.facturas.index')
                    ->with($abrir)
                    ->with('success', "Factura {$invoice->numero_completo} emitida y aceptada por SUNAT.");
            }

            return redirect()->route('empresa.facturas.index')
                ->with($abrir)
                ->with('error', 'Factura registrada, pero SUNAT respondió con error: ' . $this->errorText($result['error'] ?? null));
        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('error', 'No se pudo emitir la factura: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $factura = Invoice::where('company_id', Auth::user()->company_id)
            ->with(['client', 'branch', 'company'])
            ->findOrFail($id);

        if (request()->ajax() || request()->boolean('modal')) {
            return view('empresa.facturas._detail', ['factura' => $factura, 'modal' => true]);
        }

        return view('empresa.facturas.show', compact('factura'));
    }

    public function sendToSunat(int $id)
    {
        $factura = Invoice::where('company_id', Auth::user()->company_id)
            ->with(['client', 'branch', 'company'])
            ->findOrFail($id);

        if ($factura->estado_sunat === 'ACEPTADO') {
            return back()->with('error', 'La factura ya fue aceptada por SUNAT.');
        }

        $result = $this->documentService->sendToSunat($factura, 'invoice');

        return $result['success']
            ? back()->with('success', 'Factura enviada y aceptada por SUNAT.')
            : back()->with('error', 'SUNAT respondió con error: ' . $this->errorText($result['error'] ?? null));
    }

    /** Sucursal principal + series de factura disponibles + clientes de la empresa. */
    private function formData(): array
    {
        $companyId = Auth::user()->company_id;

        // Todas las series de la empresa, no solo las de la primera sucursal:
        // con varias sedes, las demas no podian emitir. La sucursal se deduce
        // de la serie elegida (ver ResuelveSucursalPorSerie).
        $series = $this->seriesDeLaEmpresa($companyId, '01');

        $branch = Branch::where('company_id', $companyId)->orderBy('id')->first();

        $clients = Client::where('company_id', $companyId)
            ->orderBy('razon_social')
            ->limit(500)
            ->get(['id', 'tipo_documento', 'numero_documento', 'razon_social', 'direccion', 'email']);

        return [$branch, $series, $clients];
    }

    private function assertBranchBelongsToCompany($branchId): void
    {
        $owns = Branch::where('id', $branchId)
            ->where('company_id', Auth::user()->company_id)
            ->exists();

        abort_unless($owns, 403, 'La sucursal no pertenece a tu empresa.');
    }

}
