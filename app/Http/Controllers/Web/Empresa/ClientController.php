<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Client;
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

    public function create()
    {
        return view('empresa.clients.form', ['client' => new Client()]);
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

    public function edit(Client $client)
    {
        $this->authorizeClient($client);

        return view('empresa.clients.form', compact('client'));
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
