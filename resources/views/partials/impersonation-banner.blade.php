@if(\App\Support\Impersonation::activa())
    {{-- Aviso fijo: deja claro que lo que se ve y lo que se haga es bajo la
         cuenta de la empresa, no la del Super Admin. --}}
    <div class="sticky top-0 z-[9998] border-b border-amber-300 bg-amber-100 px-4 py-2.5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm text-amber-900">
                <svg class="h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <span>
                    <strong>Sesión de soporte.</strong>
                    Estás dentro de <strong>{{ auth()->user()->company->razon_social ?? 'la empresa' }}</strong>
                    como <strong>{{ auth()->user()->email }}</strong>.
                    Todo lo que hagas queda registrado a nombre de
                    {{ \App\Support\Impersonation::nombreSuplantador() }}.
                </span>
            </div>

            <form method="POST" action="{{ route('impersonate.stop') }}" class="shrink-0">
                @csrf
                <button type="submit"
                        class="rounded-md bg-amber-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-1">
                    Salir del modo soporte
                </button>
            </form>
        </div>
    </div>
@endif
