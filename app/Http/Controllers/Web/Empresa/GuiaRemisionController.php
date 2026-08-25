<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\EnforcesPlanLimits;
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
    use \App\Traits\FormatsSunatError;
    use EnforcesPlanLimits;
    use \App\Http\Controllers\Traits\ResuelveSucursalPorSerie;

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
        // Tope del plan: si la empresa ya alcanzó su límite mensual, no emite.
        if ($limitResponse = $this->documentLimitReachedResponse()) {
            return $limitResponse;
        }

        $this->assertBranchBelongsToCompany($request->input('branch_id'));

        try {
            $guia = $this->documentService->createDispatchGuide($request->toServiceData());
            $result = $this->documentService->sendDispatchGuideToSunat($guia);
            $guia = $result['document'] ?? $guia;

            // Vuelve al listado y abre el detalle en modal, sin sacar al usuario
            // de la pantalla en la que estaba.
            $abrir = ['abrir_documento' => $guia->id, 'abrir_titulo' => "Guía {$guia->serie}-{$guia->correlativo}"];

            if ($result['success']) {
                return redirect()->route('empresa.guias.index')
                    ->with($abrir)
                    ->with('success', "Guía {$guia->serie}-{$guia->correlativo} emitida y aceptada por SUNAT.");
            }

            return redirect()->route('empresa.guias.index')
                ->with($abrir)
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

    /**
     * Deja constancia de una baja hecha en el portal de SUNAT.
     *
     * No se envia nada: SUNAT no permite anular una GRE desde el sistema del
     * contribuyente ("la comunicacion se debe realizar a traves de la opcion que
     * contemple el SEE - SOL"). Esto solo evita que una guia ya dada de baja
     * siga figurando como vigente en los listados.
     */
    public function registrarBaja(Request $request, int $id)
    {
        $guia = DispatchGuide::where('company_id', Auth::user()->company_id)->findOrFail($id);

        if ($guia->anulado_en) {
            return back()->with('error', 'Esta guía ya figura como dada de baja.');
        }

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'min:3', 'max:250'],
        ], [], ['motivo' => 'motivo']);

        $guia->update([
            'anulado_en' => now(),
            'anulado_motivo' => $datos['motivo'],
            'anulado_registrado_por' => Auth::user()->name,
        ]);

        return back()->with('success',
            "Guía {$guia->numero_completo} marcada como dada de baja. Recuerda que la baja ante SUNAT "
            . 'se realiza en su portal con tu Clave SOL; aquí solo queda el registro.');
    }

    private function formData(): array
    {
        $companyId = Auth::user()->company_id;
        $series = $this->seriesDeLaEmpresa($companyId, '09');

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
