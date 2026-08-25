<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda el logo de la empresa reduciendolo a un tamaño razonable.
 *
 * El logo se incrusta en base64 dentro de cada PDF, asi que uno de 1 MB
 * convierte cada comprobante en un archivo de 1 MB. Reduciendolo a 400 px el
 * PDF vuelve a pesar unas decenas de KB y en pantalla se ve igual.
 */
class LogoService
{
    /** Lado maximo en pixeles. Da de sobra para la cabecera del PDF. */
    private const LADO_MAXIMO = 400;

    /**
     * Guarda el archivo en el disco publico y devuelve su ruta.
     * Si por lo que sea no se puede redimensionar, guarda el original: es
     * preferible un logo pesado a quedarse sin logo.
     */
    public function guardar(UploadedFile $archivo, int $companyId): string
    {
        $extension = strtolower($archivo->getClientOriginalExtension()) === 'png' ? 'png' : 'jpg';
        $ruta = 'logos/' . $companyId . '_' . Str::random(20) . '.' . $extension;

        $reducido = $this->reducir($archivo->getRealPath(), $extension);

        if ($reducido === null) {
            return $archivo->store('logos', 'public');
        }

        Storage::disk('public')->put($ruta, $reducido);

        return $ruta;
    }

    /** Devuelve el binario de la imagen reducida, o null si no se pudo. */
    private function reducir(string $origen, string $extension): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        try {
            $info = @getimagesize($origen);

            if (! $info) {
                return null;
            }

            [$ancho, $alto] = $info;

            // Ya es pequeño: no se toca, para no perder calidad sin motivo.
            if ($ancho <= self::LADO_MAXIMO && $alto <= self::LADO_MAXIMO) {
                return file_get_contents($origen);
            }

            $imagen = match ($info[2]) {
                IMAGETYPE_PNG => @imagecreatefrompng($origen),
                IMAGETYPE_JPEG => @imagecreatefromjpeg($origen),
                default => null,
            };

            if (! $imagen) {
                return null;
            }

            $escala = self::LADO_MAXIMO / max($ancho, $alto);
            $nuevoAncho = (int) round($ancho * $escala);
            $nuevoAlto = (int) round($alto * $escala);

            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

            // Conservar la transparencia del PNG; si no, el fondo sale negro.
            if ($extension === 'png') {
                imagealphablending($destino, false);
                imagesavealpha($destino, true);
                imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));
            } else {
                imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
            }

            imagecopyresampled($destino, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

            ob_start();
            $extension === 'png' ? imagepng($destino, null, 6) : imagejpeg($destino, null, 85);
            $binario = ob_get_clean();

            imagedestroy($imagen);
            imagedestroy($destino);

            return $binario ?: null;
        } catch (\Throwable $e) {
            Log::warning('No se pudo reducir el logo, se guarda el original', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
