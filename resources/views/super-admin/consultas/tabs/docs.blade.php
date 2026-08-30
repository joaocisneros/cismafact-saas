{{-- Lo que hace falta para entregarle la API a un cliente.

     Aqui NO se repite la documentacion: esa vive en una pagina publica y se
     manda por enlace. Una copia dentro del panel se quedaria vieja el dia que
     cambie algo, y acabariamos con dos versiones distintas de lo mismo.

     Lo que si va aqui es lo que no puede ser publico: el texto de entrega con
     sus credenciales dentro. --}}
<div class="space-y-5"
     x-data="{
         copiado: null,
         copiar(texto, cual) {
             navigator.clipboard.writeText(texto).then(() => {
                 this.copiado = cual;
                 setTimeout(() => (this.copiado = null), 2000);
             });
         },
     }">

    {{-- 1. El enlace, que es lo que se manda --}}
    <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">La documentación del cliente</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Página pública: cómo se autentica, qué devuelve cada consulta, los errores y
                ejemplos en curl, PHP, Python y JavaScript. Sin precios.
            </p>
        </div>

        <div class="space-y-3 px-5 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <code class="flex-1 truncate rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700">{{ route('docs.consultas') }}</code>

                <button type="button"
                        @click="copiar('{{ route('docs.consultas') }}', 'enlace')"
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

    {{-- 2. El mensaje de entrega --}}
    <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Mensaje de entrega</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Para mandarle al cliente junto con sus credenciales. Cámbiale el nombre y pega la clave y el secreto.
            </p>
        </div>

        @php
            // Se arma aqui y no en el JavaScript para no pelearse con las
            // comillas y los saltos de linea dentro del atributo.
            $entrega = "Hola,\n\n"
                . "Ya tienes acceso a la API de consultas de RUC y DNI.\n\n"
                . "Dirección base:\n" . url('/api/consultas') . "\n\n"
                . "Tus credenciales:\n"
                . "X-Api-Key: (pegar aquí)\n"
                . "X-Api-Secret: (pegar aquí)\n\n"
                . "El secreto solo se enseña una vez, así que guárdalo ahora. Va en tu "
                . "servidor, nunca en el código de una web o de una app: ahí cualquiera "
                . "puede leerlo y gastar tu cuota.\n\n"
                . "Documentación, con ejemplos en curl, PHP, Python y JavaScript:\n"
                . route('docs.consultas') . "\n\n"
                . "Para probar rápido:\n"
                . "curl -H \"X-Api-Key: TU_KEY\" -H \"X-Api-Secret: TU_SECRET\" "
                . url('/api/consultas/ruc') . "/20000000001\n\n"
                . "Cualquier duda con la integración, escríbenos.\n\n"
                . "Un saludo.";
        @endphp

        <div class="space-y-3 px-5 py-4">
            <pre class="max-h-72 overflow-auto whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-4 text-xs leading-relaxed text-gray-700">{{ $entrega }}</pre>

            <button type="button"
                    @click="copiar(@js($entrega), 'mensaje')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <span x-text="copiado === 'mensaje' ? 'Copiado' : 'Copiar mensaje'"></span>
            </button>

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                <span class="font-medium">El secreto se enseña una sola vez.</span>
                Se copia al crear la llave, en <span class="font-medium">Mis APIs</span>. Si ya lo
                cerraste, no se puede recuperar: hay que generar credenciales nuevas.
            </div>
        </div>
    </section>

    {{-- 3. Lo que responde, para no tener que abrir la web --}}
    <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Resumen</h2>
            <p class="mt-0.5 text-xs text-gray-500">Lo justo para responder a un cliente sin abrir la documentación.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Petición</th>
                        <th class="px-5 py-3">Para qué</th>
                        <th class="px-5 py-3">Gasta cuota</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs text-gray-700">GET /api/consultas/ruc/{numero}</td>
                        <td class="px-5 py-3 text-gray-600">Razón social, estado, condición y domicilio fiscal.</td>
                        <td class="px-5 py-3 text-gray-600">Sí, si sale bien</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs text-gray-700">GET /api/consultas/dni/{numero}</td>
                        <td class="px-5 py-3 text-gray-600">Nombres y apellidos.</td>
                        <td class="px-5 py-3 text-gray-600">Sí, si sale bien</td>
                    </tr>
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs text-gray-700">GET /api/consultas/cuota</td>
                        <td class="px-5 py-3 text-gray-600">Lo gastado y lo que queda, por servicio.</td>
                        <td class="px-5 py-3 text-gray-600">No</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-5 py-4 text-xs text-gray-500">
            Cabeceras <code class="rounded bg-gray-100 px-1">X-Api-Key</code> y
            <code class="rounded bg-gray-100 px-1">X-Api-Secret</code> en cada petición ·
            30 por minuto · lo que falla no gasta cuota · la cuota se reinicia el día 1.
        </div>
    </section>

</div>
