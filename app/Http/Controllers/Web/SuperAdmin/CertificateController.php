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

        // modo_produccion y certificado_pem hacen falta para distinguir quien
        // firma con su certificado real y quien usa el de prueba de la
        // plataforma: sin eso la tabla enseñaba a todos igual.
        $companies = Company::query()
            ->select([
                'id', 'ruc', 'razon_social', 'cert_titular', 'cert_ruc',
                'cert_valido_desde', 'cert_valido_hasta', 'activo',
                'modo_produccion', 'certificado_pem',
            ])
            ->orderByDesc('modo_produccion')
            ->orderByRaw('cert_valido_hasta IS NULL, cert_valido_hasta ASC')
            ->simplePaginate(20);

        $stats = [
            'produccion' => Company::where('modo_produccion', true)->count(),
            'por_vencer' => Company::whereDate('cert_valido_hasta', '>=', $hoy)
                ->whereDate('cert_valido_hasta', '<=', $limite)->count(),
            'vencidos' => Company::whereDate('cert_valido_hasta', '<', $hoy)->count(),
            // En produccion sin certificado propio no se puede emitir: es el
            // caso que hay que mirar primero.
            'produccion_sin_cert' => Company::where('modo_produccion', true)
                ->where(fn ($q) => $q->whereNull('certificado_pem')->orWhere('certificado_pem', ''))
                ->count(),
        ];

        return view('super-admin.certificates.index', compact('companies', 'stats'));
    }
}
