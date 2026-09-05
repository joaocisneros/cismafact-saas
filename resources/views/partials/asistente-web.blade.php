{{-- El chat de la web.

     Va al lado del de WhatsApp, no en su lugar: el asistente resuelve la duda
     de las nueve de la noche, pero quien quiere hablar con una persona tiene
     que poder hacerlo sin pasar por el.

     Si no hay clave de OpenRouter no se pinta nada. Es mejor que no exista a
     que aparezca y falle al primer mensaje. --}}
@if(app(\App\Services\AsistenteWeb::class)->disponible())
    <div x-data="asistenteCismaFact()" x-cloak class="fixed bottom-6 right-24 z-50">

        {{-- La ventana --}}
        <div x-show="abierto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             @keydown.escape.window="abierto = false"
             class="absolute bottom-16 right-0 flex h-[30rem] w-[22rem] max-w-[calc(100vw-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200">

            <div class="flex items-center justify-between bg-blue-600 px-4 py-3 text-white">
                <div>
                    <p class="text-sm font-semibold">Asistente de Cisma Fact</p>
                    <p class="text-xs text-blue-100">Respuestas automáticas al momento</p>
                </div>
                <button type="button" @click="abierto = false"
                        class="text-xl leading-none text-blue-100 hover:text-white" aria-label="Cerrar">&times;</button>
            </div>

            <div class="flex-1 space-y-3 overflow-y-auto px-4 py-3" x-ref="conversacion">
                <template x-for="(m, i) in mensajes" :key="i">
                    <div :class="m.rol === 'usuario' ? 'flex justify-end' : 'flex justify-start'">
                        <p class="max-w-[85%] whitespace-pre-line rounded-2xl px-3.5 py-2 text-sm"
                           :class="m.rol === 'usuario'
                               ? 'rounded-br-sm bg-blue-600 text-white'
                               : 'rounded-bl-sm bg-gray-100 text-gray-800'"
                           x-text="m.texto"></p>
                    </div>
                </template>

                {{-- Los tres puntos mientras piensa: sin esto parece que se colgó. --}}
                <div x-show="esperando" class="flex justify-start">
                    <p class="rounded-2xl rounded-bl-sm bg-gray-100 px-3.5 py-2.5 text-sm text-gray-400">
                        <span class="inline-block animate-pulse">● ● ●</span>
                    </p>
                </div>

                {{-- Cuando ya no puede seguir, la salida a una persona. --}}
                <div x-show="cerrado" class="pt-1 text-center">
                    <a :href="'https://wa.me/' + whatsapp" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                        Seguir por WhatsApp
                    </a>
                </div>
            </div>

            <form @submit.prevent="enviar" class="border-t border-gray-100 p-3">
                <div class="flex items-end gap-2">
                    <textarea x-model="borrador" x-ref="entrada" rows="1" :maxlength="largoMaximo"
                              :disabled="esperando || cerrado"
                              @keydown.enter.prevent="if (! $event.shiftKey) enviar()"
                              @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 96) + 'px'"
                              placeholder="Escribe tu pregunta..."
                              class="max-h-24 flex-1 resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50"></textarea>
                    <button type="submit" :disabled="esperando || cerrado || ! borrador.trim()"
                            class="shrink-0 rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                        Enviar
                    </button>
                </div>
                <p class="mt-1.5 text-center text-[11px] text-gray-400">
                    Respuestas automáticas. Para tu cuenta o soporte, WhatsApp.
                </p>
            </form>
        </div>

        {{-- La burbuja --}}
        <button type="button" @click="alternar"
                class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition hover:bg-blue-700"
                aria-label="Abrir el asistente">
            {{-- Un robot, no un globo de dialogo: el de WhatsApp que tiene al
                 lado ya es un globo, y dos iguales no dejan ver cual lleva a una
                 persona y cual a una respuesta automatica. --}}
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
                whatsapp: @js(config('asistente.whatsapp')),
                largoMaximo: @js(config('asistente.limites.largo_maximo')),
                mensajes: [{
                    rol: 'asistente',
                    texto: 'Hola. Te ayudo con lo de Cisma Fact: qué puedes emitir, los planes, '
                        + 'la API de RUC y DNI o cómo empezar. ¿Qué necesitas?',
                }],

                alternar() {
                    this.abierto = ! this.abierto;

                    if (this.abierto) {
                        this.$nextTick(() => this.$refs.entrada?.focus());
                    }
                },

                async enviar() {
                    const pregunta = this.borrador.trim();

                    if (! pregunta || this.esperando || this.cerrado) return;

                    this.mensajes.push({ rol: 'usuario', texto: pregunta });
                    this.borrador = '';
                    this.esperando = true;
                    this.$nextTick(() => {
                        if (this.$refs.entrada) this.$refs.entrada.style.height = 'auto';
                        this.abajo();
                    });

                    try {
                        /* Se manda la conversacion desde el navegador: asi el
                           servidor no guarda de que habla cada visitante. */
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
                                || 'No pude responderte. Escríbenos por WhatsApp y te atendemos.',
                        });

                        if (datos.cerrado) this.cerrado = true;
                    } catch (e) {
                        this.mensajes.push({
                            rol: 'asistente',
                            texto: 'Se cortó la conexión. Revisa tu internet o escríbenos por WhatsApp.',
                        });
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
@endif
