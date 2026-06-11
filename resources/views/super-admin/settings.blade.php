@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <header>
        <h1 class="text-2xl font-bold text-slate-900">Configuración</h1>
        <p class="mt-1 text-sm text-slate-500">Identidad, correo y disponibilidad global de Cisma Fact.</p>
    </header>

    <form
        action="{{ route('super-admin.settings.update') }}"
        method="POST"
        enctype="multipart/form-data"
        class="space-y-6"
        onsubmit="return document.querySelector('[name=maintenance_mode]').value !== 'on' || confirm('¿Activar mantenimiento? El sitio quedará bloqueado hasta que lo desactives desde la URL secreta o con php artisan up.')"
    >
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Datos generales</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Nombre de la plataforma</span>
                    <input name="app_name" value="{{ old('app_name', $settings['app_name'] ?? 'Cisma Fact') }}" required maxlength="255"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('app_name')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Correo de soporte</span>
                    <input type="email" name="support_email" value="{{ old('support_email', $settings['support_email'] ?? '') }}" maxlength="255"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:ring-blue-500">
                    @error('support_email')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
                </label>

                <label class="block md:col-span-2">
                    <span class="text-sm font-medium text-slate-700">URL pública</span>
                    <input type="url" name="domain" value="{{ old('domain', $settings['domain'] ?? config('app.url')) }}"
                           placeholder="https://facturacion.midominio.com" maxlength="255"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:ring-blue-500">
                    <span class="mt-1 block text-xs text-slate-500">Incluye http:// o https://. En producción utiliza siempre HTTPS.</span>
                    @error('domain')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Identidad visual</h2>
            <div class="mt-5 grid gap-6 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700" for="platform_logo">Logo de la plataforma</label>
                    <div class="mt-2 flex min-h-24 items-center gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <img src="{{ !empty($settings['platform_logo']) ? Storage::disk('public')->url($settings['platform_logo']) : asset('assets/brand/cisma-fact.png') }}"
                             alt="Logo actual" class="max-h-16 max-w-48 object-contain">
                        <input id="platform_logo" type="file" name="platform_logo" accept=".png,.jpg,.jpeg,.webp"
                               class="min-w-0 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-blue-700">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">PNG, JPG o WebP. Máximo 2 MB.</p>
                    @error('platform_logo')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700" for="favicon">Favicon</label>
                    <div class="mt-2 flex min-h-24 items-center gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <img src="{{ !empty($settings['favicon']) ? Storage::disk('public')->url($settings['favicon']) : asset('assets/brand/favicon.png') }}"
                             alt="Favicon actual" class="h-12 w-12 object-contain">
                        <input id="favicon" type="file" name="favicon" accept=".png,.ico"
                               class="min-w-0 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-blue-700">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">PNG o ICO. Máximo 512 KB.</p>
                    @error('favicon')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Correo SMTP</h2>
                <p class="mt-1 text-sm text-slate-500">Se utilizará para recuperación de contraseñas y notificaciones.</p>
            </div>
            <div class="mt-5 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-slate-700">Servidor</span>
                    <input name="smtp_host" value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}" maxlength="255"
                           placeholder="smtp.example.com"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Puerto</span>
                    <input type="number" name="smtp_port" min="1" max="65535" value="{{ old('smtp_port', $settings['smtp_port'] ?? 587) }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Seguridad</span>
                    <select name="smtp_encryption" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:ring-blue-500">
                        <option value="tls" @selected(old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') === 'tls')>TLS</option>
                        <option value="ssl" @selected(old('smtp_encryption', $settings['smtp_encryption'] ?? '') === 'ssl')>SSL</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Usuario</span>
                    <input name="smtp_username" value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}" maxlength="255"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Contraseña</span>
                    <input type="password" name="smtp_password" maxlength="255" autocomplete="new-password"
                           placeholder="{{ isset($settings['smtp_password']) ? 'Guardada, deja vacío para conservarla' : 'Contraseña SMTP' }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:ring-blue-500">
                </label>
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-slate-700">Correo remitente</span>
                    <input type="email" name="smtp_from_address" value="{{ old('smtp_from_address', $settings['smtp_from_address'] ?? '') }}" maxlength="255"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-500 focus:ring-blue-500">
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Disponibilidad</h2>
            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <select name="maintenance_mode" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 sm:w-48">
                    <option value="off" @selected(old('maintenance_mode', $settings['maintenance_mode'] ?? 'off') === 'off')>Operativo</option>
                    <option value="on" @selected(old('maintenance_mode', $settings['maintenance_mode'] ?? 'off') === 'on')>Mantenimiento</option>
                </select>
                <p class="text-sm text-slate-500">
                    Mantenimiento bloquea todo el sitio. Para recuperarlo desde el servidor ejecuta <code class="rounded bg-slate-100 px-1.5 py-0.5">php artisan up</code>.
                </p>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 font-medium text-white hover:bg-blue-700">
                Guardar configuración
            </button>
        </div>
    </form>
</div>
@endsection
