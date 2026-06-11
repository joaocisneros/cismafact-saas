<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Invoice;
use App\Services\ConsultaCpeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConsultaCpeController extends Controller
{
    /** Tipo de documento (codigo SUNAT) => modelo. */
    private const MODELOS = [
        '01' => Invoice::class,
        '03' => Boleta::class,
        '07' => CreditNote::class,
        '08' => DebitNote::class,
    ];

    private const COLUMNAS = ['id', 'serie', 'correlativo', 'numero_completo', 'estado_sunat', 'fecha_emision', 'mto_imp_venta'];

    public function index()
    {
        $companyId = Auth::user()->company_id;

        $invoices = Invoice::where('company_id', $companyId)
            ->latest('fecha_emision')->limit(15)->get(self::COLUMNAS);

        $boletas = Boleta::where('company_id', $companyId)
            ->latest('fecha_emision')->limit(15)->get(self::COLUMNAS);

        return view('empresa.consulta-cpe.index', compact('invoices', 'boletas'));
    }

    public function consultar(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validated = $request->validate([
            'tipo_documento' => ['required', Rule::in(array_keys(self::MODELOS))],
            'documento_id' => ['required', 'integer'],
        ]);

        $modelo = self::MODELOS[$validated['tipo_documento']];
        $documento = $modelo::with('company')
            ->where('company_id', $companyId)
            ->findOrFail($validated['documento_id']);

        $numero = $documento->numero_completo ?? ($documento->serie . '-' . $documento->correlativo);

        try {
            $service = new ConsultaCpeService($documento->company);
            $resultado = $service->consultarComprobante($documento);

            return back()->with('consulta_resultado', [
                'documento' => $numero,
                'success' => (bool) ($resultado['success'] ?? false),
                'message' => $resultado['message'] ?? 'Sin mensaje.',
                'data' => $resultado['data'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return back()->with('consulta_resultado', [
                'documento' => $numero,
                'success' => false,
                'message' => 'No se pudo consultar: ' . $e->getMessage(),
                'data' => null,
            ]);
        }
    }
}
