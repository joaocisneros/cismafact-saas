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
         secreto: '',
         copiado: null,
         llaves: @js($paraEntregar),
         enlaceDocs: @js(route('docs.consultas')),
         base: @js(url('/api/consultas')),

         llave() {
             return this.llaves.find(l => String(l.id) === String(this.llaveId)) ?? null;
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
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Entregar a un cliente</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Elige su llave y el mensaje se escribe solo, con su clave dentro.
            </p>
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
                    <label for="llave-entrega" class="block text-sm font-medium text-gray-900">¿A qué cliente?</label>
                    <select id="llave-entrega" x-model="llaveId"
                            class="mt-2 w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Elige una llave…</option>
                        @foreach($paraEntregar as $l)
                            <option value="{{ $l['id'] }}">
                                {{ $l['titular'] ?: 'Sin titular' }} — {{ $l['nombre'] }}{{ $l['entorno'] === 'sandbox' ? ' (prueba)' : '' }}
                            </option>
                        @endforeach
                    </select>
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
                    <input id="secreto-entrega" type="text" x-model="secreto" autocomplete="off"
                           placeholder="Pégalo aquí"
                           class="mt-2 w-full rounded-lg border-gray-300 px-3 py-2.5 font-mono text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                    <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-2">
                        <span class="text-xs font-medium text-gray-600">Así le llegará</span>
                        <template x-if="llave() && llave().entorno === 'sandbox'">
                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">Llave de prueba</span>
                        </template>
                    </div>

                    <template x-if="llaveId">
                        <pre class="h-[19rem] overflow-auto whitespace-pre-wrap p-4 text-xs leading-relaxed text-gray-700"
                             x-text="mensaje()"></pre>
                    </template>

                    <template x-if="! llaveId">
                        <div class="flex h-[19rem] items-center justify-center px-4 text-center">
                            <p class="text-xs text-gray-400">Elige un cliente y aquí sale su mensaje.</p>
                        </div>
                    </template>

                    {{-- Las acciones, pegadas al mensaje que envian: estaban al
                         pie de la tarjeta, lejos del texto al que se refieren. --}}
                    <div x-show="llaveId" x-cloak
                         class="flex flex-wrap items-center gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3">
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

                        <p x-show="! secreto.trim()" class="ml-auto text-xs text-amber-700">
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
