<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Correlative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CorrelativeController extends Controller
{
    /** Tipos de documento (codigo SUNAT => nombre). */
    public const TIPOS = [
        '01' => 'Factura',
        '03' => 'Boleta',
        '07' => 'Nota de Crédito',
        '08' => 'Nota de Débito',
        '09' => 'Guía de Remisión',
    ];

    public function index()
    {
        $companyId = Auth::user()->company_id;

        $branches = Branch::where('company_id', $companyId)
            ->with(['correlatives' => fn ($q) => $q->orderBy('tipo_documento')->orderBy('serie')])
            ->orderBy('codigo')
            ->get();

        return view('empresa.correlatives.index', [
            'branches' => $branches,
            'tipos' => self::TIPOS,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $validated = $request->validate([
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'tipo_documento' => ['required', Rule::in(array_keys(self::TIPOS))],
            'serie' => [
                'required', 'string', 'size:4',
                Rule::unique('correlatives')->where(fn ($q) => $q->where('branch_id', $request->branch_id)),
            ],
            'correlativo_actual' => ['nullable', 'integer', 'min:0'],
        ], [
            'serie.size' => 'La serie debe tener exactamente 4 caracteres (ej: F001).',
            'serie.unique' => 'Esa serie ya existe en la sucursal seleccionada.',
        ]);

        Correlative::create([
            'branch_id' => $validated['branch_id'],
            'tipo_documento' => $validated['tipo_documento'],
            'serie' => strtoupper($validated['serie']),
            'correlativo_actual' => $validated['correlativo_actual'] ?? 0,
        ]);

        return back()->with('success', 'Serie agregada correctamente.');
    }

    public function destroy(Correlative $correlative)
    {
        abort_unless($correlative->branch->company_id === Auth::user()->company_id, 403);

        $correlative->delete();

        return back()->with('success', 'Serie eliminada correctamente.');
    }
}
