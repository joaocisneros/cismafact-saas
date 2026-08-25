<?php

namespace App\Traits;

/**
 * Convierte en texto legible el error que devuelve DocumentService::sendToSunat.
 *
 * Ese error no siempre es una excepcion: en la mayoria de los casos es un
 * stdClass con las propiedades 'code' y 'message' (ver el catch de
 * DocumentService y la rama de rechazo). Las versiones anteriores de este
 * metodo solo miraban method_exists($error, 'getMessage'), asi que con un
 * stdClass caian siempre en "Error desconocido." y se perdia el motivo real
 * del rechazo de SUNAT.
 */
trait FormatsSunatError
{
    protected function errorText($error): string
    {
        if ($error === null) {
            return 'Error desconocido.';
        }

        if (is_string($error)) {
            return $error !== '' ? $error : 'Error desconocido.';
        }

        if (is_array($error)) {
            $error = (object) $error;
        }

        if (! is_object($error)) {
            return 'Error desconocido.';
        }

        $mensaje = null;
        $codigo = null;

        if (method_exists($error, 'getMessage')) {
            $mensaje = $error->getMessage();
        } elseif (isset($error->message)) {
            $mensaje = $error->message;
        } elseif (isset($error->description)) {
            $mensaje = $error->description;
        }

        if (method_exists($error, 'getCode')) {
            $codigo = $error->getCode();
        } elseif (isset($error->code)) {
            $codigo = $error->code;
        }

        $mensaje = is_scalar($mensaje) ? trim((string) $mensaje) : '';
        $codigo = is_scalar($codigo) ? trim((string) $codigo) : '';

        if ($mensaje === '') {
            return $codigo !== ''
                ? "SUNAT devolvio el codigo {$codigo} sin descripcion."
                : 'Error desconocido.';
        }

        // El codigo de SUNAT (2335, 0160, ...) es lo que permite buscar la causa,
        // asi que se antepone. 'EXCEPTION' y 'UNKNOWN' son etiquetas internas de
        // DocumentService, no codigos de SUNAT: esas no se muestran.
        $internos = ['EXCEPTION', 'UNKNOWN', '0', ''];

        if (! in_array($codigo, $internos, true) && ! str_contains($mensaje, $codigo)) {
            return "[{$codigo}] {$mensaje}";
        }

        return $mensaje;
    }
}
