{{-- Lo que hace falta para entregarle la API a un cliente.

     Aqui NO se repite la documentacion: esa vive en una pagina publica y se
     manda por enlace. Una copia dentro del panel se quedaria vieja el dia que
     cambie algo, y acabariamos con dos versiones distintas de lo mismo.

     Lo que si va aqui es lo que no puede ser publico: el mensaje de entrega con
     sus credenciales dentro, ya escrito y listo para mandar.

     El orden va por uso: primero entregar, que es a lo que se entra; el enlace
     debajo, que es de consultar de vez en cuando. --}}
@php
    // Todas las llaves, las de verdad y las de prueba, con lo justo para armar
    // el mensaje. El secreto NO sale de la base a proposito: va cifrado y solo
    // se enseña al crearla, que es justamente lo que impide que se filtre desde
    // esta pantalla.
    $paraEntregar = $llaves->concat($sandbox)->map(fn ($l) => [
        'id' => $l->id,
        'nombre' => $l->nombre,
        'entorno' => $l->entorno,
        'titular' => $l->empresa->razon_social ?? $l->titular ?? '',
        'clave' => $l->clave,
        'plan' => $l->plan->nombre ?? null,
        'servicios' => array_map('strtoupper', (array) $l->servicios),
    ])->values();
@endphp

<div class="space-y-5"
     x-data="{
         llaveId: '',
         filtro: '',
         secreto: '',
         mostrarSecreto: false,
         copiado: null,
         llaves: @js($paraEntregar),
         enlaceDocs: @js(route('docs.consultas')),
         base: @js(url('/api')),

         llave() {
             return this.llaves.find(l => String(l.id) === String(this.llaveId)) ?? null;
         },

         etiquetaLlave(l = this.llave()) {
             if (! l) return '';
             const entorno = l.entorno === 'sandbox' ? 'SANDBOX' : 'PROD';
             return '[' + entorno + '] ' + (l.titular || 'Sin titular') + ' — ' + l.nombre;
         },

         seleccionarLlave(l) {
             this.llaveId = String(l.id);
             this.filtro = this.etiquetaLlave(l);
         },

         llavesFiltradas(entorno) {
             const termino = this.filtro.trim().toLocaleLowerCase('es');

             return this.llaves.filter(l => {
                 if (l.entorno !== entorno) return false;
                 if (! termino) return true;

                 return [l.titular, l.nombre, l.plan, l.entorno, ...(l.servicios ?? [])]
                     .filter(Boolean)
                     .join(' ')
                     .toLocaleLowerCase('es')
                     .includes(termino);
             });
         },

         /* El mensaje se arma con la llave elegida: nada que rellenar a mano y
            ninguna forma de mandarle a un cliente la clave de otro.

            Sin curl de prueba a proposito: repetia la clave y el secreto
            enteros por segunda vez y hacia el mensaje mucho mas largo. Los
            ejemplos, en curl y en tres lenguajes mas, ya estan en la
            documentacion que se enlaza justo encima. */
         mensaje() {
             const l = this.llave();
             if (! l) return '';

             const prueba = l.entorno === 'sandbox';
             const secreto = this.secreto.trim() || '(pega aquí el API Secret)';
             const servicios = l.servicios.length ? l.servicios.join(' y ') : 'RUC y DNI';

             return [
                 l.titular ? 'Hola ' + l.titular + ',' : 'Hola,',
                 '',
                 prueba
                     ? 'Aquí tienes tus credenciales de prueba para la API de consultas de ' + servicios + '.'
                     : 'Ya tienes acceso a la API de consultas de ' + servicios + '.',
                 '',
                 'Dirección base:',
                 this.base,
                 '',
                 'Tus credenciales, que van en las cabeceras de cada petición:',
                 'X-Api-Key: ' + l.clave,
                 'X-Api-Secret: ' + secreto,
                 '',
                 prueba
                     ? 'Son de prueba: responden con datos de ejemplo, no salen a internet y no gastan cuota. Cuando tengas la integración lista te paso las de producción y solo cambias estas dos cabeceras.'
                     : 'El API Secret no se puede volver a ver, así que guárdalo ahora. Va en tu servidor, nunca en el código de una web o de una app: ahí cualquiera puede leerlo y gastar tu cuota.',
                 '',
                 (l.plan && ! prueba) ? 'Tu plan es ' + l.plan + '. Puedes ver lo que llevas gastado cuando quieras en ' + this.base + '/cuota' : null,
                 (l.plan && ! prueba) ? '' : null,
                 'Documentación, con ejemplos listos en curl, PHP, Python y JavaScript:',
                 this.enlaceDocs,
                 '',
                 'Cualquier duda con la integración, escríbenos.',
                 '',
                 'Un saludo.',
             ].filter(linea => linea !== null).join('\n');
         },

         copiar(texto, cual) {
             navigator.clipboard.writeText(texto).then(() => {
                 this.copiado = cual;
                 setTimeout(() => (this.copiado = null), 2000);
             });
         },

         /* Sin numero: WhatsApp deja elegir el contacto al abrirse. */
         whatsapp() {
             return 'https://wa.me/?text=' + encodeURIComponent(this.mensaje());
         },

         correo() {
             const l = this.llave();
             const asunto = (l && l.entorno === 'sandbox')
                 ? 'Credenciales de prueba - API de RUC y DNI'
                 : 'Tus credenciales - API de RUC y DNI';

             return 'mailto:?subject=' + encodeURIComponent(asunto)
                  + '&body=' + encodeURIComponent(this.mensaje());
         },
     }">

    {{-- 1. Entregar: es a lo que se entra a esta pestaña, asi que va primero --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
            <h2 class="text-base font-semibold text-gray-900">Compartir acceso con un cliente</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Prepara y envía sus credenciales sin redactar el mensaje manualmente.
            </p>
            </div>
            <ol class="flex items-center gap-2 text-[11px] font-medium text-gray-500" aria-label="Pasos para entregar credenciales">
                <li class="flex items-center gap-1.5"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-white">1</span>Seleccionar</li>
                <li class="h-px w-5 bg-gray-200"></li>
                <li class="flex items-center gap-1.5"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-gray-600">2</span>Revisar</li>
                <li class="h-px w-5 bg-gray-200"></li>
                <li class="flex items-center gap-1.5"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-gray-600">3</span>Enviar</li>
            </ol>
        </div>

        {{-- Los datos a un lado y lo que se va a mandar al otro, para verlo
             cambiar segun se rellena en vez de tener que bajar a mirarlo.

             El alto fijo va en el area del mensaje, no en la tarjeta: asi mide
             igual haya cliente elegido o no, y la cabecera y los botones
             quedan siempre fuera del recorte. Antes el alto estaba en la
             tarjeta entera y el mensaje largo se comia los botones. --}}
        <div class="grid gap-5 px-5 py-5 lg:grid-cols-5">

            <div class="lg:col-span-2">
                <div>
                    <div class="flex min-h-6 items-center justify-between gap-3">
                        <label for="llave-entrega" class="block text-sm font-medium text-gray-900">¿A qué cliente?</label>
                        <template x-if="llave()">
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                  :class="llave().entorno === 'sandbox' ? 'bg-violet-100 text-violet-700' : 'bg-blue-100 text-blue-700'"
                                  x-text="llave().entorno === 'sandbox' ? 'SANDBOX · PRUEBAS' : 'PRODUCCIÓN'"></span>
                        </template>
                    </div>
                    <div class="relative mt-2" x-data="{ abierto: false }"
                         @click.outside="abierto = false; filtro = etiquetaLlave()"
                         @keydown.escape.window="abierto = false; filtro = etiquetaLlave()">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m20 20-4-4"/></svg>
                            <input id="llave-entrega" type="search" x-model="filtro"
                                   @focus="filtro = ''; abierto = true"
                                   @click="filtro = ''; abierto = true"
                                   @input="abierto = true"
                                   placeholder="Escribe el cliente o el nombre de la llave…"
                                   role="combobox" :aria-expanded="abierto" aria-controls="lista-llaves"
                                   class="w-full rounded-lg border-gray-300 py-2.5 pl-9 pr-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <button type="button" @click="abierto = ! abierto; if (abierto) { filtro = ''; $nextTick(() => $el.previousElementSibling.focus()) }"
                                    class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-400 hover:text-gray-700"
                                    aria-label="Mostrar todas las llaves">
                                <svg class="h-4 w-4 transition" :class="abierto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
                            </button>
                        </div>

                        <div id="lista-llaves" x-cloak x-show="abierto" x-transition.origin.top
                             class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 shadow-xl">
                            <template x-if="llavesFiltradas('produccion').length">
                                <div>
                                    <p class="sticky top-0 bg-blue-50 px-3 py-2 text-[11px] font-bold uppercase tracking-wide text-blue-700">Producción · credenciales reales</p>
                                    <template x-for="l in llavesFiltradas('produccion')" :key="'prod-' + l.id">
                                        <button type="button" @click="seleccionarLlave(l); abierto = false"
                                                class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm hover:bg-blue-50"
                                                :class="String(llaveId) === String(l.id) && 'bg-blue-50'">
                                            <span class="min-w-0"><span class="block truncate font-medium text-gray-800" x-text="l.titular || 'Sin titular'"></span><span class="block truncate text-xs text-gray-500" x-text="l.nombre"></span></span>
                                            <span class="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">PROD</span>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <template x-if="llavesFiltradas('sandbox').length">
                                <div>
                                    <p class="sticky top-0 bg-violet-50 px-3 py-2 text-[11px] font-bold uppercase tracking-wide text-violet-700">Sandbox · solo pruebas</p>
                                    <template x-for="l in llavesFiltradas('sandbox')" :key="'sandbox-' + l.id">
                                        <button type="button" @click="seleccionarLlave(l); abierto = false"
                                                class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm hover:bg-violet-50"
                                                :class="String(llaveId) === String(l.id) && 'bg-violet-50'">
                                            <span class="min-w-0"><span class="block truncate font-medium text-gray-800" x-text="l.titular || 'Sin titular'"></span><span class="block truncate text-xs text-gray-500" x-text="l.nombre"></span></span>
                                            <span class="shrink-0 rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-bold text-violet-700">SANDBOX</span>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <template x-if="! llavesFiltradas('produccion').length && ! llavesFiltradas('sandbox').length">
                                <p class="px-4 py-8 text-center text-sm text-gray-500">No se encontraron llaves con esa búsqueda.</p>
                            </template>

                            <div class="sticky bottom-0 border-t border-gray-100 bg-white px-3 py-2 text-[11px] text-gray-500"
                                 x-text="(llavesFiltradas('produccion').length + llavesFiltradas('sandbox').length) + ' resultado(s)'"></div>
                        </div>
                    </div>
                    @if($paraEntregar->isEmpty())
                        <p class="mt-2 text-xs text-gray-500">
                            Todavía no hay ninguna llave. Se crean en <span class="font-medium">Mis APIs</span>.
                        </p>
                    @endif
                </div>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <label for="secreto-entrega" class="block text-sm font-medium text-gray-900">
                        API Secret <span class="font-normal text-gray-400">(opcional)</span>
                    </label>
                    <div class="relative mt-2">
                        <input id="secreto-entrega" :type="mostrarSecreto ? 'text' : 'password'" x-model="secreto" autocomplete="off"
                               placeholder="Pégalo aquí"
                               class="w-full rounded-lg border-gray-300 py-2 pl-3 pr-20 font-mono text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" @click="mostrarSecreto = ! mostrarSecreto"
                                class="absolute inset-y-0 right-0 px-3 text-xs font-medium text-blue-600 hover:text-blue-800"
                                x-text="mostrarSecreto ? 'Ocultar' : 'Mostrar'"></button>
                    </div>
                    <p class="mt-2 flex items-start gap-1.5 text-xs leading-relaxed text-gray-500">
                        <svg class="mt-px h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Solo se ve al crear la llave, en <span class="font-medium">Mis APIs</span>. No se guarda aquí.</span>
                    </p>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="flex h-full flex-col overflow-hidden rounded-lg border border-gray-200">
                    <div class="flex h-9 shrink-0 items-center justify-between border-b border-gray-100 bg-gray-50 px-4">
                        <span class="text-xs font-medium text-gray-600">Así le llegará</span>
                        <template x-if="llave()">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                  :class="llave().entorno === 'sandbox' ? 'bg-violet-100 text-violet-700' : 'bg-blue-100 text-blue-700'"
                                  x-text="llave().entorno === 'sandbox' ? 'Sandbox · sin cuota real' : 'Producción · usa cuota'"></span>
                        </template>
                    </div>

                    <template x-if="llaveId">
                        <pre class="h-[22rem] shrink-0 overflow-auto whitespace-pre-wrap p-4 text-xs leading-relaxed text-gray-700"
                             x-text="mensaje()"></pre>
                    </template>

                    <template x-if="! llaveId">
                        <div class="flex h-[22rem] shrink-0 flex-col items-center justify-center px-4 text-center">
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-50 text-blue-500">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16l-4 3v-4a7 7 0 117 3h-1"/></svg>
                            </span>
                            <p class="mt-3 text-sm font-medium text-gray-600">Vista previa del mensaje</p>
                            <p class="mt-1 max-w-xs text-xs text-gray-400">Selecciona una llave para generar el texto que recibirá el cliente.</p>
                        </div>
                    </template>

                    {{-- Las acciones, pegadas al mensaje que envian.

                         La barra esta siempre, aunque no haya cliente elegido:
                         cuando se ocultaba, la tarjeta perdia su alto de golpe
                         y subia y bajaba al elegir en el desplegable. Sin llave
                         los botones se ven apagados y no responden. --}}
                    <div class="flex min-h-14 shrink-0 flex-wrap items-center gap-2 border-t border-gray-200 bg-gray-50 px-4 py-2"
                         :class="llaveId || 'pointer-events-none opacity-40'"
                         :aria-hidden="llaveId ? 'false' : 'true'">
                        <a :href="whatsapp()" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-green-700">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.174.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            Enviar por WhatsApp
                        </a>

                        <a :href="correo()"
                           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Enviar por correo
                        </a>

                        <button type="button" @click="copiar(mensaje(), 'mensaje')"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="copiado === 'mensaje' ? 'Copiado' : 'Copiar'"></span>
                        </button>

                        <p x-show="llaveId && ! secreto.trim()" class="ml-auto text-xs text-amber-700">
                            Falta pegar el API Secret.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </section>

    {{-- 2. El enlace. Aqui NO va un resumen de lo que dice la documentacion:
         seria una copia que se queda vieja en cuanto cambie un limite, que es
         justo lo que esta pestaña evita. Esta a un clic. --}}
    <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">La documentación del cliente</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Cómo se autentica, qué devuelve cada consulta, los errores, los límites y ejemplos
                en curl, PHP, Python y JavaScript. Sin precios.
            </p>
        </div>

        <div class="space-y-3 px-5 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <code class="flex-1 truncate rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700">{{ route('docs.consultas') }}</code>

                <button type="button" @click="copiar(enlaceDocs, 'enlace')"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span x-text="copiado === 'enlace' ? 'Copiado' : 'Copiar'"></span>
                </button>

                <a href="{{ route('docs.consultas') }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Verla
                </a>
            </div>

            <p class="text-xs text-gray-500">
                Se manda el enlace, no una copia: el día que cambie algo, cambia ahí y el cliente
                lo ve. Está enlazada desde la web, así que también la encuentran solos.
            </p>
        </div>
    </section>

</div>
