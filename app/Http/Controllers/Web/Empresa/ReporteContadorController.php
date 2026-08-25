<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Services\AccountingReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporte de ventas del periodo para entregar al contador.
 */
class ReporteContadorController extends Controller
{
    public function __construct(private AccountingReportService $reporte)
    {
    }

    public function index(Request $request)
    {
        $company = Auth::user()->company;
        [$desde, $hasta, $periodo] = $this->periodo($request);

        $soloAceptados = ! $request->boolean('incluir_no_aceptados');
        $totales = $this->reporte->totales($company->id, $desde, $hasta, $soloAceptados);

        return view('empresa.reportes.contador', [
            'company' => $company,
            'periodo' => $periodo,
            'desde' => $desde,
            'hasta' => $hasta,
            'totales' => $totales,
            'soloAceptados' => $soloAceptados,
            'meses' => $this->mesesDisponibles(),
        ]);
    }

    public function descargar(Request $request): StreamedResponse
    {
        $company = Auth::user()->company;
        [$desde, $hasta, $periodo] = $this->periodo($request);

        $soloAceptados = ! $request->boolean('incluir_no_aceptados');
        $filas = $this->reporte->filas($company->id, $desde, $hasta, $soloAceptados);

        $nombre = 'ventas_' . $company->ruc . '_' . $periodo . '.csv';

        return response()->streamDownload(function () use ($filas) {
            $salida = fopen('php://output', 'w');

            // BOM para que Excel abra bien las tildes y la ñ.
            fwrite($salida, "\xEF\xBB\xBF");

            // Punto y coma: es lo que espera Excel con configuracion regional
            // de Peru; con coma mete todo en una sola columna.
            fputcsv($salida, AccountingReportService::CABECERAS, ';');

            foreach ($filas as $fila) {
                fputcsv($salida, $fila, ';');
            }

            fclose($salida);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** Periodo pedido (YYYY-MM); por defecto, el mes en curso. */
    private function periodo(Request $request): array
    {
        $pedido = (string) $request->input('periodo', now()->format('Y-m'));

        try {
            $mes = Carbon::createFromFormat('Y-m', $pedido)->startOfMonth();
        } catch (\Throwable) {
            $mes = now()->startOfMonth();
        }

        return [
            $mes->toDateString(),
            $mes->copy()->endOfMonth()->toDateString(),
            $mes->format('Y-m'),
        ];
    }

    /** Los ultimos 24 meses, para el desplegable. */
    private function mesesDisponibles(): array
    {
        $meses = [];
        $cursor = now()->startOfMonth();

        for ($i = 0; $i < 24; $i++) {
            $meses[$cursor->format('Y-m')] = ucfirst($cursor->translatedFormat('F Y'));
            $cursor->subMonthNoOverflow();
        }

        return $meses;
    }
}
