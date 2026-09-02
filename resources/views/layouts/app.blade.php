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
        /* El menu lateral sigue desplazandose, pero sin pintar la barra: al
           desplegar Comprobantes aparecia una barra gris que cortaba el lateral
           y se veia como un fallo. Se oculta en los tres motores. */
        .sin-barra { scrollbar-width: none; -ms-overflow-style: none; }
        .sin-barra::-webkit-scrollbar { width: 0; height: 0; display: none; }

        /* Y en toda la aplicacion, no solo en el menu: la barra gris del
           navegador al lado de una tabla se lee como un fallo. Se sigue
           pudiendo desplazar con la rueda, el teclado y el gesto tactil; lo
           unico que desaparece es el dibujo. */
        html, body, .app-content, .app-shell {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        html::-webkit-scrollbar,
        body::-webkit-scrollbar,
        .app-content::-webkit-scrollbar,
        .app-shell::-webkit-scrollbar { width: 0; height: 0; display: none; }

        /* Lo que se desplaza dentro de su propia caja: tablas anchas, listas
           largas y el cuerpo de los modales. Cada pantalla pone esas clases
           por su cuenta, asi que se cubren aqui en vez de ir archivo por
           archivo (y de que a la siguiente se olvide). */
        .overflow-x-auto,
        .overflow-y-auto,
        .overflow-auto {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .overflow-x-auto::-webkit-scrollbar,
        .overflow-y-auto::-webkit-scrollbar,
        .overflow-auto::-webkit-scrollbar { width: 0; height: 0; display: none; }

        html, body { min-height: 100%; width: 100%; overflow-x: hidden; }
        body { margin: 0; background: #f8fafc; }
        .app-shell {
            width: 100%;
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
                      // Si hubo redireccion, el servidor rechazo la peticion
                      // (normalmente por permisos) y esto es otra pagina, no el
                      // formulario: sin este control se metia una pagina entera
                      // dentro del modal y se veia en blanco.
                      if (response.redirected) throw new Error('sin permiso');
                      return response.text();
                  })
                  .then(html => {
                      // Ultima red: una pagina completa nunca es contenido de modal.
                      if (/<!DOCTYPE|<html[\s>]/i.test(html)) throw new Error('sin permiso');
                      this.adminModalHtml = html;
                  })
                  .catch(error => {
                      this.adminModalHtml = error.message === 'sin permiso'
                          ? '<div class=&quot;p-6 text-sm text-amber-700&quot;>No tienes permiso para esta accion.</div>'
                          : '<div class=&quot;p-6 text-sm text-red-600&quot;>No se pudo cargar el contenido.</div>';
                  })
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
                      if (response.status === 419) throw new Error('tu sesión caducó');
                      if (response.status === 403) throw new Error('sin permiso');
                      if (!response.ok) throw new Error('el servidor respondió ' + response.status);
                      return true;
                  })
                  .then(saved => {
                      if (!saved) return;
                      const method = form.querySelector('input[name=&quot;_method&quot;]')?.value?.toUpperCase();
                      const fallback = ['PUT', 'PATCH'].includes(method)
                          ? 'Registro actualizado correctamente.'
                          : 'Operación completada correctamente.';
                      // sessionStorage puede fallar (ventana privada, cookies
                      // bloqueadas) y eso no debe convertirse en un error: el
                      // guardado ya se hizo. Solo se pierde el aviso.
                      try {
                          sessionStorage.setItem('adminSuccessMessage', form.dataset.successMessage || fallback);
                      } catch (e) {}

                      /* Con data-recargar-modal, la respuesta se ve en el propio
                         modal en vez de recargar la pagina detras.

                         Hace falta cuando lo que devuelve la accion es justo lo
                         que se estaba mirando: al generar un secret nuevo, cerrar
                         la ventana y dejarlo en un aviso al fondo de la pagina
                         obliga a buscarlo, y no se entiende que sea la respuesta
                         a lo que se acaba de pulsar. */
                      const recargarModal = form.dataset.recargarModal;

                      if (recargarModal) {
                          this.loadAdminModal(recargarModal, this.adminModalTitle);
                          return;
                      }

                      window.location.reload();
                  })
                  .catch(error => {
                      // Se muestra el motivo: un mensaje generico no deja
                      // averiguar si fue la red, un 500 o la sesion caducada.
                      const detalle = error && error.message ? ' (' + error.message + ')' : '';
                      this.adminModalError = 'No se pudo completar la operación' + detalle
                          + '. Si vuelve a pasar, recarga la página e inténtalo de nuevo.';
                      console.error('submitAdminModal:', error);
                  })
                  .finally(() => { this.adminModalLoading = false; });
          },
          hideToastLater() {
              clearTimeout(this.toastTimer);
              this.toastTimer = setTimeout(() => { this.toastMessage = ''; }, 3500);
          }
      }">
    @php
        $user = Auth::user();
        $isSuperAdmin = $user->accedeAlPanelAdmin();

        $superAdminItems = [
            ['label' => 'Dashboard', 'route' => 'super-admin.dashboard', 'active' => 'super-admin.dashboard', 'color' => 'text-blue-600', 'icon' => 'M3 13h8V3H3v10zm10 8h8V3h-8v18zM3 21h8v-6H3v6z'],
            ['label' => 'Empresas', 'route' => 'super-admin.companies.index', 'active' => 'super-admin.companies.*', 'color' => 'text-emerald-600', 'icon' => 'M4 21V5a2 2 0 012-2h8a2 2 0 012 2v16M9 7h2m-2 4h2m-2 4h2m7 6v-8h2a2 2 0 012 2v6'],
            ['label' => 'Usuarios', 'route' => 'super-admin.users.index', 'active' => 'super-admin.users.*', 'color' => 'text-violet-600', 'icon' => 'M16 11c1.657 0 3-1.79 3-4s-1.343-4-3-4-3 1.79-3 4 1.343 4 3 4zM8 11c1.657 0 3-1.79 3-4S9.657 3 8 3 5 4.79 5 7s1.343 4 3 4zm0 2c-2.761 0-5 1.79-5 4v1h10v-1c0-2.21-2.239-4-5-4zm8 0c-.7 0-1.36.11-1.94.31 1.18.95 1.94 2.24 1.94 3.69v1h5v-1c0-2.21-2.239-4-5-4z'],
            ['label' => 'Documentos', 'route' => 'super-admin.documents', 'active' => 'super-admin.documents*', 'color' => 'text-sky-600', 'icon' => 'M7 3h7l5 5v13H7V3zm7 0v5h5M9 13h8M9 17h8'],
            ['label' => 'Planes', 'route' => 'super-admin.plans', 'active' => 'super-admin.plans', 'color' => 'text-amber-600', 'icon' => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm0 3h18M7 15h4'],
            ['label' => 'Suscripciones', 'route' => 'super-admin.subscriptions.index', 'active' => 'super-admin.subscriptions.*', 'color' => 'text-green-600', 'icon' => 'M12 6v12m-4-9h6.5a2.5 2.5 0 010 5H9.5a2.5 2.5 0 000 5H16M5 4h14v16H5z'],
            ['label' => 'Pagos', 'route' => 'super-admin.payments.index', 'active' => 'super-admin.payments.*', 'color' => 'text-lime-600', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['label' => 'API Facturación', 'route' => 'super-admin.api-global', 'active' => 'super-admin.api-global*', 'color' => 'text-indigo-600', 'icon' => 'M8 9l-3 3 3 3m8-6l3 3-3 3m-5 3l2-12'],

            ['label' => 'Sandbox Facturación', 'route' => 'super-admin.tokens-prueba.index', 'active' => 'super-admin.tokens-prueba.*', 'color' => 'text-purple-600', 'icon' => 'M15 7a4 4 0 10-3.46 6H9v2H7v2H5v2H3v-4.54L9.54 8A4 4 0 0015 7zm0 0h.01'],            ['label' => 'Soporte', 'route' => 'super-admin.support', 'active' => 'super-admin.support*', 'color' => 'text-rose-600', 'icon' => 'M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4v8z'],
            ['label' => 'Reportes', 'route' => 'super-admin.statistics', 'active' => 'super-admin.statistics', 'color' => 'text-cyan-600', 'icon' => 'M4 19V9m5 10V5m5 14v-7m5 7V3'],
            ['label' => 'Certificados', 'route' => 'super-admin.certificates', 'active' => 'super-admin.certificates', 'color' => 'text-teal-600', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['label' => 'API RUC y DNI', 'route' => 'super-admin.consultas', 'active' => 'super-admin.consultas*', 'color' => 'text-indigo-600', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
            ['label' => 'Padrón SUNAT', 'route' => 'super-admin.padron', 'active' => 'super-admin.padron*', 'color' => 'text-cyan-600', 'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'],
            ['label' => 'Configuración', 'route' => 'super-admin.settings', 'active' => 'super-admin.settings', 'color' => 'text-slate-600', 'icon' => 'M12 8a4 4 0 100 8 4 4 0 000-8zm8.94 3a7.96 7.96 0 00-.68-1.64l1.13-1.13-1.41-1.41-1.13 1.13A7.96 7.96 0 0017 7.06V5h-2v2.06a7.96 7.96 0 00-1.64.68l-1.13-1.13-1.41 1.41 1.13 1.13c-.3.51-.53 1.06-.68 1.64H9v2h2.06c.15.58.38 1.13.68 1.64l-1.13 1.13 1.41 1.41 1.13-1.13c.51.3 1.06.53 1.64.68V19h2v-2.06c.58-.15 1.13-.38 1.64-.68l1.13 1.13 1.41-1.41-1.13-1.13c.3-.51.53-1.06.68-1.64H23v-2h-2.06z'],
            ['label' => 'Auditoría', 'route' => 'super-admin.audit.index', 'active' => 'super-admin.audit.*', 'color' => 'text-fuchsia-600', 'icon' => 'M9 12l2 2 4-4m4-5H5v16h14V5zm-3-2v4H8V3h8z'],
        ];

        $companyItems = [
            ['label' => 'Dashboard', 'route' => 'empresa.dashboard', 'active' => 'empresa.dashboard', 'color' => 'text-blue-600', 'icon' => 'M3 13h8V3H3v10zm10 8h8V3h-8v18zM3 21h8v-6H3v6z'],
            ['label' => 'Mi Empresa', 'route' => 'empresa.company.edit', 'active' => 'empresa.company.*', 'color' => 'text-emerald-600', 'icon' => 'M4 21V5a2 2 0 012-2h8a2 2 0 012 2v16M9 7h2m-2 4h2m-2 4h2m7 6v-8h2a2 2 0 012 2v6'],
            ['label' => 'Clientes', 'route' => 'empresa.clients.index', 'active' => 'empresa.clients.*', 'color' => 'text-violet-600', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2a4 4 0 10-8 0 4 4 0 008 0zm6-3a4 4 0 11-8 0 4 4 0 018 0z'],
            ['label' => 'Usuarios', 'route' => 'empresa.usuarios.index', 'active' => 'empresa.usuarios.*', 'color' => 'text-violet-600', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-2a4 4 0 10-8 0 4 4 0 008 0zm6-3a4 4 0 11-8 0 4 4 0 018 0z'],
            ['label' => 'Sucursales', 'route' => 'empresa.branches.index', 'active' => 'empresa.branches.*', 'color' => 'text-rose-600', 'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 9h1m-1 4h1m-1 4h1m4-8h1m-1 4h1m-1 4h1'],
            ['label' => 'Correlativos', 'route' => 'empresa.correlatives.index', 'active' => 'empresa.correlatives.*', 'color' => 'text-orange-600', 'icon' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14'],
            ['label' => 'Comprobantes', 'active' => 'empresa.facturas.*|empresa.boletas.*|empresa.notas-credito.*|empresa.notas-debito.*|empresa.guias.*', 'color' => 'text-sky-600', 'icon' => 'M7 3h7l5 5v13H7V3zm7 0v5h5M9 13h8M9 17h8', 'children' => [
                ['label' => 'Facturas', 'route' => 'empresa.facturas.index', 'active' => 'empresa.facturas.*'],
                ['label' => 'Boletas', 'route' => 'empresa.boletas.index', 'active' => 'empresa.boletas.*'],
                ['label' => 'Notas Crédito', 'route' => 'empresa.notas-credito.index', 'active' => 'empresa.notas-credito.*'],
                ['label' => 'Notas Débito', 'route' => 'empresa.notas-debito.index', 'active' => 'empresa.notas-debito.*'],
                ['label' => 'Guías Remisión', 'route' => 'empresa.guias.index', 'active' => 'empresa.guias.*'],
            ]],
            ['label' => 'Config. SUNAT', 'route' => 'empresa.sunat-config.index', 'active' => 'empresa.sunat-config.*', 'color' => 'text-teal-600', 'icon' => 'M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4zm0 6v5l3 2'],
            ['label' => 'API Keys', 'route' => 'empresa.api-keys.index', 'active' => 'empresa.api-keys.*', 'color' => 'text-indigo-600', 'icon' => 'M15 7a4 4 0 10-3.46 6H9v2H7v2H5v2H3v-4.54L9.54 8A4 4 0 0015 7zm0 0h.01'],
            ['label' => 'Documentos', 'route' => 'empresa.documents.index', 'active' => 'empresa.documents.*', 'color' => 'text-slate-600', 'icon' => 'M7 3h7l5 5v13H7V3zm7 0v5h5M9 13h8M9 17h8'],
            ['label' => 'Reporte contador', 'route' => 'empresa.reportes.contador', 'active' => 'empresa.reportes.contador*', 'color' => 'text-cyan-600', 'icon' => 'M4 19V9m5 10V5m5 14v-7m5 7V3'],
            ['label' => 'Anular comprobantes', 'route' => 'empresa.anulaciones.index', 'active' => 'empresa.anulaciones.*', 'color' => 'text-red-600', 'icon' => 'M10 11v6M14 11v6M5 7h14l-1 13H6L5 7zm3 0V4h8v3'],
            ['label' => 'Consulta CPE', 'route' => 'empresa.consulta-cpe.index', 'active' => 'empresa.consulta-cpe.*', 'color' => 'text-lime-600', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
            ['label' => 'Soporte', 'route' => 'empresa.support.index', 'active' => 'empresa.support.*', 'color' => 'text-rose-600', 'icon' => 'M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4v8z'],
            ['label' => 'Mi Plan', 'route' => 'empresa.plan.index', 'active' => 'empresa.plan.*', 'color' => 'text-green-600', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            // 'Perfil' ya no va aqui: vive en el menu del avatar, arriba a la
            // derecha, igual que en el panel de Super Admin. Tenerlo en los dos
            // sitios abria la misma pantalla de dos formas distintas (pagina
            // desde el lateral, modal desde el avatar).
        ];

        $menuItems = $isSuperAdmin ? $superAdminItems : $companyItems;

        // El contador entra al panel de Super Admin pero solo a la parte de
        // consulta. Se le deja el menu con lo que sus rutas le permiten; el
        // resto ya lo bloquea el middleware, esto es para no enseñarle puertas
        // cerradas.
        // Un empleado de empresa emite y consulta. Lo que compromete a la
        // empresa entera (datos fiscales, SUNAT, API keys, plan, equipo) no se
        // le muestra; el middleware ya se lo bloquea, esto evita enseñarle
        // puertas cerradas.
        if ($user->hasRole('company_user')) {
            $soloDelDueno = [
                'empresa.company.edit',
                'empresa.sunat-config.index',
                'empresa.api-keys.index',
                'empresa.plan.index',
                'empresa.usuarios.index',
            ];

            $menuItems = array_values(array_filter(
                $menuItems,
                fn ($item) => ! in_array($item['route'] ?? null, $soloDelDueno, true)
            ));
        }

        if ($user->hasRole('contador')) {
            $permitidos = [
                'super-admin.dashboard',
                'super-admin.companies.index',
                'super-admin.users.index',
                'super-admin.documents',
                'super-admin.support',
                'super-admin.statistics',
                'super-admin.certificates',
            ];

            $menuItems = array_values(array_filter(
                $menuItems,
                fn ($item) => in_array($item['route'] ?? null, $permitidos, true)
            ));
        }
    @endphp

    @include('partials.impersonation-banner')
    @include('partials.aviso-vencimiento')

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

            <nav class="sin-barra min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-4">
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

                {{-- Campanita a la izquierda del avatar. Antes era un modulo del
                     menu lateral, pero ocupaba un sitio para algo que casi
                     siempre esta vacio y no es un lugar donde se trabaja. --}}
                <div class="flex items-center gap-1">
                @php
                    $usuarioActual = auth()->user();
                    $sinLeer = $usuarioActual && ! $usuarioActual->accedeAlPanelAdmin()
                        ? $usuarioActual->unreadNotifications()->count()
                        : 0;

                    // Para quien atiende el panel de administracion, lo que hay
                    // que vigilar son los tickets sin resolver. Se leen en vivo
                    // en vez de guardar una notificacion: asi el contador nunca
                    // se queda desfasado respecto a la bandeja.
                    $ticketsPendientes = collect();
                    if ($usuarioActual && $usuarioActual->accedeAlPanelAdmin()) {
                        $ticketsPendientes = \App\Models\Ticket::with('company:id,razon_social')
                            ->whereIn('status', ['open', 'in_progress'])
                            ->latest()
                            ->limit(5)
                            ->get();
                        $totalPendientes = \App\Models\Ticket::whereIn('status', ['open', 'in_progress'])->count();
                    }
                    $ultimas = $usuarioActual && ! $usuarioActual->accedeAlPanelAdmin()
                        ? $usuarioActual->notifications()->latest()->limit(5)->get()
                        : collect();
                @endphp

                {{-- Guia de emision: se lee la primera semana y luego casi nunca,
                     asi que vive aqui y no ocupando un sitio del menu de trabajo. --}}
                @unless($isSuperAdmin)
                    <a href="{{ route('empresa.ayuda-emision') }}" title="¿Cómo emitir? Guía y credenciales"
                       class="flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                        <span class="sr-only">¿Cómo emitir?</span>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 9a2.5 2.5 0 114 2c-.8.6-1.5 1.1-1.5 2"/>
                            <path stroke-linecap="round" d="M12 17h.01"/>
                        </svg>
                    </a>
                @endunless

                {{-- Tickets pendientes: lo unico que le llega al administrador y
                     necesita respuesta. Enlaza directo al detalle. --}}
                @if($isSuperAdmin)
                    <div x-data="{ avisos: false }" @click.outside="avisos = false" class="relative">
                        <button type="button" @click="avisos = ! avisos" title="Tickets pendientes"
                                class="relative flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                            <span class="sr-only">Tickets pendientes</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if(($totalPendientes ?? 0) > 0)
                                <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-semibold text-white">
                                    {{ $totalPendientes > 9 ? '9+' : $totalPendientes }}
                                </span>
                            @endif
                        </button>

                        <div x-show="avisos" x-cloak x-transition.opacity
                             class="absolute right-0 mt-2 w-80 rounded-lg border border-gray-200 bg-white py-1.5 shadow-lg">
                            <div class="border-b border-gray-100 px-4 py-2.5">
                                <p class="text-sm font-medium text-gray-800">
                                    Tickets pendientes
                                    @if(($totalPendientes ?? 0) > 0)
                                        <span class="text-gray-400">({{ $totalPendientes }})</span>
                                    @endif
                                </p>
                            </div>

                            @forelse($ticketsPendientes as $ticket)
                                @php
                                    $colorPrioridad = match($ticket->priority) {
                                        'high' => 'bg-red-50 text-red-700',
                                        'low' => 'bg-gray-100 text-gray-600',
                                        default => 'bg-amber-50 text-amber-700',
                                    };
                                @endphp
                                <button type="button"
                                        @click="avisos = false; window.openAdminModal('{{ route('super-admin.support.show', $ticket) }}', 'Ticket #{{ $ticket->id }}')"
                                        class="block w-full border-b border-gray-50 px-4 py-2.5 text-left text-sm hover:bg-gray-50">
                                    <span class="flex items-start justify-between gap-2">
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-medium text-gray-800">{{ $ticket->subject }}</span>
                                            <span class="block truncate text-xs text-gray-500">{{ $ticket->company->razon_social ?? '' }}</span>
                                            <span class="block text-xs text-gray-400">{{ $ticket->created_at->diffForHumans() }}</span>
                                        </span>
                                        <span class="shrink-0 rounded px-1.5 py-0.5 text-xs font-medium {{ $colorPrioridad }}">
                                            {{ ['high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja'][$ticket->priority] ?? '' }}
                                        </span>
                                    </span>
                                </button>
                            @empty
                                <p class="px-4 py-6 text-center text-sm text-gray-500">Nada pendiente.</p>
                            @endforelse

                            <a href="{{ route('super-admin.support') }}"
                               class="block px-4 py-2.5 text-center text-sm text-blue-600 hover:bg-gray-50">Ir a Soporte</a>
                        </div>
                    </div>
                @endif

                @unless($isSuperAdmin)
                    <div x-data="{ avisos: false }" @click.outside="avisos = false" class="relative">
                        <button type="button" @click="avisos = !avisos"
                                title="Notificaciones"
                                class="relative flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                            <span class="sr-only">Notificaciones</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($sinLeer > 0)
                                <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
                                    {{ $sinLeer > 9 ? '9+' : $sinLeer }}
                                </span>
                            @endif
                        </button>

                        <div x-show="avisos" x-cloak x-transition.opacity
                             class="absolute right-0 mt-2 w-80 rounded-lg border border-gray-200 bg-white py-1.5 shadow-lg">
                            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5">
                                <p class="text-sm font-medium text-gray-800">Notificaciones</p>
                                @if($sinLeer > 0)
                                    <form method="POST" action="{{ route('empresa.notifications.read-all') }}">
                                        @csrf
                                        <button class="text-xs text-blue-600 hover:underline">Marcar todas</button>
                                    </form>
                                @endif
                            </div>

                            @forelse($ultimas as $aviso)
                                @php $datos = $aviso->data; @endphp
                                <a href="{{ route('empresa.notifications.read', $aviso->id) }}"
                                   class="flex gap-3 border-b border-gray-50 px-4 py-2.5 text-sm hover:bg-gray-50 {{ $aviso->read_at ? '' : 'bg-blue-50/40' }}">
                                    <span class="shrink-0">{{ $datos['icono'] ?? '🔔' }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-gray-800">{{ $datos['titulo'] ?? 'Aviso' }}</span>
                                        <span class="block text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($datos['mensaje'] ?? '', 70) }}</span>
                                        <span class="block text-[11px] text-gray-400">{{ $aviso->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @empty
                                <p class="px-4 py-6 text-center text-sm text-gray-500">No tienes avisos.</p>
                            @endforelse

                            <a href="{{ route('empresa.notifications.index') }}"
                               class="block px-4 py-2.5 text-center text-sm text-blue-600 hover:bg-gray-50">Ver todas</a>
                        </div>
                    </div>
                @endunless

                {{-- Menu de la cuenta: perfil, contraseña y salir, detras del avatar. --}}
                @php
                    $iniciales = collect(explode(' ', trim((string) $usuarioActual?->name)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
                        ->implode('') ?: 'U';
                    $rutaPerfil = $isSuperAdmin ? 'super-admin.profile.edit' : 'empresa.profile.edit';
                @endphp

                <div x-data="{ cuenta: false }" @click.outside="cuenta = false" class="relative">
                    <button type="button" @click="cuenta = !cuenta"
                            class="flex items-center gap-2 rounded-md py-1.5 pl-1.5 pr-2 text-sm hover:bg-gray-100">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
                            {{ $iniciales }}
                        </span>
                        <span class="hidden max-w-[140px] truncate text-left sm:block">
                            <span class="block text-sm font-medium text-gray-800 truncate">{{ $usuarioActual?->name }}</span>
                            <span class="block text-xs text-gray-500">{{ $isSuperAdmin ? 'Super Admin' : 'Mi cuenta' }}</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="cuenta && 'rotate-180'"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="cuenta" x-cloak x-transition.opacity
                         class="absolute right-0 mt-2 w-60 rounded-lg border border-gray-200 bg-white py-1.5 shadow-lg">
                        <div class="border-b border-gray-100 px-4 py-2.5">
                            <p class="truncate text-sm font-medium text-gray-800">{{ $usuarioActual?->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $usuarioActual?->email }}</p>
                        </div>

                        {{-- Perfil y contraseña se abren en el modal, sin salir de la pantalla. --}}
                        <button type="button"
                                @click="cuenta = false; window.openAdminModal('{{ route($rutaPerfil) }}', 'Mi perfil')"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z"/>
                            </svg>
                            Editar perfil
                        </button>

                        <button type="button"
                                @click="cuenta = false; window.openAdminModal('{{ route($rutaPerfil) }}', 'Cambiar contraseña')"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z"/>
                            </svg>
                            Cambiar contraseña
                        </button>

                        @unless($isSuperAdmin)
                            <a href="{{ route('empresa.profile.security') }}"
                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"/>
                                </svg>
                                Seguridad y accesos
                            </a>
                        @endunless

                        <div class="my-1 border-t border-gray-100"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50">
                                <svg class="h-4 w-4 text-red-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z"/>
                                </svg>
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
                </div>{{-- fin del grupo campanita + avatar --}}
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
            <div class="sin-barra relative max-h-[80vh] overflow-y-auto">
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
