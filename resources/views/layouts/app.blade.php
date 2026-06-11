<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Dashboard')</title>
    <link rel="icon" href="{{ config('platform.favicon_url', asset('assets/brand/favicon.png')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        html, body { min-height: 100%; width: 100%; overflow-x: hidden; }
        body { margin: 0; background: #f8fafc; }
        .app-shell {
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            overflow-x: hidden;
            background: #f8fafc;
        }
        .app-sidebar {
            width: 232px;
            flex: 0 0 232px;
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 50;
            height: auto;
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .app-content {
            width: calc(100% - 232px);
            min-width: 0;
            margin-left: 232px;
            flex: 1 1 0%;
            overflow-x: hidden;
        }
        @media (max-width: 1023px) {
            .app-shell { display: block; }
            .app-sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                z-index: 50;
                height: auto;
                min-height: 100vh;
                transform: translateX(-100%);
            }
            .app-sidebar.is-open { transform: translateX(0); }
            .app-content {
                width: 100%;
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased"
      x-data="{
          mobileMenu: false,
          adminModalOpen: false,
          adminModalLoading: false,
          adminModalTitle: '',
          adminModalHtml: '',
          adminModalError: '',
          toastMessage: @js(session('success', '')),
          toastTimer: null,
          init() {
              window.openAdminModal = (url, title = 'Detalle') => this.loadAdminModal(url, title);
              window.closeAdminModal = () => { this.adminModalOpen = false; };
              window.syncSubscriptionPlan = (select) => {
                  const option = select.selectedOptions[0];
                  const form = select.form;
                  const price = form.querySelector('[name=&quot;monthly_price&quot;]');
                  const autoRenew = form.querySelector('[name=&quot;auto_renew&quot;]');
                  const renewalHelp = form.querySelector('[data-renewal-help]');
                  const paid = Number(option?.dataset.price || 0) > 0;

                  if (price) price.value = option?.dataset.price || '0.00';
                  if (autoRenew) {
                      autoRenew.disabled = !paid;
                      if (!paid) autoRenew.checked = false;
                  }
                  if (renewalHelp) {
                      renewalHelp.textContent = paid
                          ? 'Activa esta opción para renovar el plan automáticamente al vencer.'
                          : 'El plan Free no necesita renovación automática porque no tiene vencimiento.';
                  }
              };
              window.syncSubscriptionRenewal = (form) => {
                  const autoRenew = form.querySelector('[name=&quot;auto_renew&quot;]');
                  const endsAt = form.querySelector('[name=&quot;ends_at&quot;]');
                  const nextBilling = form.querySelector('[name=&quot;next_billing_at&quot;]');
                  if (nextBilling) nextBilling.value = autoRenew?.checked ? (endsAt?.value || '') : '';
              };
              const storedMessage = sessionStorage.getItem('adminSuccessMessage');
              if (storedMessage) {
                  this.toastMessage = storedMessage;
                  sessionStorage.removeItem('adminSuccessMessage');
              }
              if (this.toastMessage) this.hideToastLater();
              window.copyCompanyCredential = (button, value) => {
                  const done = () => {
                      const original = button.textContent;
                      button.textContent = 'Copiado';
                      setTimeout(() => { button.textContent = original || 'Copiar'; }, 1500);
                  };

                  if (navigator.clipboard && window.isSecureContext) {
                      navigator.clipboard.writeText(value).then(done);
                      return;
                  }

                  const input = document.createElement('textarea');
                  input.value = value;
                  input.style.position = 'fixed';
                  input.style.opacity = '0';
                  document.body.appendChild(input);
                  input.focus();
                  input.select();
                  document.execCommand('copy');
                  document.body.removeChild(input);
                  done();
              };
          },
          loadAdminModal(url, title) {
              this.adminModalOpen = true;
              this.adminModalLoading = true;
              this.adminModalTitle = title;
              this.adminModalHtml = '';
              this.adminModalError = '';
              const separator = url.includes('?') ? '&' : '?';
              fetch(url + separator + 'modal=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                  .then(response => {
                      if (!response.ok) throw new Error('No se pudo cargar');
                      return response.text();
                  })
                  .then(html => { this.adminModalHtml = html; })
                  .catch(() => { this.adminModalHtml = '<div class=&quot;p-6 text-sm text-red-600&quot;>No se pudo cargar el contenido.</div>'; })
                  .finally(() => { this.adminModalLoading = false; });
          },
          submitAdminModal(event) {
              const form = event.target;
              if (!(form instanceof HTMLFormElement)) return;
              event.preventDefault();
              this.adminModalLoading = true;
              this.adminModalError = '';
              fetch(form.action, {
                  method: form.method || 'POST',
                  body: new FormData(form),
                  headers: {
                      'X-Requested-With': 'XMLHttpRequest',
                      'Accept': 'application/json'
                  }
              })
                  .then(async response => {
                      if (response.status === 422) {
                          const data = await response.json();
                          this.adminModalError = Object.values(data.errors || {}).flat().join(' ');
                          return false;
                      }
                      if (!response.ok) throw new Error('No se pudo guardar');
                      return true;
                  })
                  .then(saved => {
                      if (!saved) return;
                      const method = form.querySelector('input[name=&quot;_method&quot;]')?.value?.toUpperCase();
                      const fallback = ['PUT', 'PATCH'].includes(method)
                          ? 'Registro actualizado correctamente.'
                          : 'Operación completada correctamente.';
                      sessionStorage.setItem('adminSuccessMessage', form.dataset.successMessage || fallback);
                      window.location.reload();
                  })
                  .catch(() => { this.adminModalError = 'No se pudo completar la operación.'; })
                  .finally(() => { this.adminModalLoading = false; });
          },
          hideToastLater() {
              clearTimeout(this.toastTimer);
              this.toastTimer = setTimeout(() => { this.toastMessage = ''; }, 3500);
          }
      }">
    @php
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $superAdminItems = [
            ['label' => 'Dashboard', 'route' => 'super-admin.dashboard', 'active' => 'super-admin.dashboard', 'color' => 'text-blue-600', 'icon' => 'M3 13h8V3H3v10zm10 8h8V3h-8v18zM3 21h8v-6H3v6z'],
            ['label' => 'Empresas', 'route' => 'super-admin.companies.index', 'active' => 'super-admin.companies.*', 'color' => 'text-emerald-600', 'icon' => 'M4 21V5a2 2 0 012-2h8a2 2 0 012 2v16M9 7h2m-2 4h2m-2 4h2m7 6v-8h2a2 2 0 012 2v6'],
            ['label' => 'Usuarios', 'route' => 'super-admin.users.index', 'active' => 'super-admin.users.*', 'color' => 'text-violet-600', 'icon' => 'M16 11c1.657 0 3-1.79 3-4s-1.343-4-3-4-3 1.79-3 4 1.343 4 3 4zM8 11c1.657 0 3-1.79 3-4S9.657 3 8 3 5 4.79 5 7s1.343 4 3 4zm0 2c-2.761 0-5 1.79-5 4v1h10v-1c0-2.21-2.239-4-5-4zm8 0c-.7 0-1.36.11-1.94.31 1.18.95 1.94 2.24 1.94 3.69v1h5v-1c0-2.21-2.239-4-5-4z'],
            ['label' => 'Documentos', 'route' => 'super-admin.documents', 'active' => 'super-admin.documents*', 'color' => 'text-sky-600', 'icon' => 'M7 3h7l5 5v13H7V3zm7 0v5h5M9 13h8M9 17h8'],
            ['label' => 'Planes', 'route' => 'super-admin.plans', 'active' => 'super-admin.plans', 'color' => 'text-amber-600', 'icon' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm0 3h18M7 15h4'],
            ['label' => 'Suscripciones', 'route' => 'super-admin.subscriptions.index', 'active' => 'super-admin.subscriptions.*', 'color' => 'text-green-600', 'icon' => 'M12 6v12m-4-9h6.5a2.5 2.5 0 010 5H9.5a2.5 2.5 0 000 5H16M5 4h14v16H5z'],
            ['label' => 'Pagos', 'route' => 'super-admin.payments.index', 'active' => 'super-admin.payments.*', 'color' => 'text-lime-600', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['label' => 'API', 'route' => 'super-admin.api-global', 'active' => 'super-admin.api-global*', 'color' => 'text-indigo-600', 'icon' => 'M8 9l-3 3 3 3m8-6l3 3-3 3m-5 3l2-12'],
            ['label' => 'Soporte', 'route' => 'super-admin.support', 'active' => 'super-admin.support*', 'color' => 'text-rose-600', 'icon' => 'M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4v8z'],
            ['label' => 'Reportes', 'route' => 'super-admin.statistics', 'active' => 'super-admin.statistics', 'color' => 'text-cyan-600', 'icon' => 'M4 19V9m5 10V5m5 14v-7m5 7V3'],
            ['label' => 'Certificados', 'route' => 'super-admin.certificates', 'active' => 'super-admin.certificates', 'color' => 'text-teal-600', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['label' => 'Configuración', 'route' => 'super-admin.settings', 'active' => 'super-admin.settings', 'color' => 'text-slate-600', 'icon' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm8.94 3a7.96 7.96 0 00-.68-1.64l1.13-1.13-1.41-1.41-1.13 1.13A7.96 7.96 0 0017 7.06V5h-2v2.06a7.96 7.96 0 00-1.64.68l-1.13-1.13-1.41 1.41 1.13 1.13c-.3.51-.53 1.06-.68 1.64H9v2h2.06c.15.58.38 1.13.68 1.64l-1.13 1.13 1.41 1.41 1.13-1.13c.51.3 1.06.53 1.64.68V19h2v-2.06c.58-.15 1.13-.38 1.64-.68l1.13 1.13 1.41-1.41-1.13-1.13c.3-.51.53-1.06.68-1.64H23v-2h-2.06z'],
            ['label' => 'Auditoría', 'route' => 'super-admin.audit.index', 'active' => 'super-admin.audit.*', 'color' => 'text-fuchsia-600', 'icon' => 'M9 12l2 2 4-4m4-5H5v16h14V5zm-3-2v4H8V3h8z'],
        ];

        $companyItems = [
            ['label' => 'Dashboard', 'route' => 'empresa.dashboard', 'active' => 'empresa.dashboard', 'icon' => 'M3 13h8V3H3v10zm10 8h8V3h-8v18zM3 21h8v-6H3v6z'],
            ['label' => 'Mi Empresa', 'route' => 'empresa.company.edit', 'active' => 'empresa.company.*', 'icon' => 'M4 21V5a2 2 0 012-2h8a2 2 0 012 2v16M9 7h2m-2 4h2m-2 4h2m7 6v-8h2a2 2 0 012 2v6'],
            ['label' => 'Clientes', 'route' => 'empresa.clients.index', 'active' => 'empresa.clients.*', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2a4 4 0 10-8 0 4 4 0 008 0zm6-3a4 4 0 11-8 0 4 4 0 018 0z'],
            ['label' => 'Correlativos', 'route' => 'empresa.correlatives.index', 'active' => 'empresa.correlatives.*', 'icon' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14'],
            ['label' => 'Comprobantes', 'active' => 'empresa.facturas.*|empresa.boletas.*|empresa.notas-credito.*|empresa.notas-debito.*|empresa.guias.*', 'icon' => 'M7 3h7l5 5v13H7V3zm7 0v5h5M9 13h8M9 17h8', 'children' => [
                ['label' => 'Facturas', 'route' => 'empresa.facturas.index', 'active' => 'empresa.facturas.*'],
                ['label' => 'Boletas', 'route' => 'empresa.boletas.index', 'active' => 'empresa.boletas.*'],
                ['label' => 'Notas Crédito', 'route' => 'empresa.notas-credito.index', 'active' => 'empresa.notas-credito.*'],
                ['label' => 'Notas Débito', 'route' => 'empresa.notas-debito.index', 'active' => 'empresa.notas-debito.*'],
                ['label' => 'Guías Remisión', 'route' => 'empresa.guias.index', 'active' => 'empresa.guias.*'],
            ]],
            ['label' => 'Config. SUNAT', 'route' => 'empresa.sunat-config.index', 'active' => 'empresa.sunat-config.*', 'icon' => 'M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4zm0 6v5l3 2'],
            ['label' => '¿Cómo emitir?', 'route' => 'empresa.ayuda-emision', 'active' => 'empresa.ayuda-emision', 'icon' => 'M12 2a10 10 0 100 20 10 10 0 000-20zm0 15h.01M12 7a2 2 0 012 2c0 1-1 1.5-1.7 2.2-.3.3-.3.6-.3 1.3'],
            ['label' => 'API Keys', 'route' => 'empresa.api-keys.index', 'active' => 'empresa.api-keys.*', 'icon' => 'M15 7a4 4 0 10-3.46 6H9v2H7v2H5v2H3v-4.54L9.54 8A4 4 0 0015 7zm0 0h.01'],
            ['label' => 'Documentos', 'route' => 'empresa.documents.index', 'active' => 'empresa.documents.*', 'icon' => 'M7 3h7l5 5v13H7V3zm7 0v5h5M9 13h8M9 17h8'],
            ['label' => 'Anulaciones', 'route' => 'empresa.anulaciones.index', 'active' => 'empresa.anulaciones.*', 'icon' => 'M10 11v6M14 11v6M5 7h14l-1 13H6L5 7zm3 0V4h8v3'],
            ['label' => 'Resumen Boletas', 'route' => 'empresa.resumenes.index', 'active' => 'empresa.resumenes.*', 'icon' => 'M9 17v-6h6v6M4 6h16M7 6V4h10v2M5 6l1 14h12l1-14'],
            ['label' => 'Consulta CPE', 'route' => 'empresa.consulta-cpe.index', 'active' => 'empresa.consulta-cpe.*', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
            ['label' => 'Notificaciones', 'route' => 'empresa.notifications.index', 'active' => 'empresa.notifications.*', 'icon' => 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'badge' => optional(auth()->user())->unreadNotifications()->count()],
            ['label' => 'Mi Plan', 'route' => 'empresa.plan.index', 'active' => 'empresa.plan.*', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['label' => 'Perfil', 'route' => 'empresa.profile.edit', 'active' => 'empresa.profile.*', 'icon' => 'M12 12a5 5 0 100-10 5 5 0 000 10zm-8 9a8 8 0 0116 0'],
        ];

        $menuItems = $isSuperAdmin ? $superAdminItems : $companyItems;
    @endphp

    <div class="app-shell min-h-screen lg:flex">
        <div x-show="mobileMenu" x-cloak class="fixed inset-0 z-40 bg-gray-900/40 lg:hidden" @click="mobileMenu = false"></div>

        <aside class="app-sidebar fixed inset-y-0 left-0 z-50 flex -translate-x-full flex-col border-r border-gray-200 bg-white lg:sticky lg:top-0 lg:translate-x-0"
               :class="mobileMenu ? 'is-open translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <img src="{{ config('platform.icon_url', asset('assets/brand/cisma-fact-icon.png')) }}"
                         alt="{{ config('app.name') }}"
                         class="h-10 w-11 shrink-0 object-contain">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">Cisma Fact</p>
                        <p class="truncate text-xs text-gray-500">Facturacion electronica</p>
                    </div>
                </div>
                <button type="button" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 lg:hidden" @click="mobileMenu = false">
                    <span class="sr-only">Cerrar menu</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-4">
                @foreach($menuItems as $item)
                    @if(!empty($item['children']))
                        @php $grupoActivo = request()->routeIs(...explode('|', $item['active'])); @endphp
                        <div x-data="{ open: {{ $grupoActivo ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open"
                                    class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium {{ $grupoActivo ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                                <svg class="h-5 w-5 shrink-0 {{ $item['color'] ?? 'text-gray-500' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
                                <svg class="ml-auto h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="mt-1 space-y-1 pl-9">
                                @foreach($item['children'] as $child)
                                    <a href="{{ route($child['route']) }}"
                                       class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs($child['active']) ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-100' }}">
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium {{ request()->routeIs($item['active']) ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="h-5 w-5 shrink-0 {{ $item['color'] ?? 'text-gray-500' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                            </svg>
                            <span>{{ $item['label'] }}</span>
                            @if(!empty($item['badge']) && $item['badge'] > 0)
                                <span class="ml-auto inline-flex items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="mt-auto shrink-0 border-t border-gray-200 bg-white p-4">
                <p class="truncate text-sm font-medium text-gray-900">{{ $user->name }}</p>
                <p class="truncate text-xs text-gray-500">{{ $user->role->display_name ?? $user->role->name ?? '' }}</p>
            </div>
        </aside>

        <div class="app-content min-w-0 flex-1">
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-md p-2 text-gray-600 hover:bg-gray-100 lg:hidden" @click="mobileMenu = true">
                        <span class="sr-only">Abrir menu</span>
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">@yield('title', 'Dashboard')</h1>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                        Salir
                    </button>
                </form>
            </header>

            <main class="p-4 lg:p-6">
                @if(session('error'))
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div x-show="adminModalOpen" x-cloak style="z-index: 9999;"
         class="fixed inset-0 flex items-center justify-center bg-gray-900/50 p-4"
         @keydown.escape.window="adminModalOpen = false">
        <div class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-lg bg-white shadow-xl"
             @click.outside="adminModalOpen = false">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <h3 class="text-base font-semibold text-gray-900" x-text="adminModalTitle"></h3>
                <button type="button" class="rounded-md p-2 text-gray-500 hover:bg-gray-100"
                        @click="adminModalOpen = false" aria-label="Cerrar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="relative max-h-[80vh] overflow-y-auto">
                <div x-show="adminModalLoading && !adminModalHtml" class="p-6 text-sm text-gray-500">Cargando...</div>
                <div x-show="adminModalLoading && adminModalHtml"
                     class="absolute inset-0 z-20 flex items-center justify-center bg-white/70"
                     aria-live="polite">
                    <div class="rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm">
                        Guardando...
                    </div>
                </div>
                <div x-show="adminModalError" x-text="adminModalError" class="mx-5 mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
                <div x-html="adminModalHtml" @submit="submitAdminModal($event)"></div>
            </div>
        </div>
    </div>

    <div x-show="toastMessage" x-cloak x-transition.opacity
         style="z-index: 10000;"
         class="fixed right-4 top-4 max-w-sm rounded-md border border-green-200 bg-white px-4 py-3 text-sm font-medium text-green-700 shadow-lg"
         role="status">
        <div class="flex items-center gap-3">
            <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-green-500"></span>
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="ml-auto text-gray-400 hover:text-gray-700" aria-label="Cerrar">×</button>
        </div>
    </div>
</body>
</html>
