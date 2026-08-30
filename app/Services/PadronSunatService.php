<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

/**
 * Copia local del padron reducido de SUNAT.
 *
 * QUE RESUELVE. Con el padron importado la consulta se responde desde la
 * propia base: sin limite, sin terceros, en milisegundos.
 *
 * QUE NO RESUELVE, PARA QUE NADIE SE LLEVE UNA SORPRESA. El archivo trae los
 * mismos campos que ya devuelve un revendedor —nombre, estado, condicion y
 * domicilio— y ninguno mas. La fecha de inscripcion, la actividad economica y
 * el tipo de contribuyente solo estan en la ficha web de SUNAT, detras de un
 * captcha. Esto da independencia, no informacion nueva.
 *
 * ENVEJECE Y HAY QUE ASUMIRLO. Es una foto del dia en que SUNAT publico el
 * archivo: un RUC inscrito despues no figura, y uno dado de baja ayer sigue
 * apareciendo como activo. Por eso la consulta al proveedor no desaparece:
 * esta tabla responde primero y el proveedor cubre lo que falte.
 *
 * POR QUE LA DESCARGA LLEVA CABECERAS DE NAVEGADOR. El servidor de SUNAT
 * rechaza clientes sin User-Agent conocido: devuelve una pagina de error en
 * vez del ZIP. No es evasion de nada —el archivo es publico y esta para
 * descargarse— sino un filtro tosco que hay que satisfacer.
 *
 * POR QUE SE IMPORTA A UNA TABLA APARTE Y LUEGO SE CAMBIA EL NOMBRE. Son horas
 * de trabajo sobre millones de filas. Si se escribiera encima de la tabla en
 * uso, durante todo ese rato las consultas verian un padron a medias.
 */
class PadronSunatService
{
    private const URL = 'https://www.sunat.gob.pe/descargaPRR/padron_reducido_ruc.zip';

    private const NAVEGADOR = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
        'Accept-Language' => 'es-ES,es;q=0.9',
        'Referer' => 'https://www.sunat.gob.pe/descargaPRR/mrc137_padron_reducido.html',
    ];

    /** El padron viene en latin-1 y separado por barras. */
    private const CODIFICACION = 'ISO-8859-1';

    /**
     * Filas por INSERT. 2000 mantiene el paquete lejos del max_allowed_packet
     * y la memoria plana, que importa cuando son millones de filas.
     */
    private const LOTE = 2000;

    /** Asi marca el padron los campos sin dato. */
    private const VACIOS = ['', '-', '--'];

    /** @return int Filas importadas. */
    public function actualizar(?callable $avisar = null): int
    {
        $avisar ??= fn () => null;

        $id = DB::table('padron_importaciones')->insertGetId([
            'estado' => 'descargando',
            'iniciada_en' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $avisar('Descargando el padrón de SUNAT…');
            $zip = $this->descargar($id);

            $avisar('Importando…');
            $this->marcar($id, ['estado' => 'importando']);
            $filas = $this->importar($zip, $id, $avisar);

            $this->marcar($id, [
                'estado' => 'completada',
                'filas' => $filas,
                'terminada_en' => now(),
                'mensaje' => '',
            ]);

            @unlink($zip);

            return $filas;
        } catch (\Throwable $e) {
            $this->marcar($id, [
                'estado' => 'fallida',
                'mensaje' => mb_substr($e->getMessage(), 0, 500),
                'terminada_en' => now(),
            ]);

            throw $e;
        }
    }

    private function descargar(int $id): string
    {
        $destino = storage_path('app/padron_' . $id . '.zip');

        $r = Http::withHeaders(self::NAVEGADOR)->timeout(900)->sink($destino)->get(self::URL);

        if (! $r->successful()) {
            throw new RuntimeException("SUNAT respondió {$r->status()} al pedir el padrón.");
        }

        $bytes = filesize($destino) ?: 0;

        // Cuando el filtro rechaza la peticion devuelve una pagina de error de
        // unos cientos de bytes, no un ZIP. Sin esta comprobacion el fallo
        // aparece mucho despues, al intentar abrirlo.
        if ($bytes < 1_000_000) {
            @unlink($destino);
            throw new RuntimeException(
                "La descarga solo trajo {$bytes} bytes: no es el padrón. "
                . 'SUNAT pudo rechazar la petición o cambiar la dirección del archivo.'
            );
        }

        $this->marcar($id, ['bytes_descargados' => $bytes]);

        return $destino;
    }

    private function importar(string $zip, int $id, callable $avisar): int
    {
        $archivo = new ZipArchive();

        if ($archivo->open($zip) !== true) {
            throw new RuntimeException('El archivo descargado no se pudo abrir como ZIP.');
        }

        $dentro = $archivo->getNameIndex(0);
        $flujo = $archivo->getStream($dentro);

        if (! $flujo) {
            throw new RuntimeException("No se pudo leer «{$dentro}» dentro del ZIP.");
        }

        // A una tabla aparte: la que esta en uso sigue respondiendo mientras
        // tanto, y solo se cambia al final, cuando la nueva esta completa.
        DB::statement('DROP TABLE IF EXISTS padron_ruc_nuevo');
        DB::statement('CREATE TABLE padron_ruc_nuevo LIKE padron_ruc');

        $lote = [];
        $filas = 0;
        fgets($flujo);   // la cabecera

        while (($linea = fgets($flujo)) !== false) {
            $fila = $this->fila($linea);

            if ($fila === null) {
                continue;
            }

            $lote[] = $fila;

            if (count($lote) >= self::LOTE) {
                DB::table('padron_ruc_nuevo')->insertOrIgnore($lote);
                $filas += count($lote);
                $lote = [];

                if ($filas % 100000 === 0) {
                    $this->marcar($id, ['filas' => $filas]);
                    $avisar(number_format($filas) . ' RUC importados…');
                }
            }
        }

        if ($lote) {
            DB::table('padron_ruc_nuevo')->insertOrIgnore($lote);
            $filas += count($lote);
        }

        fclose($flujo);
        $archivo->close();

        if ($filas === 0) {
            DB::statement('DROP TABLE IF EXISTS padron_ruc_nuevo');
            throw new RuntimeException('El archivo no traía ninguna fila utilizable.');
        }

        // El cambio de nombre es atomico: no hay ningun instante sin padron.
        DB::statement('DROP TABLE IF EXISTS padron_ruc_viejo');
        DB::statement('RENAME TABLE padron_ruc TO padron_ruc_viejo, padron_ruc_nuevo TO padron_ruc');
        DB::statement('DROP TABLE IF EXISTS padron_ruc_viejo');

        return $filas;
    }

    /**
     * Una linea del padron a fila de la tabla.
     *
     * Columnas del archivo, en orden:
     *   0 RUC  1 NOMBRE  2 ESTADO  3 CONDICION  4 UBIGEO  5 TIPO_VIA
     *   6 NOMBRE_VIA  7 COD_ZONA  8 TIPO_ZONA  9 NUMERO  10 INTERIOR
     *   11 LOTE  12 DEPARTAMENTO  13 MANZANA  14 KILOMETRO
     *
     * @return array<string,string|null>|null Null si la linea no sirve.
     */
    private function fila(string $linea): ?array
    {
        $c = explode('|', rtrim($linea, "\r\n"));

        if (count($c) < 5) {
            return null;
        }

        $ruc = $this->limpio($c[0]);

        if (strlen($ruc) !== 11 || ! ctype_digit($ruc)) {
            return null;
        }

        return [
            'ruc' => $ruc,
            'nombre' => mb_substr($this->limpio($c[1] ?? ''), 0, 255),
            'estado' => mb_substr($this->limpio($c[2] ?? ''), 0, 30),
            'condicion' => mb_substr($this->limpio($c[3] ?? ''), 0, 30),
            'ubigeo' => $this->limpio($c[4] ?? '') ?: null,
            'direccion' => mb_substr($this->direccion(array_slice($c, 5, 10)), 0, 255),
        ];
    }

    /** Junta las diez columnas del domicilio en una linea legible. */
    private function direccion(array $partes): string
    {
        $trozos = array_filter(array_map(fn ($p) => $this->limpio($p), $partes), fn ($p) => $p !== '');

        return trim(preg_replace('/\s+/', ' ', implode(' ', $trozos)));
    }

    private function limpio(string $valor): string
    {
        $valor = trim(mb_convert_encoding($valor, 'UTF-8', self::CODIFICACION));

        return in_array($valor, self::VACIOS, true) ? '' : $valor;
    }

    private function marcar(int $id, array $campos): void
    {
        DB::table('padron_importaciones')
            ->where('id', $id)
            ->update($campos + ['updated_at' => now()]);
    }
}
