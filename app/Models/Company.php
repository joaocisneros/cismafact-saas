<?php

namespace App\Models;

use App\Traits\HasCompanyConfigurations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    // SoftDeletes: al eliminar una empresa se marca como borrada (deleted_at)
    // pero NO se borra de la base de datos, conservando sus comprobantes
    // electronicos (obligatorio por SUNAT). Las consultas normales la excluyen
    // automaticamente.
    use HasFactory, HasCompanyConfigurations, SoftDeletes;

    /**
     * Regimenes tributarios.
     *
     * No viajan en el XML —SUNAT no los pide en el comprobante— pero mandan
     * sobre lo que se puede emitir: en Nuevo RUS solo caben boletas.
     */
    public const REGIMENES = [
        'nrus' => 'NRUS — Nuevo Régimen Único Simplificado',
        'rer' => 'RER — Régimen Especial de Renta',
        'rmt' => 'RMT — Régimen MYPE Tributario',
        'rg' => 'RG — Régimen General',
    ];

    protected $fillable = [
        'ruc',
        'plan_id',
        'metodo_emision',
        'razon_social',
        'nombre_comercial',
        'regimen_tributario',
        'direccion',
        'ubigeo',
        'distrito',
        'provincia',
        'departamento',
        'telefono',
        'email',
        'web',
        'usuario_sol',
        'clave_sol',
        'certificado_pem',
        'certificado_password',
        'cert_titular',
        'cert_ruc',
        'cert_valido_desde',
        'cert_valido_hasta',
        'gre_client_id_beta',
        'gre_client_secret_beta',
        'gre_client_id_produccion',
        'gre_client_secret_produccion',
        'gre_ruc_proveedor',
        'gre_usuario_sol',
        'gre_clave_sol',
        'endpoint_beta',
        'endpoint_produccion',
        'modo_produccion',
        'logo_path',
        'activo',
        'suspendida_manualmente',
        'es_demo',
    ];

    protected $casts = [
        'modo_produccion' => 'boolean',
        'activo' => 'boolean',
        'suspendida_manualmente' => 'boolean',
        'es_demo' => 'boolean',
        // Credenciales sensibles encriptadas en reposo (responsabilidad de
        // proteger datos de los clientes). El cifrado/descifrado es transparente
        // al leer/escribir por el modelo.
        'clave_sol' => 'encrypted',
        'certificado_password' => 'encrypted',
        'gre_clave_sol' => 'encrypted',
        'gre_client_secret_beta' => 'encrypted',
        'gre_client_secret_produccion' => 'encrypted',
        'cert_valido_desde' => 'date',
        'cert_valido_hasta' => 'date',
    ];

    protected $hidden = [
        'clave_sol',
        'certificado_pem',
        'certificado_password',
        'gre_client_secret_beta',
        'gre_client_secret_produccion',
        'gre_clave_sol',
    ];

    /**
     * ¿La empresa emite desde Cisma Fact (firma + envia + CDR)?
     * Si es false, emite manualmente en el portal SUNAT y no necesita certificado.
     */
    public function emiteConCismaFact(): bool
    {
        return $this->metodo_emision !== 'sunat_manual';
    }

    public function esEmisionManual(): bool
    {
        return $this->metodo_emision === 'sunat_manual';
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(CompanyConfiguration::class);
    }

    public function activeConfigurations(): HasMany
    {
        return $this->hasMany(CompanyConfiguration::class)->where('is_active', true);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function boletas(): HasMany
    {
        return $this->hasMany(Boleta::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(DebitNote::class);
    }

    public function dispatchGuides(): HasMany
    {
        return $this->hasMany(DispatchGuide::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function dailySummaries(): HasMany
    {
        return $this->hasMany(DailySummary::class);
    }

    public function voidedDocuments(): HasMany
    {
        return $this->hasMany(VoidedDocument::class);
    }

    public function getEndpointAttribute(): string
    {
        return $this->modo_produccion ? $this->endpoint_produccion : $this->endpoint_beta;
    }

    /**
     * Endpoints (auth + cpe) para Guia de Remision. El sistema se conecta
     * DIRECTAMENTE a SUNAT (sin intermediarios): beta para homologacion y
     * produccion para emision real, segun modo_produccion. El endpoint de
     * seguridad (auth) es el mismo; cambia el de envio (cpe).
     *
     * @return array{auth: string, cpe: string}
     */
    /**
     * Credenciales de la API de SUNAT (Client ID / Secret).
     *
     * Son las mismas que usan las guias de remision y la Consulta CPE: se
     * generan una sola vez en SOL -> Credenciales de API SUNAT. Las columnas
     * api_sunat_client_id / api_sunat_client_secret existen en la tabla pero
     * no las escribe ni las lee nadie, y la pantalla de Consulta CPE miraba
     * ahi: avisaba de que faltaban credenciales aunque estuvieran puestas.
     */
    public function credencialesApiSunat(): array
    {
        return [
            'client_id' => $this->gre_client_id_produccion ?: $this->gre_client_id_beta,
            'client_secret' => $this->gre_client_secret_produccion ?: $this->gre_client_secret_beta,
        ];
    }

    public function tieneCredencialesApiSunat(): bool
    {
        $c = $this->credencialesApiSunat();

        return ! empty($c['client_id']) && ! empty($c['client_secret']);
    }

    public function greApiEndpoints(): array
    {
        // GRE no tiene ambiente beta en SUNAT (api-cpe-beta NO existe). Las guias
        // siempre van a produccion, sin importar modo_produccion (que solo afecta
        // a facturas/boletas por SOAP).
        return [
            'auth' => 'https://api-seguridad.sunat.gob.pe/v1',
            'cpe'  => 'https://api-cpe.sunat.gob.pe/v1',
        ];
    }

    /**
     * Etiqueta legible del ambiente GRE actual (para logs/mensajes).
     */
    public function greAmbienteLabel(): string
    {
        return $this->modo_produccion ? 'PRODUCCION' : 'BETA';
    }

    /**
     * Dias restantes hasta el vencimiento del certificado (negativo si ya vencio).
     */
    public function certDiasRestantes(): ?int
    {
        if (! $this->cert_valido_hasta) {
            return null;
        }

        return (int) ceil(now()->startOfDay()->diffInDays($this->cert_valido_hasta->startOfDay(), false));
    }

    /**
     * Estado del certificado: sin_certificado | vigente | por_vencer | vencido.
     * "por_vencer" cuando faltan 30 dias o menos.
     */
    public function certEstado(): string
    {
        $dias = $this->certDiasRestantes();

        if ($dias === null) {
            return 'sin_certificado';
        }

        if ($dias < 0) {
            return 'vencido';
        }

        return $dias <= 30 ? 'por_vencer' : 'vigente';
    }

    /**
     * Etiqueta legible del estado del certificado.
     */
    public function certEstadoLabel(): string
    {
        $dias = $this->certDiasRestantes();

        return match ($this->certEstado()) {
            'vigente'     => "Vigente ({$dias} días)",
            'por_vencer'  => "Por vencer ({$dias} días)",
            'vencido'     => 'Vencido',
            default       => 'Sin certificado',
        };
    }

    /**
     * Color del estado (para badges en la UI): green | yellow | red | gray.
     */
    public function certEstadoColor(): string
    {
        return match ($this->certEstado()) {
            'vigente'    => 'green',
            'por_vencer' => 'yellow',
            'vencido'    => 'red',
            default      => 'gray',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Bootstrap del modelo
     */
    protected static function boot()
    {
        parent::boot();
        
        // Al crear una nueva empresa, configurar endpoints por defecto
        static::creating(function ($company) {
            // Asignar endpoints por defecto si no están definidos
            if (empty($company->endpoint_beta)) {
                $company->endpoint_beta = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService';
            }
            
            if (empty($company->endpoint_produccion)) {
                $company->endpoint_produccion = 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService';
            }
            
            // Asignar valores por defecto
            $company->activo = $company->activo ?? true;
            $company->modo_produccion = $company->modo_produccion ?? false;
        });
        
        // Después de crear una empresa, inicializar configuraciones por defecto
        // TEMPORALMENTE DESHABILITADO - Solo crear empresa sin configuraciones adicionales
        // static::created(function ($company) {
        //     $company->initializeDefaultConfigurations();
        // });
    }

    /**
     * Inicializar configuraciones por defecto para una empresa nueva
     * TEMPORALMENTE DESHABILITADO - Solo crear empresa básica
     */
    public function initializeDefaultConfigurations(): void
    {
        // DESHABILITADO TEMPORALMENTE
        return;
        
        // Configuraciones de impuestos
        $this->setConfig('tax_settings', [
            'igv_porcentaje' => 18.00,
            'isc_porcentaje' => 0.00,
            'icbper_monto' => 0.50,
            'ivap_porcentaje' => 4.00,
            'redondeo_automatico' => true,
            'decimales_precio_unitario' => 10,
            'decimales_cantidad' => 10
        ], 'general', 'general');

        // Configuraciones de documentos
        $this->setConfig('document_settings', [
            'generar_xml_automatico' => true,
            'generar_pdf_automatico' => true,
            'enviar_sunat_automatico' => false,
            'formato_pdf_default' => 'a4',
            'orientacion_pdf_default' => 'portrait',
            'incluir_qr_pdf' => true,
            'incluir_hash_pdf' => true,
            'logo_en_pdf' => true
        ], 'general', 'general');

        // Endpoints de servicios para beta
        $this->setConfig('service_endpoints', [
            'endpoint' => $this->endpoint_beta,
            'wsdl' => str_replace('billService', 'billService?wsdl', $this->endpoint_beta),
            'timeout' => 30
        ], 'beta', 'facturacion');

        // Endpoints de servicios para producción
        $this->setConfig('service_endpoints', [
            'endpoint' => $this->endpoint_produccion,
            'wsdl' => str_replace('billService', 'billService?wsdl', $this->endpoint_produccion),
            'timeout' => 30
        ], 'produccion', 'facturacion');

        // Endpoints para guías de remisión (beta)
        $this->setConfig('service_endpoints', [
            'endpoint' => 'https://gre-test.nubefact.com/v1',
            'api_endpoint' => 'https://api-cpe-beta.sunat.gob.pe/v1/',
            'wsdl' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpgre-beta/billService?wsdl',
            'timeout' => 30
        ], 'beta', 'guias_remision');
    }
}
