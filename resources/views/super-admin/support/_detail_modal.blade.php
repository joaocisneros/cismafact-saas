@php
    $statusLabels = ['open' => 'Abierto', 'in_progress' => 'En progreso', 'closed' => 'Cerrado'];
    $statusClasses = ['open' => 'bg-amber-50 text-amber-700', 'in_progress' => 'bg-blue-50 text-blue-700', 'closed' => 'bg-green-50 text-green-700'];
    $priorityLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'];
@endphp

<div class="space-y-4 p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h4 class="text-lg font-semibold text-gray-900">Ticket #{{ $ticket->id }}</h4>
            <p class="text-sm text-gray-600">{{ $ticket->subject }}</p>
            <p class="mt-1 text-xs text-gray-500">
                {{ $ticket->company->razon_social ?? 'Sin empresa' }} · {{ $ticket->user->name ?? 'Usuario' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            {{-- La prioridad la fija el motivo, pero aqui se puede corregir. --}}
            <form method="POST" action="{{ route('super-admin.support.priority', $ticket) }}"
                  class="inline-flex items-center gap-1">
                @csrf
                <select name="priority" onchange="this.form.submit()"
                        class="rounded border border-gray-300 bg-white px-2 py-1 text-xs text-gray-700">
                    @foreach($priorityLabels as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($ticket->priority === $valor)>Prioridad {{ $etiqueta }}</option>
                    @endforeach
                </select>
            </form>
            <span class="rounded px-2 py-1 text-xs font-medium {{ $statusClasses[$ticket->status] }}">{{ $statusLabels[$ticket->status] }}</span>
        </div>
    </div>

    <div class="rounded-md bg-gray-50 p-4">
        <div class="flex justify-between gap-3 text-xs text-gray-500">
            <span>{{ $ticket->user->name ?? 'Usuario' }}</span>
            <span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <p class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $ticket->message }}</p>
    </div>

    <div class="max-h-64 space-y-3 overflow-y-auto">
        @forelse($ticket->replies as $reply)
            <div class="rounded-md border-l-2 {{ $reply->is_admin ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50' }} p-3">
                <div class="flex justify-between gap-3 text-xs text-gray-500">
                    <span>{{ $reply->user->name ?? 'Usuario' }}{{ $reply->is_admin ? ' · Soporte' : '' }}</span>
                    <span>{{ $reply->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <p class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ $reply->message }}</p>
            </div>
        @empty
            <p class="py-3 text-center text-sm text-gray-500">Aún no hay respuestas.</p>
        @endforelse
    </div>

    @if($ticket->status !== 'closed')
        <form action="{{ route('super-admin.support.reply', $ticket) }}" method="POST"
              data-success-message="Respuesta enviada correctamente.">
            @csrf
            <textarea name="message" rows="3" maxlength="5000" required placeholder="Escribe la respuesta"
                      class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></textarea>
            <div class="mt-2 flex justify-end">
                <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Responder</button>
            </div>
        </form>
    @endif

    <div class="flex justify-end border-t border-gray-200 pt-4">
        @if($ticket->status === 'closed')
            <form action="{{ route('super-admin.support.reopen', $ticket) }}" method="POST"
                  data-success-message="Ticket reabierto correctamente."
                  onsubmit="return confirm('¿Reabrir este ticket?')">
                @csrf
                <button class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">Reabrir ticket</button>
            </form>
        @else
            <form action="{{ route('super-admin.support.close', $ticket) }}" method="POST"
                  data-success-message="Ticket cerrado correctamente."
                  onsubmit="return confirm('¿Cerrar este ticket?')">
                @csrf
                <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">Cerrar ticket</button>
            </form>
        @endif
    </div>
</div>
