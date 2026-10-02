<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO básico --}}
    <title>Cisma Fact — Facturación Electrónica y API RUC/DNI en Perú</title>
    <meta name="description" content="Emite facturas, boletas, notas y guías electrónicas. Integra consultas de RUC y DNI en Sandbox desde una sola plataforma empresarial.">
    <meta name="keywords" content="facturación electrónica, factura electrónica SUNAT, API RUC, API DNI, consulta RUC DNI, boleta electrónica, guía de remisión electrónica, Cisma Fact">
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
    <meta property="og:title" content="Cisma Fact — Facturación Electrónica y API RUC/DNI">
    <meta property="og:description" content="Facturación electrónica y consultas RUC/DNI para tu empresa desde una sola plataforma.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ config('platform.logo_url', asset('assets/brand/cisma-fact.png')) }}">
    <meta property="og:locale" content="es_PE">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Cisma Fact — Facturación Electrónica y API RUC/DNI">
    <meta name="twitter:description" content="Facturación electrónica y consultas RUC/DNI para tu empresa desde una sola plataforma.">
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
                <a href="#soluciones" class="hidden md:inline-block px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-600">Soluciones</a>
                <a href="#planes" class="hidden md:inline-block px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-600">Planes</a>
                <a href="#contacto" class="hidden md:inline-block px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-600">Contacto</a>
                <a href="{{ route('login') }}" class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700">Probar Facturación</a>
                <details class="group relative md:hidden">
                    <summary class="flex cursor-pointer list-none items-center rounded-lg border border-gray-200 p-2 text-gray-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </summary>
                    <div class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white p-2 shadow-xl">
                        <a href="#soluciones" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Soluciones</a>
                        <a href="#planes" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Planes</a>
                        <a href="#contacto" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Contacto</a>
                        <a href="{{ route('docs') }}" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Docs de facturación</a>
                        <a href="{{ route('docs.consultas') }}" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Docs API RUC/DNI</a>
                    </div>
                </details>
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <section class="mx-auto grid max-w-6xl items-center gap-12 px-6 py-16 lg:grid-cols-[1.05fr_.95fr] lg:py-24">
        <div>
            <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">Una plataforma para operar y crecer</span>
            <h1 class="mt-5 text-4xl font-bold leading-tight text-gray-900 md:text-5xl">
                Facturación electrónica <span class="text-blue-600">·</span><br>API RUC/DNI
            </h1>
            <p class="mt-5 max-w-xl text-lg leading-relaxed text-gray-600">
                Emite comprobantes electrónicos y conecta consultas de RUC y DNI con tu negocio desde una plataforma segura y centralizada.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#planes-facturacion" class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white shadow-sm hover:bg-blue-700">Ver planes de Facturación</a>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('abrir-asistente', { detail: { paso: 'cons_prueba' } }))" class="rounded-lg border border-indigo-200 bg-indigo-50 px-6 py-3 font-semibold text-indigo-700 hover:bg-indigo-100">Solicitar Sandbox RUC/DNI</button>
            </div>
            <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-500">
                <span>✓ Acceso desde la web</span><span>✓ API documentada</span><span>✓ Soporte en Perú</span>
            </div>
        </div>

        <div class="relative">
            <div class="absolute -inset-6 -z-10 rounded-full bg-blue-100/70 blur-3xl"></div>
            <div class="overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-br from-white to-blue-50/60 p-6 shadow-2xl">
                    <div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-blue-100 bg-white p-4 shadow-sm"><div class="flex items-center justify-between"><p class="text-[11px] font-semibold text-blue-700">Facturación electrónica</p><span class="rounded bg-blue-50 px-1.5 py-0.5 text-[8px] text-blue-600">ESTE MES</span></div><p class="mt-1 text-xl font-bold text-gray-900">S/ 8,581.62</p><div class="mt-1 flex gap-3 text-[10px] text-gray-500"><span><b class="text-gray-800">186</b> emitidos</span><span><b class="text-emerald-600">175</b> aceptados</span></div></div>
                            <div class="rounded-xl border border-indigo-100 bg-white p-4 shadow-sm"><div class="flex items-center justify-between"><p class="text-[11px] font-semibold text-indigo-700">Consultas API RUC/DNI</p><span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[8px] text-indigo-600">REQUESTS</span></div><p class="mt-1 text-xl font-bold text-gray-900">1,248</p><div class="mt-1 flex gap-3 text-[10px] text-gray-500"><span><b class="text-indigo-700">780</b> RUC</span><span><b class="text-violet-600">468</b> DNI</span></div></div>
                        </div>
                        <div class="mt-3 grid grid-cols-[.8fr_1.2fr] gap-2">
                            <div class="rounded-lg border border-gray-200 bg-white p-3"><p class="text-[9px] font-semibold text-gray-800">Distribución de consultas</p><p class="text-[7px] text-gray-400">Uso actual por servicio</p><div class="mt-3 space-y-2.5 text-[7px] text-gray-500"><div><div class="mb-1 flex justify-between"><span>RUC</span><b>62.5%</b></div><div class="h-1.5 rounded bg-gray-100"><div class="h-1.5 w-3/5 rounded bg-indigo-500"></div></div></div><div><div class="mb-1 flex justify-between"><span>DNI</span><b>37.5%</b></div><div class="h-1.5 rounded bg-gray-100"><div class="h-1.5 w-2/5 rounded bg-violet-500"></div></div></div><div class="flex gap-3 pt-1"><span class="text-indigo-600">■ RUC</span><span class="text-violet-600">■ DNI</span></div></div></div>
                            <div class="rounded-lg border border-gray-200 bg-white p-3"><div class="flex items-center justify-between"><div><p class="text-[9px] font-semibold text-gray-800">Actividad mensual</p><p class="text-[7px] text-gray-400">Comprobantes y requests procesados</p></div><div class="flex gap-2 text-[7px]"><span class="text-blue-600">■ Facturación</span><span class="text-indigo-600">■ API</span></div></div><div class="mt-3 flex h-20 items-end justify-around gap-2 border-b border-gray-200"><div class="flex items-end gap-1"><span class="h-8 w-2 rounded-t bg-blue-500"></span><span class="h-6 w-2 rounded-t bg-indigo-400"></span></div><div class="flex items-end gap-1"><span class="h-11 w-2 rounded-t bg-blue-500"></span><span class="h-9 w-2 rounded-t bg-indigo-400"></span></div><div class="flex items-end gap-1"><span class="h-10 w-2 rounded-t bg-blue-500"></span><span class="h-12 w-2 rounded-t bg-indigo-400"></span></div><div class="flex items-end gap-1"><span class="h-14 w-2 rounded-t bg-blue-500"></span><span class="h-16 w-2 rounded-t bg-indigo-400"></span></div><div class="flex items-end gap-1"><span class="h-16 w-2 rounded-t bg-blue-500"></span><span class="h-14 w-2 rounded-t bg-indigo-400"></span></div></div><div class="mt-1 flex justify-around text-[6px] text-gray-400"><span>May</span><span>Jun</span><span>Jul</span><span>Ago</span><span>Sep</span></div></div>
                        </div>
                    </div>
            </div>
        </div>
    </section>

    <section id="soluciones" class="border-y border-gray-100 bg-gray-50 py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center"><span class="text-sm font-semibold uppercase tracking-wide text-blue-600">Nuestras soluciones</span><h2 class="mt-2 text-3xl font-bold text-gray-900">Elige lo que necesita tu empresa</h2><p class="mt-3 text-gray-600">Puedes contratar un servicio o utilizar ambos con la misma cuenta.</p></div>
            <div class="mt-10 grid gap-6 lg:grid-cols-2">
                <article id="facturacion" class="rounded-2xl border border-blue-100 bg-white p-7 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 2h9l5 5v15H6zM14 2v6h6M9 13h6M9 17h4"/></svg></div>
                    <h3 class="mt-5 text-2xl font-bold text-gray-900">Facturación electrónica</h3><p class="mt-2 text-gray-600">Emite y controla facturas, boletas, notas y guías con tu propio certificado digital.</p>
                    <ul class="mt-5 grid gap-2 text-sm text-gray-600 sm:grid-cols-2"><li>✓ Emisión desde la web</li><li>✓ API de integración</li><li>✓ Clientes y correlativos</li><li>✓ Reportes y estados SUNAT</li></ul>
                    <a href="#planes-facturacion" class="mt-6 inline-flex font-semibold text-blue-700 hover:underline">Ver planes de Facturación →</a>
                </article>
                <article id="api-ruc-dni" class="rounded-2xl border border-indigo-100 bg-white p-7 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4M8 11h6M11 8v6"/></svg></div>
                    <div class="mt-5 flex flex-wrap items-center gap-2"><h3 class="text-2xl font-bold text-gray-900">API RUC/DNI</h3><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Sandbox disponible</span></div>
                    <p class="mt-2 text-gray-600">Integra consultas de identidad y empresas, prueba tu desarrollo y revisa el consumo desde tu panel.</p>
                    <ul class="mt-5 grid gap-2 text-sm text-gray-600 sm:grid-cols-2"><li>✓ Credenciales Sandbox</li><li>✓ Consultas RUC y DNI</li><li>✓ Ejemplos de integración</li><li>✓ Historial de consultas</li></ul>
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('abrir-asistente', { detail: { paso: 'cons_prueba' } }))" class="mt-6 inline-flex font-semibold text-indigo-700 hover:underline">Solicitar Sandbox →</button>
                </article>
            </div>
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
                        <a href="{{ route('register') }}" class="mt-5 inline-block self-start px-6 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">Probar API de facturación</a>
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
                        <a href="{{ route('register') }}" class="mt-5 inline-block self-start px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700">Probar Facturación</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Planes --}}
    <section id="planes" class="max-w-6xl mx-auto px-6 py-20">
        <span class="block text-center text-sm font-semibold uppercase tracking-wide text-blue-600">Precios claros</span>
        <h2 id="planes-facturacion" class="mt-2 text-3xl font-bold text-center text-gray-900">Planes de Facturación electrónica</h2>
        <p class="text-center text-gray-500 mt-2">Elige el plan según el volumen y el equipo de tu empresa.</p>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($plans as $plan)
                <div class="relative rounded-2xl border border-gray-200 bg-white p-6 flex flex-col {{ (float) $plan->monthly_price > 0 ? 'shadow-sm' : '' }}">
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
                    @if ((float) $plan->monthly_price <= 0)
                        <a href="{{ route('register') }}" class="mt-6 rounded-lg bg-blue-600 px-4 py-2.5 text-center font-medium text-white hover:bg-blue-700">Comenzar prueba gratuita</a>
                    @else
                        <button type="button"
                                data-plan="{{ $plan->name }}"
                                data-precio="S/ {{ number_format($plan->monthly_price, 2) }} al mes"
                                onclick="window.dispatchEvent(new CustomEvent('solicitar-plan', { detail: { nombre: this.dataset.plan, precio: this.dataset.precio } }))"
                                class="mt-6 rounded-lg bg-blue-600 px-4 py-2.5 text-center font-medium text-white hover:bg-blue-700">
                            Solicitar plan {{ $plan->name }}
                        </button>
                    @endif
                </div>
            @empty
                <p class="col-span-3 text-center text-gray-500">Pronto publicaremos nuestros planes.</p>
            @endforelse
        </div>

        <div id="sandbox" class="mt-14 overflow-hidden rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-blue-50">
            <div class="grid items-center gap-8 p-7 lg:grid-cols-[1fr_auto] lg:p-9">
                <div>
                    <div class="flex flex-wrap items-center gap-2"><span class="text-sm font-semibold uppercase tracking-wide text-indigo-700">API RUC/DNI</span><span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-amber-700 shadow-sm">Solo Sandbox por ahora</span></div>
                    <h3 class="mt-3 text-2xl font-bold text-gray-900">Integra y prueba sin costo</h3>
                    <p class="mt-2 max-w-2xl text-gray-600">Valida tu integración con credenciales de prueba, ejemplos listos y un panel para revisar tus consultas. No debes registrarte: solicita el acceso al asistente y nuestro equipo te entregará las credenciales por WhatsApp.</p>
                    <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-700"><span>✓ RUC y DNI</span><span>✓ Documentación técnica</span><span>✓ Credenciales de prueba</span><span>✓ Sin compromiso</span></div>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('abrir-asistente', { detail: { paso: 'cons_prueba' } }))" class="rounded-lg bg-indigo-600 px-6 py-3 text-center font-semibold text-white shadow-sm hover:bg-indigo-700">Solicitar acceso Sandbox</button>
                    <a href="{{ route('docs.consultas') }}" class="rounded-lg border border-indigo-200 bg-white px-6 py-3 text-center font-semibold text-indigo-700 hover:bg-indigo-50">Ver documentación</a>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-blue-100 bg-gradient-to-br from-blue-50 via-white to-indigo-50 py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid gap-10 lg:grid-cols-[.8fr_1.2fr] lg:items-center">
                <div><span class="text-sm font-semibold uppercase tracking-wide text-blue-600">Confianza operativa</span><h2 class="mt-2 text-3xl font-bold text-gray-900">Tus operaciones, bajo control</h2><p class="mt-4 text-gray-600">Separamos credenciales, empresas y accesos para que cada usuario vea únicamente lo que le corresponde.</p></div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-blue-100 bg-white p-5 shadow-sm"><div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700">✓</div><p class="font-semibold text-gray-900">Accesos por usuario</p><p class="mt-2 text-sm text-gray-600">Roles y actividad identificada para cada miembro del equipo.</p></div>
                    <div class="rounded-xl border border-indigo-100 bg-white p-5 shadow-sm"><div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">✓</div><p class="font-semibold text-gray-900">Credenciales protegidas</p><p class="mt-2 text-sm text-gray-600">Llaves independientes y secretos regenerables cuando lo necesites.</p></div>
                    <div class="rounded-xl border border-cyan-100 bg-white p-5 shadow-sm"><div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-100 text-cyan-700">✓</div><p class="font-semibold text-gray-900">Trazabilidad</p><p class="mt-2 text-sm text-gray-600">Estados, consumos y documentos disponibles para supervisión.</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-6 py-20">
        <div class="text-center"><span class="text-sm font-semibold uppercase tracking-wide text-blue-600">Preguntas frecuentes</span><h2 class="mt-2 text-3xl font-bold text-gray-900">Antes de comenzar</h2></div>
        <div class="mt-10 space-y-3">
            @foreach([
                ['¿El registro también activa la API RUC/DNI?', 'No. El registro público crea únicamente una cuenta de prueba de Facturación. El acceso al Sandbox RUC/DNI lo entrega nuestro equipo por WhatsApp.'],
                ['¿Cómo solicito el Sandbox de la API RUC/DNI?', 'No debes crear una cuenta desde el registro de Facturación. Solicita el acceso mediante el asistente y nuestro equipo te entregará las credenciales por WhatsApp.'],
                ['¿Puedo emitir desde la web y desde mi propio sistema?', 'Sí. Puedes emitir desde Cisma Fact Online o conectar tu software mediante la API de facturación.'],
                ['¿Puedo controlar lo que hace cada usuario?', 'Sí. El panel identifica quién emite comprobantes y permite revisar ventas y actividad por usuario.'],
            ] as [$pregunta, $respuesta])
                <details class="group rounded-xl border border-gray-200 bg-white p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-gray-900">{{ $pregunta }}<span class="text-xl text-blue-600 transition group-open:rotate-45">+</span></summary>
                    <p class="mt-3 pr-8 text-sm leading-6 text-gray-600">{{ $respuesta }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- Contacto --}}
    <section id="contacto" class="bg-gray-50 py-16">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-gray-900">¿Conversamos?</h2>
            <p class="mt-2 text-gray-600">Escríbenos y te ayudamos a empezar a emitir hoy mismo.</p>
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-2xl mx-auto">
                <a href="https://wa.me/{{ config('asistente.whatsapp') }}" target="_blank" rel="noopener" class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
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

</body>
</html>
