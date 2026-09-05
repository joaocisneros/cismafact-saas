{{--
    Quién puede entrar a ver su llave.

    Sin tarjetas de cifras arriba: lo mismo ya se cuenta en «Mis APIs» y
    repetirlo aquí solo alarga la pantalla.

    El acceso cuelga del titular y no de la llave: quien tiene dos llaves de
    producción entra una vez y ve las dos, en vez de acabar con dos
    contraseñas para lo mismo.
--}}
@php
    $sinAcceso = $titulares->filter(fn ($t) => ! $t['usuario'])->count();
@endphp

<div x-data="{ alta: null }">

    @if($sinAcceso > 0)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <span>
                <strong>{{ $sinAcceso }}
                {{ \Illuminate\Support\Str::plural('titular', $sinAcceso) }}
                {{ $sinAcceso === 1 ? 'no puede' : 'no pueden' }} entrar.</strong>
                Tendrán que escribirte cada vez que quieran saber su consumo o recuperar su secreto.
            </span>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3">
            <h2 class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">
                Titulares con llave de producción
            </h2>
            <p class="text-xs text-gray-500">Las llaves de Sandbox no dan acceso al sistema</p>
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
                                        <span class="max-w-[16rem] truncate rounded bg-emerald-50 px-1.5 py-0.5 text-xs font-medium text-emerald-700"
                                              title="{{ $llave->nombre }}">
                                            {{ \Illuminate\Support\Str::limit($llave->nombre, 32) }}
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
                                             sin tener que leerlo. --}}
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
                                Todavía no hay ninguna llave de producción.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-gray-700">
        <p class="mb-1">
            <strong class="text-gray-900">El acceso es del titular, no de la llave.</strong>
            Si alguien tiene dos llaves de producción, entra una sola vez y ve las dos.
        </p>
        <p>
            <strong class="text-gray-900">Quitar el acceso no corta la API.</strong>
            Su programa sigue funcionando: solo deja de poder entrar a mirar. Para cortarle
            el servicio se bloquea la llave, en «Mis APIs».
        </p>
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
                    <p class="text-sm text-gray-600">
                        P&aacute;saselo t&uacute;. <strong class="text-gray-900">La contrase&ntilde;a no se vuelve a ense&ntilde;ar</strong>,
                        pero siempre puedes generarle otra desde la tabla.
                    </p>

                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-gray-600">Entra con</p>
                        <div class="flex gap-2">
                            <code class="flex-1 overflow-x-auto whitespace-nowrap rounded-md border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-mono text-sm">{{ session('acceso_creado')['correo'] }}</code>
                            <button type="button"
                                    @click="navigator.clipboard.writeText(@js(session('acceso_creado')['correo'])); $el.textContent = 'Copiado'; setTimeout(() => $el.textContent = 'Copiar', 1400)"
                                    class="shrink-0 rounded-md border border-gray-300 px-3.5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Copiar</button>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-gray-600">Contrase&ntilde;a</p>
                        <div class="flex gap-2">
                            <code class="flex-1 overflow-x-auto whitespace-nowrap rounded-md border border-gray-200 bg-gray-50 px-3.5 py-2.5 font-mono text-sm">{{ session('acceso_creado')['clave'] }}</code>
                            <button type="button"
                                    @click="navigator.clipboard.writeText(@js(session('acceso_creado')['clave'])); $el.textContent = 'Copiada'; setTimeout(() => $el.textContent = 'Copiar', 1400)"
                                    class="shrink-0 rounded-md border border-gray-300 px-3.5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Copiar</button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 px-5 py-4">
                    <button type="button" @click="abierto = false"
                            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                        Ya lo copi&eacute;
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
