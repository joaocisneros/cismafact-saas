{{-- El asistente de la web.

     Empieza guiado y no con el cursor parpadeando: quien entra no sabe que
     preguntar, y «escribe tu duda» delante de una caja vacia es la forma mas
     rapida de que cierre la ventana. Se le pregunta primero cual de los dos
     servicios le interesa, que es lo unico que hace falta saber para llevarle a
     lo suyo.

     Los botones dan respuestas escritas, no generadas: precios y planes salen
     de la base de datos y siempre dicen lo mismo. La caja de escribir queda
     debajo para todo lo demas, y eso si va al modelo.

     Va al lado del de WhatsApp, no en su lugar: resuelve la duda de las nueve
     de la noche, pero quien quiere hablar con una persona no tiene que pasar
     por aqui. --}}
@php
    $planesFacturacion = \App\Models\Plan::where('active', true)->orderBy('monthly_price')->get();

    // El gratuito es con el que se prueba y los de pago con los que se emite en
    // produccion. Se separan aqui para no repetir la condicion en cada texto.
    $planPrueba = $planesFacturacion->first(fn ($p) => (float) $p->monthly_price <= 0);
    $planesProduccion = $planesFacturacion->filter(fn ($p) => (float) $p->monthly_price > 0);
    $planesConsultas = \App\Models\ApiPlan::with('apis')
        ->where('activo', true)
        ->orderBy('orden')
        ->get()
        ->filter(fn ($p) => $p->a_medida || (float) $p->precio_mensual > 0);

    $soles = fn ($n) => 'S/ ' . number_format((float) $n, 2);

    // El guion de la conversacion. Cada paso dice algo y ofrece por donde
    // seguir; el que lleva 'fin' cierra con los enlaces de siempre.
    $guia = [
        'inicio' => [
            'texto' => "Bienvenido a Cisma Fact.\n\n¿Sobre cuál de nuestros servicios necesitas "
                . "información?",
            'opciones' => [
                [
                    'ir' => 'facturacion',
                    'texto' => 'Facturación electrónica',
                    'detalle' => 'Facturas, boletas, notas y guías de remisión',
                    'icono' => 'documento',
                ],
                [
                    'ir' => 'consultas',
                    'texto' => 'Consultas de RUC y DNI',
                    'detalle' => 'Datos de SUNAT y RENIEC desde tu sistema',
                    'icono' => 'lupa',
                ],
            ],
        ],

        'facturacion' => [
            'texto' => "Emite facturas, boletas, notas de crédito y débito, y guías de remisión "
                . "electrónicas, firmadas con tu propio certificado digital y enviadas directo a "
                . "SUNAT.\n\n¿De qué forma planeas usarlo?",
            'opciones' => [
                [
                    'ir' => 'fact_prueba',
                    'texto' => 'Evaluarlo antes de contratar',
                    'detalle' => 'Sin costo, en el ambiente de pruebas',
                ],
                [
                    'ir' => 'fact_sistema',
                    'texto' => 'Emitir desde el panel web',
                    'detalle' => 'Sin instalar nada en tu equipo',
                ],
                [
                    'ir' => 'fact_api',
                    'texto' => 'Integrar con mi sistema',
                    'detalle' => 'API REST, en cualquier lenguaje',
                ],
            ],
        ],

        'fact_prueba' => [
            'texto' => ($planPrueba
                    ? sprintf("Para eso está el plan %s: %s al mes, con %s comprobantes.\n\n",
                        $planPrueba->name,
                        $soles($planPrueba->monthly_price),
                        number_format((int) $planPrueba->monthly_document_limit))
                    : "Puedes evaluarlo sin costo.\n\n")
                . "Creas tu cuenta y emites en el ambiente de pruebas de SUNAT, con datos de prueba, "
                . "para validar el proceso completo antes de operar.\n\nCuando decidas emitir con "
                . "validez tributaria, registras tu certificado digital y tu clave SOL, y pasas a un "
                . "plan de producción.",
            'opciones' => [
                ['ir' => 'fact_planes', 'texto' => 'Ver planes y precios'],
                ['enlace' => route('register'), 'texto' => 'Crear cuenta', 'principal' => true],
            ],
        ],

        'fact_sistema' => [
            'texto' => "Gestionas todo desde el panel, sin instalar nada:",
            'lista' => [
                'Registras clientes y productos una sola vez',
                'Emites y se envía a SUNAT en el momento',
                'Consultas el estado real de cada comprobante',
                'Remites al cliente su PDF, XML y CDR por correo',
                'Comunicación de baja, resumen diario y notas',
            ],
            'opciones' => [
                ['ir' => 'fact_planes', 'texto' => 'Ver planes y precios'],
                ['enlace' => route('register'), 'texto' => 'Crear cuenta', 'principal' => true],
            ],
        ],

        'fact_api' => [
            'texto' => "Disponemos de una API REST para emitir desde tu propio sistema, en "
                . "cualquier lenguaje de programación. Envías el comprobante y recibes la respuesta de "
                . "SUNAT con su CDR.\n\nCuentas con un entorno de pruebas para desarrollar sin "
                . "consumir comprobantes reales, y documentación con ejemplos de integración.",
            'opciones' => [
                ['enlace' => url('/docs'), 'texto' => 'Ver documentación', 'principal' => true],
                ['ir' => 'fact_planes', 'texto' => 'Ver planes y precios'],
            ],
        ],

        'fact_planes' => [
            'texto' => 'Planes de facturación electrónica:',
            'grupos' => array_values(array_filter([
                $planPrueba ? [
                    'titulo' => 'Para evaluar',
                    'fichas' => [[
                        'nombre' => $planPrueba->name,
                        'precio' => $soles($planPrueba->monthly_price),
                        'detalle' => number_format((int) $planPrueba->monthly_document_limit)
                            . ' comprobantes en ambiente de pruebas',
                    ]],
                ] : null,
                [
                    'titulo' => 'Para emitir en producción',
                    'fichas' => $planesProduccion->map(fn ($p) => [
                        'nombre' => $p->name,
                        'precio' => $soles($p->monthly_price),
                        'detalle' => number_format((int) $p->monthly_document_limit) . ' comprobantes al mes',
                    ])->values()->all(),
                ],
            ])),
            'nota' => 'Los de producción incluyen la firma con tu propio certificado y el envío '
                . 'directo a SUNAT. Puedes cambiar de plan cuando lo necesites.',
            'opciones' => [
                ['enlace' => route('register'), 'texto' => 'Crear cuenta', 'principal' => true],
            ],
            'fin' => true,
        ],

        'consultas' => [
            'texto' => "La API de consultas es independiente de la facturación y se contrata por "
                . "separado.",
            'lista' => [
                'RUC: razón social, estado, condición y domicilio fiscal',
                'DNI: nombres y apellidos por separado',
            ],
            'opciones' => [
                [
                    'ir' => 'cons_planes',
                    'texto' => 'Ver planes y precios',
                    'detalle' => 'Cada consulta se contrata por separado',
                ],
                [
                    'ir' => 'cons_produccion',
                    'texto' => 'Contratar producción',
                    'detalle' => 'Coordinamos tu API Key por WhatsApp',
                ],
                [
                    'ir' => 'cons_prueba',
                    'texto' => 'Probar en Sandbox',
                    'detalle' => 'Sin costo, antes de contratar',
                ],
            ],
        ],

        'cons_planes' => [
            'texto' => 'Si solo requieres RUC, pagas únicamente RUC:',
            'grupos' => $planesConsultas->map(fn ($p) => [
                'titulo' => $p->nombre,
                'fichas' => $p->apis
                    ->filter(fn ($a) => (int) $a->pivot->limite_mensual > 0)
                    ->map(fn ($a) => [
                        'nombre' => strtoupper($a->slug),
                        'precio' => $p->a_medida ? 'A convenir' : $soles($a->pivot->precio_mensual),
                        'detalle' => number_format((int) $a->pivot->limite_mensual) . ' consultas al mes',
                    ])->values()->all(),
                'total' => $p->a_medida ? null : 'Contratando ambas: ' . $soles($p->precio_mensual),
            ])->values()->all(),
            'opciones' => [
                ['ir' => 'cons_produccion', 'texto' => 'Contratar', 'principal' => true],
            ],
        ],

        'cons_produccion' => [
            'texto' => "Las credenciales de producción se entregan de forma coordinada por "
                . "WhatsApp: preparamos tu API Key con el plan que elijas y te la enviamos en el "
                . "momento.\n\nIndícanos:",
            'lista' => [
                'Qué consultas usarás: RUC, DNI o ambas',
                'El volumen mensual estimado',
            ],
            'nota' => 'Con eso te recomendamos el plan que corresponde.',
            'fin' => true,
            'destacar_whatsapp' => true,
        ],

        'cons_prueba' => [
            'texto' => "Disponemos de un entorno Sandbox sin costo para que valides la integración "
                . "antes de contratar: mismas respuestas y mismo formato, con datos de prueba.\n\n"
                . "Escríbenos por WhatsApp y te entregamos las credenciales.",
            'opciones' => [
                ['enlace' => url('/docs'), 'texto' => 'Ver documentación'],
            ],
            'destacar_whatsapp' => true,
        ],
    ];
@endphp

<div x-data="asistenteCismaFact()" x-cloak class="fixed bottom-6 right-24 z-50">

    <div x-show="abierto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         @keydown.escape.window="abierto = false"
         class="absolute bottom-16 right-0 flex h-[34rem] max-h-[calc(100vh-7rem)] w-[24rem] max-w-[calc(100vw-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200">

        <div class="flex items-center justify-between bg-blue-600 px-4 py-3 text-white">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M12 2.5v2.6"/>
                        <circle cx="12" cy="2" r="1.1" fill="currentColor" stroke="none"/>
                        <rect x="3.6" y="5.6" width="16.8" height="12.4" rx="3.4"/>
                        <circle cx="9" cy="11.3" r="1.35" fill="currentColor" stroke="none"/>
                        <circle cx="15" cy="11.3" r="1.35" fill="currentColor" stroke="none"/>
                        <path d="M9.4 14.9h5.2"/>
                    </svg>
                </span>
                <div>
                    <p class="text-sm font-semibold leading-tight">Asistente de Cisma Fact</p>
                    <p class="flex items-center gap-1.5 text-xs text-blue-100">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-400"></span>
                        Atención inmediata
                    </p>
                </div>
            </div>
            <button type="button" @click="abierto = false"
                    class="text-xl leading-none text-blue-100 hover:text-white" aria-label="Cerrar">&times;</button>
        </div>

        <div class="flex-1 space-y-2.5 overflow-y-auto bg-gray-50 px-3.5 py-3.5" x-ref="conversacion">
            <template x-for="(m, i) in mensajes" :key="i">
                <div>
                    {{-- Lo que dice el visitante: a la derecha y sin adornos. --}}
                    <div x-show="m.rol === 'usuario'" class="flex justify-end">
                        <p class="max-w-[85%] rounded-2xl rounded-br-md bg-blue-600 px-3.5 py-2 text-sm text-white"
                           x-text="m.texto"></p>
                    </div>

                    {{-- Y la respuesta, cada cosa con su forma: el texto en su
                         globo, lo que se enumera como lista, y los precios en
                         fichas con el importe a la derecha, que dentro de un
                         parrafo no hay quien los encuentre. --}}
                    <div x-show="m.rol === 'asistente'" class="space-y-2">
                        <p x-show="m.texto"
                           class="max-w-[92%] whitespace-pre-line rounded-2xl rounded-bl-md bg-white px-3.5 py-2.5 text-sm leading-relaxed text-gray-700 shadow-sm ring-1 ring-gray-100"
                           x-text="m.texto"></p>

                        <ul x-show="m.lista?.length"
                            class="max-w-[92%] space-y-1.5 rounded-2xl bg-white px-3.5 py-2.5 shadow-sm ring-1 ring-gray-100">
                            <template x-for="p in (m.lista ?? [])" :key="p">
                                <li class="flex gap-2 text-sm leading-snug text-gray-700">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" fill="none"
                                         stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-text="p"></span>
                                </li>
                            </template>
                        </ul>

                        <template x-for="g in (m.grupos ?? [])" :key="g.titulo">
                            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                                <p class="border-b border-gray-100 bg-gray-50/70 px-3.5 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"
                                   x-text="g.titulo"></p>

                                <template x-for="f in g.fichas" :key="f.nombre">
                                    <div class="flex items-baseline justify-between gap-3 border-b border-gray-50 px-3.5 py-2.5 last:border-0">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-gray-900" x-text="f.nombre"></span>
                                            <span class="block text-xs text-gray-500" x-text="f.detalle"></span>
                                        </span>
                                        <span class="shrink-0 whitespace-nowrap text-sm font-bold tabular-nums text-blue-700"
                                              x-text="f.precio"></span>
                                    </div>
                                </template>

                                <p x-show="g.total"
                                   class="bg-blue-50/60 px-3.5 py-2 text-xs font-semibold text-blue-800"
                                   x-text="g.total"></p>
                            </div>
                        </template>

                        <p x-show="m.nota" class="max-w-[92%] px-1 text-xs leading-relaxed text-gray-500"
                           x-text="m.nota"></p>
                    </div>
                </div>
            </template>

            <div x-show="esperando" class="flex justify-start">
                <p class="rounded-2xl rounded-bl-sm bg-white px-3.5 py-2.5 text-sm text-gray-400 shadow-sm">
                    <span class="inline-block animate-pulse">● ● ●</span>
                </p>
            </div>

            {{-- Por donde seguir. Se guarda el sitio para volver: quien entra a
                 los planes y no era lo que buscaba, no tiene que reabrir el
                 chat para probar la otra rama. --}}
            {{-- Por donde seguir. Cada opcion lleva debajo lo que es: sin eso
                 hay que pulsarla para averiguar que habia detras. --}}
            <div x-show="opciones.length && ! esperando" class="space-y-1.5 pt-1">
                <template x-for="o in opciones" :key="o.texto">
                    <div>
                        <a x-show="o.enlace" :href="o.enlace"
                           class="flex items-center gap-2.5 rounded-xl border px-3.5 py-2.5 transition"
                           :class="o.principal
                               ? 'border-blue-600 bg-blue-600 text-white hover:bg-blue-700'
                               : 'border-gray-200 bg-white text-gray-800 shadow-sm hover:border-blue-300 hover:bg-blue-50/50'">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold leading-tight" x-text="o.texto"></span>
                                <span x-show="o.detalle" class="mt-0.5 block text-xs leading-snug"
                                      :class="o.principal ? 'text-blue-100' : 'text-gray-500'"
                                      x-text="o.detalle"></span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 opacity-40" fill="none" stroke="currentColor"
                                 stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>

                        <button x-show="! o.enlace" type="button" @click="elegir(o)"
                                class="flex w-full items-center gap-2.5 rounded-xl border px-3.5 py-2.5 text-left transition"
                                :class="o.principal
                                    ? 'border-blue-600 bg-blue-600 text-white hover:bg-blue-700'
                                    : 'border-gray-200 bg-white text-gray-800 shadow-sm hover:border-blue-300 hover:bg-blue-50/50'">
                            <span x-show="o.icono" class="shrink-0"
                                  :class="o.principal ? 'text-white' : 'text-blue-600'">
                                <svg x-show="o.icono === 'documento'" class="h-5 w-5" fill="none"
                                     stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <svg x-show="o.icono === 'lupa'" class="h-5 w-5" fill="none"
                                     stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold leading-tight" x-text="o.texto"></span>
                                <span x-show="o.detalle" class="mt-0.5 block text-xs leading-snug"
                                      :class="o.principal ? 'text-blue-100' : 'text-gray-500'"
                                      x-text="o.detalle"></span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 opacity-40" fill="none" stroke="currentColor"
                                 stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- El WhatsApp: siempre disponible al final de una rama, y
                 destacado cuando lo que pidio se resuelve hablando. --}}
            <div x-show="(mostrarWhatsapp || cerrado) && ! esperando" class="pt-1">
                <a :href="'https://wa.me/' + whatsapp" target="_blank" rel="noopener"
                   class="flex items-center justify-center gap-2 rounded-xl bg-green-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-600">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5 0-.2 0-.4 0-.5 0-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 2-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2z"/>
                    </svg>
                    Escríbenos por WhatsApp
                </a>
            </div>

            <div x-show="mensajes.length > 1 && ! esperando" class="pt-0.5 text-center">
                <button type="button" @click="empezar" class="text-xs text-gray-400 underline hover:text-gray-600">
                    Volver al inicio
                </button>
            </div>
        </div>

        <form @submit.prevent="enviar" class="border-t border-gray-100 bg-white p-3">
            <div class="flex items-end gap-2">
                <textarea x-model="borrador" x-ref="entrada" rows="1" :maxlength="largoMaximo"
                          :disabled="esperando || cerrado"
                          @keydown.enter.prevent="if (! $event.shiftKey) enviar()"
                          @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 96) + 'px'"
                          placeholder="O escribe tu consulta"
                          class="max-h-[5.5rem] flex-1 resize-none rounded-xl border border-gray-300 px-3.5 py-2 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-gray-50"></textarea>
                <button type="submit" :disabled="esperando || cerrado || ! borrador.trim()"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400"
                        aria-label="Enviar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-7 7m7-7l7 7"/>
                    </svg>
                </button>
            </div>
            <p class="mt-1.5 text-center text-[11px] text-gray-400">
                Para consultas sobre tu cuenta o soporte, escríbenos por WhatsApp.
            </p>
        </form>
    </div>

    <button type="button" @click="alternar"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition hover:bg-blue-700"
            aria-label="Abrir el asistente">
        {{-- Un robot, no un globo: al lado esta el de WhatsApp, que ya es un
             globo, y con dos iguales no se ve cual lleva a una persona. --}}
        <svg x-show="! abierto" class="h-7 w-7" fill="none" stroke="currentColor"
             stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M12 2.5v2.6"/>
            <circle cx="12" cy="2" r="1.1" fill="currentColor" stroke="none"/>
            <rect x="3.6" y="5.6" width="16.8" height="12.4" rx="3.4"/>
            <path d="M1.6 10.4v3.4M22.4 10.4v3.4"/>
            <circle cx="9" cy="11.3" r="1.35" fill="currentColor" stroke="none"/>
            <circle cx="15" cy="11.3" r="1.35" fill="currentColor" stroke="none"/>
            <path d="M9.4 14.9h5.2"/>
        </svg>
        <svg x-show="abierto" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

<script>
    function asistenteCismaFact() {
        return {
            abierto: false,
            esperando: false,
            cerrado: false,
            borrador: '',
            mensajes: [],
            opciones: [],
            mostrarWhatsapp: false,

            guia: @js($guia),
            whatsapp: @js(config('asistente.whatsapp')),
            largoMaximo: @js(config('asistente.limites.largo_maximo')),
            conModelo: @js(app(\App\Services\AsistenteWeb::class)->disponible()),

            init() {
                this.empezar();
            },

            empezar() {
                this.mensajes = [];
                this.opciones = [];
                this.mostrarWhatsapp = false;
                this.cerrado = false;
                this.ir('inicio');
            },

            /* Un paso del guion: lo que dice y por donde puede seguir. */
            ir(paso) {
                const p = this.guia[paso];

                if (! p) return;

                this.mensajes.push({
                    rol: 'asistente',
                    texto: p.texto ?? '',
                    lista: p.lista ?? null,
                    grupos: p.grupos ?? null,
                    nota: p.nota ?? null,
                });

                this.opciones = p.opciones ?? [];
                this.mostrarWhatsapp = !! (p.destacar_whatsapp || p.fin);

                this.$nextTick(() => this.abajo());
            },

            /* Lo elegido se ve como mensaje suyo: si no, la conversacion queda
               con las respuestas sueltas y sin lo que las provoco. */
            elegir(opcion) {
                this.mensajes.push({ rol: 'usuario', texto: opcion.texto });
                this.opciones = [];
                this.ir(opcion.ir);
            },

            alternar() {
                this.abierto = ! this.abierto;

                if (this.abierto) {
                    this.$nextTick(() => this.abajo());
                }
            },

            async enviar() {
                const pregunta = this.borrador.trim();

                if (! pregunta || this.esperando || this.cerrado) return;

                this.mensajes.push({ rol: 'usuario', texto: pregunta });
                this.borrador = '';
                this.opciones = [];
                this.$nextTick(() => {
                    if (this.$refs.entrada) this.$refs.entrada.style.height = 'auto';
                    this.abajo();
                });

                /* Sin modelo configurado no se llama a nadie: se pasa a una
                   persona en vez de dejarle esperando por nada. */
                if (! this.conModelo) {
                    this.mensajes.push({
                        rol: 'asistente',
                        texto: 'Para esa consulta te atenderá mejor una persona. Escríbenos por WhatsApp.',
                    });
                    this.mostrarWhatsapp = true;
                    this.$nextTick(() => this.abajo());
                    return;
                }

                this.esperando = true;

                try {
                    const r = await fetch(@js(route('asistente')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({
                            pregunta: pregunta,
                            historial: this.mensajes
                                .slice(-8, -1)
                                .map(m => ({ rol: m.rol, texto: m.texto }))
                                .filter(m => m.texto),
                        }),
                    });

                    const datos = await r.json().catch(() => ({}));

                    this.mensajes.push({
                        rol: 'asistente',
                        texto: datos.texto
                            || 'No fue posible procesar tu consulta. Escríbenos por WhatsApp y te atendemos.',
                    });

                    this.mostrarWhatsapp = true;

                    if (datos.cerrado) this.cerrado = true;
                } catch (e) {
                    this.mensajes.push({
                        rol: 'asistente',
                        texto: 'Se interrumpió la conexión. Verifica tu red o escríbenos por WhatsApp.',
                    });
                    this.mostrarWhatsapp = true;
                } finally {
                    this.esperando = false;
                    this.$nextTick(() => this.abajo());
                }
            },

            abajo() {
                const c = this.$refs.conversacion;
                if (c) c.scrollTop = c.scrollHeight;
            },
        };
    }
</script>
