/* ===========================================================================
   Nucleo: configuracion guardada, cliente HTTP y registro de llamadas.
   Sin dependencias ni bundler: todo cuelga de un unico objeto global App.
   ======================================================================== */

var App = window.App || (window.App = {});

/* ---------------------------------------------------------- Configuracion */

(function () {
  var CLAVE = 'cismafact_sandbox';

  var PREDETERMINADO = {
    base_url: 'https://cismafact.alwaysdata.net/api',
    api_key: '',
    api_secret: ''
  };

  App.config = {
    leer: function () {
      try {
        var guardado = JSON.parse(localStorage.getItem(CLAVE) || '{}');
        return Object.assign({}, PREDETERMINADO, guardado);
      } catch (e) {
        // Navegador con el almacenamiento bloqueado: se sigue funcionando,
        // solo que hay que volver a escribir las credenciales cada vez.
        return Object.assign({}, PREDETERMINADO);
      }
    },

    guardar: function (valores) {
      var actual = App.config.leer();
      var nuevo = Object.assign(actual, valores);
      if (nuevo.base_url) nuevo.base_url = nuevo.base_url.replace(/\/+$/, '');
      try { localStorage.setItem(CLAVE, JSON.stringify(nuevo)); } catch (e) {}
      return nuevo;
    },

    borrar: function () {
      try { localStorage.removeItem(CLAVE); } catch (e) {}
    },

    completa: function () {
      var c = App.config.leer();
      return !!(c.base_url && c.api_key && c.api_secret);
    }
  };
})();

/* ------------------------------------------------- Registro de llamadas */

(function () {
  var llamadas = [];
  var oyentes = [];
  var TOPE = 60;

  App.log = {
    anotar: function (registro) {
      registro.id = 'c' + Date.now() + Math.random().toString(36).slice(2, 6);
      registro.hora = new Date();
      llamadas.unshift(registro);
      if (llamadas.length > TOPE) llamadas.length = TOPE;
      oyentes.forEach(function (f) { f(llamadas); });
      return registro;
    },
    todas: function () { return llamadas; },
    buscar: function (id) {
      for (var i = 0; i < llamadas.length; i++) if (llamadas[i].id === id) return llamadas[i];
      return null;
    },
    limpiar: function () {
      llamadas = [];
      oyentes.forEach(function (f) { f(llamadas); });
    },
    alCambiar: function (f) { oyentes.push(f); }
  };
})();

/* ------------------------------------------------------------ Cliente HTTP */

(function () {
  /**
   * Toda peticion pasa por aqui para que quede anotada en la consola: el
   * proposito de esta herramienta es justamente ver que se envia y que vuelve.
   */
  async function pedir(metodo, ruta, cuerpo, opciones) {
    opciones = opciones || {};
    var cfg = App.config.leer();

    if (!cfg.api_key || !cfg.api_secret) {
      throw new App.ErrorApi('Falta configurar la API Key y el Secret en Conexión.', 0);
    }

    var url = cfg.base_url.replace(/\/+$/, '') + ruta;

    var cabeceras = {
      'Accept': 'application/json',
      'X-Api-Key': cfg.api_key,
      'X-Api-Secret': cfg.api_secret
    };

    var init = { method: metodo, headers: cabeceras };

    if (cuerpo !== undefined) {
      cabeceras['Content-Type'] = 'application/json';
      init.body = JSON.stringify(cuerpo);
    }

    var arranque = performance.now();
    var respuesta, texto;

    try {
      respuesta = await fetch(url, init);
    } catch (e) {
      // fetch solo falla asi cuando no se llego al servidor: sin red, dominio
      // mal escrito o el navegador bloqueando el origen.
      App.log.anotar({
        metodo: metodo, ruta: ruta, estado: 0, ms: Math.round(performance.now() - arranque),
        peticion: cuerpo, respuesta: { error: String(e) }, ok: false
      });
      throw new App.ErrorApi(
        'No se pudo contactar con ' + url + '. Revisa la URL base y tu conexión.', 0
      );
    }

    var ms = Math.round(performance.now() - arranque);
    var tipo = respuesta.headers.get('content-type') || '';

    // Descargas (PDF, XML, CDR) vuelven como binario, no como JSON.
    if (opciones.binario || (!tipo.includes('json') && respuesta.ok)) {
      var blob = await respuesta.blob();
      App.log.anotar({
        metodo: metodo, ruta: ruta, estado: respuesta.status, ms: ms,
        peticion: cuerpo, respuesta: { archivo: tipo, bytes: blob.size }, ok: respuesta.ok
      });
      if (!respuesta.ok) throw new App.ErrorApi('Error ' + respuesta.status, respuesta.status);
      return blob;
    }

    texto = await respuesta.text();
    var datos;
    try { datos = texto ? JSON.parse(texto) : {}; }
    catch (e) { datos = { crudo: texto.slice(0, 1200) }; }

    var registro = App.log.anotar({
      metodo: metodo, ruta: ruta, estado: respuesta.status, ms: ms,
      peticion: cuerpo, respuesta: datos, ok: respuesta.ok && datos.success !== false
    });

    if (!respuesta.ok || datos.success === false) {
      var err = new App.ErrorApi(
        datos.message || datos.mensaje || ('Error ' + respuesta.status),
        respuesta.status
      );
      err.errores = datos.errors || datos.errores || null;
      err.registro = registro;
      throw err;
    }

    return datos;
  }

  App.ErrorApi = function (mensaje, estado) {
    this.name = 'ErrorApi';
    this.message = mensaje;
    this.estado = estado;
    this.errores = null;
  };
  App.ErrorApi.prototype = Object.create(Error.prototype);

  function query(obj) {
    var partes = [];
    Object.keys(obj || {}).forEach(function (k) {
      if (obj[k] === undefined || obj[k] === null || obj[k] === '') return;
      partes.push(encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]));
    });
    return partes.length ? '?' + partes.join('&') : '';
  }

  App.api = {
    query: query,
    pedir: pedir,

    empresa: function () { return pedir('GET', '/empresa'); },

    sucursales: function () { return pedir('GET', '/sucursales'); },
    series: function (tipo) { return pedir('GET', '/series' + query({ tipo: tipo })); },
    clientes: function (buscar) { return pedir('GET', '/clientes' + query({ buscar: buscar, limite: 30 })); },
    buscarDocumento: function (tipo, numero) {
      return pedir('GET', '/buscar-documento' + query({ tipo: tipo, numero: numero }));
    },

    indicadores: function () { return pedir('GET', '/panel/indicadores'); },
    recientes: function (limite) { return pedir('GET', '/panel/documentos-recientes' + query({ limite: limite })); },
    ventasMensuales: function () { return pedir('GET', '/panel/ventas-mensuales'); },
    estadoSunat: function () { return pedir('GET', '/panel/estado-sunat'); },
    porMoneda: function () { return pedir('GET', '/panel/por-moneda'); },

    emitir: function (recurso, datos) { return pedir('POST', '/' + recurso, datos); },
    listar: function (recurso, params) { return pedir('GET', '/' + recurso + query(params)); },
    ver: function (recurso, id) { return pedir('GET', '/' + recurso + '/' + id); },

    /**
     * @param {string} formato  pdf | xml | cdr
     * @param {string} [papel]  solo para pdf: A4, A5, 80mm, 58mm
     */
    descargar: function (recurso, id, formato, papel) {
      var ruta = '/' + recurso + '/' + id + '/download-' + formato;
      if (formato === 'pdf') ruta += query({ format: papel || 'A4' });
      return pedir('GET', ruta, undefined, { binario: true });
    }
  };
})();
