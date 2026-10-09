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

    {{-- Dos canales del mismo servicio: integración o uso directo desde la web. --}}
    <section class="bg-gray-50 py-16">
        <div class="max-w-6xl mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-gray-900">Elige cómo quieres emitir</h2>
            <p class="text-center text-gray-500 mt-2">Conecta tu sistema mediante API o trabaja directamente desde Cisma Fact Online.</p>

            <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- Integración por API --}}
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col">
                    <div class="h-36 bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-6xl">👨‍💻</div>
                    <div class="p-7 flex flex-col flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Para sistemas propios</p>
                        <h3 class="mt-2 text-2xl font-bold text-gray-900">Integración por API</h3>
                        <p class="mt-3 text-gray-600 leading-relaxed">
                            Si ya utilizas un software, tienda online, POS o ERP, conéctalo con nuestra <strong>API REST</strong>
                            para emitir documentos electrónicos desde tu propio sistema.
                        </p>
                        <p class="mt-3 text-gray-600 leading-relaxed">
                            Consulta la documentación, ejemplos en PHP y JavaScript y la colección Postman para validar tu integración.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <button type="button" onclick="document.getElementById('registro-rapido').showModal()" class="rounded-lg bg-blue-600 px-6 py-2.5 font-medium text-white hover:bg-blue-700">Crear cuenta de prueba</button>
                            <a href="{{ route('docs') }}" class="rounded-lg border border-blue-200 bg-blue-50 px-5 py-2.5 font-medium text-blue-700 hover:bg-blue-100">Ver documentación</a>
                        </div>
                    </div>
                </div>

                {{-- Uso directo desde la web --}}
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col">
                    <div class="h-36 bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center text-white text-6xl">🏢</div>
                    <div class="p-7 flex flex-col flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Sin sistema propio</p>
                        <h3 class="mt-2 text-2xl font-bold text-gray-900">Facturación desde la web</h3>
                        <p class="mt-3 text-gray-600 leading-relaxed">
                            Usa <strong>Cisma Fact Online</strong> desde tu navegador para emitir facturas, boletas,
                            notas de crédito/débito y guías de remisión.
                        </p>
                        <p class="mt-3 text-gray-600 leading-relaxed">
                            <strong>Sin instalar programas ni realizar una integración.</strong> Registra clientes, administra series y revisa el estado de tus comprobantes en un solo panel.
                        </p>
                        <button type="button" onclick="document.getElementById('registro-rapido').showModal()" class="mt-5 inline-block self-start rounded-lg bg-emerald-600 px-6 py-2.5 font-medium text-white hover:bg-emerald-700">Probar facturación web</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Planes --}}
    <section id="planes" class="border-y border-gray-100 bg-gray-50 py-20 text-gray-900">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Planes de facturación</span>
                <h2 id="planes-facturacion" class="mt-3 text-3xl font-bold sm:text-4xl">Elige según el ritmo de tu empresa</h2>
                <p class="mt-3 text-gray-600">Precios mensuales y límites transparentes. Puedes comenzar gratis y cambiar de plan cuando lo necesites.</p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                @forelse($plans as $plan)
                    @php
                        $destacado = strtolower((string) $plan->code) === 'business';
                        $gratuito = (float) $plan->monthly_price <= 0;
                    @endphp
                    <article class="relative flex flex-col rounded-3xl border p-7 transition duration-200 hover:-translate-y-1 {{ $destacado ? 'border-indigo-400 bg-white shadow-xl shadow-indigo-100/70 ring-1 ring-indigo-100' : 'border-gray-200 bg-white shadow-sm hover:border-blue-200 hover:shadow-lg' }}">
                        @if($destacado)
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-500 px-4 py-1 text-[11px] font-bold uppercase tracking-wide text-white shadow-lg">Más elegido</span>
                        @endif

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] {{ $destacado ? 'text-indigo-600' : 'text-gray-500' }}">{{ $plan->name }}</p>
                                <p class="mt-4 flex items-end gap-2">
                                    <span class="text-4xl font-bold tracking-tight">S/ {{ number_format((float) $plan->monthly_price, 0) }}</span>
                                    @unless($gratuito)<span class="pb-1 text-sm text-gray-500">/ mes</span>@endunless
                                </p>
                                <p class="mt-1 text-xs text-gray-500">{{ $gratuito ? 'Sin costo mensual' : 'Plan mensual' }}</p>
                            </div>
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $destacado ? 'bg-indigo-600 text-white' : 'bg-blue-50 text-blue-600' }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </span>
                        </div>

                        <div class="my-6 h-px bg-gray-100"></div>
                        <ul class="flex-1 space-y-4 text-sm text-gray-700">
                            @foreach([
                                ($plan->monthly_document_limit ? number_format($plan->monthly_document_limit) : 'Sin límite') . ' comprobantes al mes',
                                ($plan->user_limit ? number_format($plan->user_limit) : 'Sin límite de') . ' ' . ($plan->user_limit === 1 ? 'usuario' : 'usuarios'),
                                ($plan->api_request_limit ? number_format($plan->api_request_limit) : 'Sin límite de') . ' solicitudes API al mes',
                                'Soporte: ' . ($plan->support_included ?: 'No incluido'),
                                'XML, PDF y respuesta CDR',
                            ] as $beneficio)
                                <li class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ $beneficio }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if($gratuito)
                            <button type="button" onclick="document.getElementById('registro-rapido').showModal()" class="mt-8 rounded-xl border border-gray-200 bg-gray-100 px-5 py-3 text-center text-sm font-semibold text-gray-800 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">Comenzar gratis</button>
                        @else
                            <button type="button"
                                    data-plan="{{ $plan->name }}"
                                    data-precio="S/ {{ number_format($plan->monthly_price, 2) }} al mes"
                                    onclick="window.dispatchEvent(new CustomEvent('solicitar-plan', { detail: { nombre: this.dataset.plan, precio: this.dataset.precio } }))"
                                    class="mt-8 rounded-xl px-5 py-3 text-center text-sm font-semibold text-white transition {{ $destacado ? 'bg-indigo-600 shadow-lg shadow-indigo-200 hover:bg-indigo-700' : 'bg-blue-600 shadow-sm hover:bg-blue-700' }}">
                                Elegir {{ $plan->name }}
                            </button>
                        @endif
                    </article>
                @empty
                    <p class="col-span-3 rounded-2xl border border-gray-200 bg-white p-8 text-center text-gray-500">Pronto publicaremos nuestros planes.</p>
                @endforelse
            </div>

            @if($plans->isNotEmpty())
                <div class="mt-12 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-5">
                        <h3 class="text-lg font-bold text-gray-900">Compara los planes</h3>
                        <p class="mt-1 text-sm text-gray-500">Revisa los límites vigentes antes de elegir.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Característica</th>
                                    @foreach($plans as $plan)
                                        <th class="px-5 py-4 text-center font-semibold {{ strtolower((string) $plan->code) === 'business' ? 'text-indigo-600' : '' }}">
                                            {{ $plan->name }}
                                            <span class="mt-0.5 block normal-case tracking-normal text-gray-400">S/ {{ number_format((float) $plan->monthly_price, 0) }}{{ (float) $plan->monthly_price > 0 ? ' / mes' : '' }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                <tr>
                                    <th class="px-6 py-4 font-medium text-gray-900">Comprobantes electrónicos</th>
                                    @foreach($plans as $plan)<td class="px-5 py-4 text-center">{{ $plan->monthly_document_limit ? number_format($plan->monthly_document_limit) . ' / mes' : 'Sin límite' }}</td>@endforeach
                                </tr>
                                <tr class="bg-gray-50/60">
                                    <th class="px-6 py-4 font-medium text-gray-900">Usuarios</th>
                                    @foreach($plans as $plan)<td class="px-5 py-4 text-center">{{ $plan->user_limit ? number_format($plan->user_limit) : 'Sin límite' }}</td>@endforeach
                                </tr>
                                <tr>
                                    <th class="px-6 py-4 font-medium text-gray-900">Solicitudes API de facturación</th>
                                    @foreach($plans as $plan)<td class="px-5 py-4 text-center">{{ $plan->api_request_limit ? number_format($plan->api_request_limit) . ' / mes' : 'Sin límite' }}</td>@endforeach
                                </tr>
                                <tr class="bg-gray-50/60">
                                    <th class="px-6 py-4 font-medium text-gray-900">XML, PDF y respuesta CDR</th>
                                    @foreach($plans as $plan)<td class="px-5 py-4 text-center"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">✓</span></td>@endforeach
                                </tr>
                                <tr>
                                    <th class="px-6 py-4 font-medium text-gray-900">Reporte CSV para contador</th>
                                    @foreach($plans as $plan)<td class="px-5 py-4 text-center"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">✓</span></td>@endforeach
                                </tr>
                                <tr class="bg-gray-50/60">
                                    <th class="px-6 py-4 font-medium text-gray-900">
                                        Consulta RUC/DNI desde el panel
                                        <span class="mt-0.5 block text-xs font-normal text-gray-500">Para completar datos de clientes dentro del sistema</span>
                                    </th>
                                    @foreach($plans as $plan)<td class="px-5 py-4 text-center"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">✓</span></td>@endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            @endif
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-6 py-16">
        <div id="sandbox" class="overflow-hidden rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-blue-50">
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

    {{-- Footer: solo servicios y canales disponibles actualmente. --}}
    <footer class="bg-slate-950 text-slate-300">
        <div class="mx-auto grid max-w-6xl gap-8 px-6 py-10 sm:grid-cols-2 lg:grid-cols-[1.35fr_1fr_1fr_1fr]">
            <div>
                <a href="{{ route('landing') }}" class="inline-flex items-center rounded-xl bg-white px-3 py-2" aria-label="Ir al inicio de Cisma Fact">
                    <img src="{{ config('platform.logo_url', asset('assets/brand/cisma-fact.png')) }}"
                         alt="{{ config('app.name', 'Cisma Fact') }}"
                         class="h-9 w-auto max-w-[180px] object-contain">
                </a>
                <p class="mt-3 max-w-xs text-sm leading-6 text-slate-400">
                    Facturación electrónica y consultas API de RUC y DNI para empresas peruanas, desde una plataforma centralizada.
                </p>
                <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1.5 text-xs font-semibold text-emerald-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Integración con SUNAT disponible
                </div>
            </div>

            <div>
                <h3 class="font-semibold text-white">Soluciones</h3>
                <ul class="mt-3 space-y-2.5 text-sm text-slate-400">
                    <li><a href="{{ route('landing') }}#facturacion" class="transition hover:text-white">Facturación electrónica</a></li>
                    <li><a href="{{ route('landing') }}#api-ruc-dni" class="transition hover:text-white">API RUC y DNI</a></li>
                    <li><a href="{{ route('landing') }}#planes" class="transition hover:text-white">Planes disponibles</a></li>
                    <li><a href="{{ route('landing') }}#sandbox" class="transition hover:text-white">Acceso Sandbox</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-white">Desarrolladores</h3>
                <ul class="mt-3 space-y-2.5 text-sm text-slate-400">
                    <li><a href="{{ route('docs') }}" class="transition hover:text-white">API de facturación</a></li>
                    <li><a href="{{ route('docs.consultas') }}" class="transition hover:text-white">API RUC/DNI</a></li>
                    <li><a href="{{ route('register') }}" class="transition hover:text-white">Crear una cuenta</a></li>
                    <li><a href="{{ route('login') }}" class="transition hover:text-white">Iniciar sesión</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-white">Ayuda y contacto</h3>
                <ul class="mt-3 space-y-2.5 text-sm text-slate-400">
                    <li><a href="https://wa.me/51921676408" target="_blank" rel="noopener noreferrer" class="transition hover:text-white">WhatsApp: +51 921 676 408</a></li>
                    <li><a href="mailto:sistemasdesk04@gmail.com" class="break-all transition hover:text-white">sistemasdesk04@gmail.com</a></li>
                    <li><a href="{{ route('landing') }}#contacto" class="transition hover:text-white">Contactar soporte</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 px-6 py-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ date('Y') }} Cisma Fact Perú. Todos los derechos reservados.</p>
                <nav class="flex flex-wrap items-center gap-x-5 gap-y-2" aria-label="Información legal">
                    <a href="{{ route('legal.privacidad') }}" class="transition hover:text-slate-200">Privacidad</a>
                    <a href="{{ route('legal.terminos') }}" class="transition hover:text-slate-200">Términos</a>
                    <a href="{{ route('legal.seguridad') }}" class="inline-flex items-center gap-1.5 transition hover:text-slate-200">
                        <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.105.895-2 2-2s2 .895 2 2v2m-8 0v-2a4 4 0 118 0v2m-9 0h10a1 1 0 011 1v6a1 1 0 01-1 1H7a1 1 0 01-1-1v-6a1 1 0 011-1z"/></svg>
                        Seguridad y conexión HTTPS
                    </a>
                </nav>
            </div>
        </div>
    </footer>

    {{-- Registro rápido. Comparte exactamente la misma ruta y validaciones que /register. --}}
    <dialog id="registro-rapido"
            class="m-auto w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-2xl bg-white p-0 text-left shadow-2xl backdrop:bg-slate-950/60"
            onclick="if (event.target === this) this.close()">
        <div class="flex max-h-[calc(100vh-2rem)] flex-col">
            <div class="flex items-start justify-between border-b border-gray-100 px-6 py-5 sm:px-7">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Plan Free</span>
                        <span class="text-xs text-gray-500">Sin costo mensual</span>
                    </div>
                    <h2 class="mt-3 text-2xl font-bold text-gray-900">Crea tu cuenta</h2>
                    <p class="mt-1 text-sm text-gray-500">Empieza en modo de pruebas, sin certificado.</p>
                </div>
                <button type="button" onclick="document.getElementById('registro-rapido').close()" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Cerrar registro">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-5 sm:px-7">
                @if($errors->any() && old('registro_modal'))
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        Revisa los campos marcados para poder crear tu cuenta.
                    </div>
                @endif

                <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="registro_modal" value="1">

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="modal-name" class="mb-1.5 block text-sm font-medium text-gray-700">Tu nombre</label>
                            <input id="modal-name" name="name" type="text" value="{{ old('name') }}" required maxlength="255" autocomplete="name" placeholder="Juan Pérez"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 {{ $errors->has('name') && old('registro_modal') ? 'border-red-400' : 'border-gray-300' }}">
                            @if(old('registro_modal')) @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                        <div>
                            <label for="modal-razon" class="mb-1.5 block text-sm font-medium text-gray-700">Razón social</label>
                            <input id="modal-razon" name="razon_social" type="text" value="{{ old('razon_social') }}" required maxlength="255" autocomplete="organization" placeholder="Mi Empresa S.A.C."
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 {{ $errors->has('razon_social') && old('registro_modal') ? 'border-red-400' : 'border-gray-300' }}">
                            @if(old('registro_modal')) @error('razon_social')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                        <div>
                            <label for="modal-ruc" class="mb-1.5 block text-sm font-medium text-gray-700">RUC</label>
                            <input id="modal-ruc" name="ruc" type="text" value="{{ old('ruc') }}" required maxlength="11" inputmode="numeric" pattern="\d{11}" placeholder="20123456789"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 {{ $errors->has('ruc') && old('registro_modal') ? 'border-red-400' : 'border-gray-300' }}">
                            @if(old('registro_modal')) @error('ruc')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                        <div>
                            <label for="modal-email" class="mb-1.5 block text-sm font-medium text-gray-700">Correo <span class="font-normal text-gray-400">— será tu usuario</span></label>
                            <input id="modal-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="tucorreo@empresa.com"
                                   class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 {{ $errors->has('email') && old('registro_modal') ? 'border-red-400' : 'border-gray-300' }}">
                            @if(old('registro_modal')) @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                        <div>
                            <label for="modal-password" class="mb-1.5 block text-sm font-medium text-gray-700">Contraseña</label>
                            <div class="relative">
                                <input id="modal-password" name="password" type="password" required autocomplete="new-password" placeholder="Mínimo 8 caracteres"
                                       class="w-full rounded-lg border py-2.5 pl-3.5 pr-11 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 {{ $errors->has('password') && old('registro_modal') ? 'border-red-400' : 'border-gray-300' }}">
                                <button type="button" data-password-toggle="modal-password" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-400 transition hover:text-blue-600" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <svg data-eye-closed class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg data-eye-open class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.585 10.587a2 2 0 002.828 2.828M9.878 4.242A9.95 9.95 0 0112 4c4.478 0 8.268 2.943 9.542 7a9.98 9.98 0 01-2.293 3.95M6.61 6.61A9.96 9.96 0 002.458 11c1.274 4.057 5.065 7 9.542 7a9.95 9.95 0 004.132-.895"/></svg>
                                </button>
                            </div>
                            @if(old('registro_modal')) @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                        <div>
                            <label for="modal-password-confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">Confirmar contraseña</label>
                            <div class="relative">
                                <input id="modal-password-confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Repite la contraseña"
                                       class="w-full rounded-lg border border-gray-300 py-2.5 pl-3.5 pr-11 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                <button type="button" data-password-toggle="modal-password-confirmation" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-400 transition hover:text-blue-600" aria-label="Mostrar contraseña" aria-pressed="false">
                                    <svg data-eye-closed class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg data-eye-open class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.585 10.587a2 2 0 002.828 2.828M9.878 4.242A9.95 9.95 0 0112 4c4.478 0 8.268 2.943 9.542 7a9.98 9.98 0 01-2.293 3.95M6.61 6.61A9.96 9.96 0 002.458 11c1.274 4.057 5.065 7 9.542 7a9.95 9.95 0 004.132-.895"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs leading-5 text-gray-500">Al crear tu cuenta aceptas nuestros <a href="{{ route('legal.terminos') }}" target="_blank" class="font-medium text-blue-600 hover:underline">Términos</a> y la <a href="{{ route('legal.privacidad') }}" target="_blank" class="font-medium text-blue-600 hover:underline">Política de privacidad</a>.</p>
                    <button type="submit" class="w-full rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700">Crear mi cuenta gratis</button>
                </form>

                <div class="mt-4 flex flex-col items-center justify-between gap-2 border-t border-gray-100 pt-4 text-sm sm:flex-row">
                    <p class="text-gray-600">¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">Inicia sesión</a></p>
                    <a href="{{ route('register') }}" class="text-gray-500 hover:text-gray-800">Abrir formulario completo</a>
                </div>
            </div>
        </div>
    </dialog>

    @if($errors->any() && old('registro_modal'))
        <script>
            document.addEventListener('DOMContentLoaded', () => document.getElementById('registro-rapido')?.showModal());
        </script>
    @endif

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordToggle);
                if (!input) return;

                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.setAttribute('aria-pressed', String(!visible));
                button.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
                button.querySelector('[data-eye-closed]')?.classList.toggle('hidden', visible);
                button.querySelector('[data-eye-open]')?.classList.toggle('hidden', !visible);
            });
        });
    </script>

    @include('partials.asistente-web')

</body>
</html>
