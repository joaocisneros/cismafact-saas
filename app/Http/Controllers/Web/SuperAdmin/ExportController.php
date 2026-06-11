<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\ApiUsage;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    public function exportStatistics($format)
    {
        if ($format !== 'csv') {
            return back()->with('error', 'Formato no soportado.');
        }

        return $this->exportCsv();
    }

    private function exportCsv()
    {
        $filename = 'estadisticas_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Separador de columnas: punto y coma. Excel en configuracion regional
            // en espanol (Peru) usa ";" como separador de lista; con "," mete todo
            // en una sola columna.
            //
            // IMPORTANTE: NO se usa la directiva "sep=;" porque Excel, al verla,
            // ignora el BOM UTF-8 y lee el archivo como ANSI, rompiendo los acentos
            // (Metrica -> MÃ©trica). Con solo el BOM y el separador ";", el Excel en
            // espanol separa las columnas y respeta los acentos.
            $sep = ';';

            // BOM UTF-8: para que Excel muestre bien los acentos (a, e, i, n, o...).
            fwrite($file, "\xEF\xBB\xBF");

            // Helper para escribir una fila con el separador correcto.
            $put = fn(array $row = []) => fputcsv($file, $row, $sep);

            // Encabezado del reporte
            $put(['REPORTE DE ESTADÍSTICAS - PLATAFORMA']);
            $put(['Generado el', now()->format('d/m/Y H:i')]);
            $put();

            // 1) Resumen general
            $put(['RESUMEN GENERAL']);
            $put(['Métrica', 'Valor']);
            foreach ($this->resumenGeneral() as $label => $value) {
                $put([$label, $value]);
            }
            $put();

            // 2) Documentos por tipo
            $put(['DOCUMENTOS POR TIPO']);
            $put(['Tipo', 'Cantidad']);
            foreach ($this->documentosPorTipo() as $tipo => $cantidad) {
                $put([$tipo, $cantidad]);
            }
            $put();

            // 3) Actividad mensual (ultimos 6 meses)
            $put(['ACTIVIDAD MENSUAL (últimos 6 meses)']);
            $put(['Mes', 'Facturas', 'Boletas', 'Total']);
            foreach ($this->actividadMensual() as $fila) {
                $put($fila);
            }
            $put();

            // 4) Crecimiento de empresas (ultimos 6 meses)
            $put(['CRECIMIENTO DE EMPRESAS (últimos 6 meses)']);
            $put(['Mes', 'Empresas Nuevas', 'Activas']);
            foreach ($this->crecimientoEmpresas() as $fila) {
                $put($fila);
            }
            $put();

            // 5) Detalle por empresa
            $put(['DETALLE POR EMPRESA']);
            $put(['RUC', 'Razón Social', 'Facturas', 'Boletas', 'Ventas Totales (S/)']);
            foreach ($this->detallePorEmpresa() as $fila) {
                $put($fila);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Resumen global de la plataforma con etiquetas en espanol.
     *
     * @return array<string, int|string>
     */
    private function resumenGeneral(): array
    {
        $ventasMes = Invoice::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('mto_imp_venta')
            + Boleta::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('mto_imp_venta');

        $ventasAnio = Invoice::whereYear('created_at', now()->year)->sum('mto_imp_venta')
            + Boleta::whereYear('created_at', now()->year)->sum('mto_imp_venta');

        return [
            'Empresas Activas' => Company::where('activo', true)->count(),
            'Empresas Inactivas' => Company::where('activo', false)->count(),
            'Total Facturas' => Invoice::count(),
            'Total Boletas' => Boleta::count(),
            'Notas de Crédito' => CreditNote::count(),
            'Notas de Débito' => DebitNote::count(),
            'Ventas del Mes' => number_format((float) $ventasMes, 2, ',', ''),
            'Ventas del Año' => number_format((float) $ventasAnio, 2, ',', ''),
            'Consumo API Hoy' => ApiUsage::whereDate('created_at', today())->count(),
            'Consumo API del Mes' => ApiUsage::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'Consumo API del Año' => ApiUsage::whereYear('created_at', now()->year)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function documentosPorTipo(): array
    {
        return [
            'Facturas' => Invoice::count(),
            'Boletas' => Boleta::count(),
            'Notas de Crédito' => CreditNote::count(),
            'Notas de Débito' => DebitNote::count(),
        ];
    }

    /**
     * Actividad mensual agregada en 2 consultas (sin bucle de queries).
     *
     * @return array<int, array{0:string,1:int,2:int,3:int}>
     */
    private function actividadMensual(): array
    {
        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();

        $facturas = Invoice::whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        $boletas = Boleta::whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        $filas = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $f = (int) ($facturas[$key] ?? 0);
            $b = (int) ($boletas[$key] ?? 0);
            $filas[] = [$date->format('m/Y'), $f, $b, $f + $b];
        }

        return $filas;
    }

    /**
     * @return array<int, array{0:string,1:int,2:int}>
     */
    private function crecimientoEmpresas(): array
    {
        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();

        $data = Company::whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total, SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activas")
            ->groupBy('ym')->get()->keyBy('ym');

        $filas = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $filas[] = [
                $date->format('m/Y'),
                (int) ($data[$key]->total ?? 0),
                (int) ($data[$key]->activas ?? 0),
            ];
        }

        return $filas;
    }

    /**
     * Desglose por empresa (facturas, boletas y ventas) en una sola consulta
     * con subconsultas agregadas (withCount / withSum), sin N+1.
     *
     * @return array<int, array{0:string,1:string,2:int,3:int,4:string}>
     */
    private function detallePorEmpresa(): array
    {
        $empresas = Company::query()
            ->withCount(['invoices', 'boletas'])
            ->withSum('invoices as ventas_facturas', 'mto_imp_venta')
            ->withSum('boletas as ventas_boletas', 'mto_imp_venta')
            ->orderBy('razon_social')
            ->get(['id', 'ruc', 'razon_social']);

        return $empresas->map(function ($empresa) {
            $ventas = (float) ($empresa->ventas_facturas ?? 0) + (float) ($empresa->ventas_boletas ?? 0);

            return [
                $empresa->ruc,
                $empresa->razon_social,
                (int) $empresa->invoices_count,
                (int) $empresa->boletas_count,
                number_format($ventas, 2, ',', ''),
            ];
        })->all();
    }
}
