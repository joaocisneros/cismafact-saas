/* ===========================================================================
   Arranque: navegacion por hash, consola de llamadas y cargadores comunes.
   ======================================================================== */

var App = window.App || (window.App = {});

(function () {

  var RUTAS = [
    { id: 'conexion',     icono: 'enchufe' },
    { id: 'panel',        icono: 'panel' },
    { id: 'emitir',       icono: 'emitir' },
    { id: 'comprobantes', icono: 'lista' },
    { id: 'resumen',      icono: 'resumen' }
  ];

  /* ---------------------------------------------------------- Navegacion */

  App.ir = function (id) {
    if (location.hash !== '#' + id) { location.hash = id; return; }
    mostrar(id);
  };

  function actual() {
    var id = (location.hash || '#panel').slice(1);
    return App.vistas[id] ? id : 'panel';
  }

  function mostrar(id) {
    var vista = App.vistas[id];
    if (!vista) return;

    document.querySelectorAll('.nav a').forEach(function (a) {
      a.classList.toggle('on', a.dataset.r === id);
    });

    document.getElementById('titulo').textContent = vista.titulo;
    document.getElementById('subtitulo').textContent = vista.sub || '';

    var host = document.getElementById('vista');
    host.innerHTML = '';
    host.scrollTop = 0;

    // Sin credenciales no tiene sentido pintar nada mas que la conexion.
    if (id !== 'conexion' && !App.config.completa()) {
      host.innerHTML = '<div class="view-inner"><div class="card"><div class="empty">'
        + 'Primero configura tus credenciales en <a href="#conexion">Conexión</a>.'
        + '</div></div></div>';
      return;
    }

    vista.render(host);
  }

  window.addEventListener('hashchange', function () { mostrar(actual()); });

  /* ------------------------------------------------ Estado de la conexion */

  App.estadoConexion = function (empresa) {
    var punto = document.getElementById('cn-punto');
    var texto = document.getElementById('cn-texto');
    var ruc = document.getElementById('cn-ruc');

    if (empresa === null) {
      punto.className = 'dot';
      texto.textContent = 'Sin configurar';
      ruc.textContent = '';
    } else if (empresa === false) {
      punto.className = 'dot err';
      texto.textContent = 'Sin conexión';
      ruc.textContent = '';
    } else {
      punto.className = 'dot ok';
      texto.textContent = (empresa.ambiente === 'produccion') ? 'Producción' : 'SUNAT beta';
      ruc.textContent = empresa.razon_social + ' · ' + empresa.ruc;
    }
  };

  /* ------------------------------------------------- Cargadores comunes */

  App.cargarSucursales = async function (idSelect, alTerminar) {
    var sel = document.getElementById(idSelect);
    if (!sel) return;
    try {
      var r = await App.api.sucursales();
      sel.innerHTML = r.data.map(function (s) {
        return '<option value="' + s.id + '">' + App.esc(s.nombre)
          + (s.es_domicilio_fiscal ? ' (domicilio fiscal)' : '') + '</option>';
      }).join('') || '<option value="">Sin sucursales</option>';
    } catch (e) {
      sel.innerHTML = '<option value="">No se pudieron cargar</option>';
    }
    if (alTerminar) alTerminar();
  };

  App.cargarSeries = async function (idSelect, tipo, alTerminar) {
    var sel = document.getElementById(idSelect);
    if (!sel) return;
    try {
      var r = await App.api.series(tipo);
      sel.innerHTML = r.data.length
        ? r.data.map(function (s) {
            return '<option value="' + App.esc(s.serie) + '" data-branch="' + s.branch_id + '">'
              + App.esc(s.serie) + ' — siguiente ' + String(s.siguiente_numero).padStart(6, '0')
              + (s.sucursal ? ' · ' + App.esc(s.sucursal) : '') + '</option>';
          }).join('')
        : '<option value="">La empresa no tiene series de este tipo</option>';
    } catch (e) {
      sel.innerHTML = '<option value="">No se pudieron cargar</option>';
    }
    if (alTerminar) alTerminar();
  };

  App.cargarClientes = async function (idSelect, alTerminar) {
    var sel = document.getElementById(idSelect);
    if (!sel) return;
    try {
      var r = await App.api.clientes('');
      sel.innerHTML = r.data.length
        ? r.data.map(function (c) {
            return '<option value="' + c.id + '">' + App.esc(c.razon_social)
              + ' · ' + App.esc(c.numero_documento) + '</option>';
          }).join('')
        : '<option value="">Aún no hay clientes registrados</option>';
    } catch (e) {
      sel.innerHTML = '<option value="">No se pudieron cargar</option>';
    }
    if (alTerminar) alTerminar();
  };

  /* --------------------------------------------------------- La consola */

  function pintarConsola(llamadas) {
    var lista = document.getElementById('consola-lista');
    var cuenta = document.getElementById('consola-cuenta');
    cuenta.textContent = llamadas.length ? llamadas.length + ' llamada(s)' : 'sin llamadas todavía';

    if (!llamadas.length) {
      lista.innerHTML = '<div class="empty" style="padding:18px">'
        + 'Aquí se anota cada petición HTTP que hace esta herramienta.</div>';
      return;
    }

    lista.innerHTML = llamadas.map(function (c) {
      return '<div class="call" data-id="' + c.id + '">'
        + '<span class="verb ' + c.metodo.toLowerCase() + '">' + c.metodo + '</span>'
        + '<span class="path">' + App.esc(c.ruta) + '</span>'
        + '<span class="st ' + (c.ok ? 'ok' : 'err') + '">' + (c.estado || '—') + '</span>'
        + '<span class="ms">' + c.ms + ' ms</span>'
        + '<span class="at">' + App.hora(c.hora) + '</span>'
        + '</div>';
    }).join('');
  }

  function abrirLlamada(id) {
    var c = App.log.buscar(id);
    if (!c) return;
    App.modal(
      c.metodo + ' ' + c.ruta,
      '<div class="row" style="gap:8px">'
      +   '<span class="pill ' + (c.ok ? 'ok' : 'err') + '">HTTP ' + (c.estado || 'sin respuesta') + '</span>'
      +   '<span class="pill mute">' + c.ms + ' ms</span>'
      +   '<span class="pill mute">' + App.hora(c.hora) + '</span>'
      + '</div>'
      + '<div><div class="card-head" style="padding:0 0 8px"><h2>Petición</h2></div>'
      +   '<pre class="code">' + (c.peticion === undefined ? '<span class="k">(sin cuerpo)</span>' : App.json(c.peticion)) + '</pre></div>'
      + '<div><div class="card-head" style="padding:0 0 8px"><h2>Respuesta</h2></div>'
      +   '<pre class="code">' + App.json(c.respuesta) + '</pre></div>',
      '<button class="btn ghost" onclick="App.cerrarModal()">Cerrar</button>'
    );
  }

  /* ---------------------------------------------------------- Arranque */

  document.addEventListener('DOMContentLoaded', function () {
    // Menu lateral
    var nav = document.getElementById('nav');
    nav.innerHTML = '<div class="nav-label">Herramienta</div>'
      + RUTAS.map(function (r) {
          var v = App.vistas[r.id];
          return '<a href="#' + r.id + '" data-r="' + r.id + '">'
            + App.icono(r.icono) + '<span>' + v.titulo + '</span></a>';
        }).join('');

    // Consola
    var consola = document.getElementById('consola');
    document.getElementById('consola-head').addEventListener('click', function (e) {
      if (e.target.closest('#consola-limpiar')) return;
      consola.classList.toggle('closed');
    });
    document.getElementById('consola-limpiar').addEventListener('click', function (e) {
      e.stopPropagation();
      App.log.limpiar();
    });
    document.getElementById('consola-lista').addEventListener('click', function (e) {
      var f = e.target.closest('.call');
      if (f) abrirLlamada(f.dataset.id);
    });
    App.log.alCambiar(pintarConsola);
    pintarConsola([]);

    // Primera pantalla: sin credenciales, a configurarlas
    if (!location.hash) location.hash = App.config.completa() ? 'panel' : 'conexion';
    App.estadoConexion(App.config.completa() ? undefined : null);
    mostrar(actual());
  });

})();
