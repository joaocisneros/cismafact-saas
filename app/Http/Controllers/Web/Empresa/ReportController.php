<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\ApiUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $ventasDiarias = $this->getVentasDiarias($companyId);
        $ventasMensuales = $this->getVentasMensuales($companyId);
        $docsPorTipo = $this->getDocsPorTipo($companyId);
        $consumoApi = $this->getConsumoApi($companyId);

        $totalVentasMes = collect($ventasMensuales)->last()['value'] ?? 0;
        $totalDocsMes = array_sum(array_column($docsPorTipo, 'value'));
        $igvGenerado = $totalVentasMes * 0.18;
        $ticketPromedio = $totalDocsMes > 0 ? $totalVentasMes / $totalDocsMes : 0;

        $data = [
            'ventasDiarias' => $ventasDiarias,
            'ventasMensuales' => $ventasMensuales,
            'docsPorTipo' => $docsPorTipo,
            'consumoApi' => $consumoApi,
            'igvGenerado' => $igvGenerado,
            'ticketPromedio' => $ticketPromedio,
            'totalVentasMes' => $totalVentasMes,
        ];

        return view('empresa.reports.index', $data);
    }

    public function weekly(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $start = now()->subWeeks(3)->startOfWeek();
        $end = now()->endOfWeek();

        $invoiceData = Invoice::where('company_id', $companyId)
            ->whereBetween('fecha_emision', [$start, $end])
            ->whereNull('anulado_en')
            ->selectRaw("YEARWEEK(fecha_emision, 1) as semana, MIN(fecha_emision) as inicio, MAX(fecha_emision) as fin, SUM(mto_imp_venta) as total")
            ->groupBy('semana')
            ->get();

        $boletaData = Boleta::where('company_id', $companyId)
            ->whereBetween('fecha_emision', [$start, $end])
            ->whereNull('anulado_en')
            ->selectRaw("YEARWEEK(fecha_emision, 1) as semana, SUM(mto_imp_venta) as total")
            ->groupBy('semana')
            ->get()
            ->keyBy('semana');

        $weeks = collect();
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $semana = $weekStart->format('Y') . str_replace('-', '', $weekStart->format('W'));

            $inv = $invoiceData->first(fn($r) => $r->semana == $semana);
            $bol = $boletaData[$semana] ?? null;

            $weeks->push([
                'label' => $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m'),
                'value' => (float)($inv->total ?? 0) + (float)($bol->total ?? 0),
            ]);
        }

        return view('empresa.reports.weekly', ['data' => $weeks]);
    }

    public function yearly(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $start = now()->subMonths(11)->startOfMonth();
        $end = now()->endOfMonth();

        $invoiceData = Invoice::where('company_id', $companyId)
            ->whereBetween('fecha_emision', [$start, $end])
            ->whereNull('anulado_en')
            ->selectRaw("YEAR(fecha_emision) as anio, MONTH(fecha_emision) as mes, SUM(mto_imp_venta) as total")
            ->groupBy('anio', 'mes')
            ->get()
            ->mapWithKeys(fn($r) => [$r->anio . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT) => $r]);

        $boletaData = Boleta::where('company_id', $companyId)
            ->whereBetween('fecha_emision', [$start, $end])
            ->whereNull('anulado_en')
            ->selectRaw("YEAR(fecha_emision) as anio, MONTH(fecha_emision) as mes, SUM(mto_imp_venta) as total")
            ->groupBy('anio', 'mes')
            ->get()
            ->mapWithKeys(fn($r) => [$r->anio . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT) => $r]);

        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $inv = $invoiceData[$key] ?? null;
            $bol = $boletaData[$key] ?? null;
            $months->push([
                'label' => $date->format('M Y'),
                'value' => (float)($inv->total ?? 0) + (float)($bol->total ?? 0),
            ]);
        }

        return view('empresa.reports.yearly', ['data' => $months]);
    }

    public function export(Request $request, string $type)
    {
        $companyId = Auth::user()->company_id;
        $format = $request->input('format', 'csv');

        $data = Invoice::where('company_id', $companyId)
            ->latest('fecha_emision')
            ->take(500)
            ->get()
            ->map(fn($doc) => [
                'Tipo' => 'Factura',
                'Serie' => $doc->serie,
                'Número' => $doc->numero_completo,
                'Fecha' => $doc->fecha_emision?->format('d/m/Y'),
                'Total' => $doc->mto_imp_venta,
                'Estado' => $doc->estado_sunat,
            ]);

        $boletas = Boleta::where('company_id', $companyId)
            ->latest('fecha_emision')
            ->take(500)
            ->get()
            ->map(fn($doc) => [
                'Tipo' => 'Boleta',
                'Serie' => $doc->serie,
                'Número' => $doc->numero_completo,
                'Fecha' => $doc->fecha_emision?->format('d/m/Y'),
                'Total' => $doc->mto_imp_venta,
                'Estado' => $doc->estado_sunat,
            ]);

        $allData = $data->concat($boletas)->sortByDesc('Fecha')->values();

        $headers = ['Content-Type' => 'text/csv'];

        $callback = function () use ($allData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tipo', 'Serie', 'Número', 'Fecha', 'Total', 'Estado']);
            foreach ($allData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers)
            ->header('Content-Disposition', 'attachment; filename="reporte_' . $type . '_' . now()->format('Ymd') . '.csv"');
    }

    private function getVentasDiarias(int $companyId): array
    {
        $cacheKey = "report_ventas_diarias_{$companyId}";
        return Cache::remember($cacheKey, 60, function () use ($companyId) {
            $start = now()->subDays(6)->startOfDay();
            $end = now()->endOfDay();

            $invoiceData = Invoice::where('company_id', $companyId)
                ->whereBetween('fecha_emision', [$start, $end])
                ->whereNull('anulado_en')
                ->selectRaw("DATE(fecha_emision) as fecha, SUM(mto_imp_venta) as total")
                ->groupBy('fecha')
                ->pluck('total', 'fecha');

            $boletaData = Boleta::where('company_id', $companyId)
                ->whereBetween('fecha_emision', [$start, $end])
                ->whereNull('anulado_en')
                ->selectRaw("DATE(fecha_emision) as fecha, SUM(mto_imp_venta) as total")
                ->groupBy('fecha')
                ->pluck('total', 'fecha');

            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $key = $date->format('Y-m-d');
                $data[] = [
                    'label' => $date->format('d/m'),
                    'value' => (float)($invoiceData[$key] ?? 0) + (float)($boletaData[$key] ?? 0),
                ];
            }
            return $data;
        });
    }

    private function getVentasMensuales(int $companyId): array
    {
        $cacheKey = "report_ventas_mensuales_{$companyId}";
        return Cache::remember($cacheKey, 120, function () use ($companyId) {
            $start = now()->subMonths(5)->startOfMonth();
            $end = now()->endOfMonth();

            $invoiceData = Invoice::where('company_id', $companyId)
                ->whereBetween('fecha_emision', [$start, $end])
                ->whereNull('anulado_en')
                ->selectRaw("YEAR(fecha_emision) as anio, MONTH(fecha_emision) as mes, SUM(mto_imp_venta) as total")
                ->groupBy('anio', 'mes')
                ->get()
                ->mapWithKeys(fn($r) => [$r->anio . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT) => $r]);

            $boletaData = Boleta::where('company_id', $companyId)
                ->whereBetween('fecha_emision', [$start, $end])
                ->whereNull('anulado_en')
                ->selectRaw("YEAR(fecha_emision) as anio, MONTH(fecha_emision) as mes, SUM(mto_imp_venta) as total")
                ->groupBy('anio', 'mes')
                ->get()
                ->mapWithKeys(fn($r) => [$r->anio . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT) => $r]);

            $data = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $key = $date->format('Y-m');
                $inv = $invoiceData[$key] ?? null;
                $bol = $boletaData[$key] ?? null;
                $data[] = [
                    'label' => $date->format('M Y'),
                    'value' => (float)($inv->total ?? 0) + (float)($bol->total ?? 0),
                ];
            }
            return $data;
        });
    }

    private function getDocsPorTipo(int $companyId): array
    {
        $cacheKey = "report_docs_tipo_{$companyId}";
        return Cache::remember($cacheKey, 60, function () use ($companyId) {
            // Se cuentan las 4 tablas en una sola consulta (UNION ALL) en lugar de
            // 4 viajes separados a la base de datos. Las etiquetas son constantes
            // (sin entrada del usuario), por eso es seguro incrustarlas en el SQL.
            $tablas = [
                'Facturas' => (new Invoice)->getTable(),
                'Boletas' => (new Boleta)->getTable(),
                'Notas Crédito' => (new CreditNote)->getTable(),
                'Notas Débito' => (new DebitNote)->getTable(),
            ];

            $union = null;
            foreach ($tablas as $label => $tabla) {
                $consulta = DB::table($tabla)
                    ->where('company_id', $companyId)
                    ->selectRaw("'{$label}' as label, COUNT(*) as value");
                $union = $union ? $union->unionAll($consulta) : $consulta;
            }

            return $union->get()
                ->map(fn ($fila) => ['label' => $fila->label, 'value' => (int) $fila->value])
                ->all();
        });
    }

    private function getConsumoApi(int $companyId): array
    {
        $cacheKey = "report_api_consumo_{$companyId}";
        return Cache::remember($cacheKey, 60, function () use ($companyId) {
            $start = now()->subDays(6)->startOfDay();
            $end = now()->endOfDay();

            $data = ApiUsage::where('company_id', $companyId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("DATE(created_at) as fecha, COUNT(*) as total")
                ->groupBy('fecha')
                ->pluck('total', 'fecha');

            $result = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $result[] = [
                    'label' => $date->format('d/m'),
                    'value' => (int)($data[$date->format('Y-m-d')] ?? 0),
                ];
            }
            return $result;
        });
    }
}
