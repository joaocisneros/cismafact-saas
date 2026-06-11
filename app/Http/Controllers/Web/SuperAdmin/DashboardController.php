<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Boleta;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('super_admin_dashboard_minimal_stats', now()->addMinute(), function () {
            return [
                'activeCompanies' => Company::where('activo', true)->count(),
                'registeredUsers' => User::count(),
                'issuedDocuments' => Invoice::count() + Boleta::count() + CreditNote::count() + DebitNote::count() + DispatchGuide::count(),
                'systemStatus' => $this->databaseIsConnected() ? 'Operativo' : 'Revisar base de datos',
            ];
        });

        return view('super-admin.dashboard', [
            'stats' => $stats,
            'latestCompanies' => Company::query()
                ->select(['id', 'ruc', 'razon_social', 'activo', 'created_at'])
                ->latest('created_at')
                ->limit(5)
                ->get(),
            'latestDocuments' => $this->latestDocuments(),
            'alerts' => $this->importantAlerts(),
        ]);
    }

    private function latestDocuments()
    {
        $documents = collect([
            $this->documentQuery('invoices', 'Factura'),
            $this->documentQuery('boletas', 'Boleta'),
            $this->documentQuery('credit_notes', 'Nota de credito'),
            $this->documentQuery('debit_notes', 'Nota de debito'),
            $this->documentQuery('dispatch_guides', 'Guia de remision'),
        ])->reduce(fn($query, $next) => $query ? $query->unionAll($next) : $next);

        return DB::query()
            ->fromSub($documents, 'documents')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    private function documentQuery(string $table, string $type)
    {
        return DB::table($table)
            ->leftJoin('companies', "{$table}.company_id", '=', 'companies.id')
            ->selectRaw('? as tipo', [$type])
            ->addSelect([
                "{$table}.id",
                "{$table}.numero_completo",
                "{$table}.estado_sunat",
                "{$table}.created_at",
                'companies.razon_social as empresa',
            ]);
    }

    private function databaseIsConnected(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function importantAlerts()
    {
        return Cache::remember('super_admin_dashboard_alerts', now()->addMinute(), function () {
            $alerts = collect();

            $companiesWithoutPlan = Company::whereNull('plan_id')->count();
            if ($companiesWithoutPlan > 0) {
                $alerts->push([
                    'type' => 'warning',
                    'title' => 'Empresas sin plan',
                    'message' => "{$companiesWithoutPlan} empresa(s) no tienen plan asignado.",
                    'route' => route('super-admin.plans'),
                ]);
            }

            // Se calculan todos los estados de una sola pasada (5 queries en total)
            // en lugar de repetir 5 COUNT por cada estado consultado.
            $statusCounts = $this->documentStatusCounts();

            $rejectedDocuments = $statusCounts['RECHAZADO'] ?? 0;
            if ($rejectedDocuments > 0) {
                $alerts->push([
                    'type' => 'danger',
                    'title' => 'Documentos rechazados',
                    'message' => "{$rejectedDocuments} documento(s) requieren revision.",
                    'route' => route('super-admin.documents', ['status' => 'RECHAZADO']),
                ]);
            }

            $pendingDocuments = $statusCounts['PENDIENTE'] ?? 0;
            if ($pendingDocuments > 0) {
                $alerts->push([
                    'type' => 'info',
                    'title' => 'Documentos pendientes',
                    'message' => "{$pendingDocuments} documento(s) pendientes de respuesta SUNAT.",
                    'route' => route('super-admin.documents', ['status' => 'PENDIENTE']),
                ]);
            }

            $certsVencidos = Company::whereDate('cert_valido_hasta', '<', now())->count();
            if ($certsVencidos > 0) {
                $alerts->push([
                    'type' => 'danger',
                    'title' => 'Certificados vencidos',
                    'message' => "{$certsVencidos} empresa(s) con certificado vencido. No pueden emitir.",
                    'route' => route('super-admin.certificates'),
                ]);
            }

            $certsPorVencer = Company::whereDate('cert_valido_hasta', '>=', now())
                ->whereDate('cert_valido_hasta', '<=', now()->addDays(30))
                ->count();
            if ($certsPorVencer > 0) {
                $alerts->push([
                    'type' => 'warning',
                    'title' => 'Certificados por vencer',
                    'message' => "{$certsPorVencer} certificado(s) vencen en 30 días o menos.",
                    'route' => route('super-admin.certificates'),
                ]);
            }

            $openTickets = Ticket::whereIn('status', ['open', 'in_progress'])->count();
            if ($openTickets > 0) {
                $alerts->push([
                    'type' => 'warning',
                    'title' => 'Tickets abiertos',
                    'message' => "{$openTickets} ticket(s) necesitan atencion.",
                    'route' => route('super-admin.support'),
                ]);
            }

            if ($alerts->isEmpty()) {
                $alerts->push([
                    'type' => 'success',
                    'title' => 'Sin alertas importantes',
                    'message' => 'La plataforma no tiene incidencias pendientes.',
                    'route' => null,
                ]);
            }

            return $alerts->take(4);
        });
    }

    /**
     * Devuelve el total de documentos agrupado por estado_sunat sumando
     * las 5 tablas. Una query por tabla (5 en total) en lugar de 5 COUNT
     * por cada estado consultado.
     *
     * @return array<string,int>
     */
    private function documentStatusCounts(): array
    {
        $tables = ['invoices', 'boletas', 'credit_notes', 'debit_notes', 'dispatch_guides'];
        $totals = [];

        foreach ($tables as $table) {
            $rows = DB::table($table)
                ->select('estado_sunat', DB::raw('COUNT(*) as total'))
                ->groupBy('estado_sunat')
                ->pluck('total', 'estado_sunat');

            foreach ($rows as $status => $count) {
                $totals[$status] = ($totals[$status] ?? 0) + (int) $count;
            }
        }

        return $totals;
    }
}
