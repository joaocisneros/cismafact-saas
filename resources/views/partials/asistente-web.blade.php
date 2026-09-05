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
            'texto' => "Bienvenido a Cisma Fact.\n\nTe oriento sobre nuestros servicios, planes y "
                . "precios.\n\n¿Cuál necesitas?",
            'opciones' => [
                ['ir' => 'facturacion', 'texto' => 'Facturación electrónica'],
                ['ir' => 'consultas', 'texto' => 'Consultas de RUC y DNI'],
            ],
        ],

        'facturacion' => [
            'texto' => "Emite facturas, boletas, notas de crédito y débito, y guías de remisión "
                . "electrónicas, firmadas con tu propio certificado digital y enviadas directo a "
                . "SUNAT.\n\n¿De qué forma planeas usarlo?",
            'opciones' => [
                ['ir' => 'fact_prueba', 'texto' => 'Evaluarlo antes de contratar'],
                ['ir' => 'fact_sistema', 'texto' => 'Emitir desde el panel web'],
                ['ir' => 'fact_api', 'texto' => 'Integrar con mi sistema (API)'],
            ],
        ],

        'fact_prueba' => [
            'texto' => "Puedes evaluarlo sin costo. Creas tu cuenta y emites en el ambiente de "
                . "pruebas de SUNAT, con datos de prueba, para validar el proceso completo antes de "
                . "operar.\n\nPara emitir con validez tributaria solo registras tu certificado digital "
                . "y tu clave SOL.",
            'opciones' => [
                ['ir' => 'fact_planes', 'texto' => 'Ver planes y precios'],
                ['enlace' => route('register'), 'texto' => 'Crear cuenta'],
            ],
        ],

        'fact_sistema' => [
            'texto' => "Gestionas todo desde el panel, sin instalar nada. Registras clientes y "
                . "productos, emites el comprobante y se envía a SUNAT en el momento. Consultas el "
                . "estado real de cada uno y remites al cliente su PDF, XML y CDR por correo.\n\n"
                . "Incluye comunicación de baja, resumen diario y notas de crédito y débito.",
            'opciones' => [
                ['ir' => 'fact_planes', 'texto' => 'Ver planes y precios'],
                ['enlace' => route('register'), 'texto' => 'Crear cuenta'],
            ],
        ],

        'fact_api' => [
            'texto' => "Disponemos de una API REST para emitir desde tu propio sistema, en "
                . "cualquier lenguaje de programación. Envías el comprobante y recibes la respuesta de "
                . "SUNAT con su CDR.\n\nCuentas con un entorno de pruebas para desarrollar sin "
                . "consumir comprobantes reales, y documentación con ejemplos de integración.",
            'opciones' => [
                ['enlace' => url('/docs'), 'texto' => 'Ver documentación'],
                ['ir' => 'fact_planes', 'texto' => 'Ver planes y precios'],
            ],
        ],

        'fact_planes' => [
            'texto' => "Los planes de facturación:\n\n"
                . $planesFacturacion->map(fn ($p) => sprintf('• %s — %s al mes, %s comprobantes',
                    $p->name,
                    $soles($p->monthly_price),
                    number_format((int) $p->monthly_document_limit)))->implode("\n")
                . "\n\nTodos incluyen la firma con tu propio certificado y el envío directo a SUNAT.",
            'fin' => true,
        ],

        'consultas' => [
            'texto' => "La API de consultas es independiente de la facturación y se contrata por "
                . "separado.\n\nDel RUC obtienes razón social, estado, condición y domicilio fiscal. "
                . "Del DNI, nombres y apellidos por separado.\n\n¿Qué necesitas?",
            'opciones' => [
                ['ir' => 'cons_planes', 'texto' => 'Ver planes y precios'],
                ['ir' => 'cons_produccion', 'texto' => 'Contratar producción'],
                ['ir' => 'cons_prueba', 'texto' => 'Probar en Sandbox'],
            ],
        ],

        'cons_planes' => [
            'texto' => "Cada consulta se contrata por separado: si solo requieres RUC, pagas "
                . "únicamente RUC.\n\n"
                . $planesConsultas->map(function ($p) use ($soles) {
                    $lineas = $p->apis
                        ->filter(fn ($a) => (int) $a->pivot->limite_mensual > 0)
                        ->map(fn ($a) => sprintf('   %s: %s consultas · %s',
                            strtoupper($a->slug),
                            number_format((int) $a->pivot->limite_mensual),
                            $p->a_medida ? 'a convenir' : $soles($a->pivot->precio_mensual)))
                        ->implode("\n");

                    return sprintf("• %s\n%s\n   Los dos juntos: %s", $p->nombre, $lineas,
                        $p->a_medida ? 'a convenir' : $soles($p->precio_mensual));
                })->implode("\n\n"),
            'opciones' => [
                ['ir' => 'cons_produccion', 'texto' => 'Contratar'],
            ],
        ],

        'cons_produccion' => [
            'texto' => "Las credenciales de producción se entregan de forma coordinada por "
                . "WhatsApp: preparamos tu API Key con el plan que elijas y te la enviamos en el "
                . "momento.\n\nIndícanos qué consultas necesitas —RUC, DNI o ambas— y el volumen "
                . "mensual estimado, y te recomendamos el plan que corresponde.",
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
         class="absolute bottom-16 right-0 flex h-[32rem] max-h-[calc(100vh-8rem)] w-[23rem] max-w-[calc(100vw-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200">

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
                    <p class="text-xs text-blue-100">Atención inmediata</p>
                </div>
            </div>
            <button type="button" @click="abierto = false"
                    class="text-xl leading-none text-blue-100 hover:text-white" aria-label="Cerrar">&times;</button>
        </div>

        <div class="flex-1 space-y-3 overflow-y-auto bg-gray-50 px-4 py-3" x-ref="conversacion">
            <template x-for="(m, i) in mensajes" :key="i">
                <div :class="m.rol === 'usuario' ? 'flex justify-end' : 'flex justify-start'">
                    <p class="max-w-[88%] whitespace-pre-line rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed shadow-sm"
                       :class="m.rol === 'usuario'
                           ? 'rounded-br-sm bg-blue-600 text-white'
                           : 'rounded-bl-sm bg-white text-gray-700'"
                       x-text="m.texto"></p>
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
            <div x-show="opciones.length && ! esperando" class="space-y-1.5 pt-0.5">
                <template x-for="o in opciones" :key="o.texto">
                    <div>
                        <a x-show="o.enlace" :href="o.enlace"
                           class="block rounded-xl border border-blue-200 bg-white px-3.5 py-2 text-sm font-medium text-blue-700 shadow-sm transition hover:border-blue-400 hover:bg-blue-50"
                           x-text="o.texto"></a>
                        <button x-show="! o.enlace" type="button" @click="elegir(o)"
                                class="w-full rounded-xl border border-blue-200 bg-white px-3.5 py-2 text-left text-sm font-medium text-blue-700 shadow-sm transition hover:border-blue-400 hover:bg-blue-50"
                                x-text="o.texto"></button>
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
                          class="max-h-24 flex-1 resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50"></textarea>
                <button type="submit" :disabled="esperando || cerrado || ! borrador.trim()"
                        class="shrink-0 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                    Enviar
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

                this.mensajes.push({ rol: 'asistente', texto: p.texto });
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
                            historial: this.mensajes.slice(-8, -1),
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
