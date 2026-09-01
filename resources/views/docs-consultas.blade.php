{{-- Documentacion publica de la API de RUC y DNI.

     Publica y no un PDF por correo: el dia que cambie algo, cambia aqui y ya
     esta. Una copia pegada en un correo se queda vieja y nadie la corrige.

     SIN PRECIOS a proposito. Explica como se usa, que es lo que necesita quien
     va a integrar; lo que cuesta se habla aparte, con cada cliente.

     Aparte de /docs, que es la de emision: quien compra consultas no factura
     necesariamente con el sistema y no tiene por que leerse lo otro. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API de RUC y DNI — Cisma Fact</title>
    <meta name="description" content="Consulta RUC y DNI del Perú desde tu sistema: razón social, estado, condición y domicilio fiscal. Autenticación, endpoints y ejemplos de código.">
    <link rel="icon" href="{{ config('platform.favicon_url', asset('assets/brand/favicon.png')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-800">

    <header class="sticky top-0 z-10 border-b border-gray-100 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
            <a href="{{ route('landing') }}" class="flex items-center">
                <img src="{{ config('platform.logo_url', asset('assets/brand/cisma-fact.png')) }}" alt="Cisma Fact" class="h-10 w-auto">
            </a>
            <nav class="flex items-center gap-2 text-sm sm:gap-3">
                <a href="{{ route('landing') }}" class="px-3 py-2 font-medium text-gray-600 hover:text-blue-600">Inicio</a>
                <a href="{{ route('docs') }}" class="hidden px-3 py-2 font-medium text-gray-600 hover:text-blue-600 sm:inline-block">API de facturación</a>
                <a href="{{ route('login') }}" class="px-3 py-2 font-medium text-gray-700 hover:text-blue-600">Iniciar sesión</a>
            </nav>
        </div>
    </header>

    <div class="mx-auto grid max-w-5xl grid-cols-1 gap-10 px-6 py-10 lg:grid-cols-[210px_1fr]">

        <aside class="hidden lg:block">
            <nav class="sticky top-24 space-y-1 text-sm">
                <p class="px-2 text-xs font-semibold uppercase text-gray-400">Contenido</p>
                <a href="#empezar" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">1. Empezar</a>
                <a href="#credenciales" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">2. Credenciales</a>
                <a href="#pruebas" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">3. Pruebas</a>
                <a href="#ruc" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">4. Consultar RUC</a>
                <a href="#dni" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">5. Consultar DNI</a>
                <a href="#cuota" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">6. Ver tu cuota</a>
                <a href="#errores" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">7. Errores</a>
                <a href="#limites" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">8. Límites</a>
                <a href="#ejemplos" class="block rounded px-2 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-blue-600">9. Ejemplos</a>
            </nav>
        </aside>

        <main class="min-w-0 space-y-12">

            <div>
                <h1 class="text-3xl font-bold text-gray-900">API de RUC y DNI</h1>
                <p class="mt-2 text-gray-600">
                    Consulta un RUC o un DNI desde tu sistema y rellena los datos del cliente solo con
                    el número. Devuelve razón social, estado, condición y domicilio fiscal para RUC;
                    nombres y apellidos para DNI.
                </p>
            </div>

            {{-- 1 --}}
            <section id="empezar" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">1. Empezar</h2>
                <p class="text-gray-600">Son tres pasos:</p>
                <ol class="ml-5 list-decimal space-y-1.5 text-gray-600">
                    <li>Pides tus credenciales. Te llegan una <strong>API Key</strong> y un <strong>API Secret</strong>.</li>
                    <li>Pruebas con una llave de sandbox, gratis y con un tope de consultas al mes.</li>
                    <li>Cambias las credenciales por las de producción y ya estás consultando de verdad.</li>
                </ol>
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-sm font-medium text-gray-700">Dirección base</p>
                    <pre class="mt-1 overflow-x-auto text-sm text-gray-800"><code>{{ url('/api/consultas') }}</code></pre>
                    <p class="mt-2 text-xs text-gray-500">Todas las peticiones son <code class="rounded bg-gray-200 px-1">GET</code> y responden en JSON.</p>
                </div>
            </section>

            {{-- 2 --}}
            <section id="credenciales" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">2. Credenciales</h2>
                <p class="text-gray-600">Van en las cabeceras de cada petición. Las dos son obligatorias.</p>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>X-Api-Key: tu_api_key
X-Api-Secret: tu_api_secret
Accept: application/json</code></pre>
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-medium">El API Secret se enseña una sola vez.</p>
                    <p class="mt-1">
                        Guárdalo en cuanto lo recibas. No lo pongas en el código de una página web ni en
                        una aplicación de móvil: cualquiera puede leerlo de ahí y gastar tu cuota. Va en
                        tu servidor. Si se te pierde o crees que alguien más lo tiene, pide que te lo cambien.
                    </p>
                </div>
            </section>

            {{-- 3 --}}
            <section id="pruebas" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">3. Pruebas</h2>
                <p class="text-gray-600">
                    Te damos una <strong>llave de sandbox</strong> gratuita para que dejes tu integración
                    terminada antes de contratar nada. Consulta <strong>los mismos datos reales</strong>
                    que producción —la misma SUNAT y el mismo RENIEC—, porque con datos inventados no
                    verías si el servicio te sirve.
                </p>

                {{-- Antes esta seccion publicaba nueve numeros magicos que
                     devolvian casos fijos. Cuando el sandbox paso a consultar
                     de verdad dejaron de existir, y quien seguia el manual
                     fallaba en su primera prueba: se los llevo por delante el
                     mismo cambio que hizo util al sandbox. --}}
                <p class="text-gray-600">
                    Pruébala con <strong>RUC y DNI de verdad</strong>: los tuyos, los de tus clientes,
                    los que quieras comprobar. No hay números especiales que devuelvan casos preparados.
                </p>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                    <p class="font-medium text-gray-900">Lo único distinto de producción es el tope.</p>
                    <p class="mt-1">
                        La llave de sandbox trae <strong>20 consultas de cada tipo al mes</strong>: 20 de RUC
                        y 20 de DNI, que se cuentan por separado. Al agotarse responde
                        <code class="rounded bg-gray-100 px-1">429</code>. Puedes ver lo que te queda en
                        cualquier momento con <code class="rounded bg-gray-100 px-1">GET /api/consultas/cuota</code>,
                        y si necesitas más para terminar, nos lo dices.
                    </p>
                </div>

                <p class="text-gray-600">
                    Cuando la consulta no sale adelante responde
                    <code class="rounded bg-gray-100 px-1 text-sm">422</code> y el porqué viene en
                    <code class="rounded bg-gray-100 px-1 text-sm">message</code>, así que conviene leerlo
                    y no solo mirar el código: es el mismo 422 para un número mal escrito
                    (prueba <code class="rounded bg-gray-100 px-1 text-sm">20000000000</code>, cuyo dígito
                    verificador no cuadra, o un DNI de 7 cifras) que para uno correcto cuyo titular
                    no aparece.
                </p>

                <p class="text-gray-600">
                    Cuando termines, cambias las dos cabeceras por las de producción. No hay que tocar nada más.
                </p>
            </section>

            {{-- 4 --}}
            <section id="ruc" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">4. Consultar RUC</h2>
                <p class="text-sm text-gray-500">
                    El número y los datos de este ejemplo son inventados, solo para enseñar la
                    forma de la respuesta. Al probar verás los datos reales del RUC que consultes.
                </p>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>GET {{ url('/api/consultas/ruc') }}/20000000001</code></pre>
                <p class="text-sm font-medium text-gray-700">Respuesta</p>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>{
  "success": true,
  "data": {
    "valido": true,
    "numero": "20000000001",
    "tipo": "ruc",
    "nombre": "EMPRESA DE EJEMPLO S.A.C.",
    "estado": "ACTIVO",
    "condicion": "HABIDO",
    "direccion": "AV. DE PRUEBA NRO. 100",
    "ubigeo": "150101",
    "departamento": "LIMA",
    "provincia": "LIMA",
    "distrito": "LIMA",
    "fuente": "consultado antes"
  },
  "message": null
}</code></pre>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr><th class="px-4 py-2.5">Campo</th><th class="px-4 py-2.5">Qué es</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-600">
                            <tr><td class="px-4 py-2 font-mono text-xs">nombre</td><td class="px-4 py-2">Razón social o nombre del contribuyente.</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs">estado</td><td class="px-4 py-2"><strong>ACTIVO</strong>, BAJA DE OFICIO, SUSPENSION TEMPORAL… Si no está activo, no puede emitir comprobantes.</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs">condicion</td><td class="px-4 py-2"><strong>HABIDO</strong> o NO HABIDO. No habido significa que SUNAT no lo encontró en su domicilio.</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs">direccion</td><td class="px-4 py-2">Domicilio fiscal.</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs">ubigeo</td><td class="px-4 py-2">Código de 6 dígitos de departamento, provincia y distrito.</td></tr>
                            <tr><td class="px-4 py-2 font-mono text-xs">fuente</td><td class="px-4 py-2">De dónde salió el dato: <code class="rounded bg-gray-100 px-1 text-xs">proveedor</code>, <code class="rounded bg-gray-100 px-1 text-xs">padron</code>, <code class="rounded bg-gray-100 px-1 text-xs">consultado antes</code> o <code class="rounded bg-gray-100 px-1 text-xs">ninguna</code>. Con <code class="rounded bg-gray-100 px-1 text-xs">ninguna</code> vienen solo el número y el tipo, sin ficha.</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- Lo que mas rompe integraciones: dar por hecho que un 200
                     siempre trae la ficha. Si el proveedor no responde, el
                     numero se da por bueno igualmente y la ficha no viene. --}}
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-sm font-medium text-amber-900">Un <code class="rounded bg-amber-100 px-1 text-xs">200</code> no siempre trae la ficha</p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-800">
                        Si el número es correcto pero no se pudo consultar en ese momento, la respuesta
                        sigue siendo <code class="rounded bg-amber-100 px-1 text-xs">200</code> con
                        <code class="rounded bg-amber-100 px-1 text-xs">success: true</code>, porque el número
                        vale y no queremos bloquearte por un proveedor caído. Pero llega
                        <code class="rounded bg-amber-100 px-1 text-xs">"fuente": "ninguna"</code>, un
                        <code class="rounded bg-amber-100 px-1 text-xs">message</code> explicándolo, y
                        <strong>sin</strong> <code class="rounded bg-amber-100 px-1 text-xs">nombre</code> ni
                        <code class="rounded bg-amber-100 px-1 text-xs">direccion</code>.
                        Comprueba que el campo existe antes de usarlo, en vez de fiarte solo de
                        <code class="rounded bg-amber-100 px-1 text-xs">success</code>.
                    </p>
                </div>
            </section>

            {{-- 5 --}}
            <section id="dni" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">5. Consultar DNI</h2>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>GET {{ url('/api/consultas/dni') }}/12345678</code></pre>
                <p class="text-sm font-medium text-gray-700">Respuesta</p>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>{
  "success": true,
  "data": {
    "valido": true,
    "numero": "12345678",
    "tipo": "dni",
    "nombre": "JUAN DE PRUEBA EJEMPLO",
    "nombres": "JUAN",
    "apellido_paterno": "DE PRUEBA",
    "apellido_materno": "EJEMPLO",
    "fuente": "consultado antes"
  },
  "message": null
}</code></pre>
                <p class="text-sm text-gray-600">
                    Tienes el nombre completo ya armado en <code class="rounded bg-gray-100 px-1">nombre</code> y también
                    por partes, por si tu sistema guarda los apellidos en campos separados.
                </p>
            </section>

            {{-- 6 --}}
            <section id="cuota" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">6. Ver tu cuota</h2>
                <p class="text-gray-600">Cuánto llevas gastado y cuánto te queda. No gasta cuota.</p>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>GET {{ url('/api/consultas/cuota') }}</code></pre>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>{
  "llave": "Producción",
  "entorno": "produccion",
  "plan": "Pro",
  "expira_en": null,
  "renueva": "{{ now()->startOfMonth()->addMonth()->toDateString() }}",
  "servicios": [
    { "servicio": "ruc", "nombre": "Consulta RUC", "disponible": true,
      "limite_mensual": 10000, "usadas": 1, "restantes": 9999 },
    { "servicio": "dni", "nombre": "Consulta DNI", "disponible": true,
      "limite_mensual": 2000, "usadas": 1, "restantes": 1999 }
  ]
}</code></pre>
                <p class="text-sm text-gray-600">
                    Los límites del ejemplo son ilustrativos: los tuyos son los de tu plan, y salen aquí.
                </p>
            </section>

            {{-- 7 --}}
            <section id="errores" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">7. Errores</h2>
                <p class="text-gray-600">
                    Cuando algo va mal, <code class="rounded bg-gray-100 px-1">success</code> es
                    <code class="rounded bg-gray-100 px-1">false</code> y
                    <code class="rounded bg-gray-100 px-1">message</code> explica qué pasó, en castellano.
                    Puedes enseñárselo a tu usuario tal cual.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr><th class="px-4 py-2.5">Código</th><th class="px-4 py-2.5">Qué pasó</th><th class="px-4 py-2.5">Qué hacer</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-600">
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">401</td>
                                <td class="px-4 py-2">Faltan las cabeceras o las credenciales no valen.</td>
                                <td class="px-4 py-2">Revisa que mandas las dos y que no llevan espacios de más.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">403</td>
                                <td class="px-4 py-2">La llave está bloqueada, venció, o tu plan no incluye ese servicio.</td>
                                <td class="px-4 py-2">El mensaje dice cuál de los tres. Escríbenos.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">422</td>
                                <td class="px-4 py-2">El número no es válido: no tiene los dígitos que toca, o el dígito verificador no cuadra.</td>
                                <td class="px-4 py-2">Nada: no gasta cuota. Enséñale el mensaje a tu usuario.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">429</td>
                                <td class="px-4 py-2">Se acabó tu cuota del mes, o vas demasiado rápido.</td>
                                <td class="px-4 py-2">Espera al día 1, sube de plan, o baja el ritmo.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">503</td>
                                <td class="px-4 py-2">Ese servicio está fuera de servicio un rato.</td>
                                <td class="px-4 py-2">Reintenta en unos minutos. No gasta cuota.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-sm font-medium text-gray-700">Ejemplo</p>
                <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>{
  "success": false,
  "data": null,
  "message": "El RUC no es válido: el dígito verificador no cuadra."
}</code></pre>
            </section>

            {{-- 8 --}}
            <section id="limites" class="scroll-mt-24 space-y-3">
                <h2 class="text-xl font-semibold text-gray-900">8. Límites</h2>
                <ul class="ml-5 list-disc space-y-1.5 text-gray-600">
                    <li><strong>30 peticiones por minuto.</strong> Es aparte de tu cuota mensual: evita que te la gastes de golpe por un fallo en tu código.</li>
                    <li><strong>Cuota mensual</strong> según tu plan, contada por separado para RUC y para DNI.</li>
                    <li><strong>Lo que falla no gasta cuota.</strong> Un número mal escrito o un servicio caído no te cuestan nada.</li>
                    <li><strong>La cuota se reinicia el día 1</strong> de cada mes. No se acumula lo que no gastaste.</li>
                </ul>
            </section>

            {{-- 9 --}}
            <section id="ejemplos" class="scroll-mt-24 space-y-5">
                <h2 class="text-xl font-semibold text-gray-900">9. Ejemplos</h2>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">curl</h3>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>curl -H "X-Api-Key: tu_api_key" \
     -H "X-Api-Secret: tu_api_secret" \
     -H "Accept: application/json" \
     {{ url('/api/consultas/ruc') }}/20000000001</code></pre>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">PHP</h3>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>&lt;?php

function consultarRuc(string $ruc): ?array
{
    $ch = curl_init('{{ url('/api/consultas/ruc') }}/' . $ruc);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER =&gt; true,
        CURLOPT_HTTPHEADER =&gt; [
            'X-Api-Key: ' . getenv('CISMA_API_KEY'),
            'X-Api-Secret: ' . getenv('CISMA_API_SECRET'),
            'Accept: application/json',
        ],
    ]);

    $cuerpo = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // Un RUC que no existe no es un fallo del programa: devuelve null y que
    // el formulario avise, en vez de reventar.
    return ($cuerpo['success'] ?? false) ? $cuerpo['data'] : null;
}

$empresa = consultarRuc('20000000001');
echo $empresa['nombre'] ?? 'No encontrado';</code></pre>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">Python</h3>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>import os, requests

CABECERAS = {
    "X-Api-Key": os.environ["CISMA_API_KEY"],
    "X-Api-Secret": os.environ["CISMA_API_SECRET"],
    "Accept": "application/json",
}

def consultar_ruc(ruc):
    r = requests.get(f"{{ url('/api/consultas/ruc') }}/{ruc}",
                     headers=CABECERAS, timeout=15)
    cuerpo = r.json()
    return cuerpo["data"] if cuerpo.get("success") else None

empresa = consultar_ruc("20000000001")
print(empresa["nombre"] if empresa else "No encontrado")</code></pre>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold uppercase text-gray-500">JavaScript / Node.js</h3>
                    <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code>// Desde el servidor, nunca desde el navegador: ahi el secreto queda a la vista.
const cabeceras = {
  'X-Api-Key': process.env.CISMA_API_KEY,
  'X-Api-Secret': process.env.CISMA_API_SECRET,
  'Accept': 'application/json',
};

async function consultarRuc(ruc) {
  const r = await fetch(`{{ url('/api/consultas/ruc') }}/${ruc}`, { headers: cabeceras });
  const cuerpo = await r.json();
  return cuerpo.success ? cuerpo.data : null;
}

const empresa = await consultarRuc('20000000001');
console.log(empresa?.nombre ?? 'No encontrado');</code></pre>
                </div>
            </section>

            {{-- Llevaba al contacto de la landing, que es el de facturacion: quien
                 viene a por consultas acababa preguntando por lo que no era. Va
                 derecho a WhatsApp y con el asunto puesto, asi se sabe de que
                 pagina llega. --}}
            <section class="rounded-lg border border-blue-200 bg-blue-50 p-5">
                <p class="font-medium text-blue-900">¿Lo quieres probar?</p>
                <p class="mt-1 text-sm text-blue-800">
                    Escríbenos y te damos credenciales de prueba para que dejes tu integración lista
                    antes de contratar nada.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="https://wa.me/51921676408?text={{ rawurlencode('Hola, me interesa la API de RUC y DNI. Quisiera credenciales de prueba.') }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.174.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        Escribir por WhatsApp
                    </a>
                    <a href="mailto:sistemasdesk04@gmail.com?subject={{ rawurlencode('API de RUC y DNI - credenciales de prueba') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Por correo
                    </a>
                </div>
            </section>

        </main>
    </div>

    <footer class="border-t border-gray-100 py-8">
        <div class="mx-auto max-w-5xl px-6 text-sm text-gray-500">
            <p>¿Dudas con la integración? Escríbenos y te echamos una mano.</p>
        </div>
    </footer>

</body>
</html>
