<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Correlative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Sucursales (establecimientos) de la empresa.
 *
 * Hasta ahora solo se podian crear por API, asi que un cliente con varias sedes
 * no tenia como añadirlas. Cada sucursal lleva sus propias series, y la serie es
 * unica por empresa: dos sedes con la misma serie emitirian el mismo numero con
 * el mismo RUC y SUNAT lo rechazaria.
 */
class BranchController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $branches = Branch::where('company_id', $companyId)
            ->withCount('correlatives')
            ->with(['correlatives' => fn ($q) => $q->orderBy('tipo_documento')->orderBy('serie')])
            ->orderBy('codigo')
            ->get();

        return view('empresa.branches.index', compact('branches'));
    }

    /** Formulario de alta, para abrir en el modal de la cabecera. */
    public function create()
    {
        return view('empresa.branches._form', [
            'branch' => new Branch(),
            'accion' => route('empresa.branches.store'),
            'metodo' => 'POST',
        ]);
    }

    /** Formulario de edicion, tambien en modal. */
    public function edit(Branch $branch)
    {
        $this->soloDeMiEmpresa($branch);

        return view('empresa.branches._form', [
            'branch' => $branch,
            'accion' => route('empresa.branches.update', $branch),
            'metodo' => 'PUT',
        ]);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $datos = $request->validate([
            // El codigo de establecimiento lo asigna SUNAT (0000 es la casa matriz).
            'codigo' => [
                'required', 'string', 'max:10',
                Rule::unique('branches', 'codigo')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'ubigeo' => ['required', 'string', 'size:6'],
            'distrito' => ['required', 'string', 'max:255'],
            'provincia' => ['required', 'string', 'max:255'],
            'departamento' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'codigo.unique' => 'Ya tienes una sucursal con ese código.',
            'ubigeo.size' => 'El ubigeo son 6 dígitos (por ejemplo 150101 para Lima).',
        ]);

        Branch::create($datos + ['company_id' => $companyId, 'activo' => true]);

        return back()->with('success', "Sucursal {$datos['nombre']} creada. Añádele sus series para que pueda emitir.");
    }

    public function update(Request $request, Branch $branch)
    {
        $this->soloDeMiEmpresa($branch);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'ubigeo' => ['required', 'string', 'size:6'],
            'distrito' => ['required', 'string', 'max:255'],
            'provincia' => ['required', 'string', 'max:255'],
            'departamento' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        // El codigo no se toca: es el que la empresa declaro ante SUNAT y va en
        // los comprobantes ya emitidos.
        $branch->update($datos);

        return back()->with('success', 'Sucursal actualizada.');
    }

    public function toggle(Branch $branch)
    {
        $this->soloDeMiEmpresa($branch);

        // No se puede desactivar la ultima sucursal activa: la empresa se
        // quedaria sin poder emitir.
        if ($branch->activo && Branch::where('company_id', $branch->company_id)->where('activo', true)->count() <= 1) {
            return back()->with('error', 'No puedes desactivar tu única sucursal activa.');
        }

        $branch->update(['activo' => ! $branch->activo]);

        return back()->with('success', $branch->activo ? 'Sucursal activada.' : 'Sucursal desactivada.');
    }

    public function destroy(Branch $branch)
    {
        $this->soloDeMiEmpresa($branch);

        // Si alguna de sus series ya emitio, la sucursal no se borra: esos
        // comprobantes la referencian y estan declarados ante SUNAT.
        $yaEmitio = Correlative::where('branch_id', $branch->id)
            ->where('correlativo_actual', '>', 0)
            ->exists();

        if ($yaEmitio) {
            return back()->with('error', 'Esta sucursal ya emitió comprobantes, así que no se puede eliminar. Desactívala si ya no la usas.');
        }

        if (Branch::where('company_id', $branch->company_id)->count() <= 1) {
            return back()->with('error', 'No puedes eliminar tu única sucursal.');
        }

        DB::transaction(function () use ($branch) {
            Correlative::where('branch_id', $branch->id)->delete();
            $branch->delete();
        });

        return back()->with('success', 'Sucursal eliminada.');
    }

    private function soloDeMiEmpresa(Branch $branch): void
    {
        abort_unless($branch->company_id === Auth::user()->company_id, 403, 'Esa sucursal no pertenece a tu empresa.');
    }
}
