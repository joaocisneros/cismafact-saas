<?php

namespace App\Http\Controllers\Web\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TicketController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tickets = Ticket::where('user_id', $user->id)
            ->withCount('replies')
            ->latest()
            ->paginate(15);

        $counts = Ticket::where('user_id', $user->id)
            ->selectRaw("
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as abiertos,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as progreso,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as cerrados
            ")
            ->first();

        $stats = [
            'abiertos' => (int) ($counts->abiertos ?? 0),
            'progreso' => (int) ($counts->progreso ?? 0),
            'cerrados' => (int) ($counts->cerrados ?? 0),
        ];

        return view('empresa.support.index', compact('tickets', 'stats'));
    }

    public function show(Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }

        $ticket->load(['replies.user']);

        return view('empresa.support.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }

        if ($ticket->status === 'closed') {
            return back()->with('error', 'No puedes responder un ticket cerrado. Reábrelo primero.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->replies()->create([
            'user_id' => $user->id,
            'message' => $validated['message'],
            'is_admin' => false,
        ]);

        $this->forgetSupportCaches();

        return back()->with('success', 'Respuesta enviada.');
    }

    public function reopen(Ticket $ticket)
    {
        $user = Auth::user();

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }

        $ticket->update(['status' => 'open']);
        $this->forgetSupportCaches();

        return back()->with('success', 'Ticket reabierto.');
    }

    public function create()
    {
        return view('empresa.support.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,medium,high',
        ]);

        $user = Auth::user();

        Ticket::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        $this->forgetSupportCaches();

        return redirect()->route('empresa.support.index')
            ->with('success', 'Ticket enviado exitosamente. Nuestro equipo te responderá pronto.');
    }

    private function forgetSupportCaches(): void
    {
        Cache::forget('super_admin_support_stats');
        Cache::forget('super_admin_dashboard_alerts');
    }
}
