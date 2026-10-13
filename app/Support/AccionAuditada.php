<?php

namespace App\Support;

use App\Models\AuditLog;

/**
 * Traduce lo que guarda la auditoría a una frase que se entienda.
 *
 * La tabla anotaba el nombre interno de la ruta y el metodo HTTP —«POST
 * create_or_action · super-admin.tokens-prueba.regenerar»—, que dice poco
 * salvo que uno conozca el codigo. Lo que hace falta saber al revisar el
 * registro es que paso: quien genero un secret nuevo, quien entro como
 * soporte, quien borro una credencial.
 *
 * El nombre de la ruta se sigue guardando y se enseña debajo, en pequeño:
 * cuando hay que rastrear algo en el codigo, es lo unico que sirve.
 */
class AccionAuditada
{
    /** @var array<int, \App\Models\User|null> */
    private static array $actoresAnteriores = [];

    public static function nombreActor(AuditLog $log): string
    {
        return $log->actor_name
            ?: $log->actor?->name
            ?: self::actorAnterior($log)?->name
            ?: self::quienEstabaDetras($log)
            ?: ($log->user?->name ?? 'Sistema');
    }

    public static function correoActor(AuditLog $log): ?string
    {
        return $log->actor_email
            ?: $log->actor?->email
            ?: self::actorAnterior($log)?->email
            ?: (! self::enSoporte($log) ? $log->user?->email : null);
    }

    /** @return array{nombre:string,correo:?string}|null */
    public static function cuentaUtilizada(AuditLog $log): ?array
    {
        if (! self::enSoporte($log) || ! $log->user) {
            return null;
        }

        return [
            'nombre' => $log->user->name,
            'correo' => $log->user->email,
        ];
    }

    /** @return array{tipo:string,navegador:string,sistema:string} */
    public static function dispositivo(AuditLog $log): array
    {
        $agent = (string) $log->user_agent;

        $tipo = match (true) {
            preg_match('/iPad|Tablet/i', $agent) === 1 => 'Tablet',
            preg_match('/Android|iPhone|iPod|Mobile/i', $agent) === 1 => 'Móvil',
            $agent === '' => 'No identificado',
            default => 'Computadora',
        };

        $navegador = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Chrome/') || str_contains($agent, 'CriOS/') => 'Chrome',
            str_contains($agent, 'Firefox/') || str_contains($agent, 'FxiOS/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            $agent === '' => 'Sin navegador',
            default => 'Otro navegador',
        };

        $sistema = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            preg_match('/iPhone|iPad|iPod/i', $agent) === 1 => 'iOS',
            preg_match('/Macintosh|Mac OS X/i', $agent) === 1 => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Sistema desconocido',
        };

        return compact('tipo', 'navegador', 'sistema');
    }

    /** @return array{servicio:string,entorno:?string,tipo:?string} */
    public static function contexto(AuditLog $log): array
    {
        $service = $log->service_context ?: AuditContext::service((string) $log->route_name);
        $environment = $log->environment_context;

        if (! $environment) {
            $fromData = data_get($log->data, 'entorno');
            $environment = in_array($fromData, ['sandbox', 'beta'], true)
                ? 'sandbox'
                : ($fromData === 'produccion' ? 'produccion' : null);
        }

        if (! $environment && $service === 'facturacion' && $log->company) {
            $environment = $log->company->modo_produccion ? 'produccion' : 'beta';
        }

        return [
            'servicio' => match ($service) {
                'api_ruc_dni' => 'API RUC y DNI',
                'facturacion' => 'Facturación electrónica',
                default => 'Gestión empresarial',
            },
            'entorno' => match ($environment) {
                'produccion' => 'Producción',
                'sandbox' => 'Sandbox',
                'beta' => 'Beta',
                default => null,
            },
            'tipo' => $log->event_type,
        ];
    }

    /**
     * Las que no se adivinan por su nombre, o que merecen decirse de otra
     * forma. Van primero: si la ruta esta aqui, se usa esta frase.
     */
    private const FRASES = [
        'super-admin.companies.impersonate' => 'Entró como soporte a la empresa',
        'impersonate.stop' => 'Salió del modo soporte',
        'super-admin.settings.update' => 'Cambió la configuración general',

        'super-admin.tokens-prueba.store' => 'Creó un token de sandbox',
        'super-admin.tokens-prueba.regenerar' => 'Generó un secret nuevo para un token de sandbox',
        'super-admin.api-global.sandbox-token' => 'Creó un token de sandbox',
        'super-admin.api-global.regenerate-key' => 'Generó un secret nuevo para una credencial',
        'super-admin.api-global.delete-key' => 'Eliminó una credencial',
        'super-admin.api-global.toggle-key' => 'Bloqueó o desbloqueó una credencial',
        'super-admin.api-global.toggle-company' => 'Cortó o restableció el acceso por API de una empresa',
        'super-admin.api-global.extend-key' => 'Amplió la vigencia de un token',

        'super-admin.consultas.llaves.guardar' => 'Creó una API Key de RUC y DNI',
        'super-admin.consultas.llaves.regenerar' => 'Generó un secret nuevo de RUC y DNI',
        'super-admin.consultas.llaves.renovar' => 'Renovó la vigencia de una API Key de RUC y DNI',
        'super-admin.consultas.llaves.borrar' => 'Eliminó una API Key de RUC y DNI',
        'super-admin.consultas.llaves.alternar' => 'Bloqueó o desbloqueó una API Key de RUC y DNI',
        'super-admin.consultas.apis.alternar' => 'Activó o desactivó un proveedor de consultas',
        'super-admin.consultas.planes.actualizar' => 'Cambió los planes de RUC y DNI',
        'super-admin.consultas.cuotas' => 'Cambió las cuotas de consultas',

        'super-admin.subscriptions.renew' => 'Renovó una suscripción',
        'super-admin.subscriptions.status' => 'Cambió el estado de una suscripción',
        'super-admin.companies.toggle-status' => 'Activó o suspendió una empresa',
        'super-admin.companies.toggle-demo' => 'Marcó o desmarcó una empresa como demo',
        'super-admin.users.toggle-active' => 'Activó o bloqueó un usuario',
        'super-admin.users.restore' => 'Restauró un usuario eliminado',
        'super-admin.plans.toggle' => 'Activó o desactivó un plan',
        'super-admin.support.close' => 'Cerró un ticket de soporte',
        'super-admin.support.priority' => 'Cambió la prioridad de un ticket de soporte',
        'super-admin.payments.confirm' => 'Confirmó un pago',
        'super-admin.payments.refund' => 'Devolvió un pago',
        'super-admin.padron.importar' => 'Importó el padrón de SUNAT',
        'super-admin.documents.consult' => 'Consultó el estado de un comprobante en SUNAT',
        'super-admin.contactos.atender' => 'Marcó una solicitud de contacto como atendida',
        'super-admin.contactos.borrar' => 'Eliminó una solicitud de contacto',

        'empresa.api-keys.store' => 'Creó una credencial de API',
        'empresa.api-keys.regenerate' => 'Generó un secret nuevo para su credencial',
        'empresa.api-keys.destroy' => 'Eliminó una credencial de API',
        'empresa.api-keys.toggle' => 'Bloqueó o desbloqueó una credencial de API',

        'empresa.consulta-cpe.consultar' => 'Consultó un comprobante en SUNAT',
        'empresa.sunat-config.test' => 'Probó la conexión con SUNAT',
        'empresa.sunat-config.go-production' => 'Pasó su facturación a producción',
        'empresa.usuarios.store' => 'Creó un usuario de su empresa',

        'empresa.rendimiento.configuracion' => 'Guardó la configuración de metas y comisiones',
        'empresa.rendimiento.comisiones.actualizar' => 'Actualizó el estado de una comisión',
        'empresa.rendimiento.comisiones.restablecer' => 'Eliminó la configuración de metas y comisiones',
        'empresa.rendimiento.comisiones.historial.limpiar' => 'Eliminó el historial de comisiones',

        'empresa.branches.store' => 'Creó una sucursal',
        'empresa.branches.update' => 'Modificó una sucursal',
        'empresa.branches.destroy' => 'Eliminó una sucursal',
        'empresa.branches.toggle' => 'Activó o desactivó una sucursal',
        'empresa.clients.store' => 'Creó un cliente',
        'empresa.clients.update' => 'Modificó un cliente',
        'empresa.clients.destroy' => 'Eliminó un cliente',
        'empresa.correlatives.store' => 'Creó un correlativo',
        'empresa.correlatives.destroy' => 'Eliminó un correlativo',
        'empresa.company.update' => 'Modificó los datos de la empresa',
        'empresa.profile.update' => 'Modificó su perfil',
        'empresa.usuarios.update' => 'Modificó un usuario de su empresa',
        'empresa.usuarios.toggle-active' => 'Activó o bloqueó un usuario de su empresa',
        'empresa.notas-credito.store' => 'Creó una nota de crédito',
        'empresa.notas-credito.send-sunat' => 'Envió una nota de crédito a SUNAT',
        'empresa.notas-debito.store' => 'Creó una nota de débito',
        'empresa.notas-debito.send-sunat' => 'Envió una nota de débito a SUNAT',

        'super-admin.users.reset-password' => 'Cambió la contraseña de un usuario',
        'super-admin.users.toggle-lock' => 'Bloqueó o liberó el acceso de un usuario',
        'super-admin.support.reply' => 'Respondió a un ticket de soporte',
        'super-admin.support.reopen' => 'Reabrió un ticket de soporte',

        'super-admin.consultas.probar' => 'Probó una consulta de RUC o DNI',
        'super-admin.consultas.apis.guardar' => 'Guardó un proveedor de consultas',
        'super-admin.consultas.apis.borrar' => 'Eliminó un proveedor de consultas',
        'super-admin.consultas.llaves.actualizar' => 'Modificó una API Key de RUC y DNI',
        'super-admin.consultas.cache.vaciar' => 'Vació la caché de consultas',
        'super-admin.padron.probar' => 'Probó una búsqueda en el padrón',
        'super-admin.consultas.acceso.crear' => 'Creó el acceso de un cliente al panel de RUC y DNI',
        'super-admin.consultas.acceso.clave' => 'Generó una contraseña nueva para el panel de RUC y DNI',
        'super-admin.consultas.acceso.quitar' => 'Eliminó el acceso de un cliente al panel de RUC y DNI',
        'super-admin.consultas.acceso.entrar' => 'Entró como soporte al panel del cliente de RUC y DNI',
        'super-admin.consultas.llaves.limpiar-vencidas' => 'Eliminó las API Keys de RUC y DNI vencidas',

        'empresa.facturas.send-sunat' => 'Envió una factura a SUNAT',
        'empresa.boletas.send-sunat' => 'Envió una boleta a SUNAT',
        'empresa.guias.registrar-baja' => 'Registró la baja de una guía de remisión',
        'empresa.guias.send-sunat' => 'Envió una guía de remisión a SUNAT',
        'empresa.guias.check-status' => 'Consultó el estado de una guía de remisión',
        'empresa.anulaciones.check-status' => 'Consultó el estado de una anulación',
        'empresa.resumenes.check-status' => 'Consultó el estado de un resumen diario',
        'empresa.notifications.read-all' => 'Marcó todas sus notificaciones como leídas',
        'empresa.profile.two-factor' => 'Cambió la verificación en dos pasos de su cuenta',
        'empresa.support.reply' => 'Respondió a un ticket de soporte',
        'empresa.support.reopen' => 'Reabrió un ticket de soporte',
    ];

    /** Que se hizo, por el ultimo trozo del nombre de la ruta. */
    private const VERBOS = [
        'store' => 'Creó',
        'create' => 'Creó',
        'update' => 'Modificó',
        'edit' => 'Modificó',
        'destroy' => 'Eliminó',
        'delete' => 'Eliminó',
        'borrar' => 'Eliminó',
        'toggle' => 'Activó o desactivó',
        'alternar' => 'Activó o desactivó',
        'renew' => 'Renovó',
        'regenerar' => 'Generó de nuevo',
        'guardar' => 'Guardó',
        'actualizar' => 'Actualizó',
        'importar' => 'Importó',
        'close' => 'Cerró',
        'confirm' => 'Confirmó',
    ];

    /** Sobre que, por el trozo del medio. */
    private const COSAS = [
        'companies' => 'una empresa',
        'users' => 'un usuario',
        'plans' => 'un plan',
        'subscriptions' => 'una suscripción',
        'payments' => 'un pago',
        'settings' => 'la configuración',
        'support' => 'un ticket de soporte',
        'certificates' => 'un certificado',
        'certificados' => 'un certificado',
        'tokens-prueba' => 'un token de sandbox',
        'api-global' => 'una credencial de API',
        'api-keys' => 'una credencial de API',
        'consultas' => 'RUC y DNI',
        'padron' => 'el padrón de SUNAT',
        'clients' => 'un cliente',
        'branches' => 'una sucursal',
        'correlatives' => 'un correlativo',
        'company' => 'los datos de la empresa',
        'profile' => 'su perfil',
        'notifications' => 'sus avisos',
        'anulaciones' => 'una anulación',
        'resumenes' => 'un resumen',
        'facturas' => 'una factura',
        'boletas' => 'una boleta',
        'guias' => 'una guía de remisión',
        'notas' => 'una nota',
        'notas-credito' => 'una nota de crédito',
        'notas-debito' => 'una nota de débito',
        'contactos' => 'una solicitud de contacto',
        'rendimiento' => 'las metas y comisiones',
        'comisiones' => 'las comisiones',
        'sunat-config' => 'la configuración de SUNAT',
    ];

    /**
     * En que pantalla del panel paso, con el nombre que se lee en el menu.
     *
     * La columna enseñaba la direccion y el nombre interno de la ruta
     * —«super-admin/settings» y «super-admin.settings.update»—, que no dicen
     * nada a quien no conoce el codigo.
     */
    private const MODULOS = [
        'settings' => 'Configuración',
        'api-global' => 'API Facturación',
        'tokens-prueba' => 'Sandbox Facturación',
        'consultas' => 'API RUC y DNI',
        'padron' => 'Padrón SUNAT',
        'companies' => 'Empresas',
        'users' => 'Usuarios',
        'usuarios' => 'Usuarios',
        'plans' => 'Planes',
        'subscriptions' => 'Suscripciones',
        'payments' => 'Pagos',
        'support' => 'Soporte',
        'audit' => 'Auditoría',
        'certificates' => 'Certificados',
        'certificados' => 'Certificados',
        'documents' => 'Documentos',
        'reports' => 'Reportes',
        'profile' => 'Mi perfil',
        'dashboard' => 'Dashboard',
        'impersonate' => 'Sesión de soporte',
        'api-keys' => 'API Keys',
        'clients' => 'Clientes',
        'branches' => 'Sucursales',
        'correlatives' => 'Correlativos',
        'company' => 'Mi Empresa',
        'sunat-config' => 'Config. SUNAT',
        'consulta-cpe' => 'Consulta CPE',
        'anulaciones' => 'Anular comprobantes',
        'resumenes' => 'Resúmenes',
        'facturas' => 'Comprobantes',
        'boletas' => 'Comprobantes',
        'notas' => 'Comprobantes',
        'notas-credito' => 'Comprobantes',
        'notas-debito' => 'Comprobantes',
        'contactos' => 'Contactos',
        'guias' => 'Comprobantes',
        'notifications' => 'Avisos',
        'rendimiento' => 'Ventas por usuario',
    ];

    /** Nombres legibles para modelos que puedan aparecer en rutas nuevas. */
    private const SUJETOS = [
        'User' => 'un usuario',
        'Company' => 'una empresa',
        'Client' => 'un cliente',
        'Branch' => 'una sucursal',
        'Correlative' => 'un correlativo',
        'Document' => 'un comprobante',
        'Invoice' => 'una factura',
        'Boleta' => 'una boleta',
        'CreditNote' => 'una nota de crédito',
        'DebitNote' => 'una nota de débito',
        'DispatchGuide' => 'una guía de remisión',
        'SupportTicket' => 'un ticket de soporte',
        'Subscription' => 'una suscripción',
        'Payment' => 'un pago',
        'ApiKey' => 'una credencial de API',
        'ConsultaLlave' => 'una API Key de RUC y DNI',
    ];

    /** La pantalla donde paso, tal como se llama en el menu. */
    public static function modulo(AuditLog $log): string
    {
        $ruta = (string) ($log->route_name ?: $log->path);
        $piezas = preg_split('#[./]#', $ruta) ?: [];

        foreach ($piezas as $pieza) {
            if (isset(self::MODULOS[$pieza])) {
                return self::MODULOS[$pieza];
            }
        }

        return 'Panel';
    }

    /** Si lo hizo el dueño desde su panel, o el Super Admin desde el suyo. */
    public static function panel(AuditLog $log): string
    {
        return str_starts_with((string) $log->route_name, 'empresa.')
            ? 'Panel de la empresa'
            : 'Panel del Super Admin';
    }

    /**
     * Las rutas cuya frase contiene ese texto.
     *
     * El registro enseña frases pero la base guarda nombres de ruta: sin esto,
     * buscar «secret» —que es lo que se lee en pantalla— no encontraba nada.
     *
     * @return list<string>
     */
    public static function rutasQueDicen(string $texto): array
    {
        $texto = mb_strtolower(trim($texto));

        if ($texto === '') {
            return [];
        }

        $coinciden = array_filter(
            self::FRASES,
            fn (string $frase) => str_contains(mb_strtolower($frase), $texto)
        );

        foreach (self::MODULOS as $pieza => $nombre) {
            if (str_contains(mb_strtolower($nombre), $texto)) {
                $coinciden['__' . $pieza] = $nombre;
            }
        }

        return array_keys($coinciden);
    }

    /** La frase que se enseña en el registro. */
    public static function describir(AuditLog $log): string
    {
        $ruta = $log->route_name;

        if ($ruta && isset(self::FRASES[$ruta])) {
            return self::FRASES[$ruta];
        }

        // Entrar y salir de soporte se guardan con su propia accion, no con el
        // metodo HTTP, asi que se reconocen por ahi aunque cambie la ruta.
        if ($log->action === 'impersonate_start') {
            return self::FRASES['super-admin.companies.impersonate'];
        }
        if ($log->action === 'impersonate_stop') {
            return self::FRASES['impersonate.stop'];
        }

        if (! $ruta) {
            return self::porMetodo($log) . ' algo en ' . ($log->path ?: 'el panel');
        }

        // Sin frase propia: se arma con las piezas del nombre de la ruta,
        // «super-admin.clients.store» -> «Creó un cliente».
        $piezas = array_values(array_diff(explode('.', $ruta), ['super-admin', 'empresa']));
        $verbo = self::VERBOS[end($piezas)] ?? self::porMetodo($log);
        $cosa = null;

        foreach ($piezas as $pieza) {
            if (isset(self::COSAS[$pieza])) {
                $cosa = self::COSAS[$pieza];
                break;
            }
        }

        if (! $cosa && $log->subject_type) {
            $subject = class_basename((string) $log->subject_type);
            $cosa = self::SUJETOS[$subject] ?? null;
        }

        return $cosa
            ? $verbo . ' ' . $cosa
            : $verbo . ' una configuración del sistema';
    }

    /** Cuando no hay nada mejor, al menos que no salga en ingles. */
    private static function porMetodo(AuditLog $log): string
    {
        return match ($log->action) {
            'update' => 'Modificó',
            'delete' => 'Eliminó',
            default => 'Ejecutó una acción sobre',
        };
    }

    /**
     * Como acabo, en cristiano.
     *
     * La columna ponia «HTTP 302», que es el codigo con el que el servidor
     * devuelve a la pantalla despues de guardar: quiere decir que salio bien,
     * pero hay que saberlo. Y «HTTP 422» tampoco dice que lo que fallo fue lo
     * que se escribio en el formulario, no el sistema.
     *
     * @return array{texto: string, tono: string, detalle: string}
     */
    public static function resultado(AuditLog $log): array
    {
        $codigo = (int) $log->response_status;

        return match (true) {
            $codigo === 0 => ['texto' => 'Sin registrar', 'tono' => 'text-gray-400',
                'detalle' => 'No se anotó cómo acabó.'],
            $codigo < 400 => ['texto' => 'Correcto', 'tono' => 'text-green-700',
                'detalle' => 'La acción se completó y se guardó.'],
            $codigo === 401 || $codigo === 403 => ['texto' => 'Sin permiso', 'tono' => 'text-amber-700',
                'detalle' => 'No tenía permiso para hacerlo: no se hizo nada.'],
            $codigo === 404 => ['texto' => 'No encontrado', 'tono' => 'text-amber-700',
                'detalle' => 'Lo que se quería tocar ya no existe.'],
            $codigo === 419 => ['texto' => 'Sesión caducada', 'tono' => 'text-amber-700',
                'detalle' => 'La sesión había caducado y hubo que entrar de nuevo.'],
            $codigo === 422 => ['texto' => 'Datos rechazados', 'tono' => 'text-amber-700',
                'detalle' => 'Faltaba algo en el formulario o estaba mal escrito: no se guardó.'],
            $codigo === 429 => ['texto' => 'Demasiados intentos', 'tono' => 'text-amber-700',
                'detalle' => 'Se pidió demasiadas veces seguidas y el sistema cortó.'],
            $codigo >= 500 => ['texto' => 'Error del sistema', 'tono' => 'text-red-700',
                'detalle' => 'Falló el sistema, no la persona. Conviene mirarlo.'],
            default => ['texto' => 'No se completó', 'tono' => 'text-red-700',
                'detalle' => 'La acción no llegó a hacerse.'],
        };
    }

    /**
     * Si la accion se hizo con una sesion de soporte abierta. Queda en la
     * descripcion, que es donde el middleware anota quien estaba detras.
     */
    public static function enSoporte(AuditLog $log): bool
    {
        return str_starts_with((string) $log->description, '[SOPORTE:');
    }

    /** El nombre del Super Admin que estaba detras, en modo soporte. */
    public static function quienEstabaDetras(AuditLog $log): ?string
    {
        if (! self::enSoporte($log)) {
            return null;
        }

        return preg_match('/^\[SOPORTE: (.+?)\]/', (string) $log->description, $m) ? $m[1] : null;
    }

    /** Recupera al actor de registros anteriores a actor_user_id. */
    private static function actorAnterior(AuditLog $log): ?\App\Models\User
    {
        if (! self::enSoporte($log)) {
            return null;
        }

        $id = (int) data_get($log->data, '_impersonador_id', 0);
        if ($id <= 0) {
            return null;
        }

        return self::$actoresAnteriores[$id] ??= \App\Models\User::find($id);
    }
}
