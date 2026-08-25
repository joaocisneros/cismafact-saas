@php
    // Aviso de vencimiento para el dueno de la empresa. Aparece cuando quedan
    // pocos dias o cuando la suscripcion ya vencio, porque al vencer la empresa
    // se desactiva sola (cron subscriptions:sync) y deja de poder emitir.
    //
    // Solo lo ve el dueño de la empresa: es quien paga y quien puede renovar.
    // A un empleado el aviso no le sirve de nada y le dice que nos escriba,
    // cuando lo que le toca es avisar a su jefe.
    //
    // Tampoco se muestra en sesiones de soporte: el aviso es para el cliente,
    // no para quien entra a ayudarle.
    $avisoParaEsteUsuario = auth()->check()
        && auth()->user()->hasRole('company_admin')
        && ! \App\Support\Impersonation::activa();

    $avisoSub = $avisoParaEsteUsuario
        ? auth()->user()->company?->subscription
        : null;

    $diasParaVencer = $avisoSub?->ends_at
        ? (int) now()->startOfDay()->diffInDays($avisoSub->ends_at->startOfDay(), false)
        : null;

    // Solo interesa cuando hay fecha, no se renueva sola, y esta cerca o pasada.
    // Se listan los planes para que el cliente diga en el mismo mensaje si
    // quiere renovar igual o cambiar. Asi la primera respuesta ya puede ser el
    // precio, en vez de otra pregunta.
    $planActual = $avisoSub?->company?->plan;
    $otrosPlanes = \App\Models\Plan::where('active', true)
        ->when($planActual, fn ($q) => $q->where('id', '!=', $planActual->id))
        ->orderBy('monthly_price')
        ->get()
        ->map(fn ($p) => $p->name . ' (S/ ' . number_format((float) $p->monthly_price, 2) . ')')
        ->implode(', ');

    $mostrarAviso = $avisoSub
        && $diasParaVencer !== null
        && ! $avisoSub->auto_renew
        && $diasParaVencer <= 7;
@endphp

@if($mostrarAviso)
    <div x-data="{
            abierto: (() => {
                try { return sessionStorage.getItem('avisoVencimiento') !== '{{ $avisoSub->ends_at->format('Y-m-d') }}'; }
                catch (e) { return true; }
            })(),
            cerrar() {
                this.abierto = false;
                try { sessionStorage.setItem('avisoVencimiento', '{{ $avisoSub->ends_at->format('Y-m-d') }}'); } catch (e) {}
            }
         }"
         x-show="abierto" x-cloak
         {{-- El z-index va en linea y no como clase: Tailwind solo compila las
              clases que ya existian, y una nueva como z-[60] no llega al CSS
              publicado. Sin z-index, el menu lateral (50) y la cabecera (30) se
              pintaban por encima del fondo oscuro. --}}
         style="z-index: 9990;"
         class="fixed inset-0 flex items-center justify-center bg-gray-900/50 p-4">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.outside="cerrar()">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $diasParaVencer < 0 ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }}">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    @if($diasParaVencer < 0)
                        <h2 class="text-lg font-semibold text-gray-900">Tu plan venció</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Venció el <strong>{{ $avisoSub->ends_at->format('d/m/Y') }}</strong>.
                            Mientras siga vencido <strong>no podrás emitir comprobantes</strong>.
                        </p>
                    @elseif($diasParaVencer === 0)
                        <h2 class="text-lg font-semibold text-gray-900">Tu plan vence hoy</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            A partir de mañana <strong>no podrás emitir comprobantes</strong>.
                        </p>
                    @else
                        <h2 class="text-lg font-semibold text-gray-900">Tu plan vence pronto</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Vence el <strong>{{ $avisoSub->ends_at->format('d/m/Y') }}</strong>,
                            en {{ $diasParaVencer }} {{ $diasParaVencer === 1 ? 'día' : 'días' }}.
                            Al vencer <strong>no podrás emitir comprobantes</strong>.
                        </p>
                    @endif
                    <p class="mt-2 text-sm text-gray-600">
                        Escríbenos para renovarlo o pasar a otro plan, y te respondemos con el importe.
                    </p>
                </div>
            </div>

            {{-- Dos botones y no tres: el aviso ya dice la fecha y que pasa al
                 vencer, asi que "Ver mi plan" solo repartia la atencion. Ambos
                 marcan el aviso como visto; si no, al navegar volvia a saltar
                 en la pantalla siguiente. --}}
            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <button type="button" @click="cerrar()"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Ahora no
                </button>
                {{-- Cierra este aviso y abre el ticket en el modal del panel, sin
                     sacar al usuario a otra pantalla. --}}
                <button type="button"
                        @click="cerrar(); window.openAdminModal('{{ route('empresa.support.create', [
                            'subject' => 'Renovación de mi plan',
                            'message' => ($diasParaVencer < 0
                                ? 'Hola, mi plan venció y necesito reactivar mi cuenta para volver a emitir comprobantes.'
                                : 'Hola, quiero renovar mi plan antes de que venza.')
                                . ' Mi plan actual es ' . ($planActual->name ?? 'el que tengo contratado') . '.'
                                . ' Indíquenme el importe y la forma de pago.'
                                . ($otrosPlanes ? ' También me interesa saber el precio de: ' . $otrosPlanes . '.' : ''),
                            'priority' => 'high',
                            'motivo' => 'renovacion',
                            'modal' => 1,
                        ]) }}', 'Renovar o cambiar de plan')"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Renovar o cambiar de plan
                </button>
            </div>
        </div>
    </div>
@endif
