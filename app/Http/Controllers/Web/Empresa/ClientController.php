<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ConsultaDocumentoService;
use App\Support\ConsumoInterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\CatalogoSunat;

class ClientController extends Controller
{
    /**
     * Tipos de documento de identidad aceptados por SUNAT.
     *
     * Los nueve del catalogo 06, no los cuatro de siempre: sin pasaporte ni
     * los documentos extranjeros no se puede dar de alta a quien vive fuera,
     * y luego tampoco facturarle una exportacion.
     */
    private const TIPOS_DOC = CatalogoSunat::DOCUMENTOS_IDENTIDAD;

    /**
     * Busquedas por minuto y empresa en el autocompletado.
     *
     * No es por seguridad sino por dinero: cada una que no este en casa sale al
     * proveedor y se paga, y un formulario abierto no puede convertirse en un
     * grifo por un dedo apoyado en una tecla.
     */
    private const BUSQUEDAS_POR_MINUTO = 20;

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $query = Client::where('company_id', $companyId);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%");
            });
        }

        $clients = $query->latest()->paginate(15)->withQueryString();

        return view('empresa.clients.index', compact('clients'));
    }

    public function create(Request $request)
    {
        // Desde el listado se pide en modal; la pagina completa sigue existiendo
        // por si se entra directo a la URL.
        $vista = ($request->ajax() || $request->boolean('modal'))
            ? 'empresa.clients._form_modal'
            : 'empresa.clients.form';

        return view($vista, ['client' => new Client()]);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $data = $this->validateClient($request, $companyId);
        $data['company_id'] = $companyId;
        $data['activo'] = true;

        Client::create($data);

        return redirect()->route('empresa.clients.index')
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function edit(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $vista = ($request->ajax() || $request->boolean('modal'))
            ? 'empresa.clients._form_modal'
            : 'empresa.clients.form';

        return view($vista, compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $data = $this->validateClient($request, $client->company_id, $client->id);
        $client->update($data);

        return redirect()->route('empresa.clients.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Client $client)
    {
        $this->authorizeClient($client);
        $client->delete();

        return redirect()->route('empresa.clients.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

    /**
     * Los datos de un RUC o un DNI, para no teclearlos a mano.
     *
     * Solo se puede consultar DNI (1) y RUC (6): el carne de extranjeria y el
     * documento de no domiciliado no estan en ningun padron que podamos mirar.
     *
     * Devuelve siempre 200 con `encontrado`: que no haya ficha no es un fallo
     * del formulario, y tratarlo como error obligaria al usuario a cerrar un
     * aviso rojo antes de poder escribir el nombre a mano.
     */
    public function consultar(string $tipo, string $numero, ConsultaDocumentoService $consultas)
    {
        if (! in_array($tipo, ['1', '6'], true)) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => 'Ese tipo de documento no se puede consultar. Escribe los datos a mano.',
            ]);
        }

        $numero = preg_replace('/\D/', '', $numero);
        $slug = $tipo === '6' ? 'ruc' : 'dni';
        $empresa = Auth::user()->company_id;

        // El tope se comprueba aqui y no con throttle: aquel corta antes de
        // llegar al controlador, asi que las cortadas no se anotaban y en el
        // panel se veian las que pasaron y ninguna señal de las que no. Es lo
        // que se mira cuando alguien avisa de que no le sale.
        $ultimoMinuto = DB::table('consultas_consumo')
            ->where('origen', 'interno')
            ->where('company_id', $empresa)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($ultimoMinuto >= self::BUSQUEDAS_POR_MINUTO) {
            ConsumoInterno::anotar($empresa, $slug, $numero, [
                'valido' => false,
                'fuente' => 'rechazada',
                'motivo' => 'Demasiadas búsquedas seguidas: más de ' . self::BUSQUEDAS_POR_MINUTO . ' en un minuto.',
            ], 0);

            return response()->json([
                'encontrado' => false,
                'mensaje' => 'Demasiadas búsquedas seguidas. Espera un momento.',
            ]);
        }

        $empezo = microtime(true);
        $r = $slug === 'ruc' ? $consultas->ruc($numero) : $consultas->dni($numero);

        // Se anota siempre, salga bien o mal. Esto no le descuenta cuota a
        // nadie, pero lo que sale al proveedor se paga y conviene verlo.
        ConsumoInterno::anotar(
            Auth::user()->company_id,
            $slug,
            $numero,
            $r,
            (int) round((microtime(true) - $empezo) * 1000),
        );

        if (! ($r['valido'] ?? false)) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => $r['motivo'] ?? 'El número no es válido.',
            ]);
        }

        // Numero correcto pero sin ficha (proveedor caido o no lo encuentra):
        // se avisa y se deja escribir a mano, que es mejor que no dejar seguir.
        if (empty($r['nombre'])) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => $r['motivo'] ?? 'No se pudo traer la ficha. Escribe los datos a mano.',
            ]);
        }

        return response()->json([
            'encontrado' => true,
            'razon_social' => $r['nombre'],
            'direccion' => $r['direccion'] ?? null,
            // Solo el RUC tiene estado y condicion; el DNI no.
            'estado' => $r['estado'] ?? null,
            'condicion' => $r['condicion'] ?? null,
        ]);
    }

    /**
     * Valida los datos del cliente. La combinacion tipo+numero es unica por empresa.
     */
    private function validateClient(Request $request, int $companyId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'tipo_documento' => ['required', Rule::in(self::TIPOS_DOC)],
            // El largo lo pone el tipo: un DNI son ocho digitos y un RUC once.
            // Con «max:15» se podia guardar un cliente con el tipo en RUC y el
            // numero de un DNI, y el fallo no aparecia hasta emitirle algo.
            'numero_documento' => array_merge(
                CatalogoSunat::reglaNumeroDocumento($request->input('tipo_documento')),
                [
                    Rule::unique('clients')
                        ->where(fn ($q) => $q->where('company_id', $companyId)->where('tipo_documento', $request->tipo_documento))
                        ->ignore($ignoreId),
                ],
            ),
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'numero_documento.unique' => 'Ya existe un cliente con ese tipo y número de documento.',
            'numero_documento.regex' => CatalogoSunat::avisoNumeroDocumento($request->input('tipo_documento')),
        ]);
    }

    /** Asegura que el cliente pertenezca a la empresa del usuario. */
    private function authorizeClient(Client $client): void
    {
        abort_unless($client->company_id === Auth::user()->company_id, 403);
    }
}
