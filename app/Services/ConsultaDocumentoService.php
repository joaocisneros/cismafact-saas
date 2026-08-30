<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta de RUC y DNI.
 *
 * EN TRES NIVELES, Y NO LLAMANDO A UNA API SIN MAS.
 *
 * El RUC lleva digito verificador. La mayoria de los numeros mal tecleados se
 * detectan aqui, sin salir a internet: un digito cambiado de sitio da un RUC
 * invalido y se puede decir al instante, gratis, aunque no haya proveedor.
 * Solo los que pasan esa comprobacion merecen una llamada de red.
 *
 *   1  validacion local     formato y digito verificador
 *   2  padron y cache       lo que ya esta en casa
 *   3  proveedor            solo si no estaba, y se guarda para la proxima
 *
 * La cache no es un adorno: los clientes facturan a los mismos cientos de RUC
 * una y otra vez, asi que a los pocos meses casi todo se responde sin salir.
 *
 * SUNAT no publica una API abierta para esto; lo que hay son revendedores del
 * padron. El proveedor se configura en Super Admin y puede no estar: entonces
 * el nivel 1 sigue en pie y el numero se valida igual.
 */
class ConsultaDocumentoService
{
    /** Factores del digito verificador del RUC, en orden. */
    private const FACTORES = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    public function ruc(string $numero, bool $usarCache = true): array
    {
        $numero = preg_replace('/\D/', '', $numero);

        if (! $this->rucValido($numero)) {
            return [
                'valido' => false,
                'motivo' => strlen($numero) !== 11
                    ? 'El RUC debe tener 11 dígitos.'
                    : 'El RUC no es válido: el dígito verificador no cuadra.',
                'numero' => $numero,
                'tipo' => 'ruc',
            ];
        }

        if ($usarCache && $encontrado = $this->enCasa('ruc', $numero)) {
            return $encontrado;
        }

        $delProveedor = $this->alProveedor('ruc', $numero);

        if ($delProveedor) {
            $this->guardar('ruc', $numero, $delProveedor, 'proveedor');

            return $delProveedor + [
                'valido' => true,
                'numero' => $numero,
                'tipo' => 'ruc',
                'fuente' => 'proveedor',
            ];
        }

        // El numero es correcto aunque no se sepa de quien es: eso ya sirve
        // para dejar emitir sin bloquear al usuario por un proveedor caido.
        return [
            'valido' => true,
            'motivo' => 'El RUC es válido, pero no se pudo traer la ficha.',
            'numero' => $numero,
            'tipo' => 'ruc',
            'fuente' => 'ninguna',
        ];
    }

    public function dni(string $numero, bool $usarCache = true): array
    {
        $numero = preg_replace('/\D/', '', $numero);

        // El DNI no lleva digito verificador comprobable: solo se puede mirar
        // que sean 8 cifras.
        if (strlen($numero) !== 8) {
            return [
                'valido' => false,
                'motivo' => 'El DNI debe tener 8 dígitos.',
                'numero' => $numero,
                'tipo' => 'dni',
            ];
        }

        if ($usarCache && $encontrado = $this->enCasa('dni', $numero)) {
            return $encontrado;
        }

        $delProveedor = $this->alProveedor('dni', $numero);

        if ($delProveedor) {
            $this->guardar('dni', $numero, $delProveedor, 'proveedor');

            return $delProveedor + [
                'valido' => true,
                'numero' => $numero,
                'tipo' => 'dni',
                'fuente' => 'proveedor',
            ];
        }

        return [
            'valido' => true,
            'motivo' => 'El DNI tiene el formato correcto, pero no se pudo traer el nombre.',
            'numero' => $numero,
            'tipo' => 'dni',
            'fuente' => 'ninguna',
        ];
    }

    /** El digito verificador del RUC, modulo 11. */
    public function rucValido(string $ruc): bool
    {
        if (strlen($ruc) !== 11 || ! ctype_digit($ruc)) {
            return false;
        }

        // Los dos primeros digitos son el tipo de contribuyente.
        if (! in_array(substr($ruc, 0, 2), ['10', '15', '16', '17', '20'], true)) {
            return false;
        }

        $suma = 0;
        foreach (self::FACTORES as $i => $factor) {
            $suma += (int) $ruc[$i] * $factor;
        }

        $resto = 11 - ($suma % 11);
        $esperado = match ($resto) {
            10 => 0,
            11 => 1,
            default => $resto,
        };

        return $esperado === (int) $ruc[10];
    }

    /** Padron primero, y si no, lo que ya se consulto antes. */
    private function enCasa(string $tipo, string $numero): ?array
    {
        if ($tipo === 'ruc') {
            $fila = DB::table('padron_ruc')->where('ruc', $numero)->first();

            if ($fila) {
                return [
                    'valido' => true,
                    'numero' => $numero,
                    'tipo' => 'ruc',
                    'nombre' => $fila->nombre,
                    'estado' => $fila->estado,
                    'condicion' => $fila->condicion,
                    'ubigeo' => $fila->ubigeo,
                    'direccion' => $fila->direccion,
                    'fuente' => 'padron',
                ];
            }
        }

        $cache = DB::table('consultas_documento')
            ->where('tipo', $tipo)
            ->where('numero', $numero)
            ->first();

        if (! $cache) {
            return null;
        }

        return json_decode($cache->datos, true) + [
            'valido' => true,
            'numero' => $numero,
            'tipo' => $tipo,
            'fuente' => 'consultado antes',
        ];
    }

    private function guardar(string $tipo, string $numero, array $datos, string $fuente): void
    {
        DB::table('consultas_documento')->updateOrInsert(
            ['tipo' => $tipo, 'numero' => $numero],
            [
                'datos' => json_encode($datos),
                'fuente' => $fuente,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    /**
     * @return array<string,mixed>|null Null si no hay proveedor o no responde.
     */
    private function alProveedor(string $tipo, string $numero): ?array
    {
        $base = trim((string) $this->ajuste('consultas_url'));
        $token = trim((string) $this->ajuste('consultas_token'));

        if ($base === '') {
            return null;
        }

        try {
            $peticion = Http::timeout(8)->acceptJson();

            if ($token !== '') {
                $peticion = $peticion->withToken($token);
            }

            $r = $peticion->get(rtrim($base, '/') . "/{$tipo}/{$numero}");

            if (! $r->successful()) {
                return null;
            }

            return $this->normalizar($tipo, $r->json() ?? []);
        } catch (\Throwable $e) {
            Log::warning('Consulta de documento: el proveedor no respondió', [
                'tipo' => $tipo,
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Un ajuste de la tabla settings, que es donde los deja el Super Admin. */
    private function ajuste(string $clave): ?string
    {
        return DB::table('settings')->where('key', $clave)->value('value');
    }

    /**
     * Deja la respuesta del proveedor en los nombres que usa el sistema.
     *
     * Cada revendedor llama distinto a lo mismo (nombre / razonSocial /
     * razon_social), asi que se aceptan los alias mas comunes y el resto de la
     * aplicacion ve siempre las mismas claves.
     */
    private function normalizar(string $tipo, array $d): array
    {
        $primero = function (array $claves) use ($d) {
            foreach ($claves as $k) {
                if (! empty($d[$k])) {
                    return $d[$k];
                }
            }

            return null;
        };

        if ($tipo === 'ruc') {
            return array_filter([
                'nombre' => $primero(['nombre', 'razonSocial', 'razon_social', 'nombre_o_razon_social']),
                'estado' => $primero(['estado', 'estadoContribuyente', 'estado_contribuyente']),
                'condicion' => $primero(['condicion', 'condicionContribuyente', 'condicion_contribuyente']),
                'direccion' => $primero(['direccion', 'direccion_completa', 'domicilio_fiscal']),
                'ubigeo' => $primero(['ubigeo', 'ubigeo_sunat']),
                'departamento' => $primero(['departamento']),
                'provincia' => $primero(['provincia']),
                'distrito' => $primero(['distrito']),
            ], fn ($v) => $v !== null);
        }

        $nombres = $primero(['nombres', 'nombre']);
        $apPaterno = $primero(['apellidoPaterno', 'apellido_paterno']);
        $apMaterno = $primero(['apellidoMaterno', 'apellido_materno']);
        $completo = $primero(['nombre_completo', 'nombreCompleto']);

        return array_filter([
            'nombre' => $completo ?: (trim("{$nombres} {$apPaterno} {$apMaterno}") ?: null),
            'nombres' => $nombres,
            'apellido_paterno' => $apPaterno,
            'apellido_materno' => $apMaterno,
        ], fn ($v) => $v !== null);
    }
}
