<?php

namespace App\Jobs;

use App\Services\DocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Manda un comprobante a SUNAT fuera de la peticion.
 *
 * Emitir tiene dos partes muy distintas: guardar el comprobante, que son unos
 * milisegundos, y esperar a que SUNAT conteste, que en lo emitido hasta ahora
 * ha ido de cero segundos a veintiseis minutos. Haciendolo todo en la peticion,
 * cada emision ocupa uno de los procesos del servidor todo ese rato, y son los
 * mismos procesos que sirven el panel: con un cliente soltando su carga del dia
 * se queda sin servicio todo el mundo.
 *
 * Asi se guarda y se responde enseguida, y el envio va por su cuenta. El
 * comprobante queda en PENDIENTE hasta que llega el CDR.
 *
 * Reintenta tres veces separandose cada vez mas: casi todo lo que falla al
 * hablar con SUNAT es momentaneo, y volver a intentarlo al instante suele
 * fallar igual.
 */
class EnviarDocumentoASunat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Un minuto, cinco y quince. */
    public array $backoff = [60, 300, 900];

    /** Media hora: lo que peor se ha portado SUNAT, con margen. */
    public int $timeout = 1800;

    public function __construct(
        private string $tipo,
        private int $documentoId,
    ) {
    }

    public function handle(DocumentService $documentos): void
    {
        $modelo = $this->modelo();

        if (! $modelo) {
            // Lo borraron mientras esperaba turno: no es un fallo.
            return;
        }

        // Ya aceptado: pudo enviarse a mano mientras esperaba en la cola.
        if ($modelo->estado_sunat === 'ACEPTADO') {
            return;
        }

        $resultado = $documentos->sendToSunat($modelo, $this->tipo);

        if (! ($resultado['success'] ?? false)) {
            // Que falle no siempre es para reintentar: si SUNAT lo rechaza por
            // los datos, mandarlo otra vez da el mismo rechazo. El servicio ya
            // ha dejado el motivo en el documento.
            Log::warning('SUNAT no aceptó el comprobante', [
                'tipo' => $this->tipo,
                'id' => $this->documentoId,
                'error' => $resultado['error'] ?? null,
            ]);
        }
    }

    /** El modelo que toca, segun el tipo. */
    private function modelo()
    {
        $clases = [
            'invoice' => \App\Models\Invoice::class,
            'boleta' => \App\Models\Boleta::class,
            'credit_note' => \App\Models\CreditNote::class,
            'debit_note' => \App\Models\DebitNote::class,
            'dispatch_guide' => \App\Models\DispatchGuide::class,
        ];

        $clase = $clases[$this->tipo] ?? null;

        return $clase ? $clase::find($this->documentoId) : null;
    }
}
