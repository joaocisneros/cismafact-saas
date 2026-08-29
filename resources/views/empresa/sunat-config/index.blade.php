@extends('layouts.app')

@section('title', 'Configuración SUNAT')

@section('content')

    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        ¿Dudas con el certificado, la Clave SOL o la diferencia entre pruebas y producción?
        <a href="{{ route('empresa.ayuda-emision') }}" class="font-medium underline">Lee la guía de emisión</a>.
    </div>
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

    @php
        // En pruebas la plataforma pone certificado y credenciales por su cuenta,
        // asi que no tiene sentido enseñar aqui todo el bloque de produccion.
        $enBeta = ! $company->modo_produccion;
    @endphp

    @if($enBeta)
        <div class="rounded-xl border-2 border-amber-200 bg-amber-50 p-5">
            <div class="flex items-start gap-3">
                <span class="text-2xl leading-none">🧪</span>
                <div>
                    <p class="text-sm font-semibold text-amber-900">Estás en modo prueba</p>
                    <p class="text-sm text-amber-800 mt-1">
                        Ya puedes emitir. <strong>No hay nada que configurar.</strong>
                        Estas facturas no tienen valor legal.
                    </p>
                </div>
            </div>
        </div>

        {{-- Lo que la plataforma esta usando ahora mismo para las pruebas. Solo
             informativo: en beta el cliente no tiene que rellenar nada. --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-4">Datos de prueba en uso</h3>

            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="rounded-lg border border-gray-200 p-4">
                    <dt class="text-xs text-gray-500">Ambiente</dt>
                    <dd class="mt-1 font-medium text-amber-700">● Beta (pruebas)</dd>
                </div>
                {{-- No se enseña el usuario de pruebas de SUNAT: no es de esta
                     empresa, es un valor compartido, y verlo solo confunde. --}}
                <div class="rounded-lg border border-gray-200 p-4">
                    <dt class="text-xs text-gray-500">Credenciales</dt>
                    <dd class="mt-1 font-medium text-green-700">Listas</dd>
                    <dd class="text-xs text-gray-500 mt-0.5">Las pone la plataforma</dd>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <dt class="text-xs text-gray-500">Certificado</dt>
                    <dd class="mt-1 font-medium text-green-700">Listo</dd>
                    <dd class="text-xs text-gray-500 mt-0.5">Para firmar tus pruebas</dd>
                </div>
            </dl>
        </div>
    @else
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
    @endif

    <form method="POST" action="{{ route('empresa.sunat-config.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- En pruebas no se enseña NADA de esto: certificado, credenciales y
             guias de remision son cosas de produccion, y en beta solo estorban.
             El formulario sigue existiendo para no romper el guardado. --}}
        @unless($enBeta)

        {{-- Método de emisión: define si la empresa emite desde Cisma Fact o manual en SUNAT --}}
        <div class="bg-white rounded-xl shadow-sm p-6 hidden">
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

        {{-- Credenciales SOL y certificado: son datos REALES, y solo se piden
             al pasar a produccion. En pruebas la plataforma pone los suyos. --}}
        @unless($enBeta)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-800 mb-1">Credenciales SOL</h3>
            <p class="text-xs text-gray-500 mb-4">Para emitir facturas, boletas y notas. Usa tu <strong>usuario secundario</strong> con perfil de facturación electrónica, no tu Clave SOL principal.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="usuario_sol" class="block text-sm font-medium text-gray-700 mb-1">Usuario SOL secundario</label>
                    <input type="text" name="usuario_sol" id="usuario_sol"
                           value="{{ old('usuario_sol', $company->usuario_sol) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                           placeholder="Ej. FACTURA01 (no tu clave principal)">
                </div>
                <div>
                    <label for="clave_sol" class="block text-sm font-medium text-gray-700 mb-1">Clave de ese usuario secundario</label>
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
        @endunless {{-- /solo en produccion: SOL + certificado --}}

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

        @else
            {{-- En beta el formulario no enseña campos, pero metodo_emision es
                 obligatorio en el validador: se manda el valor actual tal cual. --}}
            <input type="hidden" name="metodo_emision" value="{{ $company->metodo_emision ?? 'cisma_fact' }}">
        @endunless
    </form>

    {{-- Comprobacion de conexion. En pruebas no se enseña como un boton grande:
         no es algo que el cliente tenga que hacer, solo sirve para diagnosticar
         si algo falla. --}}
    <form method="POST" action="{{ route('empresa.sunat-config.test') }}" x-show="metodo === 'cisma_fact'" x-cloak>
        @csrf
        @if($enBeta)
            <button type="submit" class="text-sm text-gray-500 underline decoration-dotted hover:text-gray-700">
                ¿Algo falla? Comprobar que SUNAT responde
            </button>
        @else
            <button type="submit" class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                🔌 Probar conexión con SUNAT
            </button>
            <span class="text-xs text-gray-500 ml-2">Valida tus credenciales con SUNAT sin emitir ningún documento.</span>
        @endif
    </form>

    {{-- Pasar a producción: solo en modo Cisma Fact y mientras la empresa esté en beta --}}
    @unless($company->modo_produccion)
    {{-- Si el intento fallo, el formulario se queda abierto con lo que ya
         estaba escrito. Antes se cerraba, y al reabrirlo los campos volvian a
         los valores originales: se subia el certificado nuevo con el RUC
         viejo, y el aviso resultante no ayudaba a entender por que. --}}
    <div x-show="metodo === 'cisma_fact'" x-cloak
         x-data="{ confirmar: {{ $errors->any() || session('error') ? 'true' : 'false' }} }"
         class="rounded-xl border-2 border-red-200 bg-red-50 p-6">
        <h3 class="text-md font-semibold text-red-800">🚀 Empezar a facturar de verdad</h3>
        <p class="text-sm text-red-700 mt-1">
            Se borran las facturas de prueba y la numeración empieza en 1.
        </p>

        <button type="button" x-show="!confirmar" @click="confirmar = true"
                class="mt-4 px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
            Empezar
        </button>

        <form x-show="confirmar" x-cloak method="POST" enctype="multipart/form-data"
              action="{{ route('empresa.sunat-config.go-production') }}" class="mt-4 space-y-5">
            @csrf

            {{-- Los datos reales se piden aqui, no antes: mientras la empresa
                 prueba no necesita ninguno de estos. --}}
            <div class="rounded-lg border border-red-200 bg-white p-5 space-y-4">
                {{-- El "de donde saco esto" se deja en un desplegable: quien ya
                     tiene sus datos no necesita leerlo. --}}
                {{-- Datos fiscales reales: en pruebas se pudo registrar con un RUC
                     cualquiera, y estos son los que iran en cada comprobante. --}}
                <div>
                    <p class="text-sm font-semibold text-gray-800">1. Tus datos fiscales reales</p>
                    <p class="mt-0.5 text-xs text-gray-500">Son los que aparecerán en cada comprobante ante SUNAT.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label for="prod_ruc" class="mb-1 block text-sm font-medium text-gray-700">RUC</label>
                        <input type="text" name="ruc" id="prod_ruc" maxlength="11" inputmode="numeric"
                               value="{{ old('ruc', $company->ruc) }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div class="md:col-span-2">
                        <label for="prod_razon_social" class="mb-1 block text-sm font-medium text-gray-700">Razón social</label>
                        <input type="text" name="razon_social" id="prod_razon_social" maxlength="255"
                               value="{{ old('razon_social', $company->razon_social) }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    {{-- La direccion y el ubigeo tambien van en cada XML. Faltaban
                         aqui, y las facturas reales salian con la de pruebas. --}}
                    <div class="md:col-span-3">
                        <label for="prod_direccion" class="mb-1 block text-sm font-medium text-gray-700">Dirección fiscal</label>
                        <input type="text" name="direccion" id="prod_direccion" maxlength="255"
                               value="{{ old('direccion', $company->direccion) }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-red-500"
                               placeholder="La que figura en tu ficha RUC">
                    </div>

                    <div>
                        <label for="prod_departamento" class="mb-1 block text-sm font-medium text-gray-700">Departamento</label>
                        <input type="text" name="departamento" id="prod_departamento" maxlength="100"
                               value="{{ old('departamento', $company->departamento) }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label for="prod_provincia" class="mb-1 block text-sm font-medium text-gray-700">Provincia</label>
                        <input type="text" name="provincia" id="prod_provincia" maxlength="100"
                               value="{{ old('provincia', $company->provincia) }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label for="prod_distrito" class="mb-1 block text-sm font-medium text-gray-700">Distrito</label>
                        <input type="text" name="distrito" id="prod_distrito" maxlength="100"
                               value="{{ old('distrito', $company->distrito) }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label for="prod_ubigeo" class="mb-1 block text-sm font-medium text-gray-700">Ubigeo</label>
                        <input type="text" name="ubigeo" id="prod_ubigeo" maxlength="6" inputmode="numeric"
                               value="{{ old('ubigeo', $company->ubigeo) }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:ring-2 focus:ring-red-500"
                               placeholder="150101">
                        <p class="mt-1 text-xs text-gray-500">6 dígitos. Ej. 150101 = Lima · Lima · Lima.</p>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <p class="text-sm font-semibold text-gray-800">2. Tu certificado digital</p>
                    <details class="mt-1">
                        <summary class="cursor-pointer text-xs text-blue-600 hover:underline">¿De dónde lo saco?</summary>
                        <p class="text-xs text-gray-600 mt-2 leading-relaxed">
                            Si eres <strong>MYPE</strong> (hasta 300 UIT de ventas), SUNAT te lo da gratis: entra con tu
                            Clave SOL, abre tu <strong>Buzón SOL</strong> y busca el mensaje
                            «Emisión de Certificado Digital Tributario».<br>
                            Si no, se compra a una entidad certificadora acreditada.
                        </p>
                    </details>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="prod_certificado_pfx" class="block text-sm font-medium text-gray-700 mb-1">Certificado .pfx</label>
                        <input type="file" name="certificado_pfx" id="prod_certificado_pfx" accept=".pfx,.p12"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm">
                        @if($company->certificado_pem)
                            <p class="text-xs text-green-600 mt-1">✓ Ya hay un certificado cargado. Sube uno nuevo solo si quieres reemplazarlo.</p>
                        @endif
                    </div>
                    <div>
                        <label for="prod_certificado_password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña del certificado</label>
                        <input type="password" name="certificado_password" id="prod_certificado_password" autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 outline-none"
                               placeholder="********">
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <p class="text-sm font-semibold text-gray-800">3. Tu usuario SOL <span class="text-red-600">secundario</span></p>
                    {{-- Esto se explicaba dentro de un desplegable cerrado, asi que
                         casi todo el mundo ponia su clave principal. Va a la vista. --}}
                    <p class="mt-1 text-xs leading-relaxed text-gray-600">
                        <strong class="text-gray-800">No pongas aquí tu Clave SOL principal.</strong>
                        SUNAT pide un <strong>usuario secundario</strong> con el perfil de facturación
                        electrónica. Es gratis y se crea en un minuto. Si pusieras la principal, esa clave
                        —que da acceso a todas tus declaraciones y a tu RUC— quedaría guardada aquí.
                    </p>
                    <details class="mt-2">
                        <summary class="cursor-pointer text-xs text-blue-600 hover:underline">¿Cómo lo creo?</summary>
                        <p class="text-xs text-gray-600 mt-2 leading-relaxed">
                            Entra a SUNAT con tu Clave SOL <em>principal</em> →
                            <em>Administración de usuarios secundarios</em> → crea uno y actívale el perfil de
                            <strong>facturación electrónica</strong>. Luego vuelve aquí y escribe ese usuario y su clave.
                        </p>
                    </details>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="prod_usuario_sol" class="block text-sm font-medium text-gray-700 mb-1">Usuario SOL secundario</label>
                        <input type="text" name="usuario_sol" id="prod_usuario_sol" autocomplete="off"
                               value="{{ old('usuario_sol', strcasecmp((string) $company->usuario_sol, 'MODDATOS') === 0 ? '' : $company->usuario_sol) }}"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 outline-none"
                               placeholder="Ej. FACTURA01 (no tu clave principal)">
                    </div>
                    <div>
                        <label for="prod_clave_sol" class="block text-sm font-medium text-gray-700 mb-1">Clave de ese usuario secundario</label>
                        <input type="password" name="clave_sol" id="prod_clave_sol" autocomplete="new-password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 outline-none"
                               placeholder="********">
                    </div>
                </div>
            </div>

            {{-- Ya no hay que escribir PRODUCCION a mano: basta el boton, con un
                 aviso del navegador. El campo se manda igual porque el servidor
                 lo sigue exigiendo, asi nadie llega aqui por una URL suelta. --}}
            <input type="hidden" name="confirmacion" value="PRODUCCION">
            <div class="flex gap-2">
                <button type="submit"
                        onclick="return confirm('Vas a pasar a producción.\n\nSe BORRAN las facturas de prueba y la numeración vuelve a empezar en 1. No se puede deshacer.\n\n¿Continuar?')"
                        class="px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                    Pasar a producción
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
