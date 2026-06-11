@extends('layouts.app')

@section('title', 'Cómo funciona la emisión')

@section('content')
@php $manual = $company->esEmisionManual(); @endphp
<div class="space-y-8 max-w-5xl">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">¿Cómo funciona la emisión?</h1>
            <p class="text-gray-500 mt-1">Guía visual de las formas de emitir y las credenciales que necesitas.</p>
        </div>
        <span class="inline-flex items-center self-start rounded-full px-3 py-1 text-sm font-medium {{ $manual ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }}">
            Tu método actual: {{ $manual ? 'SUNAT Manual' : 'Cisma Fact' }}
        </span>
    </div>

    {{-- DOS FORMAS DE EMITIR --}}
    <div class="grid gap-5 lg:grid-cols-2">

        {{-- Manual --}}
        <div class="rounded-xl border-2 {{ $manual ? 'border-amber-300 bg-amber-50/40' : 'border-gray-200 bg-white' }} p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">🅰️</span>
                <h2 class="text-lg font-semibold text-gray-800">Manual (en SUNAT)</h2>
            </div>
            <div class="space-y-2">
                @foreach(['Tu empresa', 'Entra al portal SUNAT', 'Llena y emite a mano', 'SUNAT firma y registra'] as $i => $step)
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 text-sm font-bold">{{ $i + 1 }}</div>
                        <div class="flex-1 rounded-lg bg-white border border-gray-200 px-3 py-2 text-sm text-gray-700">{{ $step }}</div>
                    </div>
                    @if(!$loop->last)<div class="ml-4 text-gray-300">↓</div>@endif
                @endforeach
            </div>
            <div class="mt-4 rounded-lg bg-white border border-gray-200 p-3 text-sm">
                <p class="text-green-700">✅ No necesita certificado</p>
                <p class="text-red-600">❌ Lento · sin automatización · sin API</p>
            </div>
        </div>

        {{-- Automático --}}
        <div class="rounded-xl border-2 {{ !$manual ? 'border-blue-300 bg-blue-50/40' : 'border-gray-200 bg-white' }} p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">🅱️</span>
                <h2 class="text-lg font-semibold text-gray-800">Automático (Cisma Fact)</h2>
            </div>
            <div class="space-y-2">
                @foreach(['Tu empresa', 'Cisma Fact firma el XML', 'Envía a SUNAT', 'SUNAT devuelve el CDR', 'Genera PDF · XML · CDR'] as $i => $step)
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-sm font-bold">{{ $i + 1 }}</div>
                        <div class="flex-1 rounded-lg bg-white border border-gray-200 px-3 py-2 text-sm text-gray-700">{{ $step }}</div>
                    </div>
                    @if(!$loop->last)<div class="ml-4 text-gray-300">↓</div>@endif
                @endforeach
            </div>
            <div class="mt-4 rounded-lg bg-white border border-gray-200 p-3 text-sm">
                <p class="text-green-700">✅ Rápido · masivo · API · documentos automáticos</p>
                <p class="text-amber-600">⚙️ Requiere certificado digital</p>
            </div>
        </div>
    </div>

    {{-- LA DIFERENCIA CLAVE --}}
    <div class="rounded-xl bg-gray-900 text-white p-6">
        <p class="text-sm uppercase tracking-wide text-gray-400 mb-1">La diferencia clave</p>
        <p class="text-lg">En <strong>manual</strong>, SUNAT firma por ti. En <strong>automático</strong>, firma tu sistema con el certificado de la empresa.</p>
        <p class="text-gray-400 text-sm mt-2">Por eso el certificado <strong>solo</strong> se necesita en modo automático.</p>
    </div>

    {{-- CREDENCIALES --}}
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-1">Las credenciales, explicadas</h2>
        <p class="text-gray-500 mb-4">Piensa que vas a dejar documentos firmados en un edificio (SUNAT).</p>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-center gap-2 mb-1"><span class="text-xl">🆔</span><h3 class="font-semibold text-gray-800">RUC</h3></div>
                <p class="text-sm text-gray-600">El número de identidad de la empresa (11 dígitos). Identifica quién emite. Es público.</p>
                <p class="text-xs text-gray-400 mt-2">Analogía: tu documento de identidad.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-center gap-2 mb-1"><span class="text-xl">🔑</span><h3 class="font-semibold text-gray-800">Usuario + Clave SOL</h3></div>
                <p class="text-sm text-gray-600">Usuario y contraseña para entrar a SUNAT. El sistema los usa para enviar los comprobantes en tu nombre.</p>
                <p class="text-xs text-gray-400 mt-2">Analogía: la llave para entrar al edificio.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-center gap-2 mb-1"><span class="text-xl">📜</span><h3 class="font-semibold text-gray-800">Certificado Digital + contraseña</h3></div>
                <p class="text-sm text-gray-600">Archivo que firma el XML. Garantiza que el documento lo emitió tu empresa y que nadie lo alteró. Se compra a una entidad autorizada.</p>
                <p class="text-xs text-gray-400 mt-2">Analogía: tu firma y sello. Solo en modo automático.</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex items-center gap-2 mb-1"><span class="text-xl">🚚</span><h3 class="font-semibold text-gray-800">Credenciales GRE (Client ID/Secret)</h3></div>
                <p class="text-sm text-gray-600">Solo para <strong>Guías de Remisión</strong>. Usan una API distinta de SUNAT que pide estas credenciales extra. Se generan en el portal SOL.</p>
                <p class="text-xs text-gray-400 mt-2">Analogía: un pase especial solo para guías.</p>
            </div>
        </div>
    </div>

    {{-- TABLA RESUMEN --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Credencial</th>
                    <th class="px-4 py-3 text-center">Manual</th>
                    <th class="px-4 py-3 text-center">Automático<br>facturas/boletas</th>
                    <th class="px-4 py-3 text-center">Automático<br>guías</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-700">
                @foreach([
                    ['RUC', '✅', '✅', '✅'],
                    ['Usuario + Clave SOL', '✅', '✅', '✅'],
                    ['Certificado + contraseña', '❌', '✅', '✅'],
                    ['Credenciales GRE', '❌', '❌', '✅'],
                ] as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $row[0] }}</td>
                        <td class="px-4 py-3 text-center">{{ $row[1] }}</td>
                        <td class="px-4 py-3 text-center">{{ $row[2] }}</td>
                        <td class="px-4 py-3 text-center">{{ $row[3] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- BETA vs PRODUCCIÓN --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">🧪 Beta vs 🚀 Producción</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-4">
                <p class="font-semibold text-gray-700">Beta (pruebas)</p>
                <p class="text-sm text-gray-600 mt-1">Para practicar. Los comprobantes <strong>no tienen valor legal</strong>. Disponible para facturas y boletas.</p>
            </div>
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-4">
                <p class="font-semibold text-gray-700">Producción (real)</p>
                <p class="text-sm text-gray-600 mt-1">Emisión real con <strong>valor legal</strong>. Requiere certificado real y RUC activo.</p>
            </div>
        </div>
        <p class="mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            ⚠️ Las <strong>Guías de Remisión NO tienen ambiente beta</strong> en SUNAT: solo existen en producción y requieren certificado real.
        </p>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('empresa.sunat-config.index') }}" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Ir a Configuración SUNAT</a>
        <a href="{{ route('empresa.dashboard') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm text-gray-700 hover:bg-gray-50">Volver al inicio</a>
    </div>
</div>
@endsection
