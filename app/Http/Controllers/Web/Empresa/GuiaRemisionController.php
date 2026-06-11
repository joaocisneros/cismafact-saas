<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empresa\StoreGuiaRequest;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Correlative;
use App\Models\DispatchGuide;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuiaRemisionController extends Controller
{
    public function __construct(private DocumentService $documentService)
    {
    }

    public function index(Request $request)
    {
        $guias = DispatchGuide::where('company_id', Auth::user()->company_id)
            ->with('client:id,razon_social,numero_documento')
            ->when($request->string('search')->trim()->toString(), function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('numero_completo', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($c) => $c->where('razon_social', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('status'), fn ($q, $status) => $q->where('estado_sunat', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('empresa.guias.index', compact('guias'));
    }

    public function create()
    {
        [$branch, $series, $clients] = $this->formData();

        if (! $branch || $series->isEmpty()) {
            return redirect()->route('empresa.correlatives.index')
                ->with('error', 'Primero registra una serie de guía (T001) en Correlativos.');
        }

        return view('empresa.guias.create', [
            'branch' => $branch,
            'series' => $series,
            'clients' => $clients,
            'motivos' => StoreGuiaRequest::MOTIVOS,
            'modalidades' => StoreGuiaRequest::MODALIDADES,
        ]);
    }

    public function store(StoreGuiaRequest $request)
    {
        $this->assertBranchBelongsToCompany($request->input('branch_id'));

        try {
            $guia = $this->documentService->createDispatchGuide($request->toServiceData());
            $result = $this->documentService->sendDispatchGuideToSunat($guia);
            $guia = $result['document'] ?? $guia;

            if ($result['success']) {
                return redirect()->route('empresa.guias.show', $guia->id)
                    ->with('success', "Guía {$guia->serie}-{$guia->correlativo} emitida y aceptada por SUNAT.");
            }

            return redirect()->route('empresa.guias.show', $guia->id)
                ->with('error', 'Guía registrada, pero SUNAT respondió con error: ' . $this->errorText($result['error'] ?? $result['message'] ?? null));
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo emitir la guía: ' . $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $guia = DispatchGuide::where('company_id', Auth::user()->company_id)
            ->with(['client', 'branch', 'company'])
            ->findOrFail($id);

        $data = [
            'guia' => $guia,
            'motivos' => StoreGuiaRequest::MOTIVOS,
            'modalidades' => StoreGuiaRequest::MODALIDADES,
        ];

        if (request()->ajax() || request()->boolean('modal')) {
            return view('empresa.guias._detail', $data + ['modal' => true]);
        }

        return view('empresa.guias.show', $data);
    }

    public function sendToSunat(int $id)
    {
        $guia = DispatchGuide::where('company_id', Auth::user()->company_id)
            ->with(['client', 'branch', 'company'])
            ->findOrFail($id);

        if ($guia->estado_sunat === 'ACEPTADO') {
            return back()->with('error', 'La guía ya fue aceptada por SUNAT.');
        }

        $result = $this->documentService->sendDispatchGuideToSunat($guia);

        if (! $result['success']) {
            return back()->with('error', 'SUNAT respondió con error: ' . $this->errorText($result['error'] ?? $result['message'] ?? null));
        }

        // El envio de guias es asincrono: SUNAT devuelve un ticket y la guia
        // queda PROCESANDO hasta consultar el estado.
        return back()->with('success', "Guía enviada a SUNAT (ticket {$result['ticket']}). Usa «Consultar estado» para obtener el CDR.");
    }

    /**
     * Consulta el estado de una guía ya enviada (ticket asíncrono GRE).
     */
    public function checkStatus(int $id)
    {
        $guia = DispatchGuide::where('company_id', Auth::user()->company_id)
            ->with(['client', 'branch', 'company'])
            ->findOrFail($id);

        if (empty($guia->ticket)) {
            return back()->with('error', 'Esta guía aún no tiene ticket. Primero envíala a SUNAT.');
        }

        if ($guia->estado_sunat === 'ACEPTADO') {
            return back()->with('error', 'La guía ya fue aceptada por SUNAT.');
        }

        $result = $this->documentService->checkDispatchGuideStatus($guia);

        return $result['success']
            ? back()->with('success', 'Guía aceptada por SUNAT. CDR disponible.')
            : back()->with('error', 'SUNAT respondió: ' . $this->errorText($result['error'] ?? $result['message'] ?? null));
    }

    private function formData(): array
    {
        $companyId = Auth::user()->company_id;
        $branch = Branch::where('company_id', $companyId)->orderBy('id')->first();

        $series = $branch
            ? Correlative::where('branch_id', $branch->id)
                ->where('tipo_documento', '09')
                ->orderBy('serie')
                ->get(['serie', 'correlativo_actual'])
            : collect();

        $clients = Client::where('company_id', $companyId)
            ->orderBy('razon_social')
            ->limit(500)
            ->get(['id', 'tipo_documento', 'numero_documento', 'razon_social', 'direccion']);

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
