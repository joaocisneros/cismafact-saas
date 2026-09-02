<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\BoletaController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\PanelController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\CompanyConfigController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CorrelativeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsultaController as ApiConsultaController;
use App\Http\Controllers\Api\ConsultaCpeController;
use App\Http\Controllers\Api\SetupController;
use App\Http\Controllers\Api\UbigeoController;
use App\Http\Controllers\Api\CreditNoteController;
use App\Http\Controllers\Api\DebitNoteController;
use App\Http\Controllers\Api\DispatchGuideController;
use App\Http\Controllers\Api\RetentionController;
use App\Http\Controllers\Api\VoidedDocumentController;
use App\Http\Controllers\Api\DailySummaryController;

// ========================
// RUTAS PÚBLICAS (SIN AUTENTICACIÓN)
// ========================

// Información del sistema
Route::get('/system/info', [AuthController::class, 'systemInfo']);

// Setup del sistema. Ejecuta migraciones/seeders y expone el estado interno,
// asi que exige la cabecera X-Setup-Token (ver SETUP_TOKEN en .env). Sin ese
// token configurado las rutas responden 404.
Route::prefix('setup')->middleware(['setup.token', 'throttle:10,1'])->group(function () {
    Route::post('/migrate', [SetupController::class, 'migrate']);
    Route::post('/seed', [SetupController::class, 'seed']);
    Route::get('/status', [SetupController::class, 'status']);
});

// Inicialización del sistema
Route::post('/auth/initialize', [AuthController::class, 'initialize']);

// Autenticación
Route::post('/auth/login', [AuthController::class, 'login']);

Route::match(['get', 'post'], '/empresa', function (Request $request) {
    $apiKeyValue = $request->header('X-Api-Key') ?? $request->header('X-API-KEY');
    $apiSecretValue = $request->header('X-Api-Secret') ?? $request->header('X-API-SECRET');

    if (! $apiKeyValue || ! $apiSecretValue) {
        return response()->json([
            'success' => false,
            'message' => 'Debes enviar X-Api-Key y X-Api-Secret.',
        ], 401);
    }

    $apiKey = \App\Models\ApiKey::query()
        ->with('company:id,ruc,razon_social,email,activo,modo_produccion')
        ->where('key', $apiKeyValue)
        ->where('active', true)
        ->first();

    // Mismo criterio que el middleware: el secret se guarda cifrado, no
    // hasheado, asi que se compara el valor. Con Hash::check ademas
    // reventaba, porque bcrypt no reconoce lo que recibe como hash suyo.
    if (! $apiKey || ! hash_equals((string) $apiKey->secret, (string) $apiSecretValue)) {
        return response()->json([
            'success' => false,
            'message' => 'Credenciales API invalidas.',
        ], 401);
    }

    if (! $apiKey->company || ! $apiKey->company->activo) {
        return response()->json([
            'success' => false,
            'message' => 'Empresa inactiva o suspendida.',
        ], 403);
    }

    $apiKey->forceFill(['last_used_at' => now()])->save();

    return response()->json([
        'success' => true,
        'message' => 'Conexion API correcta.',
        'data' => [
            'company' => [
                'id' => $apiKey->company->id,
                'ruc' => $apiKey->company->ruc,
                'razon_social' => $apiKey->company->razon_social,
                'email' => $apiKey->company->email,
                'ambiente' => $apiKey->company->modo_produccion ? 'produccion' : 'demo',
            ],
            'api_key' => [
                'name' => $apiKey->name,
                'last_used_at' => optional($apiKey->last_used_at)->toDateTimeString(),
            ],
        ],
    ]);
});

/*
 * Datos auxiliares y cifras para las aplicaciones que consumen la API.
 * Van antes de los comprobantes porque es lo que una aplicacion pide primero:
 * con que sucursal, que serie y que cliente va a emitir.
 */
Route::middleware('api.key')->group(function () {
    Route::get('/sucursales', [CatalogoController::class, 'sucursales']);
    Route::get('/series', [CatalogoController::class, 'series']);
    Route::get('/clientes', [CatalogoController::class, 'clientes']);
    Route::get('/buscar-documento', [CatalogoController::class, 'buscarDocumento']);

    Route::prefix('panel')->group(function () {
        Route::get('/indicadores', [PanelController::class, 'indicadores']);
        Route::get('/documentos-recientes', [PanelController::class, 'documentosRecientes']);
        Route::get('/ventas-mensuales', [PanelController::class, 'ventasMensuales']);
        Route::get('/estado-sunat', [PanelController::class, 'estadoSunat']);
        Route::get('/por-moneda', [PanelController::class, 'porMoneda']);
    });
});

/*
 * Emitir tiene su propio limite.
 *
 * Cada emision se queda esperando a SUNAT, que tarda entre nada y media
 * hora, y mientras tanto ocupa uno de los procesos del servidor. Sin limite,
 * un solo cliente que suelte su carga del dia de golpe se lleva todos los
 * procesos y el panel deja de responder para los demas.
 *
 * El limite es por credencial, asi que un cliente pasado de vueltas se frena
 * a si mismo y no al resto. Consultar y descargar no llevan limite: se
 * resuelven en casa y no bloquean nada.
 */
Route::middleware(['api.key', 'emision'])->prefix('boletas')->group(function () {
    Route::get('/', [BoletaController::class, 'index']);
    Route::post('/', [BoletaController::class, 'store']);
    Route::get('/{id}', [BoletaController::class, 'show']);
    Route::post('/{id}/send-sunat', [BoletaController::class, 'sendToSunat']);
    Route::get('/{id}/download-xml', [BoletaController::class, 'downloadXml']);
    Route::get('/{id}/download-cdr', [BoletaController::class, 'downloadCdr']);
    Route::get('/{id}/download-pdf', [BoletaController::class, 'downloadPdf']);
    Route::post('/{id}/generate-pdf', [BoletaController::class, 'generatePdf']);
});

Route::middleware(['api.key', 'emision'])->prefix('facturas')->group(function () {
    Route::get('/', [InvoiceController::class, 'index']);
    Route::post('/', [InvoiceController::class, 'store']);
    Route::get('/{id}', [InvoiceController::class, 'show']);
    Route::post('/{id}/send-sunat', [InvoiceController::class, 'sendToSunat']);
    Route::get('/{id}/download-xml', [InvoiceController::class, 'downloadXml']);
    Route::get('/{id}/download-cdr', [InvoiceController::class, 'downloadCdr']);
    Route::get('/{id}/download-pdf', [InvoiceController::class, 'downloadPdf']);
});

Route::middleware(['api.key', 'emision'])->prefix('notas-credito')->group(function () {
    Route::get('/', [CreditNoteController::class, 'index']);
    Route::post('/', [CreditNoteController::class, 'store']);
    Route::get('/{id}', [CreditNoteController::class, 'show']);
    Route::post('/{id}/send-sunat', [CreditNoteController::class, 'sendToSunat']);
    Route::get('/{id}/download-xml', [CreditNoteController::class, 'downloadXml']);
    Route::get('/{id}/download-cdr', [CreditNoteController::class, 'downloadCdr']);
    Route::get('/{id}/download-pdf', [CreditNoteController::class, 'downloadPdf']);
});

Route::middleware(['api.key', 'emision'])->prefix('notas-debito')->group(function () {
    Route::get('/', [DebitNoteController::class, 'index']);
    Route::post('/', [DebitNoteController::class, 'store']);
    Route::get('/{id}', [DebitNoteController::class, 'show']);
    Route::post('/{id}/send-sunat', [DebitNoteController::class, 'sendToSunat']);
    Route::get('/{id}/download-xml', [DebitNoteController::class, 'downloadXml']);
    Route::get('/{id}/download-cdr', [DebitNoteController::class, 'downloadCdr']);
    Route::get('/{id}/download-pdf', [DebitNoteController::class, 'downloadPdf']);
});

Route::middleware(['api.key', 'emision'])->prefix('guias-remision')->group(function () {
    Route::get('/', [DispatchGuideController::class, 'index']);
    Route::post('/', [DispatchGuideController::class, 'store']);
    Route::get('/{id}', [DispatchGuideController::class, 'show']);
    Route::post('/{id}/send-sunat', [DispatchGuideController::class, 'sendToSunat']);
    Route::post('/{id}/check-status', [DispatchGuideController::class, 'checkStatus']);
    Route::get('/{id}/download-xml', [DispatchGuideController::class, 'downloadXml']);
    Route::get('/{id}/download-cdr', [DispatchGuideController::class, 'downloadCdr']);
    Route::get('/{id}/download-pdf', [DispatchGuideController::class, 'downloadPdf']);
});

Route::middleware(['api.key', 'emision'])->prefix('resumenes')->group(function () {
    Route::get('/', [DailySummaryController::class, 'index']);
    Route::post('/', [DailySummaryController::class, 'store']);
    Route::get('/pendientes/boletas', [DailySummaryController::class, 'pendingBoletas']);
    // Anular boletas: van por resumen, no por comunicacion de baja.
    Route::get('/boletas/anulables', [DailySummaryController::class, 'boletasForVoiding']);
    Route::post('/anular', [DailySummaryController::class, 'voidBoletas']);
    Route::get('/{id}', [DailySummaryController::class, 'show']);
    Route::post('/{id}/enviar', [DailySummaryController::class, 'sendToSunat']);
    Route::match(['get', 'post'], '/{id}/estado', [DailySummaryController::class, 'checkStatus']);
    Route::get('/{id}/xml', [DailySummaryController::class, 'downloadXml']);
    Route::get('/{id}/cdr', [DailySummaryController::class, 'downloadCdr']);
});

Route::middleware(['api.key', 'emision'])->prefix('anulaciones')->group(function () {
    Route::get('/', [VoidedDocumentController::class, 'index']);
    Route::post('/', [VoidedDocumentController::class, 'store']);
    Route::get('/documentos/disponibles', [VoidedDocumentController::class, 'documentsForVoiding']);
    Route::get('/{id}', [VoidedDocumentController::class, 'show']);
    Route::post('/{id}/enviar', [VoidedDocumentController::class, 'sendToSunat']);
    Route::match(['get', 'post'], '/{id}/estado', [VoidedDocumentController::class, 'checkStatus']);
    Route::get('/{id}/xml', [VoidedDocumentController::class, 'downloadXml']);
    Route::get('/{id}/cdr', [VoidedDocumentController::class, 'downloadCdr']);
});

Route::middleware('api.key')->prefix('consulta-cpe')->group(function () {
    Route::post('/factura/{id}', [ConsultaCpeController::class, 'consultarFactura']);
    Route::post('/boleta/{id}', [ConsultaCpeController::class, 'consultarBoleta']);
    Route::post('/nota-credito/{id}', [ConsultaCpeController::class, 'consultarNotaCredito']);
    Route::post('/nota-debito/{id}', [ConsultaCpeController::class, 'consultarNotaDebito']);
    Route::post('/masivo', [ConsultaCpeController::class, 'consultarDocumentosMasivo']);
    Route::get('/estadisticas', [ConsultaCpeController::class, 'estadisticasConsultas']);
});

// ========================
// RUTAS PROTEGIDAS (CON AUTENTICACIÓN)
// ========================
Route::prefix('v1')->middleware('api.key')->group(function () {

    // ========================
    // AUTENTICACIÓN Y USUARIO
    // ========================
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/create-user', [AuthController::class, 'createUser']);
    
    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ========================
    // SETUP AVANZADO
    // ========================
    Route::prefix('setup')->group(function () {
        Route::post('/complete', [SetupController::class, 'setup']);
        Route::post('/configure-sunat', [SetupController::class, 'configureSunat']);
    });

    // ========================
    // GESTIÓN DE UBIGEOS
    // ========================
    Route::prefix('ubigeos')->group(function () {
        Route::get('/regiones', [UbigeoController::class, 'getRegiones']);
        Route::get('/provincias', [UbigeoController::class, 'getProvincias']);
        Route::get('/distritos', [UbigeoController::class, 'getDistritos']);
        Route::get('/search', [UbigeoController::class, 'searchUbigeo']);
        Route::get('/{id}', [UbigeoController::class, 'getUbigeoById']);
    });

    // ========================
    // EMPRESAS Y CONFIGURACIONES
    // ========================
    
    // Empresas
    Route::apiResource('companies', CompanyController::class);
    Route::post('/companies/{company}/activate', [CompanyController::class, 'activate']);
    Route::post('/companies/{company}/toggle-production', [CompanyController::class, 'toggleProductionMode']);

    // Configuraciones de empresas
    Route::prefix('companies/{company_id}/config')->group(function () {
        Route::get('/', [CompanyConfigController::class, 'show']);
        Route::get('/{section}', [CompanyConfigController::class, 'getSection']);
        Route::put('/{section}', [CompanyConfigController::class, 'updateSection']);
        Route::get('/validate/services', [CompanyConfigController::class, 'validateServices']);
        Route::post('/reset', [CompanyConfigController::class, 'resetToDefaults']);
        Route::post('/migrate', [CompanyConfigController::class, 'migrateCompany']);
        Route::delete('/cache', [CompanyConfigController::class, 'clearCache']);
    });

    // Configuraciones generales
    Route::prefix('config')->group(function () {
        Route::get('/defaults', [CompanyConfigController::class, 'getDefaults']);
        Route::get('/summary', [CompanyConfigController::class, 'getSummary']);
    });

    // ========================
    // SUCURSALES
    // ========================
    Route::apiResource('branches', BranchController::class);
    Route::post('/branches/{branch}/activate', [BranchController::class, 'activate']);
    Route::get('/companies/{company}/branches', [BranchController::class, 'getByCompany']);

    // ========================
    // CLIENTES
    // ========================
    Route::apiResource('clients', ClientController::class);
    Route::post('/clients/{client}/activate', [ClientController::class, 'activate']);
    Route::get('/companies/{company}/clients', [ClientController::class, 'getByCompany']);
    Route::post('/clients/search-by-document', [ClientController::class, 'searchByDocument']);

    // ========================
    // CORRELATIVOS
    // ========================
    Route::get('/branches/{branch}/correlatives', [CorrelativeController::class, 'index']);
    Route::post('/branches/{branch}/correlatives', [CorrelativeController::class, 'store']);
    Route::put('/branches/{branch}/correlatives/{correlative}', [CorrelativeController::class, 'update']);
    Route::delete('/branches/{branch}/correlatives/{correlative}', [CorrelativeController::class, 'destroy']);
    Route::post('/branches/{branch}/correlatives/batch', [CorrelativeController::class, 'createBatch']);
    Route::post('/branches/{branch}/correlatives/{correlative}/increment', [CorrelativeController::class, 'increment']);
    
    // Catálogos de correlativos
    Route::get('/correlatives/document-types', [CorrelativeController::class, 'getDocumentTypes']);

    // ========================
    // DOCUMENTOS ELECTRÓNICOS SUNAT
    // ========================

    // PDF Formatos
    Route::prefix('pdf')->group(function () {
        Route::get('/formats', [PdfController::class, 'getAvailableFormats']);
    });

    // Facturas
    Route::prefix('invoices')->group(function () {
        Route::get('/', [InvoiceController::class, 'index']);
        Route::post('/', [InvoiceController::class, 'store']);
        Route::get('/{id}', [InvoiceController::class, 'show']);
        Route::post('/{id}/send-sunat', [InvoiceController::class, 'sendToSunat']);
        Route::get('/{id}/download-xml', [InvoiceController::class, 'downloadXml']);
        Route::get('/{id}/download-cdr', [InvoiceController::class, 'downloadCdr']);
        Route::get('/{id}/download-pdf', [InvoiceController::class, 'downloadPdf']);
        Route::post('/{id}/generate-pdf', [InvoiceController::class, 'generatePdf']);
    });

    // Boletas
    Route::prefix('boletas')->group(function () {
        Route::get('/', [BoletaController::class, 'index']);
        Route::post('/', [BoletaController::class, 'store']);
        Route::get('/{id}', [BoletaController::class, 'show']);
        Route::post('/{id}/send-sunat', [BoletaController::class, 'sendToSunat']);
        Route::get('/{id}/download-xml', [BoletaController::class, 'downloadXml']);
        Route::get('/{id}/download-cdr', [BoletaController::class, 'downloadCdr']);
        Route::get('/{id}/download-pdf', [BoletaController::class, 'downloadPdf']);
        Route::post('/{id}/generate-pdf', [BoletaController::class, 'generatePdf']);
    });

    // ========================
    // NOTAS DE CRÉDITO (TIPO 07)
    // ========================
    Route::prefix('credit-notes')->group(function () {
        Route::get('/', [CreditNoteController::class, 'index']);
        Route::post('/', [CreditNoteController::class, 'store']);
        Route::get('/{id}', [CreditNoteController::class, 'show']);
        Route::post('/{id}/send-sunat', [CreditNoteController::class, 'sendToSunat']);
        Route::get('/{id}/download-xml', [CreditNoteController::class, 'downloadXml']);
        Route::get('/{id}/download-cdr', [CreditNoteController::class, 'downloadCdr']);
        Route::get('/{id}/download-pdf', [CreditNoteController::class, 'downloadPdf']);
        Route::post('/{id}/generate-pdf', [CreditNoteController::class, 'generatePdf']);
    });

    // ========================
    // NOTAS DE DÉBITO (TIPO 08)
    // ========================
    Route::prefix('debit-notes')->group(function () {
        Route::get('/', [DebitNoteController::class, 'index']);
        Route::post('/', [DebitNoteController::class, 'store']);
        Route::get('/{id}', [DebitNoteController::class, 'show']);
        Route::post('/{id}/send-sunat', [DebitNoteController::class, 'sendToSunat']);
        Route::get('/{id}/download-xml', [DebitNoteController::class, 'downloadXml']);
        Route::get('/{id}/download-cdr', [DebitNoteController::class, 'downloadCdr']);
        Route::get('/{id}/download-pdf', [DebitNoteController::class, 'downloadPdf']);
        Route::post('/{id}/generate-pdf', [DebitNoteController::class, 'generatePdf']);
    });

    // ========================
    // GUÍAS DE REMISIÓN (TIPO 09)
    // ========================
    Route::prefix('dispatch-guides')->group(function () {
        Route::get('/', [DispatchGuideController::class, 'index']);
        Route::post('/', [DispatchGuideController::class, 'store']);
        Route::get('/{id}', [DispatchGuideController::class, 'show']);
        Route::post('/{id}/send-sunat', [DispatchGuideController::class, 'sendToSunat']);
        Route::post('/{id}/check-status', [DispatchGuideController::class, 'checkStatus']);
        Route::get('/{id}/download-xml', [DispatchGuideController::class, 'downloadXml']);
        Route::get('/{id}/download-cdr', [DispatchGuideController::class, 'downloadCdr']);
        Route::get('/{id}/download-pdf', [DispatchGuideController::class, 'downloadPdf']);
        Route::post('/{id}/generate-pdf', [DispatchGuideController::class, 'generatePdf']);
    });

    // ========================
    // RETENCIONES (TIPO 20)
    // ========================
    Route::prefix('retentions')->group(function () {
        Route::get('/', [RetentionController::class, 'index']);
        Route::post('/', [RetentionController::class, 'store']);
        Route::get('/{id}', [RetentionController::class, 'show']);
        Route::post('/{id}/send-sunat', [RetentionController::class, 'sendToSunat']);
        Route::get('/{id}/download-xml', [RetentionController::class, 'downloadXml']);
        Route::get('/{id}/download-cdr', [RetentionController::class, 'downloadCdr']);
        Route::get('/{id}/download-pdf', [RetentionController::class, 'downloadPdf']);
        Route::post('/{id}/generate-pdf', [RetentionController::class, 'generatePdf']);
    });

    // ========================
    // COMUNICACIONES DE BAJA (ANULACIONES)
    // ========================
    Route::prefix('voided-documents')->group(function () {
        Route::get('/', [VoidedDocumentController::class, 'index']);
        Route::post('/', [VoidedDocumentController::class, 'store']);
        Route::get('/{id}', [VoidedDocumentController::class, 'show']);
        Route::post('/{id}/send-sunat', [VoidedDocumentController::class, 'sendToSunat']);
        Route::post('/{id}/check-status', [VoidedDocumentController::class, 'checkStatus']);
        Route::get('/documents-for-voiding', [VoidedDocumentController::class, 'documentsForVoiding']);
        Route::get('/{id}/download-xml', [VoidedDocumentController::class, 'downloadXml']);
        Route::get('/{id}/download-cdr', [VoidedDocumentController::class, 'downloadCdr']);
    });

    // ========================
    // RESÚMENES DIARIOS (RA)
    // ========================
    Route::prefix('daily-summaries')->group(function () {
        Route::get('/', [DailySummaryController::class, 'index']);
        Route::post('/', [DailySummaryController::class, 'store']);
        Route::get('/{id}', [DailySummaryController::class, 'show']);
        Route::post('/{id}/send-sunat', [DailySummaryController::class, 'sendToSunat']);
        Route::post('/{id}/check-status', [DailySummaryController::class, 'checkStatus']);
        Route::get('/pending-boletas', [DailySummaryController::class, 'pendingBoletas']);
    });

    // ========================
    // CONSULTA DE COMPROBANTES ELECTRÓNICOS (CPE)
    // ========================
    Route::prefix('consulta-cpe')->group(function () {
        // Consultas individuales por tipo de documento
        Route::post('/factura/{id}', [ConsultaCpeController::class, 'consultarFactura']);
        Route::post('/boleta/{id}', [ConsultaCpeController::class, 'consultarBoleta']);
        Route::post('/nota-credito/{id}', [ConsultaCpeController::class, 'consultarNotaCredito']);
        Route::post('/nota-debito/{id}', [ConsultaCpeController::class, 'consultarNotaDebito']);

        // Consulta masiva
        Route::post('/masivo', [ConsultaCpeController::class, 'consultarDocumentosMasivo']);

        // Estadísticas de consultas
        Route::get('/estadisticas', [ConsultaCpeController::class, 'estadisticasConsultas']);
    });
});

/*
|--------------------------------------------------------------------------
| Consulta de RUC y DNI
|--------------------------------------------------------------------------
|
| Con su propia llave, NO con la de emision. Son dos negocios distintos: quien
| compra consultas puede no facturar aqui, y quien factura no deberia recibir
| consultas de regalo. Ademas, bloquear una cosa no puede cortar la otra.
|
| El tope por minuto no es lo mismo que la cuota del plan. La cuota dice cuanto
| puede consumir al mes; esto evita que se lo queme de golpe y, de paso, que una
| rafaga tumbe al proveedor del que dependemos.
|
*/
Route::middleware(['llave.consulta', 'throttle:30,1'])->prefix('consultas')->group(function () {
    Route::get('/ruc/{numero}', [ApiConsultaController::class, 'ruc']);
    Route::get('/dni/{numero}', [ApiConsultaController::class, 'dni']);
    Route::get('/cuota', [ApiConsultaController::class, 'cuota']);
});
