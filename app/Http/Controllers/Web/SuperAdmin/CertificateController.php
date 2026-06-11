<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $hoy = now()->toDateString();
        $limite = now()->addDays(30)->toDateString();

        $companies = Company::query()
            ->select(['id', 'ruc', 'razon_social', 'cert_titular', 'cert_ruc', 'cert_valido_desde', 'cert_valido_hasta', 'activo'])
            ->orderByRaw('cert_valido_hasta IS NULL, cert_valido_hasta ASC')
            ->simplePaginate(20);

        $stats = [
            'vigentes' => Company::whereDate('cert_valido_hasta', '>', $limite)->count(),
            'por_vencer' => Company::whereDate('cert_valido_hasta', '>=', $hoy)
                ->whereDate('cert_valido_hasta', '<=', $limite)->count(),
            'vencidos' => Company::whereDate('cert_valido_hasta', '<', $hoy)->count(),
            'sin_certificado' => Company::whereNull('cert_valido_hasta')->count(),
        ];

        return view('super-admin.certificates.index', compact('companies', 'stats'));
    }
}
