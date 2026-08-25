@extends('layouts.app')

@section('title', 'Cómo funciona la emisión')

@section('content')
@php
    $manual = $company->esEmisionManual();

    // Se evita el emoji como icono: en una pantalla que explica algo serio
    // (credenciales, valor legal) resta credibilidad y se ve distinto en cada
    // sistema. Se usan etiquetas de texto, del mismo estilo que el resto del panel.
    $si = '<span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700">Sí</span>';
    $no = '<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">No</span>';
@endphp

<div class="mx-auto max-w-4xl space-y-10 pb-10">

    <header class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">¿Cómo funciona la emisión?</h1>
        <p class="mx-auto mt-2 max-w-2xl leading-relaxed text-gray-500">
            Hay dos formas de emitir comprobantes electrónicos ante SUNAT.
            Esta guía explica en qué se diferencian y qué credencial hace falta para cada una.
        </p>
        <span class="mt-4 inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $manual ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-blue-50 text-blue-700 ring-1 ring-blue-200' }}">
            Tu método actual: {{ $manual ? 'SUNAT Manual' : 'Cisma Fact' }}
        </span>
    </header>

    <section class="grid gap-5 md:grid-cols-2">
        @foreach([
            [
                'titulo' => 'Manual, en el portal de SUNAT',
                'activo' => $manual,
                'color' => 'amber',
                'pasos' => ['Entras al portal de SUNAT', 'Llenas el comprobante a mano', 'SUNAT lo firma y lo registra'],
                'favor' => 'No necesita certificado digital.',
                'contra' => 'Uno por uno, sin automatizar y sin API.',
            ],
            [
                'titulo' => 'Automático, desde Cisma Fact',
                'activo' => ! $manual,
                'color' => 'blue',
                'pasos' => ['Emites desde el sistema', 'Cisma Fact firma el XML y lo envía', 'SUNAT devuelve el CDR', 'Se generan PDF, XML y CDR'],
                'favor' => 'Rápido, masivo, con API y documentos automáticos.',
                'contra' => 'Requiere certificado digital.',
            ],
        ] as $via)
            <article class="rounded-xl border bg-white p-6 {{ $via['activo'] ? ($via['color'] === 'amber' ? 'border-amber-300' : 'border-blue-300') : 'border-gray-200' }}">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="font-semibold text-gray-900">{{ $via['titulo'] }}</h2>
                    @if($via['activo'])
                        <span class="shrink-0 rounded-full bg-gray-900 px-2 py-0.5 text-xs font-medium text-white">Tu método</span>
                    @endif
                </div>

                <ol class="mt-4 space-y-3">
                    @foreach($via['pasos'] as $i => $paso)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $via['color'] === 'amber' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700' }}">{{ $i + 1 }}</span>
                            <span class="text-sm leading-relaxed text-gray-700">{{ $paso }}</span>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-5 border-t border-gray-100 pt-4 text-sm">
                    <p class="text-green-700">{{ $via['favor'] }}</p>
                    <p class="mt-1 text-gray-500">{{ $via['contra'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="rounded-xl bg-gray-900 p-6 text-center text-white">
        <p class="text-xs uppercase tracking-wide text-gray-400">La diferencia de fondo</p>
        <p class="mx-auto mt-2 max-w-2xl text-lg leading-relaxed">
            En manual, <strong>SUNAT firma por ti</strong>. En automático, <strong>firma tu propio sistema</strong>
            con el certificado de la empresa.
        </p>
        <p class="mt-2 text-sm text-gray-400">Por eso el certificado solo hace falta en el modo automático.</p>
    </section>

    <section>
        <div class="text-center">
            <h2 class="text-xl font-bold text-gray-900">Las credenciales, una por una</h2>
            <p class="mt-1 text-gray-500">Piensa que vas a dejar documentos firmados en un edificio, y SUNAT es ese edificio.</p>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            @foreach([
                ['RUC', 'El número de identidad de la empresa, 11 dígitos. Identifica quién emite y es público.', 'Como tu documento de identidad.'],
                ['Usuario y Clave SOL', 'El usuario secundario y su contraseña para entrar a SUNAT. El sistema los usa para enviar los comprobantes en tu nombre.', 'Como la llave para entrar al edificio.'],
                ['Certificado digital y su contraseña', 'Un archivo que firma el XML. Garantiza que el comprobante lo emitió tu empresa y que nadie lo alteró después.', 'Como tu firma y tu sello. Solo en modo automático.'],
                ['Credenciales GRE', 'Solo para Guías de Remisión, que usan otra API de SUNAT y piden un client id y un client secret aparte. Se generan desde el portal SOL.', 'Como un pase especial, válido solo para guías.'],
            ] as $credencial)
                <article class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="font-semibold text-gray-900">{{ $credencial[0] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">{{ $credencial[1] }}</p>
                    <p class="mt-3 border-t border-gray-100 pt-3 text-xs text-gray-400">{{ $credencial[2] }}</p>
                </article>
            @endforeach
        </div>

        <p class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <strong>Sobre el certificado:</strong> SUNAT entrega gratis un Certificado Digital Tributario a las
            micro y pequeñas empresas con ingresos de hasta 300 UIT, vigente hasta el 31/12/2027. Se solicita
            desde tu Clave SOL con el Formulario 2046. Solo hace falta comprarlo si no calificas para ese beneficio.
        </p>
    </section>

    <section>
        <h2 class="text-center text-xl font-bold text-gray-900">Qué necesita cada caso</h2>
        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Credencial</th>
                        <th class="px-4 py-3 text-center">Manual</th>
                        <th class="px-4 py-3 text-center">Automático<br><span class="font-normal">facturas y boletas</span></th>
                        <th class="px-4 py-3 text-center">Automático<br><span class="font-normal">guías</span></th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @foreach([
                        ['RUC', true, true, true],
                        ['Usuario y Clave SOL', true, true, true],
                        ['Certificado digital', false, true, true],
                        ['Credenciales GRE', false, false, true],
                    ] as $fila)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $fila[0] }}</td>
                            @foreach(array_slice($fila, 1) as $necesario)
                                <td class="px-4 py-3 text-center">{!! $necesario ? $si : $no !!}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-center text-lg font-semibold text-gray-900">Pruebas y producción</h2>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="font-semibold text-gray-800">Pruebas (beta)</p>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Para practicar sin consecuencias. Los comprobantes <strong>no tienen valor legal</strong>
                    y no quedan registrados ante SUNAT. Disponible para facturas y boletas.
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="font-semibold text-gray-800">Producción</p>
                <p class="mt-1 text-sm leading-relaxed text-gray-600">
                    Emisión real, con <strong>valor legal</strong>. Requiere el certificado y el RUC activo,
                    y lo que emitas queda declarado.
                </p>
            </div>
        </div>

        <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>Las Guías de Remisión no tienen ambiente de pruebas.</strong> SUNAT solo las ofrece en
            producción, así que para emitirlas hace falta certificado real y estar en producción.
        </p>
    </section>

    <div class="flex flex-wrap justify-center gap-3">
        <a href="{{ route('empresa.sunat-config.index') }}"
           class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Ir a Configuración SUNAT</a>
        <a href="{{ route('empresa.dashboard') }}"
           class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm text-gray-700 hover:bg-gray-50">Volver al inicio</a>
    </div>
</div>
@endsection
