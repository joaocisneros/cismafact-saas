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
        'super-admin.plans.toggle' => 'Activó o desactivó un plan',
        'super-admin.support.close' => 'Cerró un ticket de soporte',
        'super-admin.payments.confirm' => 'Confirmó un pago',
        'super-admin.payments.refund' => 'Devolvió un pago',
        'super-admin.padron.importar' => 'Importó el padrón de SUNAT',

        'empresa.api-keys.store' => 'Creó una credencial de API',
        'empresa.api-keys.regenerate' => 'Generó un secret nuevo para su credencial',
        'empresa.api-keys.destroy' => 'Eliminó una credencial de API',
        'empresa.api-keys.toggle' => 'Bloqueó o desbloqueó una credencial de API',

        'empresa.consulta-cpe.consultar' => 'Consultó un comprobante en SUNAT',
        'empresa.sunat-config.test' => 'Probó la conexión con SUNAT',
        'empresa.sunat-config.go-production' => 'Pasó su facturación a producción',
        'empresa.usuarios.store' => 'Creó un usuario de su empresa',

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
        'guias' => 'Comprobantes',
        'notifications' => 'Avisos',
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

        return $cosa ? $verbo . ' ' . $cosa : $verbo . ' algo';
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
}
