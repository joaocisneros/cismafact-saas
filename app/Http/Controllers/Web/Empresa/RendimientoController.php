<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Boleta;
use App\Models\CommissionConfig;
use App\Models\CommissionResult;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Models\User;
use App\Services\CommissionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RendimientoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $companyId = (int) Auth::user()->company_id;
        $desde = Carbon::parse($request->input('desde', now()->startOfMonth()->toDateString()))->startOfDay();
        $hasta = Carbon::parse($request->input('hasta', now()->toDateString()))->endOfDay();

        abort_if($desde->diffInDays($hasta) > 366, 422, 'El rango no puede superar un año.');

        $usuarios = User::withTrashed()
            ->where('company_id', $companyId)
            ->with('role:id,name,display_name')
            ->get(['id', 'name', 'email', 'role_id', 'active', 'deleted_at']);

        $porUsuario = $usuarios->map(function (User $usuario) use ($companyId, $desde, $hasta) {
            $facturas = $this->resumen(Invoice::class, $companyId, $usuario->id, $desde, $hasta);
            $boletas = $this->resumen(Boleta::class, $companyId, $usuario->id, $desde, $hasta);
            $notasCredito = $this->resumenActividad(CreditNote::class, $companyId, $usuario->id, $desde, $hasta);
            $notasDebito = $this->resumenActividad(DebitNote::class, $companyId, $usuario->id, $desde, $hasta);
            $guias = $this->resumenActividad(DispatchGuide::class, $companyId, $usuario->id, $desde, $hasta);
            $documentos = $facturas->documentos + $boletas->documentos
                + $notasCredito->documentos + $notasDebito->documentos + $guias->documentos;
            $documentosValidos = $facturas->validos + $boletas->validos;
            $ventas = max(0, $facturas->ventas + $boletas->ventas
                - $this->montoAceptado(CreditNote::class, $companyId, $usuario->id, $desde, $hasta)
                + $this->montoAceptado(DebitNote::class, $companyId, $usuario->id, $desde, $hasta));

            return (object) [
                'usuario' => $usuario,
                'facturas' => $facturas->documentos,
                'boletas' => $boletas->documentos,
                'notas_credito' => $notasCredito->documentos,
                'notas_debito' => $notasDebito->documentos,
                'guias' => $guias->documentos,
                'documentos' => $documentos,
                'documentos_validos' => $documentosValidos,
                'ventas' => $ventas,
                'ticket_promedio' => $documentosValidos ? $ventas / $documentosValidos : 0,
                'aceptados' => $facturas->aceptados + $boletas->aceptados + $notasCredito->aceptados + $notasDebito->aceptados + $guias->aceptados,
                'pendientes' => $facturas->pendientes + $boletas->pendientes + $notasCredito->pendientes + $notasDebito->pendientes + $guias->pendientes,
                'rechazados' => $facturas->rechazados + $boletas->rechazados + $notasCredito->rechazados + $notasDebito->rechazados + $guias->rechazados,
            ];
        })->sortByDesc('ventas')->values();

        $config = CommissionConfig::firstOrNew(['company_id' => $companyId], [
            'active' => false,
            'mode' => 'goal',
            'applies_to_all' => true,
        ]);
        $seleccionados = collect($config->selected_user_ids ?? [])->map(fn ($id) => (int) $id);
        $periodo = $desde->copy()->startOfMonth()->toDateString();
        $resultadosGuardados = CommissionResult::where('company_id', $companyId)
            ->whereDate('period', $periodo)->get()->keyBy('user_id');

        $comisiones = $porUsuario
            ->filter(fn ($fila) => $fila->usuario->role?->name === 'company_user' && ! $fila->usuario->trashed())
            ->filter(fn ($fila) => $config->applies_to_all || $seleccionados->contains($fila->usuario->id))
            ->map(function ($fila) use ($config, $resultadosGuardados) {
                $meta = (float) $config->monthly_goal;
                $calculo = app(CommissionCalculator::class)->calculate(
                    $config->mode,
                    (float) $fila->ventas,
                    $meta,
                    (float) $config->commission_percentage,
                    (float) $config->goal_bonus,
                );

                return (object) [
                    'usuario' => $fila->usuario,
                    'ventas' => $fila->ventas,
                    'avance' => $meta > 0 ? min(100, round($fila->ventas * 100 / $meta, 1)) : 0,
                    'meta_cumplida' => $calculo['goal_reached'],
                    'comision' => $calculo['commission'],
                    'bono' => $calculo['bonus'],
                    'total' => $calculo['total'],
                    'resultado' => $resultadosGuardados->get($fila->usuario->id),
                ];
            })->values();

        $historial = CommissionResult::where('company_id', $companyId)
            ->with('user:id,name')
            ->latest('period')->latest('id')->limit(20)->get();

        return view('empresa.rendimiento.index', [
            'filas' => $porUsuario,
            'desde' => $desde,
            'hasta' => $hasta,
            'totalVentas' => $porUsuario->sum('ventas'),
            'totalDocumentos' => $porUsuario->sum('documentos'),
            'lider' => $porUsuario->first(fn ($fila) => $fila->documentos > 0),
            'configComision' => $config,
            'comisiones' => $comisiones,
            'historialComisiones' => $historial,
            'empleados' => $usuarios->filter(fn ($usuario) => $usuario->role?->name === 'company_user' && ! $usuario->trashed()),
        ]);
    }

    public function guardarConfiguracion(Request $request)
    {
        $companyId = (int) Auth::user()->company_id;
        $datos = $request->validate([
            'active' => ['nullable', 'boolean'],
            'mode' => ['required', Rule::in(['goal', 'commission', 'both'])],
            'monthly_goal' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'goal_bonus' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'applies_to_all' => ['required', 'boolean'],
            'selected_user_ids' => ['nullable', 'array'],
            'selected_user_ids.*' => ['integer'],
        ]);

        $permitidos = User::where('company_id', $companyId)->whereHas('role', fn ($q) => $q->where('name', 'company_user'))
            ->pluck('id');
        $seleccionados = collect($datos['selected_user_ids'] ?? [])->map(fn ($id) => (int) $id)
            ->intersect($permitidos)->values()->all();

        CommissionConfig::updateOrCreate(['company_id' => $companyId], [
            'active' => $request->boolean('active'),
            'mode' => $datos['mode'],
            'monthly_goal' => $datos['monthly_goal'],
            'commission_percentage' => $datos['commission_percentage'],
            'goal_bonus' => $datos['goal_bonus'],
            'applies_to_all' => $request->boolean('applies_to_all'),
            'selected_user_ids' => $request->boolean('applies_to_all') ? null : $seleccionados,
        ]);

        return back()->with('success', 'Configuración de metas y comisiones guardada.');
    }

    public function actualizarComision(Request $request, User $usuario)
    {
        abort_unless($usuario->company_id === Auth::user()->company_id && $usuario->role?->name === 'company_user', 404);
        $datos = $request->validate([
            'period' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['approved', 'paid'])],
        ]);

        $inicio = Carbon::parse($datos['period'])->startOfMonth();
        $resultado = CommissionResult::firstOrNew([
            'company_id' => Auth::user()->company_id,
            'user_id' => $usuario->id,
            'period' => $inicio->toDateString(),
        ]);

        if ($datos['status'] === 'paid') {
            abort_unless($resultado->exists && $resultado->status === 'approved', 422,
                'Primero debes aprobar la comisión.');
            $resultado->forceFill(['status' => 'paid', 'paid_at' => now(), 'paid_by' => Auth::id()])->save();

            return back()->with('success', 'Comisión marcada como pagada.');
        }

        $config = CommissionConfig::where('company_id', Auth::user()->company_id)->where('active', true)->firstOrFail();
        abort_if(! $config->applies_to_all && ! in_array($usuario->id, $config->selected_user_ids ?? []), 422,
            'El usuario no participa en la configuración vigente.');

        $fin = $inicio->copy()->endOfMonth();
        $ventas = $this->ventasNetasUsuario((int) Auth::user()->company_id, $usuario->id, $inicio, $fin);
        $calculo = app(CommissionCalculator::class)->calculate(
            $config->mode,
            $ventas,
            (float) $config->monthly_goal,
            (float) $config->commission_percentage,
            (float) $config->goal_bonus,
        );

        $resultado->fill([
            'net_sales' => $ventas,
            'commission_amount' => $calculo['commission'],
            'bonus_amount' => $calculo['bonus'],
            'total_amount' => $calculo['total'],
        ]);

        abort_if($resultado->status === 'paid', 422, 'Una comisión pagada ya no puede recalcularse.');
        $resultado->forceFill(['status' => 'approved', 'approved_at' => now(), 'approved_by' => Auth::id()]);
        $resultado->save();

        return back()->with('success', 'Comisión aprobada.');
    }

    public function restablecerComisiones(Request $request)
    {
        $request->validate(['confirmacion' => ['required', 'in:RESTABLECER']]);
        $companyId = (int) Auth::user()->company_id;

        CommissionConfig::where('company_id', $companyId)->delete();

        return back()->with('success', 'Configuración eliminada. El historial de comisiones, los usuarios y los comprobantes se conservaron.');
    }

    public function exportarComisiones()
    {
        $companyId = (int) Auth::user()->company_id;
        $registros = CommissionResult::where('company_id', $companyId)
            ->with('user:id,name,email')
            ->orderByDesc('period')->orderBy('user_id')->get();

        return response()->streamDownload(function () use ($registros) {
            $archivo = fopen('php://output', 'w');
            fwrite($archivo, "\xEF\xBB\xBF");
            fputcsv($archivo, ['Periodo', 'Usuario', 'Correo', 'Venta valida', 'Comision', 'Bono', 'Total', 'Estado'], ';');

            foreach ($registros as $registro) {
                fputcsv($archivo, [
                    $registro->period->format('m/Y'),
                    $registro->user?->name ?? 'Usuario eliminado',
                    $registro->user?->email ?? '',
                    number_format((float) $registro->net_sales, 2, '.', ''),
                    number_format((float) $registro->commission_amount, 2, '.', ''),
                    number_format((float) $registro->bonus_amount, 2, '.', ''),
                    number_format((float) $registro->total_amount, 2, '.', ''),
                    $registro->status === 'paid' ? 'Pagada' : 'Aprobada',
                ], ';');
            }

            fclose($archivo);
        }, 'historial-comisiones-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function limpiarHistorial(Request $request)
    {
        $request->validate(['confirmacion' => ['required', 'in:LIMPIAR_HISTORIAL']]);

        CommissionResult::where('company_id', Auth::user()->company_id)->delete();

        return back()->with('success', 'Historial de comisiones eliminado. La configuración, los usuarios y los comprobantes se conservaron.');
    }

    private function resumen(string $modelo, int $companyId, int $userId, Carbon $desde, Carbon $hasta): object
    {
        return $modelo::where('company_id', $companyId)
            ->where('created_by_user_id', $userId)
            ->whereBetween('fecha_emision', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('COUNT(*) documentos')
            ->selectRaw("SUM(anulado_en IS NULL AND estado_sunat <> 'RECHAZADO') validos")
            ->selectRaw("COALESCE(SUM(CASE WHEN anulado_en IS NULL AND estado_sunat <> 'RECHAZADO' THEN mto_imp_venta ELSE 0 END), 0) ventas")
            ->selectRaw("SUM(estado_sunat = 'ACEPTADO') aceptados")
            ->selectRaw("SUM(estado_sunat = 'PENDIENTE') pendientes")
            ->selectRaw("SUM(estado_sunat = 'RECHAZADO') rechazados")
            ->first();
    }

    private function resumenActividad(string $modelo, int $companyId, int $userId, Carbon $desde, Carbon $hasta): object
    {
        return $modelo::where('company_id', $companyId)
            ->where('created_by_user_id', $userId)
            ->whereBetween('fecha_emision', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('COUNT(*) documentos')
            ->selectRaw("SUM(estado_sunat = 'ACEPTADO') aceptados")
            ->selectRaw("SUM(estado_sunat = 'PENDIENTE') pendientes")
            ->selectRaw("SUM(estado_sunat = 'RECHAZADO') rechazados")
            ->first();
    }

    private function montoAceptado(string $modelo, int $companyId, int $userId, Carbon $desde, Carbon $hasta): float
    {
        return (float) $modelo::where('company_id', $companyId)
            ->where('created_by_user_id', $userId)
            ->whereNull('anulado_en')
            ->where('estado_sunat', 'ACEPTADO')
            ->whereBetween('fecha_emision', [$desde->toDateString(), $hasta->toDateString()])
            ->sum('mto_imp_venta');
    }

    private function ventasNetasUsuario(int $companyId, int $userId, Carbon $desde, Carbon $hasta): float
    {
        $ventas = collect([Invoice::class, Boleta::class])->sum(function (string $modelo) use ($companyId, $userId, $desde, $hasta) {
            return (float) $modelo::where('company_id', $companyId)
                ->where('created_by_user_id', $userId)
                ->whereNull('anulado_en')
                ->where('estado_sunat', '<>', 'RECHAZADO')
                ->whereBetween('fecha_emision', [$desde->toDateString(), $hasta->toDateString()])
                ->sum('mto_imp_venta');
        });

        return max(0, $ventas
            - $this->montoAceptado(CreditNote::class, $companyId, $userId, $desde, $hasta)
            + $this->montoAceptado(DebitNote::class, $companyId, $userId, $desde, $hasta));
    }
}
