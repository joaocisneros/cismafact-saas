<?php

namespace App\Traits;

use App\Jobs\EnviarDocumentoASunat;
use Illuminate\Http\Request;

/**
 * Si el comprobante se manda esperando a SUNAT o por detras.
 *
 * Esperar deja el proceso ocupado lo que tarde SUNAT —de dos segundos a media
 * hora— y son los mismos procesos que sirven el panel: un cliente cargando su
 * dia entero los ocupa todos. En segundo plano se responde en milisegundos y
 * el envio va por su cuenta.
 *
 * Lo decide la credencial, con «async» en la peticion como atajo para probarlo
 * sin cambiar el ajuste. Lo que no se hace es cambiarlo para todos: quien ya
 * integro lee el CDR en la respuesta de emitir y dejaria de llegarle.
 */
trait DecideElEnvioASunat
{
    protected function envioEnSegundoPlano(Request $request): bool
    {
        if ($request->boolean('async')) {
            return true;
        }

        $credencial = $request->attributes->get('api_key');

        return (bool) ($credencial?->emitir_async);
    }

    /**
     * Encola el envio y devuelve la respuesta de «recibido».
     *
     * 202 y no 201: el comprobante esta guardado, pero lo que se pidio —que
     * SUNAT lo tenga— todavia no ha pasado. Quien integra lo distingue por el
     * codigo sin mirar el cuerpo.
     */
    protected function respuestaEncolada($documento, string $tipo, string $nombre)
    {
        EnviarDocumentoASunat::dispatch($tipo, $documento->id);

        return response()->json([
            'success' => true,
            'data' => $documento,
            'message' => "{$nombre} registrada. Se está enviando a SUNAT; consulta su estado en unos segundos.",
        ], 202);
    }
}
