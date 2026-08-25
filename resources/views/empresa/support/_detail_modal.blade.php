@php
    $estados = ['open' => 'Abierto', 'in_progress' => 'En progreso', 'closed' => 'Cerrado'];
    $estadoColor = ['open' => 'bg-amber-50 text-amber-700', 'in_progress' => 'bg-blue-50 text-blue-700', 'closed' => 'bg-green-50 text-green-700'];
    $prioridades = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'];
@endphp

<div class="p-5 space-y-4">
    <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="rounded px-2 py-1 {{ $estadoColor[$ticket->status] ?? 'bg-gray-100 text-gray-700' }}">
            {{ $estados[$ticket->status] ?? $ticket->status }}
        </span>
        <span class="rounded bg-slate-100 px-2 py-1 text-slate-700">{{ $ticket->motivo_nombre }}</span>
        <span class="rounded bg-gray-100 px-2 py-1 text-gray-700">Prioridad {{ $prioridades[$ticket->priority] ?? $ticket->priority }}</span>
        <span class="text-gray-500">Abierto el {{ $ticket->created_at->format('d/m/Y H:i') }}</span>
    </div>

    <div>
        <h3 class="font-semibold text-gray-900">{{ $ticket->subject }}</h3>
        <p class="mt-1 text-xs text-gray-500">Enviado por {{ $ticket->user->name ?? '—' }}</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm whitespace-pre-line text-gray-800">{{ $ticket->message }}</div>

    @forelse($ticket->replies as $respuesta)
        <div class="rounded-lg border p-4 text-sm {{ $respuesta->is_admin ?? false ? 'border-blue-200 bg-blue-50' : 'border-gray-200 bg-white' }}">
            <p class="mb-1 text-xs text-gray-500">
                {{ $respuesta->user->name ?? 'Soporte' }} · {{ $respuesta->created_at->format('d/m/Y H:i') }}
            </p>
            <div class="whitespace-pre-line text-gray-800">{{ $respuesta->message }}</div>
        </div>
    @empty
        <p class="text-sm text-gray-500">Todavía no hay respuestas. Te avisaremos en cuanto contestemos.</p>
    @endforelse

    @if($ticket->status !== 'closed')
        <form method="POST" action="{{ route('empresa.support.reply', $ticket) }}"
              data-success-message="Respuesta enviada.">
            @csrf
            <label class="text-sm font-medium text-gray-700">Responder
                <textarea name="message" required rows="3"
                          class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></textarea>
            </label>
            <div class="mt-3 flex justify-end gap-2">
                <button type="button" onclick="window.closeAdminModal()"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cerrar</button>
                <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Enviar respuesta</button>
            </div>
        </form>
    @else
        <div class="flex justify-end">
            <button type="button" onclick="window.closeAdminModal()"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cerrar</button>
        </div>
    @endif
</div>
