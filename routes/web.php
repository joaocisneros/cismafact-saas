<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\Auth\RegisteredUserController;
use App\Http\Controllers\Web\Auth\PasswordResetLinkController;
use App\Http\Controllers\Web\Auth\NewPasswordController;
use App\Http\Controllers\Web\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Web\SuperAdmin\CompanyController as SuperAdminCompanyController;
use App\Http\Controllers\Web\SuperAdmin\UserController as SuperAdminUserController;
use App\Http\Controllers\Web\SuperAdmin\StatisticController as SuperAdminStatisticController;
use App\Http\Controllers\Web\SuperAdmin\DocumentController as SuperAdminDocumentController;
use App\Http\Controllers\Web\SuperAdmin\PlanController as SuperAdminPlanController;
use App\Http\Controllers\Web\SuperAdmin\ApiGlobalController as SuperAdminApiGlobalController;
use App\Http\Controllers\Web\SuperAdmin\ConsultaController as SuperAdminConsultaController;
use App\Http\Controllers\Web\SuperAdmin\PadronController as SuperAdminPadronController;
use App\Http\Controllers\Web\SuperAdmin\SettingController as SuperAdminSettingController;
use App\Http\Controllers\Web\SuperAdmin\SupportController as SuperAdminSupportController;
use App\Http\Controllers\Web\SuperAdmin\ExportController as SuperAdminExportController;
use App\Http\Controllers\Web\SuperAdmin\SubscriptionController as SuperAdminSubscriptionController;
use App\Http\Controllers\Web\SuperAdmin\AuditController as SuperAdminAuditController;
use App\Http\Controllers\Web\Empresa\DashboardController as EmpresaDashboardController;
use App\Http\Controllers\Web\Empresa\ApiKeyController;
use App\Http\Controllers\Web\Empresa\SunatConfigController;
use App\Http\Controllers\Web\Empresa\DocumentController;
use App\Http\Controllers\Web\Empresa\ReportController;
use App\Http\Controllers\Web\Empresa\CompanyController as EmpresaCompanyController;
use App\Http\Controllers\Web\Empresa\ProfileController;

// Landing publica (si no hay sesion muestra la pagina de ventas; si hay, va al panel)
Route::get('/', [\App\Http\Controllers\Web\LandingController::class, 'index'])->name('landing');

// Documentación pública de la API (sin login).
Route::view('/docs', 'docs')->name('docs');

// Sitemap para buscadores (usa el dominio real automaticamente)
Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'priority' => '1.0'],
        ['loc' => route('docs'), 'priority' => '0.7'],
        ['loc' => route('register'), 'priority' => '0.8'],
        ['loc' => route('login'), 'priority' => '0.5'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= "  <url><loc>{$u['loc']}</loc><changefreq>weekly</changefreq><priority>{$u['priority']}</priority></url>\n";
    }
    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

// ========================
// RUTAS DE AUTENTICACIÓN
// ========================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthWebController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');
    Route::get('/register', [RegisteredUserController::class, 'showRegistrationForm'])->name('register');
    // Limite por IP: el registro crea empresa + usuario + serie + API key, asi
    // que sin tope se puede llenar la base con empresas basura.
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('register.post');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('password.update');
});

Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

// Salir de la sesión de soporte. Va fuera del grupo super-admin porque durante
// la suplantación el usuario autenticado ya no tiene ese rol; y fuera de
// 'company.active' para poder salir aunque la empresa esté suspendida.
Route::middleware('auth')
    ->post('/impersonate/stop', [\App\Http\Controllers\Web\SuperAdmin\ImpersonationController::class, 'stop'])
    ->name('impersonate.stop');

// ========================
// DASHBOARD REDIRECT
// ========================
Route::middleware('auth', 'company.active')->get('/dashboard', function () {
    $user = auth()->user();
    if ($user->accedeAlPanelAdmin()) {
        return redirect()->route('super-admin.dashboard');
    }
    return redirect()->route('empresa.dashboard');
})->name('dashboard');

// ========================
// SUPER ADMIN
// ========================
// El grupo lo comparten dos roles: 'super_admin' con acceso total y 'contador',
// que solo consulta empresas, documentos y reportes (y puede entrar como
// soporte). Todo lo que el contador NO debe tocar lleva ademas
// 'role:super_admin', que se acumula sobre el del grupo.
Route::prefix('super-admin')
    ->middleware(['auth', 'role:super_admin,contador', 'company.active', 'audit.admin'])
    ->name('super-admin.')
    ->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        // Crear, editar, borrar o suspender empresas: solo Super Admin.
        // OJO al orden: 'companies/create' tiene que registrarse ANTES que
        // 'companies/{company}', o Laravel toma "create" como el id de una
        // empresa y responde 404.
        // Eliminar una empresa corta la facturacion de un cliente: eso es
        // decision comercial y se queda con el Super Admin.
        //
        // Suspender/reactivar el acceso de una empresa ya NO vive aqui: se hace
        // desde Suscripciones, que es donde esta el motivo real (pago o impago).
        // Antes habia tres botones llamados "Suspender" en tres modulos
        // distintos y dos de ellos hacian lo mismo por caminos que se pisaban.
        Route::middleware('role:super_admin')->group(function () {
            Route::resource('companies', SuperAdminCompanyController::class)->only(['destroy']);
            Route::post('/companies/{company}/toggle-demo', [SuperAdminCompanyController::class, 'toggleDemo'])->name('companies.toggle-demo');
        });

        // Consultar, dar de alta y editar empresas: el contador tambien entra.
        Route::resource('companies', SuperAdminCompanyController::class)->only(['create', 'store', 'index', 'show', 'edit', 'update']);

        // Soporte: entrar al panel de la empresa como su administrador.
        Route::post('/companies/{company}/impersonate', [\App\Http\Controllers\Web\SuperAdmin\ImpersonationController::class, 'start'])
            ->name('companies.impersonate');

        // Alta y edicion de usuarios: el contador entra, pero UserController le
        // impide tocar cuentas de plataforma y asignar roles de plataforma.
        Route::resource('users', SuperAdminUserController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::get('/users/{user}/activity', [SuperAdminUserController::class, 'activity'])->name('users.activity');

        // Listado de certificados: solo lectura, sirve para ver cual esta por vencer.
        Route::get('/certificates', [\App\Http\Controllers\Web\SuperAdmin\CertificateController::class, 'index'])->name('certificates');

        // Borrar, bloquear o cambiarle la contrasena a un usuario es tomar
        // control de su cuenta: se queda con el Super Admin.
        Route::middleware('role:super_admin')->group(function () {

        Route::post('/users/{user}/toggle-active', [SuperAdminUserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::get('/users/{user}/reset-password', [SuperAdminUserController::class, 'resetPasswordForm'])->name('users.reset-password.form');
        Route::post('/users/{user}/reset-password', [SuperAdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [SuperAdminUserController::class, 'destroy'])->name('users.destroy');

        }); // fin del bloque solo Super Admin (acciones sobre cuentas ajenas)

        // Reportes y documentos: el contador tambien entra.
        Route::get('/statistics', [SuperAdminStatisticController::class, 'index'])->name('statistics');
        Route::get('/export/statistics/{format}', [SuperAdminExportController::class, 'exportStatistics'])->name('export.statistics');

        Route::get('/documents', [SuperAdminDocumentController::class, 'index'])->name('documents');
        Route::get('/documents/{type}/{id}', [SuperAdminDocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{type}/{id}/download/{file}', [SuperAdminDocumentController::class, 'download'])->name('documents.download');
        Route::get('/documents/{type}/{id}/view/{file}', [SuperAdminDocumentController::class, 'view'])->name('documents.view');
        Route::post('/documents/{type}/{id}/consult', [SuperAdminDocumentController::class, 'consult'])->name('documents.consult');

        // Tickets de soporte: el contador atiende consultas de clientes. No hay
        // borrado, solo responder, cerrar y reabrir.
        Route::get('/support', [SuperAdminSupportController::class, 'index'])->name('support');
        Route::get('/support/{ticket}', [SuperAdminSupportController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [SuperAdminSupportController::class, 'reply'])->name('support.reply');
        Route::post('/support/{ticket}/priority', [SuperAdminSupportController::class, 'changePriority'])->name('support.priority');
        Route::post('/support/{ticket}/close', [SuperAdminSupportController::class, 'close'])->name('support.close');
        Route::post('/support/{ticket}/reopen', [SuperAdminSupportController::class, 'reopen'])->name('support.reopen');

        // Planes, suscripciones, pagos, API, configuracion y auditoria:
        // solo Super Admin.
        Route::middleware('role:super_admin')->group(function () {

        Route::get('/plans', [SuperAdminPlanController::class, 'index'])->name('plans');
        Route::get('/plans/create', [SuperAdminPlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [SuperAdminPlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [SuperAdminPlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [SuperAdminPlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [SuperAdminPlanController::class, 'destroy'])->name('plans.destroy');
        Route::post('/plans/{plan}/toggle', [SuperAdminPlanController::class, 'toggle'])->name('plans.toggle');

        Route::get('/subscriptions', [SuperAdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('/subscriptions/create', [SuperAdminSubscriptionController::class, 'create'])->name('subscriptions.create');
        Route::get('/subscriptions/{subscription}/edit', [SuperAdminSubscriptionController::class, 'edit'])->name('subscriptions.edit');
        Route::post('/subscriptions', [SuperAdminSubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::put('/subscriptions/{subscription}', [SuperAdminSubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::post('/subscriptions/{subscription}/renew', [SuperAdminSubscriptionController::class, 'renew'])->name('subscriptions.renew');
        Route::patch('/subscriptions/{subscription}/status', [SuperAdminSubscriptionController::class, 'changeStatus'])->name('subscriptions.status');

        Route::get('/payments', [\App\Http\Controllers\Web\SuperAdmin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/create', [\App\Http\Controllers\Web\SuperAdmin\PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments', [\App\Http\Controllers\Web\SuperAdmin\PaymentController::class, 'store'])->name('payments.store');
        Route::post('/payments/{payment}/confirm', [\App\Http\Controllers\Web\SuperAdmin\PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::post('/payments/{payment}/refund', [\App\Http\Controllers\Web\SuperAdmin\PaymentController::class, 'refund'])->name('payments.refund');

        Route::get('/api-global', [SuperAdminApiGlobalController::class, 'index'])->name('api-global');
        Route::get('/api-global/api-keys', [SuperAdminApiGlobalController::class, 'apiKeys'])->name('api-global.api-keys');
        Route::get('/api-global/performance', [SuperAdminApiGlobalController::class, 'performance'])->name('api-global.performance');
        Route::get('/api-global/logs', [SuperAdminApiGlobalController::class, 'logs'])->name('api-global.logs');
        Route::get('/api-global/api-keys/{apiKey}', [SuperAdminApiGlobalController::class, 'showApiKey'])->name('api-global.show-key');
        Route::get('/api-global/api-keys/{apiKey}/actividad', [SuperAdminApiGlobalController::class, 'apiKeyActividad'])->name('api-global.key-actividad');
        Route::post('/api-global/api-keys/{apiKey}/toggle', [SuperAdminApiGlobalController::class, 'toggleApiKey'])->name('api-global.toggle-key');
        Route::post('/api-global/sandbox-token', [SuperAdminApiGlobalController::class, 'generateSandboxToken'])->name('api-global.sandbox-token');
        Route::post('/api-global/empresas/{company}/toggle', [SuperAdminApiGlobalController::class, 'toggleCompanyApi'])->name('api-global.toggle-company');
        Route::post('/api-global/api-keys/{apiKey}/extend', [SuperAdminApiGlobalController::class, 'extendApiKey'])->name('api-global.extend-key');
        Route::delete('/api-global/api-keys/{apiKey}', [SuperAdminApiGlobalController::class, 'destroyApiKey'])->name('api-global.delete-key');

        // Credenciales de prueba para programadores externos. Tiene modulo
        // propio: repartir accesos no es lo mismo que vigilar el servicio.
        Route::get('/tokens-prueba', [\App\Http\Controllers\Web\SuperAdmin\TokenPruebaController::class, 'index'])->name('tokens-prueba.index');
        Route::post('/tokens-prueba', [\App\Http\Controllers\Web\SuperAdmin\TokenPruebaController::class, 'store'])->name('tokens-prueba.store');

        // Consultas de RUC y DNI: proveedor, cache y padron.
        Route::get('/consultas', [SuperAdminConsultaController::class, 'index'])->name('consultas');
        Route::put('/consultas', [SuperAdminConsultaController::class, 'update'])->name('consultas.update');
        Route::post('/consultas/probar', [SuperAdminConsultaController::class, 'probar'])->name('consultas.probar');
        Route::put('/consultas/cuotas', [SuperAdminConsultaController::class, 'cuotas'])->name('consultas.cuotas');
        Route::delete('/consultas/cache', [SuperAdminConsultaController::class, 'vaciarCache'])->name('consultas.cache.vaciar');

        // Padron reducido de SUNAT: estado y actualizacion.
        Route::get('/padron', [SuperAdminPadronController::class, 'index'])->name('padron');
        Route::post('/padron/actualizar', [SuperAdminPadronController::class, 'actualizar'])->name('padron.actualizar');
        Route::get('/padron/estado', [SuperAdminPadronController::class, 'estado'])->name('padron.estado');

        Route::get('/settings', [SuperAdminSettingController::class, 'index'])->name('settings');
        Route::put('/settings', [SuperAdminSettingController::class, 'update'])->name('settings.update');

        Route::get('/audit', [SuperAdminAuditController::class, 'index'])->name('audit.index');

        }); // fin del bloque solo Super Admin

        // El perfil propio lo tiene cualquiera que entre a este panel.
        Route::get('/profile', [\App\Http\Controllers\Web\SuperAdmin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [\App\Http\Controllers\Web\SuperAdmin\ProfileController::class, 'update'])->name('profile.update');
    });

// ========================
// EMPRESA
// ========================
Route::prefix('empresa')
    // 'audit.admin' aqui solo graba cuando hay una sesion de soporte abierta:
    // asi queda registro de lo que el Super Admin hace dentro de la empresa.
    ->middleware(['auth', 'role:company_admin,company_user', 'company.active', 'audit.admin'])
    ->name('empresa.')
    ->group(function () {
        Route::get('/dashboard', [EmpresaDashboardController::class, 'index'])->name('dashboard');

        // Datos fiscales, credenciales SUNAT, API keys y equipo son cosa del
        // dueño de la empresa. Un empleado emite y consulta, pero no toca nada
        // que comprometa a la empresa entera.
        Route::middleware('role:company_admin')->group(function () {

        Route::get('/company/edit', [EmpresaCompanyController::class, 'edit'])->name('company.edit');
        Route::put('/company', [EmpresaCompanyController::class, 'update'])->name('company.update');

        Route::resource('api-keys', ApiKeyController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::post('/api-keys/{apiKey}/regenerate', [ApiKeyController::class, 'regenerate'])->name('api-keys.regenerate');
        Route::post('/api-keys/{apiKey}/toggle', [ApiKeyController::class, 'toggle'])->name('api-keys.toggle');
        Route::get('/api-keys/{apiKey}/secret', [ApiKeyController::class, 'showSecret'])->name('api-keys.show-secret');
        Route::get('/api-keys/docs', [ApiKeyController::class, 'documentation'])->name('api-keys.documentation');
        Route::get('/api-keys/postman', [ApiKeyController::class, 'postman'])->name('api-keys.postman');

        Route::get('/sunat-config', [SunatConfigController::class, 'index'])->name('sunat-config.index');
        Route::put('/sunat-config', [SunatConfigController::class, 'update'])->name('sunat-config.update');
        Route::post('/sunat-config/test', [SunatConfigController::class, 'test'])->name('sunat-config.test');
        Route::post('/sunat-config/go-production', [SunatConfigController::class, 'goToProduction'])->name('sunat-config.go-production');

        // Equipo de la empresa. El tope lo pone el plan (Free 1, Pro 5,
        // Business 20) y lo comprueba UsuarioController.
        Route::get('/usuarios', [\App\Http\Controllers\Web\Empresa\UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/create', [\App\Http\Controllers\Web\Empresa\UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [\App\Http\Controllers\Web\Empresa\UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/edit', [\App\Http\Controllers\Web\Empresa\UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [\App\Http\Controllers\Web\Empresa\UsuarioController::class, 'update'])->name('usuarios.update');
        Route::post('/usuarios/{usuario}/toggle-active', [\App\Http\Controllers\Web\Empresa\UsuarioController::class, 'toggleActive'])->name('usuarios.toggle-active');

        // El plan y su consumo son informacion comercial de la empresa:
        // la ve el dueño, no cada empleado.
        Route::get('/plan', [\App\Http\Controllers\Web\Empresa\PlanController::class, 'index'])->name('plan.index');

        }); // fin del bloque solo dueño de empresa

        // Guía visual: cómo se emite y qué credenciales se necesitan
        Route::get('/ayuda-emision', function () {
            return view('empresa.ayuda-emision', ['company' => \Illuminate\Support\Facades\Auth::user()->company]);
        })->name('ayuda-emision');

        // Emisión de Facturas (web)
        Route::get('/facturas', [\App\Http\Controllers\Web\Empresa\FacturaController::class, 'index'])->name('facturas.index');
        Route::get('/facturas/create', [\App\Http\Controllers\Web\Empresa\FacturaController::class, 'create'])->name('facturas.create');
        Route::post('/facturas', [\App\Http\Controllers\Web\Empresa\FacturaController::class, 'store'])->name('facturas.store');
        Route::get('/facturas/{id}', [\App\Http\Controllers\Web\Empresa\FacturaController::class, 'show'])->whereNumber('id')->name('facturas.show');
        Route::post('/facturas/{id}/send-sunat', [\App\Http\Controllers\Web\Empresa\FacturaController::class, 'sendToSunat'])->whereNumber('id')->name('facturas.send-sunat');

        // Emisión de Boletas (web)
        Route::get('/boletas', [\App\Http\Controllers\Web\Empresa\BoletaController::class, 'index'])->name('boletas.index');
        Route::get('/boletas/create', [\App\Http\Controllers\Web\Empresa\BoletaController::class, 'create'])->name('boletas.create');
        Route::post('/boletas', [\App\Http\Controllers\Web\Empresa\BoletaController::class, 'store'])->name('boletas.store');
        Route::get('/boletas/{id}', [\App\Http\Controllers\Web\Empresa\BoletaController::class, 'show'])->whereNumber('id')->name('boletas.show');
        Route::post('/boletas/{id}/send-sunat', [\App\Http\Controllers\Web\Empresa\BoletaController::class, 'sendToSunat'])->whereNumber('id')->name('boletas.send-sunat');

        // Notas de Crédito (web)
        Route::get('/notas-credito', [\App\Http\Controllers\Web\Empresa\NotaCreditoController::class, 'index'])->name('notas-credito.index');
        Route::get('/notas-credito/create', [\App\Http\Controllers\Web\Empresa\NotaCreditoController::class, 'create'])->name('notas-credito.create');
        Route::post('/notas-credito', [\App\Http\Controllers\Web\Empresa\NotaCreditoController::class, 'store'])->name('notas-credito.store');
        Route::get('/notas-credito/{id}', [\App\Http\Controllers\Web\Empresa\NotaCreditoController::class, 'show'])->whereNumber('id')->name('notas-credito.show');
        Route::post('/notas-credito/{id}/send-sunat', [\App\Http\Controllers\Web\Empresa\NotaCreditoController::class, 'sendToSunat'])->whereNumber('id')->name('notas-credito.send-sunat');

        // Notas de Débito (web)
        Route::get('/notas-debito', [\App\Http\Controllers\Web\Empresa\NotaDebitoController::class, 'index'])->name('notas-debito.index');
        Route::get('/notas-debito/create', [\App\Http\Controllers\Web\Empresa\NotaDebitoController::class, 'create'])->name('notas-debito.create');
        Route::post('/notas-debito', [\App\Http\Controllers\Web\Empresa\NotaDebitoController::class, 'store'])->name('notas-debito.store');
        Route::get('/notas-debito/{id}', [\App\Http\Controllers\Web\Empresa\NotaDebitoController::class, 'show'])->whereNumber('id')->name('notas-debito.show');
        Route::post('/notas-debito/{id}/send-sunat', [\App\Http\Controllers\Web\Empresa\NotaDebitoController::class, 'sendToSunat'])->whereNumber('id')->name('notas-debito.send-sunat');

        // Guías de Remisión (web)
        Route::get('/guias', [\App\Http\Controllers\Web\Empresa\GuiaRemisionController::class, 'index'])->name('guias.index');
        Route::get('/guias/create', [\App\Http\Controllers\Web\Empresa\GuiaRemisionController::class, 'create'])->name('guias.create');
        Route::post('/guias', [\App\Http\Controllers\Web\Empresa\GuiaRemisionController::class, 'store'])->name('guias.store');
        Route::get('/guias/{id}', [\App\Http\Controllers\Web\Empresa\GuiaRemisionController::class, 'show'])->whereNumber('id')->name('guias.show');
        // Registrar una baja hecha en el portal SOL: SUNAT no permite anular
        // guias desde el sistema del contribuyente.
        Route::post('/guias/{id}/registrar-baja', [\App\Http\Controllers\Web\Empresa\GuiaRemisionController::class, 'registrarBaja'])->whereNumber('id')->name('guias.registrar-baja');
        Route::post('/guias/{id}/send-sunat', [\App\Http\Controllers\Web\Empresa\GuiaRemisionController::class, 'sendToSunat'])->whereNumber('id')->name('guias.send-sunat');
        Route::post('/guias/{id}/check-status', [\App\Http\Controllers\Web\Empresa\GuiaRemisionController::class, 'checkStatus'])->whereNumber('id')->name('guias.check-status');

        // Anulación de comprobantes (Comunicación de Baja - facturas y notas)
        Route::get('/anulaciones', [\App\Http\Controllers\Web\Empresa\AnulacionController::class, 'index'])->name('anulaciones.index');
        Route::get('/anulaciones/create', [\App\Http\Controllers\Web\Empresa\AnulacionController::class, 'create'])->name('anulaciones.create');
        Route::post('/anulaciones', [\App\Http\Controllers\Web\Empresa\AnulacionController::class, 'store'])->name('anulaciones.store');
        Route::get('/anulaciones/{id}', [\App\Http\Controllers\Web\Empresa\AnulacionController::class, 'show'])->whereNumber('id')->name('anulaciones.show');
        Route::post('/anulaciones/{id}/check-status', [\App\Http\Controllers\Web\Empresa\AnulacionController::class, 'checkStatus'])->whereNumber('id')->name('anulaciones.check-status');

        // Resumen Diario de Boletas (anulación de boletas vía RC)
        Route::get('/resumenes', [\App\Http\Controllers\Web\Empresa\ResumenController::class, 'index'])->name('resumenes.index');
        Route::get('/resumenes/create', [\App\Http\Controllers\Web\Empresa\ResumenController::class, 'create'])->name('resumenes.create');
        Route::post('/resumenes', [\App\Http\Controllers\Web\Empresa\ResumenController::class, 'store'])->name('resumenes.store');
        Route::get('/resumenes/{id}', [\App\Http\Controllers\Web\Empresa\ResumenController::class, 'show'])->whereNumber('id')->name('resumenes.show');
        Route::post('/resumenes/{id}/check-status', [\App\Http\Controllers\Web\Empresa\ResumenController::class, 'checkStatus'])->whereNumber('id')->name('resumenes.check-status');

        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{type}/{id}/download/{file}', [DocumentController::class, 'download'])->name('documents.download');
        Route::get('/documents/{type}/{id}/view/{file}', [DocumentController::class, 'view'])->name('documents.view');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/weekly', [ReportController::class, 'weekly'])->name('reports.weekly');
        Route::get('/reports/yearly', [ReportController::class, 'yearly'])->name('reports.yearly');
        Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');

        // Clientes
        Route::resource('clients', \App\Http\Controllers\Web\Empresa\ClientController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        // Reporte de ventas del periodo para entregar al contador.
        Route::get('/reportes/contador', [\App\Http\Controllers\Web\Empresa\ReporteContadorController::class, 'index'])
            ->name('reportes.contador');
        Route::get('/reportes/contador/descargar', [\App\Http\Controllers\Web\Empresa\ReporteContadorController::class, 'descargar'])
            ->name('reportes.contador.descargar');

        // Sucursales (establecimientos declarados ante SUNAT)
        Route::get('/branches', [\App\Http\Controllers\Web\Empresa\BranchController::class, 'index'])->name('branches.index');
        // 'create' antes que '{branch}': si no, Laravel toma "create" como id.
        Route::get('/branches/create', [\App\Http\Controllers\Web\Empresa\BranchController::class, 'create'])->name('branches.create');
        Route::get('/branches/{branch}/edit', [\App\Http\Controllers\Web\Empresa\BranchController::class, 'edit'])->name('branches.edit');
        Route::post('/branches', [\App\Http\Controllers\Web\Empresa\BranchController::class, 'store'])->name('branches.store');
        Route::put('/branches/{branch}', [\App\Http\Controllers\Web\Empresa\BranchController::class, 'update'])->name('branches.update');
        Route::post('/branches/{branch}/toggle', [\App\Http\Controllers\Web\Empresa\BranchController::class, 'toggle'])->name('branches.toggle');
        Route::delete('/branches/{branch}', [\App\Http\Controllers\Web\Empresa\BranchController::class, 'destroy'])->name('branches.destroy');

        // Correlativos (series por sucursal)
        Route::get('/correlatives', [\App\Http\Controllers\Web\Empresa\CorrelativeController::class, 'index'])->name('correlatives.index');
        Route::post('/correlatives', [\App\Http\Controllers\Web\Empresa\CorrelativeController::class, 'store'])->name('correlatives.store');
        Route::delete('/correlatives/{correlative}', [\App\Http\Controllers\Web\Empresa\CorrelativeController::class, 'destroy'])->name('correlatives.destroy');

        // Consulta CPE (verificar estado real en SUNAT)
        Route::get('/consulta-cpe', [\App\Http\Controllers\Web\Empresa\ConsultaCpeController::class, 'index'])->name('consulta-cpe.index');
        Route::post('/consulta-cpe', [\App\Http\Controllers\Web\Empresa\ConsultaCpeController::class, 'consultar'])->name('consulta-cpe.consultar');

        // Notificaciones
        Route::get('/notifications', [\App\Http\Controllers\Web\Empresa\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{id}/read', [\App\Http\Controllers\Web\Empresa\NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Web\Empresa\NotificationController::class, 'markAllRead'])->name('notifications.read-all');


        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/security', [ProfileController::class, 'security'])->name('profile.security');
        Route::post('/profile/two-factor', [ProfileController::class, 'toggleTwoFactor'])->name('profile.two-factor');

        Route::get('/support', [\App\Http\Controllers\Web\Empresa\TicketController::class, 'index'])->name('support.index');
        Route::get('/support/create', [\App\Http\Controllers\Web\Empresa\TicketController::class, 'create'])->name('support.create');
        Route::post('/support', [\App\Http\Controllers\Web\Empresa\TicketController::class, 'store'])->name('support.store');
        Route::get('/support/{ticket}', [\App\Http\Controllers\Web\Empresa\TicketController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [\App\Http\Controllers\Web\Empresa\TicketController::class, 'reply'])->name('support.reply');
        Route::post('/support/{ticket}/reopen', [\App\Http\Controllers\Web\Empresa\TicketController::class, 'reopen'])->name('support.reopen');
    });
