<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empresa\StoreBoletaRequest;
use App\Models\Boleta;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Correlative;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoletaController extends Controller
{
    public function __construct(private DocumentService $documentService)
    {
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $boletas = Boleta::where('company_id', $companyId)
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

        return view('empresa.boletas.index', compact('boletas'));
    }

    public function create()
    {
        [$branch, $series, $clients] = $this->formData();

        if (! $branch || $series->isEmpty()) {
            return redirect()->route('empresa.correlatives.index')
                ->with('error', 'Primero registra una serie de boleta (B001) en Correlativos.');
        }

        return view('empresa.boletas.create', compact('branch', 'series', 'clients'));
    }

    public function store(StoreBoletaRequest $request)
    {
        $this->assertBranchBelongsToCompany($request->input('branch_id'));

        try {
            $boleta = $this->documentService->createBoleta($request->toServiceData());
            $result = $this->documentService->sendToSunat($boleta, 'boleta');
            $boleta = $result['document'] ?? $boleta;

            if ($result['success']) {
                return redirect()->route('empresa.boletas.show', $boleta->id)
                    ->with('success', "Boleta {$boleta->numero_completo} emitida y aceptada por SUNAT.");
            }

            return redirect()->route('empresa.boletas.show', $boleta->id)
                ->with('error', 'Boleta registrada, pero SUNAT respondió con error: ' . $this->errorText($result['error'] ?? null));
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo emitir la boleta: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $boleta = Boleta::where('company_id', Auth::user()->company_id)
            ->with(['client', 'branch', 'company'])
            ->findOrFail($id);

        if (request()->ajax() || request()->boolean('modal')) {
            return view('empresa.boletas._detail', ['boleta' => $boleta, 'modal' => true]);
        }

        return view('empresa.boletas.show', compact('boleta'));
    }

    public function sendToSunat(int $id)
    {
        $boleta = Boleta::where('company_id', Auth::user()->company_id)
            ->with(['client', 'branch', 'company'])
            ->findOrFail($id);

        if ($boleta->estado_sunat === 'ACEPTADO') {
            return back()->with('error', 'La boleta ya fue aceptada por SUNAT.');
        }

        $result = $this->documentService->sendToSunat($boleta, 'boleta');

        return $result['success']
            ? back()->with('success', 'Boleta enviada y aceptada por SUNAT.')
            : back()->with('error', 'SUNAT respondió con error: ' . $this->errorText($result['error'] ?? null));
    }

    private function formData(): array
    {
        $companyId = Auth::user()->company_id;
        $branch = Branch::where('company_id', $companyId)->orderBy('id')->first();

        $series = $branch
            ? Correlative::where('branch_id', $branch->id)
                ->where('tipo_documento', '03')
                ->orderBy('serie')
                ->get(['serie', 'correlativo_actual'])
            : collect();

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

    private function errorText($error): string
    {
        if (is_string($error)) {
            return $error;
        }
        if (is_object($error) && method_exists($error, 'getMessage')) {
            return $error->getMessage();
        }

        return 'Error desconocido.';
    }
}
