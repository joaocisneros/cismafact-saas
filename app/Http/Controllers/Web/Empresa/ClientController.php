<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ConsultaDocumentoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /** Tipos de documento de identidad aceptados por SUNAT. */
    private const TIPOS_DOC = ['1', '4', '6', '0'];

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

        $r = $tipo === '6' ? $consultas->ruc($numero) : $consultas->dni($numero);

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
            'numero_documento' => [
                'required', 'string', 'max:15',
                Rule::unique('clients')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->where('tipo_documento', $request->tipo_documento))
                    ->ignore($ignoreId),
            ],
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'numero_documento.unique' => 'Ya existe un cliente con ese tipo y número de documento.',
        ]);
    }

    /** Asegura que el cliente pertenezca a la empresa del usuario. */
    private function authorizeClient(Client $client): void
    {
        abort_unless($client->company_id === Auth::user()->company_id, 403);
    }
}
