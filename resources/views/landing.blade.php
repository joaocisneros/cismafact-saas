<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO básico --}}
    <title>Cisma Fact — Facturación Electrónica SUNAT en Perú | Facturas, Boletas y Guías</title>
    <meta name="description" content="Emite facturas, boletas, notas de crédito/débito y guías de remisión electrónicas directo a SUNAT con tu propio certificado. Sin intermediarios, con API para integrar tu negocio. Empieza gratis.">
    <meta name="keywords" content="facturación electrónica, factura electrónica SUNAT, boleta electrónica, guía de remisión electrónica, comprobantes electrónicos Perú, facturación SUNAT, API facturación, Cisma Fact">
    <meta name="author" content="Cisma Fact">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">
    @if(config('services.google.site_verification'))
        <meta name="google-site-verification" content="{{ config('services.google.site_verification') }}">
    @endif
    <link rel="icon" href="{{ config('platform.favicon_url', asset('assets/brand/favicon.png')) }}">

    {{-- Open Graph (Facebook, WhatsApp, LinkedIn) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Cisma Fact">
    <meta property="og:title" content="Cisma Fact — Facturación Electrónica SUNAT en Perú">
    <meta property="og:description" content="Emite tus comprobantes electrónicos directo a SUNAT con tu propio certificado. Sin intermediarios y con API para integrar tu negocio.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ config('platform.logo_url', asset('assets/brand/cisma-fact.png')) }}">
    <meta property="og:locale" content="es_PE">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Cisma Fact — Facturación Electrónica SUNAT en Perú">
    <meta name="twitter:description" content="Emite tus comprobantes electrónicos directo a SUNAT con tu propio certificado. Sin intermediarios y con API.">
    <meta name="twitter:image" content="{{ config('platform.logo_url', asset('assets/brand/cisma-fact.png')) }}">

    {{-- Datos estructurados para Google (JSON-LD) --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "SoftwareApplication",
        "name": "Cisma Fact",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Web",
        "description": "Plataforma peruana de facturación electrónica que emite facturas, boletas, notas y guías de remisión directo a SUNAT con tu propio certificado digital.",
        "url": "{{ url('/') }}",
        "offers": {
            "@@type": "Offer",
            "priceCurrency": "PEN"
        },
        "provider": {
            "@@type": "Organization",
            "name": "Cisma Fact",
            "url": "{{ url('/') }}",
            "areaServed": "PE"
        }
    }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-800">

    {{-- Header --}}
    <header class="border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="#" class="flex items-center">
                <img src="{{ config('platform.logo_url', asset('assets/brand/cisma-fact.png')) }}"
                     alt="{{ config('app.name', 'Cisma Fact') }}"
                     class="h-11 w-auto">
            </a>
            <nav class="flex items-center gap-1 sm:gap-3">
                {{-- Dos documentaciones, y son cosas distintas: una es para emitir
                     comprobantes y la otra para consultar RUC y DNI. Sueltas en la
                     barra no se sabria cual es cual, y la dejaban llena. Va sin
                     JavaScript porque esta pagina no carga Alpine; con focus ademas
                     de hover, para que tambien se abra con el teclado. --}}
                <div class="group relative hidden md:block">
                    <button type="button"
                            class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-600 group-hover:text-blue-600 group-focus-within:text-blue-600">
                        Documentación
                        <svg class="h-4 w-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Pegado al boton, sin hueco: con separacion el raton se sale
                         por el medio y el menu se cierra antes de llegar. --}}
                    <div class="invisible absolute left-0 top-full z-20 w-64 pt-2 opacity-0 transition
                                group-hover:visible group-hover:opacity-100
                                group-focus-within:visible group-focus-within:opacity-100">
                        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
                            <a href="{{ route('docs') }}" class="block px-4 py-3 hover:bg-gray-50">
                                <span class="block text-sm font-medium text-gray-900">API de facturación</span>
                                <span class="block text-xs text-gray-500">Emitir comprobantes a SUNAT</span>
                            </a>
                            <a href="{{ route('docs.consultas') }}" class="block border-t border-gray-100 px-4 py-3 hover:bg-gray-50">
                                <span class="block text-sm font-medium text-gray-900">API de RUC y DNI</span>
                                <span class="block text-xs text-gray-500">Consultar datos por número</span>
                            </a>
                        </div>
                    </div>
                </div>
                <a href="#nosotros" class="hidden md:inline-block px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-600">Nosotros</a>
                <a href="#planes" class="hidden md:inline-block px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-600">Planes</a>
                <a href="#contacto" class="hidden md:inline-block px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-600">Contacto</a>
                <a href="{{ route('login') }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700">Crear cuenta</a>
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <section class="max-w-6xl mx-auto px-6 py-20 text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 leading-tight">
            Emite tus comprobantes electrónicos<br><span class="text-blue-600">directo a SUNAT</span>
        </h1>
        <p class="mt-5 text-lg text-gray-600 max-w-2xl mx-auto">
            Facturas, boletas, notas y guías de remisión. Sin intermediarios, con tu propio certificado, y con API para integrar tu negocio.
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">Comenzar ahora</a>
            <a href="#planes" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200">Ver planes</a>
        </div>
    </section>

    {{-- No importa qué sistema uses → Cisma Fact → SUNAT --}}
    <section class="bg-gray-50 py-16">
        <div class="max-w-6xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-gray-900">No importa qué sistema uses</h2>
            <p class="mt-2 text-center text-gray-500 max-w-2xl mx-auto">
                Con Cisma Fact —desde la web o con la API— generas tus comprobantes electrónicos y los envías
                <strong>directo a SUNAT</strong>, sin importar tu software, lenguaje de programación o dispositivo.
            </p>

            <div class="mt-12 flex flex-col items-center justify-center gap-6 lg:flex-row lg:gap-4">
                {{-- Tus plataformas --}}
                <div class="w-full max-w-xs">
                    <p class="mb-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-400">Tu sistema / plataforma</p>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach(['🪟 Windows', '🐧 Linux', '🍎 Mac', '🌐 Web', '🤖 Android', '📱 iOS'] as $p)
                            <div class="rounded-lg border border-gray-200 bg-white px-2 py-3 text-center text-xs font-medium text-gray-600 shadow-sm">{{ $p }}</div>
                        @endforeach
                    </div>
                </div>

                <div class="text-3xl text-gray-300 rotate-90 lg:rotate-0">&rarr;</div>

                {{-- Cisma Fact --}}
                <div class="rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 px-10 py-7 text-center text-white shadow-lg">
                    <div class="text-2xl font-bold">Cisma Fact</div>
                    <div class="mt-1 text-xs text-blue-100">Web + API · firma con tu certificado</div>
                </div>

                <div class="text-3xl text-gray-300 rotate-90 lg:rotate-0">&rarr;</div>

                {{-- SUNAT --}}
                <div class="rounded-2xl border-2 border-gray-200 bg-white px-10 py-7 text-center shadow-sm">
                    <div class="text-2xl font-bold text-gray-800">SUNAT</div>
                    <div class="mt-1 text-xs text-gray-400">Comprobantes válidos</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="bg-gray-50 py-16">
        <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['📄', 'Todos los comprobantes', 'Facturas, boletas, notas de crédito/débito y guías de remisión electrónicas.'],
                ['🔒', 'Directo a SUNAT', 'Sin OSE ni intermediarios. Firmas con tu propio certificado digital.'],
                ['⚡', 'API para integrar', 'Conecta tu sistema, ERP o tienda online con nuestra API REST.'],
                ['🧾', 'Gestión de clientes y series', 'Administra tus clientes y correlativos desde un solo lugar.'],
                ['🔔', 'Alertas y consultas', 'Verifica el estado real en SUNAT y recibe avisos de vencimientos.'],
                ['📊', 'Reportes', 'Estadísticas de ventas y documentos al instante.'],
            ] as [$icon, $titulo, $desc])
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="text-3xl mb-3">{{ $icon }}</div>
                    <h3 class="font-semibold text-gray-900 mb-1">{{ $titulo }}</h3>
                    <p class="text-sm text-gray-600">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Nosotros / Por qué elegirnos --}}
    <section id="nosotros" class="max-w-6xl mx-auto px-6 py-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-sm font-semibold text-blue-600 uppercase tracking-wide">Nosotros</span>
                <h2 class="mt-2 text-3xl font-bold text-gray-900">Facturación electrónica, sin intermediarios</h2>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    Cisma Fact es una plataforma peruana de facturación electrónica que emite tus comprobantes
                    <strong>directamente a SUNAT</strong>. A diferencia de otros servicios, no dependes de un OSE
                    ni de terceros: firmas con <strong>tu propio certificado digital</strong> y tus documentos
                    viajan directo, sin pasar por las manos de nadie más.
                </p>
                <p class="mt-4 text-gray-600 leading-relaxed">
                    Eso significa <strong>más control, más privacidad y menos puntos de falla</strong>. Tu información
                    es tuya, tu certificado es tuyo, y tu operación no se detiene porque un intermediario tenga problemas.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">Crear cuenta gratis</a>
                    <a href="#contacto" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200">Hablar con ventas</a>
                </div>
            </div>
            <div class="space-y-4">
                @foreach([
                    ['🔒', 'Tú tienes el control', 'Emites con tu propio certificado digital, sin ceder tus datos a un intermediario.'],
                    ['⚡', 'Activación rápida', 'Cargas tu certificado y credenciales SUNAT, y empiezas a emitir el mismo día.'],
                    ['🧩', 'Todo en un solo lugar', 'Clientes, series, comprobantes, consultas a SUNAT y reportes en una sola plataforma.'],
                    ['👨‍💻', 'Pensado para crecer', 'API REST lista para conectar tu tienda, POS o ERP cuando lo necesites.'],
                ] as [$icon, $titulo, $desc])
                    <div class="flex items-start gap-4 bg-gray-50 rounded-xl p-5">
                        <div class="text-2xl">{{ $icon }}</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $titulo }}</h3>
                            <p class="text-sm text-gray-600">{{ $desc }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Para desarrolladores / Para empresas --}}
    <section class="bg-gray-50 py-16">
        <div class="max-w-6xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-gray-900">Dos formas de emitir con Cisma Fact</h2>
            <p class="text-center text-gray-500 mt-2">Elige la que mejor se adapte a tu negocio.</p>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Para desarrolladores --}}
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col">
                    <div class="h-36 bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-6xl">👨‍💻</div>
                    <div class="p-7 flex flex-col flex-1">
                        <h3 class="text-2xl font-light text-gray-900">Para <span class="font-bold text-blue-600">desarrolladores</span></h3>
                        <p class="mt-3 text-gray-600 leading-relaxed">
                            Si tienes un software, tienda online, POS o ERP en <strong>cualquier lenguaje de programación</strong>,
                            intégralo con nuestra <strong>API REST</strong> y emite documentos electrónicos en cuestión de minutos.
                        </p>
                        <p class="mt-3 text-gray-600 leading-relaxed">
                            Te damos <strong>documentación con ejemplos</strong> en PHP, Laravel y JavaScript, además de una
                            <strong>colección Postman</strong> para que pruebes la integración sin escribir una sola línea.
                        </p>
                        <a href="{{ route('register') }}" class="mt-5 inline-block self-start px-6 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">Obtener mis API Keys</a>
                    </div>
                </div>

                {{-- Para empresas --}}
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col">
                    <div class="h-36 bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center text-white text-6xl">🏢</div>
                    <div class="p-7 flex flex-col flex-1">
                        <h3 class="text-2xl font-light text-gray-900">Para <span class="font-bold text-emerald-600">empresas</span></h3>
                        <p class="mt-3 text-gray-600 leading-relaxed">
                            ¿No tienes un sistema de ventas? Usa <strong>Cisma Fact Online</strong>: emite facturas, boletas,
                            notas de crédito/débito y guías de remisión directamente desde tu navegador.
                        </p>
                        <p class="mt-3 text-gray-600 leading-relaxed">
                            <strong>Sin instalar nada y sin conocimientos técnicos.</strong> Solo cargas tu certificado,
                            registras tus clientes y series, y empiezas a emitir el mismo día.
                        </p>
                        <a href="{{ route('register') }}" class="mt-5 inline-block self-start px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700">Empezar gratis</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Planes --}}
    <section id="planes" class="max-w-6xl mx-auto px-6 py-20">
        <h2 class="text-3xl font-bold text-center text-gray-900">Planes</h2>
        <p class="text-center text-gray-500 mt-2">Elige el plan que se ajuste a tu negocio.</p>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($plans as $plan)
                <div class="rounded-2xl border border-gray-200 p-6 flex flex-col {{ (float) $plan->monthly_price > 0 ? 'shadow-sm' : '' }}">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h3>
                    <p class="mt-3">
                        <span class="text-3xl font-bold text-gray-900">S/ {{ number_format($plan->monthly_price, 2) }}</span>
                        <span class="text-gray-500 text-sm">/mes</span>
                    </p>
                    <ul class="mt-5 space-y-2 text-sm text-gray-600 flex-1">
                        <li>✔ {{ $plan->monthly_document_limit ? number_format($plan->monthly_document_limit) : 'Ilimitados' }} documentos/mes</li>
                        <li>✔ {{ $plan->user_limit ? $plan->user_limit : 'Ilimitados' }} usuarios</li>
                        <li>✔ {{ $plan->api_request_limit ? number_format($plan->api_request_limit) : 'Ilimitadas' }} llamadas API/mes</li>
                        <li>{{ $plan->support_included ? '✔ Soporte incluido' : '✖ Sin soporte' }}</li>
                    </ul>
                    <a href="{{ route('register') }}" class="mt-6 px-4 py-2.5 text-center bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">Empezar</a>
                </div>
            @empty
                <p class="col-span-3 text-center text-gray-500">Pronto publicaremos nuestros planes.</p>
            @endforelse
        </div>
    </section>

    {{-- Contacto --}}
    <section id="contacto" class="bg-gray-50 py-16">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-gray-900">¿Conversamos?</h2>
            <p class="mt-2 text-gray-600">Escríbenos y te ayudamos a empezar a emitir hoy mismo.</p>
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-2xl mx-auto">
                <a href="https://wa.me/51921676408" target="_blank" rel="noopener" class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
                    <div class="text-3xl mb-2">💬</div>
                    <p class="font-semibold text-gray-900">WhatsApp</p>
                    <p class="text-sm text-gray-600">+51 921 676 408</p>
                </a>
                <a href="mailto:sistemasdesk04@gmail.com" class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
                    <div class="text-3xl mb-2">✉️</div>
                    <p class="font-semibold text-gray-900">Correo</p>
                    <p class="text-sm text-gray-600">sistemasdesk04@gmail.com</p>
                </a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-gray-100 py-8">
        <div class="max-w-6xl mx-auto px-6 text-center text-sm text-gray-500">
            © {{ date('Y') }} Cisma Fact — Facturación Electrónica. Hecho en Perú 🇵🇪
        </div>
    </footer>

    @include('partials.asistente-web')

    {{-- WhatsApp flotante --}}
    <a href="https://wa.me/51921676408" target="_blank" rel="noopener"
       class="fixed bottom-6 right-6 z-50 flex items-center justify-center w-14 h-14 rounded-full bg-green-500 text-white text-2xl shadow-lg hover:bg-green-600 transition"
       aria-label="Escríbenos por WhatsApp">
        💬
    </a>

</body>
</html>
