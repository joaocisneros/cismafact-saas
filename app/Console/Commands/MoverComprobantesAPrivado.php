<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Traslada los comprobantes ya emitidos de la carpeta publica a la privada.
 *
 * Hace falta una sola vez en cada entorno que venia de antes: el codigo nuevo
 * los busca en storage/app/comprobantes, y los que ya existian se quedaron en
 * storage/app/public, donde ademas cualquiera podia descargarlos por URL.
 * Volver a ejecutarlo no rompe nada: solo mueve lo que encuentre.
 */
class MoverComprobantesAPrivado extends Command
{
    protected $signature = 'comprobantes:mover-a-privado {--forzar : Sobrescribe los archivos que ya existan en destino}';

    protected $description = 'Mueve XML, CDR, PDF y el certificado compartido fuera de la carpeta publica';

    /** Carpetas que genera FileService, una por tipo de comprobante. */
    private const CARPETAS = [
        'facturas', 'boletas', 'notas-credito', 'notas-debito', 'guias-remision',
        'percepciones', 'retenciones', 'resumenes-diarios', 'otros-comprobantes',
    ];

    public function handle(): int
    {
        $origen = storage_path('app/public');
        $destino = storage_path('app/comprobantes');

        $this->info('Moviendo comprobantes a la carpeta privada...');
        $movidos = 0;

        foreach (self::CARPETAS as $carpeta) {
            $movidos += $this->moverCarpeta("{$origen}/{$carpeta}", "{$destino}/{$carpeta}", $carpeta);
        }

        // El .pem compartido lleva la clave privada dentro: nunca en publico.
        $certOrigen = "{$origen}/certificado/certificado.pem";
        $certDestino = storage_path('app/private/certificado/certificado.pem');

        if (is_file($certOrigen)) {
            $this->crearDirectorio(dirname($certDestino));

            if (is_file($certDestino) && ! $this->option('forzar')) {
                $this->line('  certificado: ya estaba en privado, se borra la copia publica');
                @unlink($certOrigen);
            } elseif (@rename($certOrigen, $certDestino)) {
                $this->line('  certificado: movido a app/private/certificado/');
                $movidos++;
            } else {
                $this->error('  certificado: no se pudo mover, revisa permisos');
            }

            @rmdir("{$origen}/certificado");
        }

        $this->newLine();
        $this->info($movidos > 0
            ? "Listo: {$movidos} archivo(s) fuera del alcance de la web."
            : 'No habia nada que mover: ya estaba todo en su sitio.');

        return self::SUCCESS;
    }

    /** Mueve una carpeta entera conservando la estructura de subdirectorios. */
    private function moverCarpeta(string $origen, string $destino, string $nombre): int
    {
        if (! is_dir($origen)) {
            return 0;
        }

        $movidos = 0;

        $archivos = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($origen, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($archivos as $archivo) {
            $relativo = substr($archivo->getPathname(), strlen($origen) + 1);
            $rutaDestino = "{$destino}/" . str_replace(DIRECTORY_SEPARATOR, '/', $relativo);

            if ($archivo->isDir()) {
                $this->crearDirectorio($rutaDestino);
                continue;
            }

            $this->crearDirectorio(dirname($rutaDestino));

            if (is_file($rutaDestino) && ! $this->option('forzar')) {
                continue;
            }

            if (@rename($archivo->getPathname(), $rutaDestino)) {
                $movidos++;
            }
        }

        $this->borrarVacias($origen);
        $this->line(sprintf('  %-20s %d archivo(s)', $nombre, $movidos));

        return $movidos;
    }

    private function crearDirectorio(string $ruta): void
    {
        if (! is_dir($ruta)) {
            @mkdir($ruta, 0755, true);
        }
    }

    /** Deja limpia la carpeta publica una vez vaciada. */
    private function borrarVacias(string $ruta): void
    {
        if (! is_dir($ruta)) {
            return;
        }

        foreach (array_diff(scandir($ruta), ['.', '..']) as $hijo) {
            $this->borrarVacias("{$ruta}/{$hijo}");
        }

        @rmdir($ruta);
    }
}
