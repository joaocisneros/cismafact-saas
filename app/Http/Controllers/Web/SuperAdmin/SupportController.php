<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'closed'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'motivo' => ['nullable', Rule::in(array_keys(Ticket::MOTIVOS))],
        ]);

        $query = Ticket::query()
            ->select(['id', 'user_id', 'company_id', 'subject', 'status', 'priority', 'motivo', 'created_at'])
            ->with([
                'user:id,name',
                'company:id,razon_social',
            ])
            ->withCount('replies');

        // Por defecto solo lo pendiente: un ticket cerrado ya no pide nada, y
        // dejarlos en la lista hace que lo que sí necesita respuesta se pierda
        // entre lo resuelto. Los cerrados se ven eligiendo su filtro.
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (! $request->boolean('todos')) {
            $query->whereIn('status', ['open', 'in_progress']);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('motivo')) {
            $query->where('motivo', $request->motivo);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $tickets = $query->latest()->simplePaginate(15)->withQueryString();

        $stats = Cache::remember('super_admin_support_stats', now()->addMinute(), function () {
            $counts = Ticket::query()
                ->selectRaw("
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as abiertos,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as progreso,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as cerrados
                ")
                ->first();

            return [
                'abiertos' => (int) ($counts->abiertos ?? 0),
                'progreso' => (int) ($counts->progreso ?? 0),
                'cerrados' => (int) ($counts->cerrados ?? 0),
            ];
        });

        return view('super-admin.support.index', compact('tickets', 'stats'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'user:id,name,email',
            'company:id,ruc,razon_social',
            'replies.user:id,name',
        ]);

        if (request()->ajax() || request()->boolean('modal')) {
            return view('super-admin.support._detail_modal', compact('ticket'));
        }

        return view('super-admin.support.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        if ($ticket->status === 'closed') {
            return back()->with('error', 'No se puede responder un ticket cerrado. Reábrelo primero.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($ticket, $validated) {
            $ticket->replies()->create([
                'user_id' => auth()->id(),
                'message' => $validated['message'],
                'is_admin' => true,
            ]);

            if ($ticket->status === 'open') {
                $ticket->update(['status' => 'in_progress']);
            }
        });

        $this->forgetSupportCaches();

        return back()->with('success', 'Respuesta enviada.');
    }

    /**
     * Ajusta la prioridad de un ticket.
     *
     * La pone el sistema segun el motivo, pero quien atiende ve el caso completo
     * y puede corregirla: un cliente que reporta un fallo como "consulta" no
     * deberia quedarse al final de la cola.
     */
    public function changePriority(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
        ]);

        $ticket->update(['priority' => $validated['priority']]);
        $this->forgetSupportCaches();

        $nombres = ['low' => 'baja', 'medium' => 'media', 'high' => 'alta'];

        return back()->with('success', "Prioridad cambiada a {$nombres[$validated['priority']]}.");
    }

    public function close(Ticket $ticket)
    {
        $ticket->update(['status' => 'closed']);
        $this->forgetSupportCaches();

        return back()->with('success', 'Ticket cerrado.');
    }

    public function reopen(Ticket $ticket)
    {
        $ticket->update(['status' => 'open']);
        $this->forgetSupportCaches();

        return back()->with('success', 'Ticket reabierto.');
    }

    private function forgetSupportCaches(): void
    {
        Cache::forget('super_admin_support_stats');
        Cache::forget('super_admin_dashboard_alerts');
    }
}
