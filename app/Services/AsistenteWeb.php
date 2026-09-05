<?php

namespace App\Services;

use App\Models\ApiPlan;
use App\Models\Plan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * El asistente que responde en la web, contra OpenRouter.
 *
 * Los modelos gratuitos se agotan por cuota a lo largo del dia, asi que se
 * intentan en cadena: en cuanto uno dice que no —por cuota, por caida o porque
 * lo retiraron— se pasa al siguiente. Al visitante no le llega ese detalle.
 *
 * Lo que sabe sale de la propia base de datos, no de un texto escrito a mano:
 * si se sube un plan de S/49 a S/59, el asistente lo dice bien al minuto
 * siguiente sin que nadie se acuerde de venir a cambiarlo aqui.
 */
class AsistenteWeb
{
    /** Lo que responde cuando han fallado todos los modelos. */
    private const SIN_MODELOS = 'Ahora mismo no puedo responderte. '
        . 'Escríbenos por WhatsApp y te atendemos al momento.';

    public function disponible(): bool
    {
        return filled(config('asistente.clave')) && filled(config('asistente.modelos'));
    }

    /**
     * Responde a lo ultimo que escribio el visitante.
     *
     * @param  array  $historial  Turnos previos: [['rol' => 'usuario'|'asistente', 'texto' => '...']]
     * @return array{texto: string, modelo: ?string, agotado: bool}
     */
    public function responder(string $pregunta, array $historial = []): array
    {
        if (! $this->disponible()) {
            return ['texto' => self::SIN_MODELOS, 'modelo' => null, 'agotado' => true];
        }

        $mensajes = array_merge(
            [['role' => 'system', 'content' => $this->contexto()]],
            $this->turnosPrevios($historial),
            [['role' => 'user', 'content' => $pregunta]],
        );

        foreach (config('asistente.modelos') as $modelo) {
            $respuesta = $this->preguntarA($modelo, $mensajes);

            if ($respuesta !== null) {
                return ['texto' => $respuesta, 'modelo' => $modelo, 'agotado' => false];
            }
        }

        // Han caido todos: se avisa, porque si pasa a diario hay que revisar la
        // lista de modelos —los gratuitos de OpenRouter van y vienen—.
        Log::warning('Asistente web: ningún modelo respondió', [
            'modelos' => config('asistente.modelos'),
        ]);

        return ['texto' => self::SIN_MODELOS, 'modelo' => null, 'agotado' => true];
    }

    /**
     * Un modelo concreto. Devuelve null si no responde, para pasar al siguiente.
     */
    private function preguntarA(string $modelo, array $mensajes): ?string
    {
        try {
            $respuesta = Http::withToken(config('asistente.clave'))
                ->withHeaders([
                    // OpenRouter los usa para atribuir el trafico a la web.
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => config('app.name'),
                ])
                ->timeout(config('asistente.timeout'))
                ->post(config('asistente.url'), [
                    'model' => $modelo,
                    'messages' => $mensajes,
                    // Corto a proposito: es un globo de chat, no un articulo.
                    'max_tokens' => 400,
                    'temperature' => 0.3,
                ]);

            if ($respuesta->failed()) {
                Log::info('Asistente web: modelo descartado', [
                    'modelo' => $modelo,
                    'estado' => $respuesta->status(),
                ]);

                return null;
            }

            $texto = trim((string) $respuesta->json('choices.0.message.content', ''));

            // Un 200 con el texto vacio pasa con algunos gratuitos cuando estan
            // saturados: cuenta como que no respondio.
            return $texto !== '' ? $texto : null;
        } catch (\Throwable $e) {
            Log::info('Asistente web: modelo caído', [
                'modelo' => $modelo,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Los ultimos turnos, en el formato que espera la API. */
    private function turnosPrevios(array $historial): array
    {
        $cuantos = config('asistente.limites.historial');

        return collect($historial)
            ->slice(-$cuantos)
            ->map(fn ($t) => [
                'role' => ($t['rol'] ?? '') === 'asistente' ? 'assistant' : 'user',
                'content' => mb_substr((string) ($t['texto'] ?? ''), 0, 1000),
            ])
            ->filter(fn ($t) => $t['content'] !== '')
            ->values()
            ->all();
    }

    /**
     * Lo que el asistente sabe y como debe comportarse.
     *
     * Se guarda diez minutos: son dos consultas a la base por cada visitante
     * que escribe, y los planes no cambian de un mensaje a otro.
     */
    private function contexto(): string
    {
        return Cache::remember('asistente.contexto', now()->addMinutes(10), function () {
            $facturacion = Plan::where('active', true)
                ->orderBy('monthly_price')
                ->get()
                ->map(fn ($p) => sprintf('%s: S/ %s al mes, %s comprobantes al mes',
                    $p->name,
                    number_format((float) $p->monthly_price, 2),
                    number_format((int) $p->monthly_document_limit)))
                ->implode('; ');

            $consultas = ApiPlan::with('apis')
                ->where('activo', true)
                ->orderBy('orden')
                ->get()
                ->filter(fn ($p) => $p->a_medida || (float) $p->precio_mensual > 0)
                ->map(function ($p) {
                    $porServicio = $p->apis
                        ->map(fn ($a) => sprintf('%s %s consultas por %s',
                            strtoupper($a->slug),
                            number_format((int) $a->pivot->limite_mensual),
                            $p->a_medida ? 'precio a convenir' : 'S/ ' . number_format((float) $a->pivot->precio_mensual, 2)))
                        ->implode(' y ');

                    return sprintf('%s: %s (los dos juntos %s)',
                        $p->nombre,
                        $porServicio,
                        $p->a_medida ? 'a convenir' : 'S/ ' . number_format((float) $p->precio_mensual, 2));
                })
                ->implode('; ');

            $whatsapp = config('asistente.whatsapp');

            return <<<TEXTO
            Eres el asistente de Cisma Fact, un sistema peruano de facturación electrónica.
            Respondes en la página web a visitantes que todavía no son clientes.

            QUÉ ES CISMA FACT
            Dos servicios, que se contratan por separado:

            1. Facturación electrónica directo a SUNAT. Se emiten facturas, boletas, notas
               de crédito y débito, y guías de remisión electrónicas (GRE). Cada empresa
               firma con su propio certificado digital, sin intermediarios. Se usa desde la
               web o desde una API REST, así que se integra con cualquier sistema.
               También hay comunicación de baja, resumen diario y envío de los comprobantes
               por correo al cliente con su PDF, XML y CDR.
               Planes: {$facturacion}
               El plan gratuito es para evaluar el sistema en el ambiente de pruebas de
               SUNAT. Para emitir con validez tributaria hace falta uno de pago, registrar
               el certificado digital y la clave SOL. No digas que con el gratuito se
               factura de verdad.

            2. API de consultas de RUC y DNI. Devuelve, del RUC: razón social, estado,
               condición y domicilio fiscal. Del DNI: nombre y apellidos por separado.
               Cada consulta se contrata por separado, así que quien solo quiere RUC paga
               solo el RUC.
               Planes: {$consultas}
               Hay un entorno Sandbox gratuito para que los desarrolladores prueben.

            LO QUE NO EXISTE, NO LO OFREZCAS
            Esto es lo que hay, y no hay nada más. Si te preguntan por algo que no está en
            esta lista, di que no lo tenemos y pasa el WhatsApp. Nunca lo des por supuesto
            porque sea lo normal en otros sistemas:
            - Los planes son los de arriba y nada más. No hay pago por comprobante suelto,
              ni comprobantes extra sobre el plan, ni saldo recargable, ni descuento por
              pago anual, ni periodo de prueba de X días.
            - No hay app móvil, ni versión de escritorio, ni módulo de inventario, ni
              punto de venta, ni contabilidad. Es facturación y consultas.
            - No prometas plazos de alta, migraciones desde otro sistema, ni integraciones
              con programas concretos.
            - Si no estás seguro de que algo existe, no lo digas. Es preferible pasar el
              WhatsApp que prometer lo que luego no hay.

            CÓMO RESPONDES
            - En español peruano, claro y directo. Dos o tres frases; solo te alargas si te
              piden detalle.
            - De usted no: tutea, como el resto de la web.
            - Nada de markdown ni listas con asteriscos: el chat muestra texto llano.
            - Si no sabes algo, dilo y pasa el WhatsApp {$whatsapp}. Nunca te inventes
              precios, plazos, funciones ni nada sobre normativa de SUNAT.
            - No pides ni recoges datos personales, contraseñas ni números de tarjeta.
            - Si preguntan por su cuenta, una factura suya o algo de soporte, no puedes
              verlo: pasa el WhatsApp.
            - Si preguntan de algo que no es Cisma Fact ni facturación en Perú, di que solo
              puedes ayudar con eso y ofrece el WhatsApp.
            TEXTO;
        });
    }
}
