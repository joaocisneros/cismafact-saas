<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cifras de la empresa para el tablero de una aplicacion cliente.
 *
 * Los comprobantes viven en cinco tablas distintas (facturas, boletas, notas de
 * credito, notas de debito y guias), asi que aqui se unen en una sola consulta
 * y se resume. Sin esto, quien integra tenia que pedir cada listado y sumar por
 * su cuenta.
 *
 * Los comprobantes anulados no cuentan en ninguna cifra de venta: si se anulo,
 * no se vendio. Las guias no llevan importe, por eso van con total 0.
 */
class PanelController extends Controller
{
    /** Tabla => [clave, etiqueta, lleva importe]. */
    private const TABLAS = [
        'invoices'     => ['factura', 'Factura', true],
        'boletas'      => ['boleta', 'Boleta', true],
        'credit_notes' => ['nc', 'Nota de crédito', true],
        'debit_notes'  => ['nd', 'Nota de débito', true],
    ];

    /** Ventas de hoy, de la semana y del mes, con su variacion. */
    public function indicadores(Request $request): JsonResponse
    {
        $empresa = $this->empresaId($request);
        $hoy = Carbon::today();

        $periodo = function (Carbon $desde, ?Carbon $hasta = null) use ($empresa) {
            $fila = $this->comprobantes($empresa)
                ->whereDate('fecha_emision', '>=', $desde->toDateString())
                ->when($hasta, fn ($q) => $q->whereDate('fecha_emision', '<=', $hasta->toDateString()))
                ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total')
                ->first();

            return [
                'cantidad' => (int) ($fila->cantidad ?? 0),
                'total' => round((float) ($fila->total ?? 0), 2),
            ];
        };

        $mesActual = $periodo($hoy->copy()->startOfMonth());
        $mesAnterior = $periodo(
            $hoy->copy()->subMonthNoOverflow()->startOfMonth(),
            $hoy->copy()->subMonthNoOverflow()->endOfMonth()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'hoy' => $periodo($hoy),
                'semana' => $periodo($hoy->copy()->startOfWeek()),
                'mes' => $mesActual,
                'mes_anterior' => $mesAnterior,
                // Sin mes anterior no hay con que comparar: se devuelve null en
                // vez de un 100% que no significaria nada.
                'variacion_mensual' => $mesAnterior['total'] > 0
                    ? round((($mesActual['total'] - $mesAnterior['total']) / $mesAnterior['total']) * 100, 1)
                    : null,
                'moneda' => 'PEN',
            ],
        ]);
    }

    /** Los ultimos comprobantes emitidos, de cualquier tipo. */
    public function documentosRecientes(Request $request): JsonResponse
    {
        $request->validate(['limite' => ['nullable', 'integer', 'min:1', 'max:50']]);

        $filas = $this->comprobantes($this->empresaId($request), true)
            ->orderByDesc('created_at')
            ->limit((int) $request->input('limite', 10))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $filas->map(fn ($d) => [
                'id' => $d->id,
                'tipo' => $d->tipo,
                'tipo_nombre' => $d->tipo_nombre,
                'numero' => $d->numero_completo,
                'cliente' => $d->cliente ?: '—',
                'total' => round((float) $d->total, 2),
                'moneda' => $d->moneda ?: 'PEN',
                'estado_sunat' => $d->estado_sunat,
                'anulado' => (bool) $d->anulado_en,
                'fecha' => $d->fecha_emision,
            ]),
        ]);
    }

    /** Ventas de los ultimos doce meses, para dibujar la curva. */
    public function ventasMensuales(Request $request): JsonResponse
    {
        $desde = Carbon::today()->startOfMonth()->subMonths(11);

        $filas = $this->comprobantes($this->empresaId($request))
            ->whereDate('fecha_emision', '>=', $desde->toDateString())
            ->selectRaw("DATE_FORMAT(fecha_emision, '%Y-%m') as mes, COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $cantidades = $this->comprobantes($this->empresaId($request))
            ->whereDate('fecha_emision', '>=', $desde->toDateString())
            ->selectRaw("DATE_FORMAT(fecha_emision, '%Y-%m') as mes, COUNT(*) as cantidad")
            ->groupBy('mes')
            ->pluck('cantidad', 'mes');

        // Los meses sin ventas tambien salen, con cero: si no, la grafica se
        // comprime y parece que no hubo huecos.
        $serie = [];
        for ($i = 0; $i < 12; $i++) {
            $mes = $desde->copy()->addMonths($i);
            $clave = $mes->format('Y-m');
            $serie[] = [
                'mes' => $clave,
                'etiqueta' => ucfirst($mes->locale('es')->isoFormat('MMM YY')),
                'total' => round((float) ($filas[$clave] ?? 0), 2),
                'cantidad' => (int) ($cantidades[$clave] ?? 0),
            ];
        }

        return response()->json(['success' => true, 'data' => $serie]);
    }

    /** Cuantos comprobantes hay en cada estado frente a SUNAT. */
    public function estadoSunat(Request $request): JsonResponse
    {
        $filas = $this->comprobantes($this->empresaId($request), false, false)
            ->selectRaw('estado_sunat, COUNT(*) as cantidad')
            ->groupBy('estado_sunat')
            ->pluck('cantidad', 'estado_sunat');

        $conocidos = ['ACEPTADO', 'RECHAZADO', 'PENDIENTE', 'ANULADO', 'OBSERVADO'];
        $data = [];

        foreach ($conocidos as $estado) {
            $data[] = ['estado' => $estado, 'cantidad' => (int) ($filas[$estado] ?? 0)];
        }

        // Cualquier estado que no estuviera previsto tambien se muestra, en vez
        // de desaparecer de la suma.
        foreach ($filas as $estado => $cantidad) {
            if (! in_array($estado, $conocidos, true)) {
                $data[] = ['estado' => $estado ?: 'SIN ESTADO', 'cantidad' => (int) $cantidad];
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** Ventas separadas por moneda, para las empresas que facturan en dolares. */
    public function porMoneda(Request $request): JsonResponse
    {
        $filas = $this->comprobantes($this->empresaId($request))
            ->selectRaw('moneda, COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total')
            ->groupBy('moneda')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $filas->map(fn ($f) => [
                'moneda' => $f->moneda ?: 'PEN',
                'cantidad' => (int) $f->cantidad,
                'total' => round((float) $f->total, 2),
            ]),
        ]);
    }

    /**
     * Une las tablas de comprobantes de la empresa en una sola consulta.
     *
     * @param  bool  $conCliente   añade el nombre del cliente (cuesta un join más)
     * @param  bool  $soloVigentes descarta los anulados, que no son venta
     */
    private function comprobantes(int $empresaId, bool $conCliente = false, bool $soloVigentes = true)
    {
        $partes = [];

        foreach (self::TABLAS as $tabla => [$clave, $etiqueta, $llevaImporte]) {
            $q = DB::table($tabla)
                ->where("{$tabla}.company_id", $empresaId)
                ->selectRaw('? as tipo, ? as tipo_nombre', [$clave, $etiqueta])
                ->addSelect([
                    "{$tabla}.id",
                    "{$tabla}.numero_completo",
                    "{$tabla}.estado_sunat",
                    "{$tabla}.moneda",
                    "{$tabla}.fecha_emision",
                    "{$tabla}.anulado_en",
                    "{$tabla}.created_at",
                ])
                ->selectRaw($llevaImporte ? "{$tabla}.mto_imp_venta as total" : '0 as total');

            if ($conCliente) {
                $q->leftJoin('clients', 'clients.id', '=', "{$tabla}.client_id")
                  ->addSelect('clients.razon_social as cliente');
            }

            if ($soloVigentes) {
                $q->whereNull("{$tabla}.anulado_en");
            }

            $partes[] = $q;
        }

        $union = array_reduce(
            $partes,
            fn ($acumulado, $siguiente) => $acumulado ? $acumulado->unionAll($siguiente) : $siguiente
        );

        return DB::query()->fromSub($union, 'comprobantes');
    }

    private function empresaId(Request $request): int
    {
        return (int) $request->attributes->get('api_company')->id;
    }
}
