<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * La auditoría completa: lo que se hizo en el panel y también quién entró y
 * qué se pidió por las APIs.
 *
 * Antes solo se veían los cambios hechos desde el panel. Los inicios de
 * sesión, cada petición a la API de facturación y cada consulta de RUC o DNI
 * ya se guardaban, pero cada uno en su tabla y en su pantalla, así que para
 * saber en qué momento entró alguien con su correo, o si usaba sandbox o
 * producción, había que ir mirando cuatro sitios.
 *
 * No se copian a audit_logs: cada petición a la API quedaría guardada dos
 * veces. Se leen de donde ya están y se juntan al pintar la página, de modo
 * que también aparece todo lo anterior a esta pantalla.
 *
 * Cada fuente se filtra y se corta por su cuenta antes de juntarlas: así cada
 * una usa su índice por fecha y la unión nunca lee más filas de las que caben
 * en la página que se pide, por grande que llegue a ser api_usage.
 */
class AuditoriaUnificada
{
    public const FUENTES = [
        'panel' => 'Acciones en el panel',
        'acceso' => 'Inicios de sesión',
        'api_facturacion' => 'API Facturación',
        'api_consultas' => 'API RUC y DNI',
    ];

    public const ENTORNOS = [
        'produccion' => 'Producción',
        'sandbox' => 'Sandbox',
        'beta' => 'Beta',
    ];

    /** Las columnas que dan todas las fuentes, en el mismo orden. */
    private const COLUMNAS = [
        'id', 'user_id', 'actor_user_id', 'actor_name', 'actor_email', 'target_email',
        'target_name', 'company_id', 'action', 'method', 'route_name', 'path',
        'subject_type', 'subject_id', 'description', 'service_context',
        'environment_context', 'event_type', 'data', 'ip_address', 'user_agent',
        'response_status', 'created_at', 'fuente',
    ];

    /**
     * @param  array{user_id?:?int,company_id?:?int,action?:?string,date_from?:?string,date_to?:?string,search?:?string,fuente?:?string,entorno?:?string}  $filtros
     * @param  int  $tope  filas que necesita la página pedida, contando la de «hay más»
     */
    public static function consulta(array $filtros, int $tope): EloquentBuilder
    {
        $fuentes = collect([
            'panel' => fn () => self::panel($filtros),
            'acceso' => fn () => self::accesos(),
            'api_facturacion' => fn () => self::apiFacturacion(),
            'api_consultas' => fn () => self::apiConsultas(),
        ])
            ->when(! empty($filtros['fuente']), fn ($c) => $c->only($filtros['fuente']))
            // El filtro de acción solo existe en el panel, y «Responsable» es
            // una persona: la API de facturación entra con una credencial de
            // empresa, no con un usuario, así que ahí no hay a quién buscar.
            ->when(! empty($filtros['action']), fn ($c) => $c->only('panel'))
            ->when(! empty($filtros['user_id']), fn ($c) => $c->except('api_facturacion'));

        $partes = $fuentes->map(function ($fuente, $nombre) use ($filtros, $tope) {
            $consulta = $fuente();

            // El panel ya filtra con sus propias reglas; el resto, sobre las
            // columnas comunes.
            if ($nombre !== 'panel') {
                $consulta = self::filtrosComunes(DB::query()->fromSub($consulta, 'f'), $filtros);
            } elseif (! empty($filtros['entorno'])) {
                $consulta->where('environment_context', $filtros['entorno']);
            }

            return $consulta->orderByDesc('created_at')->limit($tope);
        })->values();

        if ($partes->isEmpty()) {
            return AuditLog::query()->whereRaw('1 = 0');
        }

        $union = $partes->shift();
        foreach ($partes as $parte) {
            $union->unionAll($parte);
        }

        return AuditLog::query()->fromSub($union, 'audit_logs');
    }

    private static function filtrosComunes(Builder $consulta, array $filtros): Builder
    {
        return $consulta
            ->when(! empty($filtros['user_id']), fn ($q) => $q->where('actor_user_id', $filtros['user_id']))
            ->when(! empty($filtros['company_id']), fn ($q) => $q->where('company_id', $filtros['company_id']))
            ->when(! empty($filtros['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filtros['date_from']))
            ->when(! empty($filtros['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filtros['date_to']))
            ->when(! empty($filtros['entorno']), fn ($q) => $q->where('environment_context', $filtros['entorno']))
            ->when(! empty($filtros['search']), function ($q) use ($filtros) {
                $texto = '%' . $filtros['search'] . '%';
                $q->where(fn ($w) => $w->where('actor_name', 'like', $texto)
                    ->orWhere('actor_email', 'like', $texto)
                    ->orWhere('target_name', 'like', $texto)
                    ->orWhere('path', 'like', $texto)
                    ->orWhere('event_type', 'like', $texto)
                    ->orWhere('ip_address', 'like', $texto));
            });
    }

    /** Lo de siempre: los cambios hechos desde el panel. */
    private static function panel(array $filtros): Builder
    {
        $query = DB::table('audit_logs')
            ->select(array_map(fn ($c) => "audit_logs.$c", array_slice(self::COLUMNAS, 0, -1)))
            ->selectRaw("'panel' as fuente");

        if (! empty($filtros['user_id'])) {
            $userId = (int) $filtros['user_id'];
            $query->where(function ($responsable) use ($userId) {
                $responsable->where('actor_user_id', $userId)
                    ->orWhere(function ($legacy) use ($userId) {
                        $legacy->whereNull('actor_user_id')
                            ->where(function ($legacyActor) use ($userId) {
                                $legacyActor->where('user_id', $userId)
                                    ->orWhere('data->_impersonador_id', $userId);
                            });
                    });
            });
        }

        if (! empty($filtros['company_id'])) {
            $query->where('company_id', $filtros['company_id']);
        }

        if (! empty($filtros['action'])) {
            $query->where('action', $filtros['action']);
        }

        if (! empty($filtros['date_from'])) {
            $query->whereDate('created_at', '>=', $filtros['date_from']);
        }

        if (! empty($filtros['date_to'])) {
            $query->whereDate('created_at', '<=', $filtros['date_to']);
        }

        if ($search = $filtros['search'] ?? null) {
            // Tambien por lo que se lee en pantalla: la frase y el nombre de
            // la pantalla se arman al pintarlos, asi que se traducen al reves
            // y se buscan las rutas que los dicen. Sin esto, buscar «secret» o
            // «Configuración» no daba ningun resultado.
            $rutas = AccionAuditada::rutasQueDicen($search);
            $piezas = collect($rutas)->filter(fn ($r) => str_starts_with($r, '__'))
                ->map(fn ($r) => substr($r, 2));
            $nombres = collect($rutas)->reject(fn ($r) => str_starts_with($r, '__'));

            $query->where(function ($auditQuery) use ($search, $nombres, $piezas) {
                $auditQuery->where('route_name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('actor_name', 'like', "%{$search}%")
                    ->orWhere('actor_email', 'like', "%{$search}%")
                    ->orWhere('target_email', 'like', "%{$search}%")
                    ->orWhere('target_name', 'like', "%{$search}%")
                    ->orWhere('event_type', 'like', "%{$search}%")
                    ->when($nombres->isNotEmpty(), fn ($q) => $q->orWhereIn('route_name', $nombres))
                    ->when($piezas->isNotEmpty(), fn ($q) => $piezas->each(
                        fn ($p) => $q->orWhere('route_name', 'like', "%{$p}%")
                    ));
            });
        }

        return $query;
    }

    /** Cada vez que alguien entró al panel con su correo, o lo intentó. */
    private static function accesos(): Builder
    {
        return DB::table('login_history as lh')
            ->leftJoin('users as u', 'u.id', '=', 'lh.user_id')
            ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
            ->selectRaw("
                lh.id, lh.user_id, lh.user_id as actor_user_id,
                coalesce(u.name, concat('Usuario eliminado #', lh.user_id)) as actor_name,
                u.email as actor_email, null as target_email, null as target_name,
                u.company_id, 'login' as action, 'POST' as method,
                case when lh.success = 1 then 'login.correcto' else 'login.fallido' end as route_name,
                'login' as path, null as subject_type, null as subject_id, null as description,
                'acceso' as service_context, null as environment_context,
                case r.name
                    when 'super_admin' then 'Super Admin'
                    when 'company_admin' then 'Administrador de empresa'
                    when 'company_user' then 'Usuario de empresa'
                    when 'cliente_consultas' then 'Cliente RUC y DNI'
                    else null
                end as event_type,
                null as data, lh.ip_address, lh.user_agent,
                case when lh.success = 1 then 200 else 401 end as response_status,
                lh.created_at, 'acceso' as fuente
            ");
    }

    /**
     * Cada petición a la API de facturación.
     *
     * La credencial no guarda correo propio: se enseña su nombre y el correo
     * de la empresa dueña, que es a quien hay que llamar si algo pasa.
     */
    private static function apiFacturacion(): Builder
    {
        return DB::table('api_usage as au')
            ->leftJoin('api_keys as k', 'k.id', '=', 'au.api_key_id')
            ->leftJoin('companies as c', 'c.id', '=', 'au.company_id')
            ->selectRaw("
                au.id, null as user_id, null as actor_user_id,
                coalesce(k.name, 'Credencial eliminada') as actor_name, c.email as actor_email,
                null as target_email, null as target_name, au.company_id,
                case when au.method = 'GET' then 'read' else 'create_or_action' end as action,
                au.method, 'api.facturacion' as route_name, au.path,
                null as subject_type, null as subject_id, null as description,
                'facturacion' as service_context,
                case
                    when k.origen = 'sandbox' or c.es_demo = 1 then 'sandbox'
                    when c.modo_produccion = 1 then 'produccion'
                    else 'beta'
                end as environment_context,
                case
                    when au.path like 'api/consultas/ruc%' then 'RUC'
                    when au.path like 'api/consultas/dni%' then 'DNI'
                    when au.path like '%facturas%' or au.path like '%invoices%' then 'Factura'
                    when au.path like '%boletas%' then 'Boleta'
                    when au.path like '%notas-credito%' or au.path like '%credit-notes%' then 'Nota de crédito'
                    when au.path like '%notas-debito%' or au.path like '%debit-notes%' then 'Nota de débito'
                    when au.path like '%guias-remision%' or au.path like '%dispatch-guides%' then 'Guía de remisión'
                    when au.path like '%resumenes%' then 'Resumen diario'
                    when au.path like '%anulaciones%' then 'Comunicación de baja'
                    else null
                end as event_type,
                null as data, au.ip_address, au.user_agent, au.status_code as response_status,
                au.created_at, 'api_facturacion' as fuente
            ");
    }

    /**
     * Cada consulta de RUC o DNI que salió por una API Key, y las que hizo
     * una empresa desde su panel al registrar clientes.
     *
     * Las internas sin empresa no se traen: son las pruebas del Super Admin,
     * que ya quedan en el panel como «Probó una consulta».
     */
    private static function apiConsultas(): Builder
    {
        return DB::table('consultas_consumo as cc')
            ->leftJoin('consulta_llaves as l', 'l.id', '=', 'cc.llave_id')
            ->leftJoin('users as u', 'u.id', '=', 'l.usuario_id')
            ->leftJoin('companies as c', 'c.id', '=', 'cc.company_id')
            ->where(fn ($q) => $q->where('cc.origen', 'externo')->orWhereNotNull('cc.company_id'))
            ->selectRaw("
                cc.id, l.usuario_id as user_id, l.usuario_id as actor_user_id,
                case when cc.llave_id is not null
                    then coalesce(l.titular, l.nombre, 'API Key eliminada')
                    else c.razon_social
                end as actor_name,
                case when cc.llave_id is not null
                    then coalesce(l.titular_email, u.email)
                    else c.email
                end as actor_email,
                null as target_email, cc.numero as target_name, cc.company_id,
                'read' as action, 'GET' as method,
                case when cc.origen = 'externo' then 'api.consultas' else 'api.consultas.interna' end as route_name,
                null as path, null as subject_type, null as subject_id, cc.motivo as description,
                'api_ruc_dni' as service_context,
                case
                    when l.entorno = 'produccion' then 'produccion'
                    when l.entorno = 'sandbox' then 'sandbox'
                    else null
                end as environment_context,
                upper(cc.tipo) as event_type, null as data, null as ip_address, null as user_agent,
                case when cc.exito = 1 then 200 else 404 end as response_status,
                cc.created_at, 'api_consultas' as fuente
            ");
    }
}
