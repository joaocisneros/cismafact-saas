#!/bin/sh
set -e

# Railway/Render asignan el puerto de la app en la variable $PORT.
# Apache debe escuchar en ese puerto (por defecto 80 si no viene).
LISTEN_PORT="${PORT:-80}"
sed -ri "s/^Listen 80$/Listen ${LISTEN_PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${LISTEN_PORT}>/" /etc/apache2/sites-available/000-default.conf

# Crear/actualizar las tablas (idempotente en cada arranque).
php artisan migrate --force || echo "AVISO: las migraciones fallaron. Revisa las variables DB_* en el panel."

# Sembrar roles + usuario administrador SOLO la primera vez (si aun no hay roles).
# Asi no se resetea la contrasena del admin en cada reinicio.
NEED_SEED=$(php -r 'require "vendor/autoload.php"; $a=require "bootstrap/app.php"; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); try { echo \App\Models\Role::count() > 0 ? "no" : "yes"; } catch (\Throwable $e) { echo "no"; }' 2>/dev/null)
if [ "$NEED_SEED" = "yes" ]; then
    echo "Primera vez: sembrando roles y usuario administrador..."
    php artisan db:seed --class=RolesAndPermissionsSeeder --force || echo "AVISO: el seed inicial fue parcial (el admin igual deberia existir)."
fi

# Enlace de storage publico (logos, PDF, certificado de prueba).
php artisan storage:link || true

# Arrancar Apache en primer plano (proceso principal del contenedor).
exec apache2-foreground
