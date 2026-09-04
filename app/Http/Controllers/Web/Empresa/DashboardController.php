<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Support\CertificadoDigital;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\ApiUsage;
use App\Models\ApiKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $company = $user->company;

        $todayStart = today();
        $tomorrowStart = $todayStart->copy()->addDay();
        $monthStart = now()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();

        $invoiceStats = $this->getDocumentStats(Invoice::class, $companyId, $todayStart, $tomorrowStart, $monthStart, $nextMonthStart);
        $boletaStats = $this->getDocumentStats(Boleta::class, $companyId, $todayStart, $tomorrowStart, $monthStart, $nextMonthStart);
        $apiStats = $this->getApiUsageStats($companyId, $todayStart, $tomorrowStart, $monthStart, $nextMonthStart);

        $data = [
            'facturasHoy' => (int) $invoiceStats->today_count,
            'boletasHoy' => (int) $boletaStats->today_count,
            'docsHoy' => (int) $invoiceStats->today_count + (int) $boletaStats->today_count,
            'docsMes' => (int) $invoiceStats->month_count + (int) $boletaStats->month_count,
            'totalFacturas' => (int) $invoiceStats->total_count,
            'totalBoletas' => (int) $boletaStats->total_count,
            'facturasPendientes' => (int) $invoiceStats->pending_count,
            'boletasPendientes' => (int) $boletaStats->pending_count,
            'consumoApi' => (int) $apiStats->total_count,
            'consumoApiHoy' => (int) $apiStats->today_count,
            'consumoApiMes' => (int) $apiStats->month_count,
            'apiKeysActivas' => ApiKey::where('company_id', $companyId)->active()->count(),
            'empresaActiva' => $company->activo ?? false,
            'sunatConfigurado' => !empty($company->usuario_sol),
            'certificadoExiste' => !empty($company->certificado_pem),
            'certificadoExpira' => null,
            'certificadoVencido' => false,
            'ultimasFacturas' => $this->getLatestDocuments(Invoice::class, $companyId),
            'ultimasBoletas' => $this->getLatestDocuments(Boleta::class, $companyId),
            'ventasHoy' => (float) $invoiceStats->today_sales + (float) $boletaStats->today_sales,
            'ventasMes' => (float) $invoiceStats->month_sales + (float) $boletaStats->month_sales,
        ];

        if ($company && $company->certificado_pem) {
            $certPath = storage_path('app/' . $company->certificado_pem);
            if (file_exists($certPath)) {
                $certContent = file_get_contents($certPath);
                // Por el lector propio: el certificado gratuito de SUNAT usa
                // cifrado antiguo y openssl_pkcs12_read() solo no lo abre, asi
                // que la tarjeta de vencimiento salia siempre vacia.
                try {
                    $certs = CertificadoDigital::leer($certContent, (string) ($company->certificado_password ?? ''));
                } catch (\RuntimeException $e) {
                    $certs = [];
                }

                if (! empty($certs['cert'])) {
                    $certData = openssl_x509_parse($certs['cert']);
                    if ($certData && isset($certData['validTo_time_t'])) {
                        $data['certificadoExpira'] = \Carbon\Carbon::createFromTimestamp($certData['validTo_time_t']);
                        $data['certificadoVencido'] = $data['certificadoExpira']->isPast();
                    }
                }
            }
        }

        return view('empresa.dashboard', $data);
    }

    private function getDocumentStats(string $model, int $companyId, Carbon $todayStart, Carbon $tomorrowStart, Carbon $monthStart, Carbon $nextMonthStart): object
    {
        /*
         * Por fecha de emision y no por la de grabado.
         *
         * Un comprobante se puede emitir con fecha anterior al dia en que se
         * registra, y lo que cuenta —para SUNAT, para el contador y para la
         * pantalla de Reportes— es la del comprobante. Con created_at, el
         * Dashboard y Reportes daban numeros distintos del mismo mes y no
         * habia forma de saber cual creer.
         */
        return $model::where('company_id', $companyId)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN fecha_emision >= ? AND fecha_emision < ? THEN 1 ELSE 0 END) as today_count', [$todayStart, $tomorrowStart])
            ->selectRaw('SUM(CASE WHEN fecha_emision >= ? AND fecha_emision < ? THEN 1 ELSE 0 END) as month_count', [$monthStart, $nextMonthStart])
            ->selectRaw('SUM(CASE WHEN estado_sunat = ? THEN 1 ELSE 0 END) as pending_count', ['PENDIENTE'])
            // Un comprobante dado de baja ya no es una venta: se cuenta como
            // emitido, pero su importe no puede seguir sumando.
            ->selectRaw('COALESCE(SUM(CASE WHEN fecha_emision >= ? AND fecha_emision < ? AND anulado_en IS NULL THEN mto_imp_venta ELSE 0 END), 0) as today_sales', [$todayStart, $tomorrowStart])
            ->selectRaw('COALESCE(SUM(CASE WHEN fecha_emision >= ? AND fecha_emision < ? AND anulado_en IS NULL THEN mto_imp_venta ELSE 0 END), 0) as month_sales', [$monthStart, $nextMonthStart])
            ->first();
    }

    private function getApiUsageStats(int $companyId, Carbon $todayStart, Carbon $tomorrowStart, Carbon $monthStart, Carbon $nextMonthStart): object
    {
        return ApiUsage::where('company_id', $companyId)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as today_count', [$todayStart, $tomorrowStart])
            ->selectRaw('SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as month_count', [$monthStart, $nextMonthStart])
            ->first();
    }

    private function getLatestDocuments(string $model, int $companyId)
    {
        return $model::where('company_id', $companyId)
            ->select(['id', 'serie', 'numero_completo', 'fecha_emision', 'mto_imp_venta', 'estado_sunat', 'created_at'])
            ->latest()
            ->take(5)
            ->get();
    }
}
