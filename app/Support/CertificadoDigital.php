<?php

namespace App\Support;

use RuntimeException;

/**
 * Lee un certificado .pfx/.p12.
 *
 * SUNAT genera el Certificado Digital Tributario (el gratuito) con cifrado
 * antiguo: RC2-40 y 3DES con PBE viejo. OpenSSL 3 desactivo esos algoritmos
 * por defecto, asi que openssl_pkcs12_read() falla con
 *
 *   error:0308010C:digital envelope routines::unsupported
 *
 * aunque la contrasena sea la correcta. Como practicamente toda MYPE usa ese
 * certificado, sin esto ningun cliente podria emitir.
 *
 * Se distinguen los dos fallos, que hasta ahora se contaban como el mismo:
 *
 *   mac verify failure  -> la contrasena esta mal
 *   unsupported         -> la contrasena esta bien, el cifrado es antiguo
 *
 * En el segundo caso se reintenta con el binario de openssl y su proveedor
 * legacy, que si lo entiende.
 */
class CertificadoDigital
{
    /**
     * @return array{cert: string, pkey: string} Certificado y llave privada en PEM.
     *
     * @throws RuntimeException con un motivo que se le puede enseñar al usuario.
     */
    public static function leer(string $contenido, string $clave): array
    {
        $certs = [];

        if (openssl_pkcs12_read($contenido, $certs, $clave)) {
            return [
                'cert' => (string) ($certs['cert'] ?? ''),
                'pkey' => (string) ($certs['pkey'] ?? ''),
            ];
        }

        $errores = [];
        while ($e = openssl_error_string()) {
            $errores[] = $e;
        }
        $motivo = implode(' | ', $errores);

        if (str_contains($motivo, 'mac verify failure')) {
            throw new RuntimeException('La contraseña del certificado no es correcta.');
        }

        // Cifrado antiguo: la contrasena ya paso la verificacion.
        if (str_contains($motivo, 'unsupported') || str_contains($motivo, 'digital envelope')) {
            return self::leerConLegacy($contenido, $clave);
        }

        throw new RuntimeException('No se pudo abrir el certificado digital. ' . ($motivo ?: 'Formato no reconocido.'));
    }

    /** ¿Es un certificado que solo el proveedor legacy puede abrir? */
    public static function necesitaLegacy(string $contenido, string $clave): bool
    {
        $certs = [];

        if (openssl_pkcs12_read($contenido, $certs, $clave)) {
            return false;
        }

        $motivo = '';
        while ($e = openssl_error_string()) {
            $motivo .= $e;
        }

        return str_contains($motivo, 'unsupported') || str_contains($motivo, 'digital envelope');
    }

    private static function leerConLegacy(string $contenido, string $clave): array
    {
        $temporal = tempnam(sys_get_temp_dir(), 'cert');

        if ($temporal === false) {
            throw new RuntimeException('No se pudo preparar la lectura del certificado.');
        }

        try {
            file_put_contents($temporal, $contenido);

            // La contrasena entra por stdin, no por la linea de comandos: ahi
            // la veria cualquiera que liste los procesos. Tampoco se pasa un
            // entorno propio, porque eso deja al proceso sin PATH y entonces
            // no encuentra el propio openssl.
            $pem = null;

            foreach ([true, false] as $conLegacy) {
                $orden = 'openssl pkcs12' . ($conLegacy ? ' -legacy' : '')
                    . ' -in ' . escapeshellarg($temporal)
                    . ' -nodes -passin stdin';

                $salida = self::ejecutar($orden, $clave);

                if ($salida !== null && str_contains($salida, '-----BEGIN')) {
                    $pem = $salida;
                    break;
                }
            }

            if ($pem === null) {
                throw new RuntimeException(
                    'Este certificado usa un cifrado antiguo que este servidor no puede abrir. '
                    . 'Hace falta el binario "openssl" con soporte legacy. Avisa al administrador.'
                );
            }

            preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $pem, $c);
            preg_match('/-----BEGIN (?:ENCRYPTED )?PRIVATE KEY-----.*?-----END (?:ENCRYPTED )?PRIVATE KEY-----/s', $pem, $k);

            if (empty($c[0]) || empty($k[0])) {
                throw new RuntimeException('El certificado se abrió pero no trae certificado y llave privada completos.');
            }

            return ['cert' => $c[0] . "\n", 'pkey' => $k[0] . "\n"];
        } finally {
            @unlink($temporal);
        }
    }

    private static function ejecutar(string $orden, string $clave): ?string
    {
        if (! function_exists('proc_open')) {
            return null;
        }

        $tuberias = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proceso = @proc_open($orden, $tuberias, $canales);

        if (! is_resource($proceso)) {
            return null;
        }

        fwrite($canales[0], $clave . "
");
        fclose($canales[0]);

        $salida = stream_get_contents($canales[1]);
        fclose($canales[1]);
        fclose($canales[2]);
        proc_close($proceso);

        return $salida ?: null;
    }
}
