<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\ApiUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StatisticController extends Controller
{
    public function index()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfYear = $now->copy()->startOfYear();
        $endOfYear = $now->copy()->endOfYear();
        $today = now()->toDateString();

        $data = Cache::remember('super_admin_stats', 60, function () use ($startOfMonth, $endOfMonth, $startOfYear, $endOfYear, $today) {
            $companyStats = Company::selectRaw("SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activas, SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivas")->first();

            $invoiceStats = Invoice::selectRaw("COUNT(*) as total, SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as mes, SUM(CASE WHEN created_at BETWEEN ? AND ? AND anulado_en IS NULL THEN mto_imp_venta ELSE 0 END) as ventas_mes, SUM(CASE WHEN created_at BETWEEN ? AND ? AND anulado_en IS NULL THEN mto_imp_venta ELSE 0 END) as ventas_anio")
                ->addBinding([$startOfMonth, $endOfMonth, $startOfYear, $endOfYear, $startOfYear, $endOfYear], 'select')
                ->first();

            $boletaStats = Boleta::selectRaw("COUNT(*) as total, SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as mes, SUM(CASE WHEN created_at BETWEEN ? AND ? AND anulado_en IS NULL THEN mto_imp_venta ELSE 0 END) as ventas_mes, SUM(CASE WHEN created_at BETWEEN ? AND ? AND anulado_en IS NULL THEN mto_imp_venta ELSE 0 END) as ventas_anio")
                ->addBinding([$startOfMonth, $endOfMonth, $startOfYear, $endOfYear, $startOfYear, $endOfYear], 'select')
                ->first();

            $apiToday = ApiUsage::whereDate('created_at', $today)->count();
            $apiMonth = ApiUsage::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $apiYear = ApiUsage::whereBetween('created_at', [$startOfYear, $endOfYear])->count();

            return [
                'empresasActivas' => (int)($companyStats->activas ?? 0),
                'empresasInactivas' => (int)($companyStats->inactivas ?? 0),
                'totalFacturas' => (int)$invoiceStats->total,
                'totalBoletas' => (int)$boletaStats->total,
                'facturasMes' => (int)$invoiceStats->mes,
                'boletasMes' => (int)$boletaStats->mes,
                'consumoApiHoy' => $apiToday,
                'consumoApiMes' => $apiMonth,
                'consumoApiAnio' => $apiYear,
                'ventasMes' => (float)$invoiceStats->ventas_mes + (float)$boletaStats->ventas_mes,
                'ventasAnio' => (float)$invoiceStats->ventas_anio + (float)$boletaStats->ventas_anio,
            ];
        });

        $data['actividadDiaria'] = $this->getActividadDiaria();
        $data['actividadMensual'] = $this->getActividadMensual();
        $data['actividadAnual'] = $this->getActividadAnual();
        $data['docsPorTipo'] = $this->getDocsPorTipo();
        $data['consumoApiDiario'] = $this->getConsumoApiDiario();
        $data['crecimientoEmpresas'] = $this->getCrecimientoEmpresas();

        return view('super-admin.statistics', $data);
    }

    private function getActividadDiaria()
    {
        return Cache::remember('stats_actividad_diaria', 60, function () {
            $start = now()->subDays(6)->startOfDay();
            $end = now()->endOfDay();

            $invoices = Invoice::whereBetween('created_at', [$start, $end])
                ->selectRaw("DATE(created_at) as fecha, COUNT(*) as total")
                ->groupBy('fecha')
                ->pluck('total', 'fecha');

            $boletas = Boleta::whereBetween('created_at', [$start, $end])
                ->selectRaw("DATE(created_at) as fecha, COUNT(*) as total")
                ->groupBy('fecha')
                ->pluck('total', 'fecha');

            $days = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $key = $date->format('Y-m-d');
                $days->push([
                    'fecha' => $date->format('d/m'),
                    'facturas' => (int)($invoices[$key] ?? 0),
                    'boletas' => (int)($boletas[$key] ?? 0),
                ]);
            }
            return $days;
        });
    }

    private function getActividadMensual()
    {
        return Cache::remember('stats_actividad_mensual', 120, function () {
            $start = now()->subMonths(5)->startOfMonth();
            $end = now()->endOfMonth();

            $invoices = Invoice::whereBetween('created_at', [$start, $end])
                ->selectRaw("YEAR(created_at) as anio, MONTH(created_at) as mes, COUNT(*) as total")
                ->groupBy('anio', 'mes')
                ->get()
                ->mapWithKeys(fn($r) => [$r->anio . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT) => $r->total]);

            $boletas = Boleta::whereBetween('created_at', [$start, $end])
                ->selectRaw("YEAR(created_at) as anio, MONTH(created_at) as mes, COUNT(*) as total")
                ->groupBy('anio', 'mes')
                ->get()
                ->mapWithKeys(fn($r) => [$r->anio . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT) => $r->total]);

            $months = collect();
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $key = $date->format('Y-m');
                $months->push([
                    'mes' => $date->format('M Y'),
                    'facturas' => (int)($invoices[$key] ?? 0),
                    'boletas' => (int)($boletas[$key] ?? 0),
                ]);
            }
            return $months;
        });
    }

    private function getActividadAnual()
    {
        return Cache::remember('stats_actividad_anual', 120, function () {
            $start = now()->subMonths(11)->startOfMonth();
            $end = now()->endOfMonth();

            $invoices = Invoice::whereBetween('created_at', [$start, $end])
                ->selectRaw("YEAR(created_at) as anio, MONTH(created_at) as mes, COUNT(*) as total, SUM(mto_imp_venta) as ventas")
                ->groupBy('anio', 'mes')
                ->get()
                ->mapWithKeys(fn($r) => [$r->anio . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT) => $r]);

            $boletas = Boleta::whereBetween('created_at', [$start, $end])
                ->selectRaw("YEAR(created_at) as anio, MONTH(created_at) as mes, COUNT(*) as total, SUM(mto_imp_venta) as ventas")
                ->groupBy('anio', 'mes')
                ->get()
                ->mapWithKeys(fn($r) => [$r->anio . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT) => $r]);

            $months = collect();
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $key = $date->format('Y-m');
                $inv = $invoices[$key] ?? null;
                $bol = $boletas[$key] ?? null;
                $months->push([
                    'mes' => $date->format('M Y'),
                    'facturas' => (int)($inv->total ?? 0),
                    'boletas' => (int)($bol->total ?? 0),
                    'ventas' => (float)($inv->ventas ?? 0) + (float)($bol->ventas ?? 0),
                ]);
            }
            return $months;
        });
    }

    private function getDocsPorTipo()
    {
        return Cache::remember('stats_docs_por_tipo', 60, function () {
            return [
                'Facturas' => Invoice::count(),
                'Boletas' => Boleta::count(),
                'Notas Crédito' => CreditNote::count(),
                'Notas Débito' => DebitNote::count(),
            ];
        });
    }

    private function getConsumoApiDiario()
    {
        return Cache::remember('stats_api_diario', 60, function () {
            $start = now()->subDays(6)->startOfDay();
            $end = now()->endOfDay();

            $data = ApiUsage::whereBetween('created_at', [$start, $end])
                ->selectRaw("DATE(created_at) as fecha, COUNT(*) as total")
                ->groupBy('fecha')
                ->pluck('total', 'fecha');

            $days = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $days->push([
                    'fecha' => $date->format('d/m'),
                    'requests' => (int)($data[$date->format('Y-m-d')] ?? 0),
                ]);
            }
            return $days;
        });
    }

    private function getCrecimientoEmpresas()
    {
        return Cache::remember('stats_crecimiento_empresas', 120, function () {
            $start = now()->subMonths(5)->startOfMonth();
            $end = now()->endOfMonth();

            $data = Company::whereBetween('created_at', [$start, $end])
                ->selectRaw("YEAR(created_at) as anio, MONTH(created_at) as mes, COUNT(*) as total, SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activas")
                ->groupBy('anio', 'mes')
                ->get()
                ->mapWithKeys(fn($r) => [$r->anio . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT) => $r]);

            $months = collect();
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $key = $date->format('Y-m');
                $months->push([
                    'mes' => $date->format('M Y'),
                    'total' => (int)($data[$key]->total ?? 0),
                    'activas' => (int)($data[$key]->activas ?? 0),
                ]);
            }
            return $months;
        });
    }
}
