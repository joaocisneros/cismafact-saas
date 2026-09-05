@extends('layouts.app')

@section('title', 'Contactos de la web')

@section('content')
{{-- Quien dejo su numero en el chat de la web.

     Los pendientes primero y por defecto: lo que hay que mirar aqui es a quien
     falta llamar, no la lista entera de todo lo que ha entrado nunca. --}}
<div class="space-y-5">

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Contactos de la web</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Quienes pidieron que les escribieras desde el asistente de la página
            </p>
        </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-3">
        <x-stat-card title="Por atender" :value="number_format($pendientes)"
                     :subtitle="$pendientes ? 'Están esperando tu mensaje' : 'No queda ninguno pendiente'"
                     :color="$pendientes ? 'orange' : 'green'">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Este mes" :value="number_format($delMes)"
                     subtitle="Dejaron sus datos en la web" color="blue">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="En total" :value="number_format($total)"
                     subtitle="Desde que se puso el asistente" color="indigo">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </section>

    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center gap-1 border-b border-gray-100 px-4 py-3">
            @foreach(['pendientes' => 'Por atender', 'atendidos' => 'Atendidos', 'todos' => 'Todos'] as $clave => $etiqueta)
                <a href="{{ route('super-admin.contactos.index', ['ver' => $clave]) }}"
                   class="rounded-lg px-3 py-1.5 text-sm font-medium transition
                          {{ $ver === $clave ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-50' }}">
                    {{ $etiqueta }}
                </a>
            @endforeach
        </div>

        @if($contactos->isEmpty())
            <p class="px-4 py-10 text-center text-sm text-gray-500">
                @if($ver === 'pendientes')
                    No hay nadie esperando. Cuando alguien deje sus datos en el chat, aparece aquí.
                @else
                    Todavía no hay contactos.
                @endif
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                            <th class="px-4 py-2">Quién</th>
                            <th class="px-4 py-2">Qué buscaba</th>
                            <th class="px-4 py-2">Cuándo</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2 text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @foreach($contactos as $c)
                            <tr class="{{ $c->estaAtendido() ? '' : 'bg-amber-50/30' }}">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ $c->nombre }}</p>
                                    <p class="font-mono text-xs text-gray-500">{{ $c->telefono }}</p>
                                </td>

                                <td class="max-w-md px-4 py-3">
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                        {{ $c->interesTexto() }}
                                    </span>
                                    @if($c->mensaje)
                                        <p class="mt-1 text-xs leading-snug text-gray-600">{{ $c->mensaje }}</p>
                                    @endif
                                    @if($c->nota)
                                        <p class="mt-1 text-xs italic leading-snug text-gray-400">{{ $c->nota }}</p>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                    {{ $c->created_at->diffForHumans() }}
                                    <span class="block text-xs text-gray-400">{{ $c->created_at->format('d/m/Y H:i') }}</span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($c->estaAtendido())
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Atendido
                                        </span>
                                        @if($c->atendidoPor)
                                            <span class="mt-0.5 block text-xs text-gray-400">por {{ $c->atendidoPor->name }}</span>
                                        @endif
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                            Por atender
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- Escribirle es lo que se hace aqui, asi que abre
                                             WhatsApp con su numero y con el mensaje ya
                                             redactado: quien atiende acaba mandando un «hola»
                                             a secas, y al otro lado han pasado dias y no se
                                             acuerdan de que iba. --}}
                                        <a href="{{ $c->enlaceWhatsapp() }}" target="_blank" rel="noopener"
                                           title="Escribirle por WhatsApp"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-600 ring-1 ring-inset ring-green-200 transition hover:bg-green-100">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5 0-.2 0-.4 0-.5 0-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 2-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2z"/>
                                            </svg>
                                        </a>

                                        <form method="POST" action="{{ route('super-admin.contactos.atender', $c) }}">
                                            @csrf
                                            {{-- Un visto, no un ojo: el ojo dice «mirar», y lo
                                                 que se hace aqui es dar el contacto por
                                                 zanjado. --}}
                                            <x-icon-action :icon="$c->estaAtendido() ? 'deshacer' : 'confirmar'"
                                                           :label="$c->estaAtendido() ? 'Devolver a pendientes' : 'Marcar como atendido'"
                                                           :color="$c->estaAtendido() ? 'slate' : 'emerald'" />
                                        </form>

                                        <form method="POST" action="{{ route('super-admin.contactos.borrar', $c) }}"
                                              onsubmit="return confirm('Se elimina el contacto de «{{ $c->nombre }}». ¿Continuar?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-icon-action icon="eliminar" label="Eliminar" color="red" />
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($contactos->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">{{ $contactos->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
