<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ContactoWeb;
use Illuminate\Http\Request;

/**
 * Quien dejo su numero en el chat de la web.
 *
 * Se guardaban y no habia donde verlos, que es lo mismo que no guardarlos. Los
 * pendientes salen primero: lo que importa es a quien falta llamar, no la
 * lista entera.
 */
class ContactoWebController extends Controller
{
    public function index(Request $request)
    {
        $ver = $request->query('ver', 'pendientes');

        $contactos = ContactoWeb::query()
            ->with('atendidoPor:id,name')
            ->when($ver === 'pendientes', fn ($q) => $q->pendientes())
            ->when($ver === 'atendidos', fn ($q) => $q->whereNotNull('atendido_en'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('super-admin.contactos.index', [
            'contactos' => $contactos,
            'ver' => $ver,
            'pendientes' => ContactoWeb::pendientes()->count(),
            'total' => ContactoWeb::count(),
            'delMes' => ContactoWeb::where('created_at', '>=', now()->startOfMonth())->count(),
        ]);
    }

    /** Marcar que ya se le escribio, o deshacerlo. */
    public function atender(Request $request, ContactoWeb $contacto)
    {
        $datos = $request->validate([
            'nota' => ['nullable', 'string', 'max:500'],
        ]);

        if ($contacto->estaAtendido()) {
            $contacto->update(['atendido_en' => null, 'atendido_por' => null]);

            return back()->with('success', "«{$contacto->nombre}» vuelve a los pendientes.");
        }

        $contacto->update([
            'atendido_en' => now(),
            'atendido_por' => $request->user()->id,
            'nota' => $datos['nota'] ?? $contacto->nota,
        ]);

        return back()->with('success', "«{$contacto->nombre}» queda como atendido.");
    }

    public function destroy(ContactoWeb $contacto)
    {
        $nombre = $contacto->nombre;
        $contacto->delete();

        return back()->with('success', "Se eliminó el contacto de «{$nombre}».");
    }
}
