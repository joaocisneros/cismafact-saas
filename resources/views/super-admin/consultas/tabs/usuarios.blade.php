{{--
    Quién puede entrar a ver su llave.

    Sin tarjetas de cifras arriba: lo mismo ya se cuenta en «Mis APIs» y
    repetirlo aquí solo alarga la pantalla.

    El acceso cuelga del titular y no de la llave: quien tiene llaves de
    Producción y Sandbox entra una vez y ve todas, en vez de acabar con dos
    contraseñas para lo mismo.

    Sin aviso de «hay titulares sin acceso»: si se lo acabas de quitar tú, el
    sistema te estaría regañando por una decisión tuya. La columna Estado ya
    lo dice en su fila.
--}}
<div x-data="{ alta: null }">

    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3">
            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">
                Usuarios y accesos de API RUC/DNI
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-4 py-2">Titular</th>
                        <th class="px-4 py-2">Sus llaves</th>
                        <th class="px-4 py-2">Último acceso</th>
                        <th class="px-4 py-2">Estado</th>
                        <th class="px-4 py-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($titulares as $fila)
                        @php($usuario = $fila['usuario'])
                        <tr class="border-b border-gray-50 last:border-0 {{ $usuario ? '' : 'bg-gray-50' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-semibold
                                                {{ $usuario ? 'bg-blue-50 text-blue-700' : 'bg-gray-200 text-gray-500' }}">
                                        {{ $usuario ? \Illuminate\Support\Str::of($fila['titular'])->substr(0, 2)->upper() : '—' }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900" title="{{ $fila['titular'] }}">
                                            {{ \Illuminate\Support\Str::limit($fila['titular'], 34) }}
                                        </p>
                                        <p class="truncate text-xs text-gray-500">
                                            {{ $usuario?->email ?? $fila['correo'] ?? 'sin correo registrado' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-col items-start gap-1">
                                    @foreach($fila['llaves'] as $llave)
                                        <span class="max-w-[16rem] truncate rounded px-1.5 py-0.5 text-xs font-medium {{ $llave->entorno === 'sandbox' ? 'bg-violet-50 text-violet-700' : 'bg-emerald-50 text-emerald-700' }}"
                                              title="{{ $llave->nombre }}">
                                            {{ $llave->entorno === 'sandbox' ? 'Sandbox' : 'Producción' }} · {{ \Illuminate\Support\Str::limit($llave->nombre, 24) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-4 py-3 text-sm tabular-nums text-gray-700">
                                @if($usuario?->last_login_at)
                                    {{ $usuario->last_login_at->diffForHumans() }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @if($usuario && $usuario->active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>Con acceso
                                    </span>
                                @elseif($usuario)
                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Bloqueado</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500">Sin acceso</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1.5">
                                    @if($usuario)
                                        {{-- Los iconos del sistema, los mismos que en Empresas y
                                             Usuarios: quien administra ya sabe qué hace cada uno
                                             sin tener que leerlo.

                                             El ojo entra a su panel, igual que se entra al de una
                                             empresa: se ve lo mismo que ve el, que es la unica
                                             forma de comprobar de verdad como le quedo. Solo con
                                             acceso, que sin cuenta no hay panel al que entrar. --}}
                                        <form method="POST" action="{{ route('super-admin.consultas.acceso.entrar', $usuario->id) }}"
                                              onsubmit="return confirm('Vas a ver el sistema como lo ve «{{ $fila['titular'] }}». Sal cuando termines para volver a tu cuenta.')">
                                            @csrf
                                            <x-icon-action icon="ver" label="Ver su panel como lo ve él" />
                                        </form>
                                        <form method="POST" action="{{ route('super-admin.consultas.acceso.clave', $usuario->id) }}"
                                              onsubmit="return confirm('Se le pondrá una contraseña nueva y se te mostrará para que se la pases. ¿Seguir?')">
                                            @csrf
                                            <x-icon-action icon="clave" label="Ponerle una contraseña nueva" color="amber" />
                                        </form>
                                        <form method="POST" action="{{ route('super-admin.consultas.acceso.quitar', $usuario->id) }}"
                                              onsubmit="return confirm('Dejará de poder entrar. Sus llaves seguirán funcionando: la API no se corta. ¿Seguro?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-icon-action icon="bloquear" label="Quitarle el acceso" color="red" />
                                        </form>
                                    @else
                                        <button type="button"
                                                @click="alta = @js(['titular' => $fila['titular'], 'correo' => $fila['correo'], 'llaves' => $fila['llaves']->pluck('id')])"
                                                class="text-xs font-semibold text-blue-700 hover:underline">
                                            Dar acceso
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                Todavía no hay llaves de Producción ni Sandbox.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


    {{-- Alta: se abre con el titular y sus llaves ya elegidos, así no hay que
         volver a buscarlos. --}}
    <div x-show="alta" x-cloak @keydown.escape.window="alta = null"
         class="fixed inset-0 z-[9999] flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
        <div @click.outside="alta = null" class="my-auto w-full max-w-md rounded-xl bg-white shadow-xl">
            <form method="POST" action="{{ route('super-admin.consultas.acceso.crear') }}">
                @csrf
                <template x-for="id in (alta?.llaves ?? [])" :key="id">
                    <input type="hidden" name="llaves[]" :value="id">
                </template>

                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">Dar acceso al panel</h3>
                    <button type="button" @click="alta = null" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <div class="space-y-3 p-5">
                    <p class="text-sm text-gray-600">
                        Para <strong class="text-gray-900" x-text="alta?.titular"></strong>.
                        Entrará por el mismo login que todos y verá solo sus llaves.
                    </p>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-gray-600">Nombre</span>
                        <input name="nombre" required maxlength="120" :value="alta?.titular"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-gray-600">Correo — con este entra</span>
                        <input name="correo" type="email" required maxlength="150" :value="alta?.correo"
                               placeholder="cliente@ejemplo.pe"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-gray-600">
                            Contraseña
                            <span class="font-normal text-gray-500">— se la pasas tú; él la cambia luego</span>
                        </span>
                        <input name="clave" type="text" required minlength="8" value="{{ \Illuminate\Support\Str::password(12, symbols: false) }}"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-sm">
                    </label>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                    <button type="button" @click="alta = null"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancelar</button>
                    <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Crear acceso
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- La contraseña recién creada, en un modal que se abre solo.
         Debajo de la tabla se perdía: es un dato que se enseña una vez, hay que
         copiarlo en ese momento y después ya no se puede recuperar. --}}
    @if(session('acceso_creado'))
        <div x-data="{ abierto: true }" x-show="abierto" x-cloak
             @keydown.escape.window="abierto = false"
             class="fixed inset-0 z-[9999] flex items-start justify-center overflow-y-auto bg-gray-900/50 p-4">
            <div @click.outside="abierto = false" class="my-auto w-full max-w-lg rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">
                        Acceso listo para &laquo;{{ session('acceso_creado')['titular'] }}&raquo;
                    </h3>
                    <button type="button" @click="abierto = false" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <div class="space-y-4 p-5">
                    <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.7 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg>
                        <p class="text-sm leading-5 text-amber-800"><strong>Guarda estas credenciales ahora.</strong> La contraseña no volverá a mostrarse; después solo podrás generar una nueva.</p>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-indigo-200 bg-white">
                        <div class="flex items-center justify-between border-b border-indigo-200 bg-indigo-50 px-4 py-2.5">
                            <span class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Credenciales de acceso</span>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium text-emerald-700">Listas para entregar</span>
                        </div>

                        <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 border-b border-gray-100 px-4 py-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18v12H3zM3 7l9 6 9-6"/></svg>
                            </span>
                            <div class="min-w-0"><p class="text-xs text-gray-500">Correo de acceso</p><code class="block truncate font-mono text-sm font-medium text-gray-900">{{ session('acceso_creado')['correo'] }}</code></div>
                            <button type="button" onclick="window.copyCompanyCredential(this, @js(session('acceso_creado')['correo']))"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span data-copy-label>Copiar</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 px-4 py-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </span>
                            <div class="min-w-0"><p class="text-xs text-gray-500">Contraseña temporal</p><code class="block truncate font-mono text-sm font-medium text-gray-900">{{ session('acceso_creado')['clave'] }}</code></div>
                            <button type="button" onclick="window.copyCompanyCredential(this, @js(session('acceso_creado')['clave']))"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 002 2v8a2 2 0 002 2z"/></svg>
                                <span data-copy-label>Copiar</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 px-5 py-4">
                    <button type="button" @click="abierto = false"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        Ya lo copi&eacute;
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
