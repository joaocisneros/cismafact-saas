<?php

namespace App\Console\Commands;

use App\Services\PadronSunatService;
use Illuminate\Console\Command;

/**
 * Descarga e importa el padron reducido de SUNAT.
 *
 * Va como comando y no como trabajo en cola porque son millones de filas y
 * horas de proceso: no cabe en una peticion web, y este proyecto corre con
 * QUEUE_CONNECTION=sync, donde un trabajo en cola se ejecutaria dentro de la
 * propia peticion. Asi se puede lanzar a mano, por cron, o desde el panel
 * (que lo arranca suelto y luego mira la tabla de importaciones).
 */
class ActualizarPadron extends Command
{
    protected $signature = 'padron:actualizar';

    protected $description = 'Descarga el padrón reducido de SUNAT y lo importa';

    public function handle(PadronSunatService $padron): int
    {
        $empezo = microtime(true);

        try {
            $filas = $padron->actualizar(fn (string $aviso) => $this->line('  ' . $aviso));
        } catch (\Throwable $e) {
            $this->error('No se pudo actualizar el padrón: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Padrón actualizado: %s RUC en %s.',
            number_format($filas),
            $this->duracion(microtime(true) - $empezo),
        ));

        return self::SUCCESS;
    }

    private function duracion(float $segundos): string
    {
        if ($segundos < 60) {
            return round($segundos) . ' s';
        }

        $minutos = floor($segundos / 60);

        return $minutos < 60
            ? $minutos . ' min'
            : floor($minutos / 60) . ' h ' . ($minutos % 60) . ' min';
    }
}
