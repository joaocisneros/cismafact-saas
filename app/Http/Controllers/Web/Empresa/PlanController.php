<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\ApiUsage;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\DispatchGuide;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    public function index()
    {
        $company = Auth::user()->company;
        $company->loadMissing('subscription.plan');

        $subscription = $company->subscription;
        $plan = $subscription?->plan;

        $inicioMes = now()->startOfMonth();

        // Documentos emitidos este mes (todos los tipos).
        $docsUsados = collect([Invoice::class, Boleta::class, CreditNote::class, DebitNote::class, DispatchGuide::class])
            ->sum(fn ($model) => $model::where('company_id', $company->id)
                ->where('created_at', '>=', $inicioMes)
                ->count());

        $apiUsado = ApiUsage::where('company_id', $company->id)
            ->where('created_at', '>=', $inicioMes)
            ->count();

        $usuariosUsados = User::where('company_id', $company->id)->count();

        $uso = [
            'documentos' => $this->metric($docsUsados, $plan?->monthly_document_limit),
            'api' => $this->metric($apiUsado, $plan?->api_request_limit),
            'usuarios' => $this->metric($usuariosUsados, $plan?->user_limit),
        ];

        return view('empresa.plan.index', compact('company', 'subscription', 'plan', 'uso'));
    }

    /**
     * Calcula uso/límite/porcentaje. Límite null = ilimitado.
     */
    private function metric(int $usado, ?int $limite): array
    {
        $ilimitado = empty($limite);
        $porcentaje = $ilimitado ? 0 : min(100, (int) round($usado / max(1, $limite) * 100));

        return [
            'usado' => $usado,
            'limite' => $limite,
            'ilimitado' => $ilimitado,
            'disponible' => $ilimitado ? null : max(0, $limite - $usado),
            'porcentaje' => $porcentaje,
        ];
    }
}
