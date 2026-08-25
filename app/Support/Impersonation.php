<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * Estado de la sesión de soporte: cuando el Super Admin "entra como" el
 * administrador de una empresa, se guarda en sesión quién es el suplantador
 * para poder volver a su propia cuenta.
 */
class Impersonation
{
    /** Id del Super Admin que inició la suplantación. */
    public const KEY_ID = 'impersonator_id';

    /** Nombre del Super Admin, para el aviso en pantalla. */
    public const KEY_NAME = 'impersonator_name';

    /** Momento en que empezó, para mostrarlo y para el registro de auditoría. */
    public const KEY_STARTED = 'impersonator_started_at';

    public static function activa(): bool
    {
        return Session::has(self::KEY_ID);
    }

    public static function idSuplantador(): ?int
    {
        return Session::get(self::KEY_ID);
    }

    public static function nombreSuplantador(): ?string
    {
        return Session::get(self::KEY_NAME);
    }

    public static function iniciar(User $superAdmin): void
    {
        Session::put(self::KEY_ID, $superAdmin->id);
        Session::put(self::KEY_NAME, $superAdmin->name);
        Session::put(self::KEY_STARTED, now()->toDateTimeString());
    }

    public static function terminar(): void
    {
        Session::forget([self::KEY_ID, self::KEY_NAME, self::KEY_STARTED]);
    }
}
