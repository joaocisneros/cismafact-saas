/* ===========================================================================
   Pantallas de lectura: conexion, panel, comprobantes y resumen diario.
   ======================================================================== */

var App = window.App || (window.App = {});
App.vistas = App.vistas || {};

/* ========================================================== 1. CONEXION == */

App.vistas.conexion = {
  titulo: 'Conexión',
  sub: 'Credenciales de acceso a la API',

  render: function (host) {
    var c = App.config.leer();

    host.innerHTML = ''
      + '<div class="view-inner">'
      +   '<div class="note">Estas credenciales se guardan solo en este navegador. '
      +     'Pídelas en Cisma Fact → Tokens de prueba: emiten contra SUNAT beta, así que '
      +     'los comprobantes no tienen valor legal.</div>'
      +   '<div class="card">'
      +     '<div class="card-head"><h2>Credenciales</h2>'
      +       '<span class="hint">Cabeceras X-Api-Key y X-Api-Secret</span></div>'
      +     '<div class="card-body">'
      +       '<div class="grid" style="gap:14px">'
      +         campo('URL base de la API', 'base_url', c.base_url, 'https://cismafact.alwaysdata.net/api', 'Sin barra final y sin /v1.')
      +         campo('X-Api-Key', 'api_key', c.api_key, 'cf_…', '')
      +         campo('X-Api-Secret', 'api_secret', c.api_secret, '', 'Se envía en cada petición.')
      +       '</div>'
      +       '<div class="row end" style="margin-top:16px">'
      +         '<button class="btn ghost" id="cx-olvidar">Olvidar credenciales</button>'
      +         '<button class="btn primary" id="cx-probar">' + App.icono('enchufe') + 'Guardar y probar</button>'
      +       '</div>'
      +     '</div>'
      +   '</div>'
      +   '<div id="cx-salida"></div>'
      + '</div>';

    function campo(etiqueta, id, valor, marcador, ayuda) {
      return '<div class="field">'
        + '<label for="cx-' + id + '">' + etiqueta + '</label>'
        + '<input class="mono" id="cx-' + id + '" value="' + App.esc(valor) + '" '
        +   'placeholder="' + App.esc(marcador || '') + '" spellcheck="false" autocomplete="off">'
        + (ayuda ? '<span class="help">' + ayuda + '</span>' : '')
        + '</div>';
    }

    document.getElementById('cx-probar').addEventListener('click', function () {
      App.config.guardar({
        base_url: document.getElementById('cx-base_url').value.trim(),
        api_key: document.getElementById('cx-api_key').value.trim(),
        api_secret: document.getElementById('cx-api_secret').value.trim()
      });
      App.vistas.conexion.probar();
    });

    document.getElementById('cx-olvidar').addEventListener('click', function () {
      App.config.borrar();
      App.estadoConexion(null);
      App.aviso('Credenciales borradas', 'Este navegador ya no las recuerda.');
      App.ir('conexion');
    });

    if (App.config.completa()) App.vistas.conexion.probar();
  },

  probar: async function () {
    var salida = document.getElementById('cx-salida');
    if (!salida) return;
    salida.innerHTML = '<div class="card"><div class="card-body"><span class="hint">Probando…</span></div></div>';

    try {
      var r = await App.api.empresa();
      var e = r.data.company;
      App.estadoConexion(e);

      salida.innerHTML = ''
        + '<div class="card">'
        +   '<div class="card-head"><h2>Conexión correcta</h2>'
        +     '<span class="pill ok">' + App.esc((e.ambiente || 'demo').toUpperCase()) + '</span></div>'
        +   '<div class="card-body">'
        +     '<div class="grid c3">'
        +       dato('Razón social', e.razon_social)
        +       dato('RUC', e.ruc)
        +       dato('Token', r.data.api_key ? r.data.api_key.name : '—')
        +     '</div>'
        +     '<hr class="sep" style="margin:16px 0">'
        +     '<div class="card-head" style="padding:0 0 10px"><h2>Respuesta</h2>'
        +       '<span class="hint"><span class="verb get">GET</span> /empresa</span></div>'
        +     '<pre class="code">' + App.json(r) + '</pre>'
        +   '</div>'
        + '</div>';

      App.aviso('Conectado', e.razon_social, 'ok');
    } catch (err) {
      App.estadoConexion(false);
      salida.innerHTML = ''
        + '<div class="card">'
        +   '<div class="card-head"><h2>No se pudo conectar</h2>'
        +     '<span class="pill err">' + (err.estado || 'SIN RESPUESTA') + '</span></div>'
        +   '<div class="card-body"><p style="margin:0 0 10px">' + App.esc(err.message) + '</p>'
        +     '<div class="note">Comprueba que la URL base termine en <b>/api</b>, que el token esté '
        +       'activo y que no haya caducado.</div></div>'
        + '</div>';
    }

    function dato(k, v) {
      return '<div class="metric"><div class="k">' + App.esc(k) + '</div>'
        + '<div class="v" style="font-size:15px">' + App.esc(v || '—') + '</div></div>';
    }
  }
};

/* ============================================================= 2. PANEL == */

App.vistas.panel = {
  titulo: 'Panel',
  sub: 'Cifras de la empresa',

  render: async function (host) {
    host.innerHTML = '<div class="view-inner"><div class="card"><div class="card-body">'
      + '<span class="hint">Cargando…</span></div></div></div>';

    try {
      var r = await Promise.all([
        App.api.indicadores(),
        App.api.ventasMensuales(),
        App.api.estadoSunat(),
        App.api.recientes(8)
      ]);
      pintar(host, r[0].data, r[1].data, r[2].data, r[3].data);
    } catch (e) {
      App.avisoError(e);
      host.innerHTML = '<div class="view-inner"><div class="card"><div class="empty">'
        + App.esc(e.message) + '</div></div></div>';
    }
  }
};

function pintar(host, ind, meses, estados, recientes) {
  var tope = Math.max.apply(null, meses.map(function (m) { return m.total; }).concat([1]));

  var barras = meses.map(function (m) {
    var alto = Math.max(2, Math.round((m.total / tope) * 100));
    return '<div class="bar' + (m.total > 0 ? ' on' : '') + '" style="height:' + alto + '%" '
      + 'title="' + App.esc(m.etiqueta) + ': ' + App.money(m.total) + '"></div>';
  }).join('');

  var etiquetas = meses.map(function (m) {
    return '<span>' + App.esc(m.etiqueta.slice(0, 3)) + '</span>';
  }).join('');

  var variacion = '';
  if (ind.variacion_mensual !== null && ind.variacion_mensual !== undefined) {
    var sube = ind.variacion_mensual >= 0;
    variacion = '<div class="d ' + (sube ? 'up' : 'down') + '">'
      + (sube ? '▲ ' : '▼ ') + Math.abs(ind.variacion_mensual) + '% vs. mes anterior</div>';
  } else {
    variacion = '<div class="d">Sin mes anterior con el que comparar</div>';
  }

  host.innerHTML = ''
    + '<div class="view-inner">'

    +   '<div class="grid c4">'
    +     metrica('Hoy', App.money(ind.hoy.total), ind.hoy.cantidad + ' comprobante(s)')
    +     metrica('Esta semana', App.money(ind.semana.total), ind.semana.cantidad + ' comprobante(s)')
    +     metrica('Este mes', App.money(ind.mes.total), null, variacion)
    +     metrica('Mes anterior', App.money(ind.mes_anterior.total), ind.mes_anterior.cantidad + ' comprobante(s)')
    +   '</div>'

    +   '<div class="split">'
    +     '<div class="card">'
    +       '<div class="card-head"><h2>Ventas de los últimos 12 meses</h2>'
    +         '<span class="hint"><span class="verb get">GET</span> /panel/ventas-mensuales</span></div>'
    +       '<div class="card-body">'
    +         '<div class="spark">' + barras + '</div>'
    +         '<div class="spark-x">' + etiquetas + '</div>'
    +       '</div>'
    +     '</div>'

    +     '<div class="card">'
    +       '<div class="card-head"><h2>Estado frente a SUNAT</h2>'
    +         '<span class="hint"><span class="verb get">GET</span> /panel/estado-sunat</span></div>'
    +       '<div class="card-body"><div class="grid" style="gap:8px">'
    +         estados.filter(function (e) { return e.cantidad > 0; }).map(function (e) {
                return '<div class="row between">'
                  + App.estadoPill(e.estado)
                  + '<b style="font-family:var(--mono);font-variant-numeric:tabular-nums">' + e.cantidad + '</b></div>';
              }).join('')
    +       '</div></div>'
    +     '</div>'
    +   '</div>'

    +   '<div class="card">'
    +     '<div class="card-head"><h2>Últimos comprobantes</h2>'
    +       '<span class="hint"><span class="verb get">GET</span> /panel/documentos-recientes</span></div>'
    +     '<div class="table-wrap">' + tabla(recientes) + '</div>'
    +   '</div>'

    + '</div>';

  function metrica(k, v, d, extra) {
    return '<div class="metric"><div class="k">' + k + '</div><div class="v">' + v + '</div>'
      + (extra || (d ? '<div class="d">' + d + '</div>' : '')) + '</div>';
  }

  function tabla(filas) {
    if (!filas.length) return '<div class="empty">Todavía no hay comprobantes emitidos.</div>';
    return '<table><thead><tr>'
      + '<th>Tipo</th><th>Número</th><th>Cliente</th><th>Fecha</th>'
      + '<th style="text-align:right">Total</th><th>Estado</th></tr></thead><tbody>'
      + filas.map(function (d) {
          return '<tr>'
            + '<td><span class="pill mute">' + App.esc(d.tipo_nombre) + '</span></td>'
            + '<td class="mono">' + App.esc(d.numero) + '</td>'
            + '<td>' + App.esc(d.cliente) + '</td>'
            + '<td class="mono">' + App.fecha(d.fecha) + '</td>'
            + '<td class="num">' + App.money(d.total, d.moneda) + '</td>'
            + '<td>' + App.estadoPill(d.estado_sunat, d.anulado) + '</td>'
            + '</tr>';
        }).join('')
      + '</tbody></table>';
  }
}

/* ====================================================== 3. COMPROBANTES == */

App.vistas.comprobantes = {
  titulo: 'Comprobantes',
  sub: 'Consultar y descargar',

  recursos: [
    { r: 'facturas',       n: 'Facturas' },
    { r: 'boletas',        n: 'Boletas' },
    { r: 'notas-credito',  n: 'Notas de crédito' },
    { r: 'notas-debito',   n: 'Notas de débito' },
    { r: 'guias-remision', n: 'Guías de remisión' }
  ],

  actual: 'facturas',

  render: function (host) {
    var self = this;

    host.innerHTML = ''
      + '<div class="view-inner">'
      +   '<div class="tabs" id="cp-tabs">'
      +     this.recursos.map(function (x) {
              return '<button data-r="' + x.r + '"' + (x.r === self.actual ? ' class="on"' : '') + '>'
                + x.n + '</button>';
            }).join('')
      +   '</div>'
      +   '<div class="card"><div class="card-head">'
      +     '<h2 id="cp-titulo">Facturas</h2>'
      +     '<div class="row" style="gap:9px">'
      +       '<span class="hint" id="cp-ruta"></span>'
      +       '<select id="cp-papel" class="mono" style="width:auto;padding:5px 26px 5px 8px;font-size:12px" '
      +         'title="Formato del PDF al descargar">'
      +         App.FORMATOS_PDF.map(function (f) {
                  return '<option value="' + f.c + '">' + App.esc(f.n) + '</option>';
                }).join('')
      +       '</select>'
      +     '</div></div>'
      +     '<div id="cp-tabla"><div class="empty">Cargando…</div></div>'
      +   '</div>'
      + '</div>';

    document.getElementById('cp-tabs').addEventListener('click', function (e) {
      var b = e.target.closest('button[data-r]');
      if (!b) return;
      self.actual = b.dataset.r;
      [].forEach.call(this.children, function (c) { c.classList.toggle('on', c === b); });
      self.cargar();
    });

    this.cargar();
  },

  cargar: async function () {
    var caja = document.getElementById('cp-tabla');
    var recurso = this.actual;
    var nombre = this.recursos.filter(function (x) { return x.r === recurso; })[0].n;

    document.getElementById('cp-titulo').textContent = nombre;
    document.getElementById('cp-ruta').innerHTML = '<span class="verb get">GET</span> /' + recurso;
    caja.innerHTML = '<div class="empty">Cargando…</div>';

    try {
      var r = await App.api.listar(recurso, { per_page: 25 });
      var filas = (r.data && r.data.data) ? r.data.data : (Array.isArray(r.data) ? r.data : []);

      if (!filas.length) {
        caja.innerHTML = '<div class="empty">No hay ' + nombre.toLowerCase() + ' todavía.</div>';
        return;
      }

      var esGuia = recurso === 'guias-remision';

      caja.innerHTML = '<div class="table-wrap"><table><thead><tr>'
        + '<th>Número</th><th>Fecha</th>'
        + (esGuia ? '' : '<th style="text-align:right">Total</th>')
        + '<th>Estado</th><th style="text-align:right">Archivos</th>'
        + '</tr></thead><tbody>'
        + filas.map(function (d) {
            return '<tr>'
              + '<td class="mono">' + App.esc(d.numero_completo) + '</td>'
              + '<td class="mono">' + App.fecha(d.fecha_emision) + '</td>'
              + (esGuia ? '' : '<td class="num">' + App.money(d.mto_imp_venta, d.moneda) + '</td>')
              + '<td>' + App.estadoPill(d.estado_sunat, d.anulado_en) + '</td>'
              + '<td style="text-align:right"><div class="row end" style="gap:5px">'
              +   ['pdf', 'xml', 'cdr'].map(function (f) {
                    return '<button class="btn sm ghost" data-bajar="' + f + '" data-id="' + d.id + '" '
                      + 'data-num="' + App.esc(d.numero_completo) + '">' + f.toUpperCase() + '</button>';
                  }).join('')
              + '</div></td>'
              + '</tr>';
          }).join('')
        + '</tbody></table></div>';

      caja.addEventListener('click', async function (e) {
        var b = e.target.closest('button[data-bajar]');
        if (!b) return;
        var formato = b.dataset.bajar;
        b.disabled = true;
        try {
          var papel = document.getElementById('cp-papel').value;
          var blob = await App.api.descargar(recurso, b.dataset.id, formato, papel);
          var ext = formato === 'cdr' ? 'zip' : formato;
          var prefijo = formato === 'cdr' ? 'R-' : '';
          var sufijo = (formato === 'pdf' && papel !== 'A4') ? '_' + papel : '';
          var nombre = prefijo + b.dataset.num + sufijo + '.' + ext;
          App.guardarBlob(blob, nombre);
          App.aviso('Descargado', nombre, 'ok');
        } catch (err) {
          App.avisoError(err);
        } finally {
          b.disabled = false;
        }
      });
    } catch (e) {
      App.avisoError(e);
      caja.innerHTML = '<div class="empty">' + App.esc(e.message) + '</div>';
    }
  }
};

/* ==================================================== 4. RESUMEN DIARIO == */

App.vistas.resumen = {
  titulo: 'Resumen diario',
  sub: 'Comunicar boletas a SUNAT',

  render: function (host) {
    var hoy = new Date().toISOString().slice(0, 10);

    host.innerHTML = ''
      + '<div class="view-inner">'
      +   '<div class="note">Las boletas no se envían una a una: se comunican en un resumen diario. '
      +     'Solo entran las boletas de esa fecha que todavía no se hayan resumido.</div>'
      +   '<div class="split">'
      +     '<div class="card">'
      +       '<div class="card-head"><h2>Generar resumen</h2>'
      +         '<span class="hint"><span class="verb post">POST</span> /resumenes</span></div>'
      +       '<div class="card-body">'
      +         '<div class="grid c2">'
      +           '<div class="field"><label for="rs-fecha">Fecha de las boletas</label>'
      +             '<input type="date" id="rs-fecha" value="' + hoy + '"></div>'
      +           '<div class="field"><label for="rs-suc">Sucursal</label>'
      +             '<select id="rs-suc"><option>Cargando…</option></select></div>'
      +         '</div>'
      +         '<div class="row end" style="margin-top:14px">'
      +           '<button class="btn primary" id="rs-enviar">' + App.icono('enviar') + 'Generar y enviar</button>'
      +         '</div>'
      +       '</div>'
      +     '</div>'
      +     '<div class="card">'
      +       '<div class="card-head"><h2>Resúmenes enviados</h2>'
      +         '<span class="hint"><span class="verb get">GET</span> /resumenes</span></div>'
      +       '<div id="rs-lista"><div class="empty">Cargando…</div></div>'
      +     '</div>'
      +   '</div>'
      + '</div>';

    App.cargarSucursales('rs-suc');
    this.listar();

    document.getElementById('rs-enviar').addEventListener('click', async function () {
      var boton = this;
      boton.disabled = true;
      try {
        var r = await App.api.emitir('resumenes', {
          branch_id: Number(document.getElementById('rs-suc').value),
          fecha_resumen: document.getElementById('rs-fecha').value
        });
        App.aviso('Resumen generado', (r.data && r.data.numero_completo) || '', 'ok');
        App.vistas.resumen.listar();
      } catch (e) {
        App.avisoError(e);
      } finally {
        boton.disabled = false;
      }
    });
  },

  listar: async function () {
    var caja = document.getElementById('rs-lista');
    if (!caja) return;
    try {
      var r = await App.api.listar('resumenes', { per_page: 15 });
      var filas = (r.data && r.data.data) ? r.data.data : (Array.isArray(r.data) ? r.data : []);
      if (!filas.length) { caja.innerHTML = '<div class="empty">Aún no hay resúmenes.</div>'; return; }

      caja.innerHTML = '<div class="table-wrap"><table><thead><tr>'
        + '<th>Resumen</th><th>Fecha</th><th>Estado</th><th style="text-align:right">Ticket</th>'
        + '</tr></thead><tbody>'
        + filas.map(function (d) {
            return '<tr>'
              + '<td class="mono">' + App.esc(d.numero_completo || d.identificador || '—') + '</td>'
              + '<td class="mono">' + App.fecha(d.fecha_resumen || d.fecha_emision) + '</td>'
              + '<td>' + App.estadoPill(d.estado_sunat) + '</td>'
              + '<td class="num">' + App.esc(d.ticket || '—') + '</td>'
              + '</tr>';
          }).join('')
        + '</tbody></table></div>';
    } catch (e) {
      caja.innerHTML = '<div class="empty">' + App.esc(e.message) + '</div>';
    }
  }
};
