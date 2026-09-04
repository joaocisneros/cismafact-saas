#!/bin/sh
#
# Prueba todos los endpoints de lectura de la API contra el servidor de
# producción.
#
# Solo hace peticiones GET: no crea ni modifica nada, no emite comprobantes ni
# habla con SUNAT. Lo que comprueba es que cada uno responde y que la
# credencial sirve.
#
# Uso:  sh probar-api.sh  https://cismafact.alwaysdata.net  LA_KEY  EL_SECRET

URL="${1:-https://cismafact.alwaysdata.net}"
KEY="$2"
SECRET="$3"

if [ -z "$KEY" ] || [ -z "$SECRET" ]; then
    echo "Faltan las credenciales."
    echo "Uso: sh probar-api.sh $URL X-Api-Key X-Api-Secret"
    exit 1
fi

RUTAS="
api/sucursales
api/series
api/clientes
api/panel/indicadores
api/panel/documentos-recientes
api/panel/ventas-mensuales
api/panel/estado-sunat
api/panel/por-moneda
api/boletas
api/facturas
api/notas-credito
api/notas-debito
api/guias-remision
api/resumenes
api/anulaciones
api/consulta-cpe/estadisticas
api/v1/companies
api/v1/branches
api/v1/clients
api/v1/invoices
api/v1/boletas
api/v1/credit-notes
api/v1/debit-notes
api/v1/dispatch-guides
api/v1/retentions
api/v1/voided-documents
api/v1/daily-summaries
api/v1/ubigeos/regiones
api/v1/config/defaults
api/v1/pdf/formats
"

bien=0
mal=0

printf '%-42s %s\n' 'ENDPOINT' 'RESPUESTA'
printf '%s\n' '--------------------------------------------------------'

for ruta in $RUTAS; do
    codigo=$(curl -s -o /dev/null -w '%{http_code}' \
        -H "X-Api-Key: $KEY" \
        -H "X-Api-Secret: $SECRET" \
        -H 'Accept: application/json' \
        --max-time 20 \
        "$URL/$ruta")

    case "$codigo" in
        200) bien=$((bien + 1)) ;;
        *)   mal=$((mal + 1)); printf '%-42s %s\n' "$ruta" "$codigo" ;;
    esac
done

printf '%s\n' '--------------------------------------------------------'
printf 'responden bien: %d   con problema: %d\n' "$bien" "$mal"

echo
echo 'Y la autenticación:'

sin=$(curl -s -o /dev/null -w '%{http_code}' -H 'Accept: application/json' --max-time 20 "$URL/api/sucursales")
malo=$(curl -s -o /dev/null -w '%{http_code}' -H "X-Api-Key: $KEY" -H 'X-Api-Secret: esto-no-es' -H 'Accept: application/json' --max-time 20 "$URL/api/sucursales")

printf '  sin credenciales        %s  (debe ser 401)\n' "$sin"
printf '  con secret equivocado   %s  (debe ser 401)\n' "$malo"
