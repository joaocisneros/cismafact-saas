#!/bin/sh
#
# Prueba todos los endpoints de lectura de la API contra un servidor.
#
# Solo hace peticiones GET: no crea, no modifica y no borra nada, no emite
# comprobantes ni habla con SUNAT. Lo que comprueba es que cada endpoint
# responde, que la credencial sirve y que sin ella no se entra.
#
# Los endpoints de detalle no se piden con un id inventado: primero se pide su
# listado y se usa un id que existe de verdad. Un id inventado da 404 siempre y
# eso no probaria nada.
#
# Uso:  sh scripts/probar-api.sh  https://tu-servidor  LA_KEY  EL_SECRET
#
# Las consultas de RUC y DNI se quedan fuera a proposito: cada llamada gasta
# cuota del proveedor externo.

URL="${1%/}"
KEY="$2"
SECRET="$3"

if [ -z "$URL" ] || [ -z "$KEY" ] || [ -z "$SECRET" ]; then
    echo "Uso: sh scripts/probar-api.sh https://tu-servidor X-Api-Key X-Api-Secret"
    exit 1
fi

# --- Los 48 endpoints que no llevan ningun dato en la direccion.
LISTADOS="
api/anulaciones
api/anulaciones/documentos/disponibles
api/boletas
api/buscar-documento
api/clientes
api/consulta-cpe/estadisticas
api/consultas/cuota
api/empresa
api/facturas
api/guias-remision
api/notas-credito
api/notas-debito
api/panel/documentos-recientes
api/panel/estado-sunat
api/panel/indicadores
api/panel/por-moneda
api/panel/ventas-mensuales
api/resumenes
api/resumenes/boletas/anulables
api/resumenes/pendientes/boletas
api/series
api/setup/status
api/sucursales
api/system/info
api/v1/auth/me
api/v1/boletas
api/v1/branches
api/v1/clients
api/v1/companies
api/v1/config/defaults
api/v1/config/summary
api/v1/consulta-cpe/estadisticas
api/v1/correlatives/document-types
api/v1/credit-notes
api/v1/daily-summaries
api/v1/daily-summaries/pending-boletas
api/v1/debit-notes
api/v1/dispatch-guides
api/v1/invoices
api/v1/pdf/formats
api/v1/retentions
api/v1/ubigeos/distritos
api/v1/ubigeos/provincias
api/v1/ubigeos/regiones
api/v1/ubigeos/search
api/v1/user
api/v1/voided-documents
api/v1/voided-documents/documents-for-voiding
"

# --- Los de detalle: "de donde saco el id : la direccion a probar".
DETALLES="
api/anulaciones:api/anulaciones/ID
api/anulaciones:api/anulaciones/ID/cdr
api/anulaciones:api/anulaciones/ID/estado
api/anulaciones:api/anulaciones/ID/xml
api/boletas:api/boletas/ID
api/boletas:api/boletas/ID/download-cdr
api/boletas:api/boletas/ID/download-pdf
api/boletas:api/boletas/ID/download-xml
api/facturas:api/facturas/ID
api/facturas:api/facturas/ID/download-cdr
api/facturas:api/facturas/ID/download-pdf
api/facturas:api/facturas/ID/download-xml
api/guias-remision:api/guias-remision/ID
api/guias-remision:api/guias-remision/ID/download-cdr
api/guias-remision:api/guias-remision/ID/download-pdf
api/guias-remision:api/guias-remision/ID/download-xml
api/notas-credito:api/notas-credito/ID
api/notas-credito:api/notas-credito/ID/download-cdr
api/notas-credito:api/notas-credito/ID/download-pdf
api/notas-credito:api/notas-credito/ID/download-xml
api/notas-debito:api/notas-debito/ID
api/notas-debito:api/notas-debito/ID/download-cdr
api/notas-debito:api/notas-debito/ID/download-pdf
api/notas-debito:api/notas-debito/ID/download-xml
api/resumenes:api/resumenes/ID
api/resumenes:api/resumenes/ID/cdr
api/resumenes:api/resumenes/ID/estado
api/resumenes:api/resumenes/ID/xml
api/v1/boletas:api/v1/boletas/ID
api/v1/boletas:api/v1/boletas/ID/download-cdr
api/v1/boletas:api/v1/boletas/ID/download-pdf
api/v1/boletas:api/v1/boletas/ID/download-xml
api/v1/branches:api/v1/branches/ID
api/v1/branches:api/v1/branches/ID/correlatives
api/v1/clients:api/v1/clients/ID
api/v1/companies:api/v1/companies/ID
api/v1/companies:api/v1/companies/ID/branches
api/v1/companies:api/v1/companies/ID/clients
api/v1/companies:api/v1/companies/ID/config
api/v1/companies:api/v1/companies/ID/config/validate/services
api/v1/companies:api/v1/companies/ID/config/tax_settings
api/v1/companies:api/v1/companies/ID/config/invoice_settings
api/v1/companies:api/v1/companies/ID/config/gre_settings
api/v1/companies:api/v1/companies/ID/config/document_settings
api/v1/credit-notes:api/v1/credit-notes/ID
api/v1/credit-notes:api/v1/credit-notes/ID/download-cdr
api/v1/credit-notes:api/v1/credit-notes/ID/download-pdf
api/v1/credit-notes:api/v1/credit-notes/ID/download-xml
api/v1/daily-summaries:api/v1/daily-summaries/ID
api/v1/debit-notes:api/v1/debit-notes/ID
api/v1/debit-notes:api/v1/debit-notes/ID/download-cdr
api/v1/debit-notes:api/v1/debit-notes/ID/download-pdf
api/v1/debit-notes:api/v1/debit-notes/ID/download-xml
api/v1/dispatch-guides:api/v1/dispatch-guides/ID
api/v1/dispatch-guides:api/v1/dispatch-guides/ID/download-cdr
api/v1/dispatch-guides:api/v1/dispatch-guides/ID/download-pdf
api/v1/dispatch-guides:api/v1/dispatch-guides/ID/download-xml
api/v1/invoices:api/v1/invoices/ID
api/v1/invoices:api/v1/invoices/ID/download-cdr
api/v1/invoices:api/v1/invoices/ID/download-pdf
api/v1/invoices:api/v1/invoices/ID/download-xml
api/v1/retentions:api/v1/retentions/ID
api/v1/retentions:api/v1/retentions/ID/download-cdr
api/v1/retentions:api/v1/retentions/ID/download-pdf
api/v1/retentions:api/v1/retentions/ID/download-xml
api/v1/ubigeos/regiones:api/v1/ubigeos/ID
api/v1/voided-documents:api/v1/voided-documents/ID
api/v1/voided-documents:api/v1/voided-documents/ID/download-cdr
api/v1/voided-documents:api/v1/voided-documents/ID/download-xml
"

GUARDADOS=$(mktemp -d)
trap "rm -rf $GUARDADOS" EXIT

# Solo el codigo de respuesta.
codigo_de() {
    curl -s -o /dev/null -w "%{http_code}" \
        -H "X-Api-Key: $KEY" -H "X-Api-Secret: $SECRET" -H "Accept: application/json" \
        --max-time 30 "$URL/$1"
}

# Un id que exista de verdad, sacado del listado. Se pide una sola vez por
# listado y se guarda: hay endpoints que comparten el mismo.
id_de() {
    fichero="$GUARDADOS/$(printf '%s' "$1" | tr / _)"

    if [ ! -f "$fichero" ]; then
        curl -s -H "X-Api-Key: $KEY" -H "X-Api-Secret: $SECRET" -H "Accept: application/json" \
            --max-time 30 "$URL/$1" \
            | grep -oE '"id"[[:space:]]*:[[:space:]]*[0-9]+' | head -1 | grep -oE '[0-9]+$' > "$fichero"
    fi

    cat "$fichero"
}

bien=0
vacios=0
fallos=0
FALLOS="$GUARDADOS/fallos"
: > "$FALLOS"

anotar() {
    case "$2" in
        200|204)
            bien=$((bien + 1))
            ;;
        404)
            # En un detalle esto es "ese documento no tiene ese archivo
            # todavia", no un fallo: un XML o un CDR solo existe despues de
            # haber ido a SUNAT.
            vacios=$((vacios + 1))
            printf '  %-54s %s  (sin ese dato)\n' "$1" "$2"
            ;;
        *)
            fallos=$((fallos + 1))
            printf '%-54s %s\n' "$1" "$2" >> "$FALLOS"
            printf '  %-54s %s  <-- REVISAR\n' "$1" "$2"
            ;;
    esac
}

echo
echo "Servidor: $URL"
echo
echo "== Listados y consultas generales (48)"

for ruta in $LISTADOS; do
    anotar "$ruta" "$(codigo_de "$ruta")"
done

echo
echo "== Detalle y descargas, con ids reales (68)"

for par in $DETALLES; do
    padre=${par%%:*}
    plantilla=${par#*:}
    id=$(id_de "$padre")

    if [ -z "$id" ]; then
        vacios=$((vacios + 1))
        printf '  %-54s --   (no hay ningun registro en %s)\n' "$plantilla" "$padre"
        continue
    fi

    ruta=$(printf '%s' "$plantilla" | sed "s/ID/$id/")
    anotar "$ruta" "$(codigo_de "$ruta")"
done

echo
echo "== La puerta: sin credencial no se entra"

comprobar_puerta() {
    if [ "$2" = "401" ]; then
        printf '  %-30s %s  correcto\n' "$1" "$2"
    else
        fallos=$((fallos + 1))
        printf '  %-30s %s  <-- DEBERIA SER 401\n' "$1" "$2"
        printf '%-54s %s (deberia ser 401)\n' "puerta, $1" "$2" >> "$FALLOS"
    fi
}

sin=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" --max-time 30 "$URL/api/sucursales")
malo=$(curl -s -o /dev/null -w "%{http_code}" -H "X-Api-Key: $KEY" -H "X-Api-Secret: esto-no-es-el-secret" -H "Accept: application/json" --max-time 30 "$URL/api/sucursales")
inventada=$(curl -s -o /dev/null -w "%{http_code}" -H "X-Api-Key: no-existe" -H "X-Api-Secret: tampoco" -H "Accept: application/json" --max-time 30 "$URL/api/sucursales")

comprobar_puerta "sin credencial" "$sin"
comprobar_puerta "con el secret mal" "$malo"
comprobar_puerta "con una key que no existe" "$inventada"

echo
echo "-----------------------------------------------------------"
printf 'responden bien: %d    sin datos que devolver: %d    con problema: %d\n' "$bien" "$vacios" "$fallos"

if [ "$fallos" -gt 0 ]; then
    echo
    echo "Lo que hay que mirar:"
    cat "$FALLOS"
    exit 1
fi

echo
echo "Todo responde. Nada se creo ni se modifico: solo fueron consultas."
