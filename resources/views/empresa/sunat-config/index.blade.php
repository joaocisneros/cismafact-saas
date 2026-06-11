@extends('layouts.app')

@section('title', 'Configuración SUNAT')

@section('content')
<div class="space-y-6" x-data="{ metodo: '{{ old('metodo_emision', $company->metodo_emision ?? 'cisma_fact') }}' }">
    <h2 class="text-lg font-semibold text-gray-800">Configuración SUNAT</h2>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3">
            <p class="text-sm font-semibold mb-1">No se pudo guardar. Revisa:</p>
            <ul class="list-disc list-inside text-sm space-y-0.5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="w-10 h-10 {{ !empty($company->usuario_sol) ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 {{ !empty($company->usuario_sol) ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm font-medium {{ !empty($company->usuario_sol) ? 'text-green-600' : 'text-red-600' }}">
                {{ !empty($company->usuario_sol) ? 'SUNAT Configurado' : 'SUNAT Pendiente' }}
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="w-10 h-10 {{ !empty($company->certificado_pem) ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 {{ !empty($company->certificado_pem) ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <p class="text-sm font-medium {{ !empty($company->certificado_pem) ? 'text-green-600' : 'text-red-600' }}">
                {{ !empty($company->certificado_pem) ? 'Certificado Instalado' : 'Certificado Pendiente' }}
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 text-center">
            <div class="w-10 h-10 {{ $company->activo ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 {{ $company->activo ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <p class="text-sm font-medium {{ $company->activo ? 'text-green-600' : 'text-red-600' }}">
                {{ $company->activo ? 'Empresa Activa' : 'Empresa Inactiva' }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('empresa.sunat-config.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Método de emisión: define si la empresa emite desde Cisma Fact o manual en SUNAT --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-1">Método de Emisión</h3>
            <p class="text-xs text-gray-500 mb-4">Define cómo emite comprobantes esta empresa.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition"
                       :class="metodo === 'cisma_fact' ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-gray-200'">
                    <input type="radio" name="metodo_emision" value="cisma_fact" x-model="metodo" class="mt-1">
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">Emitir con Cisma Fact</span>
                        <span class="block text-xs text-gray-500 mt-0.5">La plataforma firma, envía a SUNAT y genera PDF/XML/CDR. Requiere certificado digital.</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition"
                       :class="metodo === 'sunat_manual' ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-gray-200'">
                    <input type="radio" name="metodo_emision" value="sunat_manual" x-model="metodo" class="mt-1">
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">SUNAT Manual</span>
                        <span class="block text-xs text-gray-500 mt-0.5">La empresa emite directamente en el portal de SUNAT. No requiere certificado en la plataforma.</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Aviso cuando es emisión manual --}}
        <div x-show="metodo === 'sunat_manual'" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm font-semibold text-amber-800">Modo SUNAT Manual activado</p>
            <p class="text-sm text-amber-700 mt-1">
                Esta empresa emitirá sus comprobantes directamente en el portal de SUNAT. No necesitas cargar certificado ni credenciales aquí.
                Guarda para aplicar el cambio.
            </p>
        </div>

        {{-- Configuración de emisión (solo para método Cisma Fact) --}}
        <div x-show="metodo === 'cisma_fact'" x-cloak class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-1">Credenciales SOL</h3>
            <p class="text-xs text-gray-500 mb-4">Para emitir facturas, boletas y notas.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="usuario_sol" class="block text-sm font-medium text-gray-700 mb-1">Usuario SOL</label>
                    <input type="text" name="usuario_sol" id="usuario_sol"
                           value="{{ old('usuario_sol', $company->usuario_sol) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="MODDATOS">
                </div>
                <div>
                    <label for="clave_sol" class="block text-sm font-medium text-gray-700 mb-1">Clave SOL</label>
                    <input type="password" name="clave_sol" id="clave_sol"
                           value="{{ old('clave_sol', $company->clave_sol) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="********">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ambiente actual</label>
                    @if($company->modo_produccion)
                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 text-green-700 px-3 py-1 text-sm font-medium">● Producción (emisión real)</span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-700 px-3 py-1 text-sm font-medium">● Beta (pruebas)</span>
                    @endif
                    <p class="text-xs text-gray-500 mt-1">El cambio a producción se hace abajo en «Pasar a producción».</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Certificado Digital</h3>

            @if($company->cert_valido_hasta)
                @php
                    $certBadge = [
                        'green'  => 'bg-green-100 text-green-700',
                        'yellow' => 'bg-yellow-100 text-yellow-700',
                        'red'    => 'bg-red-100 text-red-700',
                        'gray'   => 'bg-gray-100 text-gray-600',
                    ][$company->certEstadoColor()];
                @endphp
                <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-gray-700">Certificado actual</span>
                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $certBadge }}">
                            {{ $company->certEstadoLabel() }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-sm text-gray-600">
                        <p><span class="font-medium text-gray-500">Titular:</span> {{ $company->cert_titular ?? 'N/A' }}</p>
                        <p><span class="font-medium text-gray-500">RUC:</span> {{ $company->cert_ruc ?? 'N/A' }}</p>
                        <p><span class="font-medium text-gray-500">Válido desde:</span> {{ optional($company->cert_valido_desde)->format('d/m/Y') }}</p>
                        <p><span class="font-medium text-gray-500">Válido hasta:</span> {{ optional($company->cert_valido_hasta)->format('d/m/Y') }}</p>
                    </div>
                    @if($company->certEstado() === 'por_vencer')
                        <p class="mt-2 text-xs text-yellow-700">⚠️ Tu certificado está por vencer. Renuévalo y súbelo para no dejar de emitir.</p>
                    @elseif($company->certEstado() === 'vencido')
                        <p class="mt-2 text-xs text-red-700">❌ Tu certificado venció. No podrás emitir hasta subir uno vigente.</p>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="certificado_pfx" class="block text-sm font-medium text-gray-700 mb-1">
                        Certificado .pfx
                    </label>
                    <input type="file" name="certificado_pfx" id="certificado_pfx"
                           accept=".pfx,.p12"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    @if($company->certificado_pem)
                        <p class="text-xs text-green-600 mt-1">✓ Certificado actual instalado</p>
                    @endif
                </div>
                <div>
                    <label for="certificado_password" class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña del Certificado
                    </label>
                    <input type="password" name="certificado_password" id="certificado_password"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="********">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6" x-data="{ greOpen: false }">
            <button type="button" @click="greOpen = !greOpen" class="flex w-full items-center justify-between text-left">
                <span>
                    <span class="block text-md font-semibold text-gray-800">
                        Guías de Remisión (GRE) <span class="text-xs font-normal text-gray-400">— opcional</span>
                    </span>
                    <span class="block text-xs text-gray-500 mt-0.5">Solo si tu empresa emite Guías de Remisión. Si no las usas, puedes ignorar esta sección.</span>
                </span>
                <svg class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="greOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="greOpen" x-cloak class="mt-5">
            <p class="text-xs text-gray-500 mb-4">Las credenciales las obtienes en SUNAT SOL → Credenciales de API. Las guías siempre se emiten en producción.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="gre_ruc_proveedor" class="block text-sm font-medium text-gray-700 mb-1">RUC Proveedor GRE</label>
                    <input type="text" name="gre_ruc_proveedor" id="gre_ruc_proveedor"
                           value="{{ old('gre_ruc_proveedor', $company->gre_ruc_proveedor) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="{{ $company->ruc }}">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="gre_usuario_sol" class="block text-sm font-medium text-gray-700 mb-1">Usuario SOL (GRE)</label>
                    <input type="text" name="gre_usuario_sol" id="gre_usuario_sol"
                           value="{{ old('gre_usuario_sol', $company->gre_usuario_sol) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="Usuario secundario SOL">
                </div>
                <div>
                    <label for="gre_clave_sol" class="block text-sm font-medium text-gray-700 mb-1">Clave SOL (GRE)</label>
                    <input type="password" name="gre_clave_sol" id="gre_clave_sol"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="{{ $company->gre_clave_sol ? 'Dejar vacío para mantener la actual' : '********' }}">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="gre_client_id_beta" class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                        <input type="text" name="gre_client_id_beta" id="gre_client_id_beta"
                               value="{{ old('gre_client_id_beta', $company->gre_client_id_beta) }}"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="Client ID de SUNAT">
                    </div>
                    <div>
                        <label for="gre_client_secret_beta" class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                        <input type="password" name="gre_client_secret_beta" id="gre_client_secret_beta"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="{{ $company->gre_client_secret_beta ? 'Dejar vacío para mantener el actual' : '********' }}">
                    </div>
                </div>
            </div>
            </div> {{-- /x-show greOpen --}}
        </div>
        </div> {{-- /x-show cisma_fact --}}

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                Guardar Configuración
            </button>
            <a href="{{ route('empresa.dashboard') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>
    </form>

    {{-- Botón de prueba de conexión real con SUNAT (formulario aparte) --}}
    <form method="POST" action="{{ route('empresa.sunat-config.test') }}" x-show="metodo === 'cisma_fact'" x-cloak>
        @csrf
        <button type="submit" class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
            🔌 Probar conexión con SUNAT
        </button>
        <span class="text-xs text-gray-500 ml-2">Valida tus credenciales con SUNAT sin emitir ningún documento.</span>
    </form>

    {{-- Pasar a producción: solo en modo Cisma Fact y mientras la empresa esté en beta --}}
    @unless($company->modo_produccion)
    <div x-show="metodo === 'cisma_fact'" x-cloak x-data="{ confirmar: false }"
         class="rounded-xl border-2 border-red-200 bg-red-50 p-6">
        <h3 class="text-md font-semibold text-red-800">🚀 Pasar a producción</h3>
        <p class="text-sm text-red-700 mt-1">
            Cuando termines de probar, esto deja todo listo para emisión <strong>real</strong>:
            elimina los comprobantes de prueba, reinicia la numeración (correlativos) y activa el modo producción.
            <strong>Esta acción no se puede deshacer.</strong>
        </p>

        <button type="button" x-show="!confirmar" @click="confirmar = true"
                class="mt-4 px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
            Pasar a producción
        </button>

        <form x-show="confirmar" x-cloak method="POST" action="{{ route('empresa.sunat-config.go-production') }}" class="mt-4 space-y-3">
            @csrf
            <p class="text-sm text-red-800">Para confirmar, escribe <strong>PRODUCCION</strong> y presiona el botón:</p>
            <input type="text" name="confirmacion" placeholder="PRODUCCION" autocomplete="off"
                   class="w-full md:w-64 px-4 py-2.5 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 outline-none uppercase">
            <div class="flex gap-2">
                <button type="submit" class="px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                    Confirmar y pasar a producción
                </button>
                <button type="button" @click="confirmar = false" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
    @endunless

    {{-- Aviso cuando ya está en producción --}}
    @if($company->modo_produccion)
    <div class="rounded-xl border-2 border-green-200 bg-green-50 p-5">
        <p class="text-sm font-semibold text-green-800">✅ Empresa en producción</p>
        <p class="text-sm text-green-700 mt-1">Esta empresa emite comprobantes reales con valor legal.</p>
    </div>
    @endif
</div>
@endsection
