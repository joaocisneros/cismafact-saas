#!/bin/sh
set -e

# Railway/Render asignan el puerto de la app en la variable $PORT.
# Apache debe escuchar en ese puerto (por defecto 80 si no viene).
LISTEN_PORT="${PORT:-80}"
sed -ri "s/^Listen 80$/Listen ${LISTEN_PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${LISTEN_PORT}>/" /etc/apache2/sites-available/000-default.conf

# Preparar la aplicacion en cada arranque (idempotente).
# Crea/actualiza las tablas y el enlace de storage publico.
php artisan migrate --force || echo "AVISO: las migraciones fallaron. Revisa las variables DB_* en el panel."
php artisan storage:link || true

# Arrancar Apache en primer plano (proceso principal del contenedor).
exec apache2-foreground
