<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\EnforcesPlanLimits;
use App\Models\Boleta;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Correlative;
use App\Models\Invoice;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lógica común a Notas de Crédito (07) y Débito (08).
 */
abstract class BaseNotaController extends Controller
{
    use EnforcesPlanLimits;
    use \App\Http\Controllers\Traits\ResuelveSucursalPorSerie;
    use \App\Traits\FormatsSunatError;

    public function __construct(protected DocumentService $documentService)
    {
    }

    /** '07' (crédito) | '08' (débito). */
    abstract protected function tipoDocumento(): string;

    /** 'credit_note' | 'debit_note' para sendToSunat(). */
    abstract protected function documentType(): string;

    /** Modelo Eloquent del comprobante. */
    abstract protected function modelClass(): string;

    /** Prefijo de vistas: 'empresa.notas-credito' | 'empresa.notas-debito'. */
    abstract protected function viewPrefix(): string;

    /** Prefijo de rutas: 'notas-credito' | 'notas-debito'. */
    abstract protected function routePrefix(): string;

    /** Motivos válidos [codigo => label]. */
    abstract protected function motivos(): array;

    /** Crea el documento vía DocumentService (createCreditNote / createDebitNote). */
    abstract protected function createDocument(array $data);

    public function index(Request $request)
    {
        $model = $this->modelClass();

        $notas = $model::where('company_id', Auth::user()->company_id)
            ->with('client:id,razon_social,numero_documento')
            ->when($request->string('search')->trim()->toString(), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('numero_completo', 'like', "%{$search}%")
                        ->orWhere('num_doc_afectado', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($c) => $c->where('razon_social', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('status'), fn ($q, $status) => $q->where('estado_sunat', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view($this->viewPrefix() . '.index', [
            'notas' => $notas,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function create()
    {
        [$branch, $series, $clients, $referencias] = $this->formData();

        if (! $branch || $series->isEmpty()) {
            return redirect()->route('empresa.correlatives.index')
                ->with('error', 'Primero registra una serie para esta nota en Correlativos.');
        }

        return view($this->viewPrefix() . '.create', [
            'branch' => $branch,
            'series' => $series,
            'clients' => $clients,
            'referencias' => $referencias,
            'motivos' => $this->motivos(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    protected function handleStore(array $data)
    {
        // Tope del plan: si la empresa ya alcanzó su límite mensual, no emite.
        if ($limitResponse = $this->documentLimitReachedResponse()) {
            return $limitResponse;
        }

        $this->assertBranchBelongsToCompany($data['branch_id']);

        try {
            $nota = $this->createDocument($data);
            $result = $this->documentService->sendToSunat($nota, $this->documentType());
            $nota = $result['document'] ?? $nota;

            // Vuelve al listado y abre el detalle en modal, sin sacar al usuario
            // de la pantalla en la que estaba.
            $route = 'empresa.' . $this->routePrefix() . '.index';
            $abrir = ['abrir_documento' => $nota->id, 'abrir_titulo' => "Nota {$nota->serie}-{$nota->correlativo}"];

            if ($result['success']) {
                return redirect()->route($route)
                    ->with($abrir)
                    ->with('success', "Nota {$nota->serie}-{$nota->correlativo} emitida y aceptada por SUNAT.");
            }

            return redirect()->route($route)
                ->with($abrir)
                ->with('error', 'Nota registrada, pero SUNAT respondió con error: ' . $this->errorText($result['error'] ?? null));
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo emitir la nota: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $model = $this->modelClass();

        $nota = $model::where('company_id', Auth::user()->company_id)
            ->with(['client', 'branch', 'company'])
            ->findOrFail($id);

        $data = [
            'nota' => $nota,
            'motivos' => $this->motivos(),
            'routePrefix' => $this->routePrefix(),
            'docType' => $this->tipoDocumento() === '07' ? 'nota_credito' : 'nota_debito',
            'titulo' => $this->tipoDocumento() === '07' ? 'Nota de Crédito' : 'Nota de Débito',
        ];

        if (request()->ajax() || request()->boolean('modal')) {
            return view('empresa.notas._show', $data + ['modal' => true]);
        }

        return view($this->viewPrefix() . '.show', $data);
    }

    public function sendToSunat(int $id)
    {
        $model = $this->modelClass();

        $nota = $model::where('company_id', Auth::user()->company_id)
            ->with(['client', 'branch', 'company'])
            ->findOrFail($id);

        if ($nota->estado_sunat === 'ACEPTADO') {
            return back()->with('error', 'La nota ya fue aceptada por SUNAT.');
        }

        $result = $this->documentService->sendToSunat($nota, $this->documentType());

        return $result['success']
            ? back()->with('success', 'Nota enviada y aceptada por SUNAT.')
            : back()->with('error', 'SUNAT respondió con error: ' . $this->errorText($result['error'] ?? null));
    }

    private function formData(): array
    {
        $companyId = Auth::user()->company_id;
        // Todas las series de la empresa, no solo las de la primera sucursal.
        $series = $this->seriesDeLaEmpresa($companyId, $this->tipoDocumento());

        $branch = Branch::where('company_id', $companyId)->orderBy('id')->first();

        $clients = Client::where('company_id', $companyId)
            ->orderBy('razon_social')
            ->limit(500)
            ->get(['id', 'tipo_documento', 'numero_documento', 'razon_social', 'direccion', 'email']);

        // Comprobantes que se pueden afectar: solo los aceptados. Decia
        // «aceptadas» pero no lo filtraba, asi que ofrecia tambien los
        // pendientes y los rechazados, que no se pueden modificar.
        $facturas = Invoice::where('company_id', $companyId)
            ->where('estado_sunat', 'ACEPTADO')
            ->latest('id')->limit(100)
            ->get(['numero_completo', 'client_id'])
            ->map(fn ($f) => ['tipo' => '01', 'num' => $f->numero_completo, 'client_id' => $f->client_id]);

        $boletas = Boleta::where('company_id', $companyId)
            ->where('estado_sunat', 'ACEPTADO')
            ->latest('id')->limit(100)
            ->get(['numero_completo', 'client_id'])
            ->map(fn ($b) => ['tipo' => '03', 'num' => $b->numero_completo, 'client_id' => $b->client_id]);

        // Se parte de collect() a proposito. Si la consulta no devuelve filas,
        // map() sigue entregando una Eloquent\Collection (no hay items que la
        // "degraden" a coleccion base), y su merge() llama getKey() sobre cada
        // elemento: con arrays revienta. Empezando por una coleccion base el
        // merge es una simple union, haya o no resultados.
        $referencias = collect()->merge($facturas)->merge($boletas)->values();

        return [$branch, $series, $clients, $referencias];
    }

    private function assertBranchBelongsToCompany($branchId): void
    {
        $owns = Branch::where('id', $branchId)
            ->where('company_id', Auth::user()->company_id)
            ->exists();

        abort_unless($owns, 403, 'La sucursal no pertenece a tu empresa.');
    }

}
