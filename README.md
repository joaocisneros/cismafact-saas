# SUNAT Facturacion Electronica

Microservicio Laravel para facturacion electronica SUNAT Peru, basado en PHP 8.2+, Laravel 12 y Greenter 5.1.

## Requisitos

- PHP 8.2 o superior.
- Composer 2.x.
- MySQL 8.0 o compatible.
- Extensiones PHP: `bcmath`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `soap`, `xml`, `xmlwriter`, `zlib`.
- Certificado digital SUNAT en formato PEM para envío real.
- Para Beta se necesita un certificado demo instalado manualmente en `storage/app/private/certificado/certificado.pem`. Las claves privadas no se versionan.

En esta maquina se agregaron atajos locales para evitar que Windows use PHP 8.0 de XAMPP:

```powershell
.\php82.bat -v
.\composer82.bat --version
```

## Instalacion Local

1. Copiar variables de entorno:

```powershell
Copy-Item .env.example .env
```

2. Revisar base de datos en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_cisma_fact
DB_USERNAME=root
DB_PASSWORD=
```

3. Instalar dependencias con PHP 8.2:

```powershell
.\composer82.bat install
```

4. Generar llave si `APP_KEY` esta vacia:

```powershell
.\php82.bat artisan key:generate
```

5. Ejecutar migraciones:

```powershell
.\php82.bat artisan migrate
```

6. Instalar manualmente un certificado demo para pruebas Beta:

```powershell
New-Item -ItemType Directory -Force storage\app\private\certificado
Copy-Item C:\ruta-segura\certificado-beta.pem storage\app\private\certificado\certificado.pem -Force
```

El archivo debe contener el certificado y su llave privada en formato PEM. Nunca debe agregarse al repositorio.

7. Levantar servidor local:

```powershell
.\php82.bat artisan serve --host=127.0.0.1 --port=8090
```

## Docker

Tambien se puede ejecutar aislado con Docker:

```powershell
docker compose up --build
```

Servicios:

- Aplicacion: `http://localhost:8090`
- MySQL container: puerto host `3307`, database `db_cisma_fact`, usuario `sunat`, password `secret`.

Comandos utiles dentro del contenedor:

```powershell
docker compose exec app php artisan migrate
docker compose exec app composer test
```

## Pruebas

El script de pruebas esta configurado en `composer.json`:

```json
"test": "@php vendor/bin/pest"
```

Ejecutar:

```powershell
.\composer82.bat test
```

Las pruebas smoke base validan que la aplicacion responda y que las rutas de facturacion esten registradas. Las pruebas extendidas de notas, guias y credenciales GRE quedan marcadas como pendientes hasta crear una base de datos `testing` aislada.

## Rendimiento Local

Para una ejecucion local mas fluida, usar estos valores en `.env`:

```env
APP_DEBUG=false
LOG_LEVEL=warning
QUEUE_CONNECTION=sync
CACHE_STORE=file
```

Despues de cambiar `.env`, limpiar y regenerar cache:

```powershell
.\php82.bat artisan optimize:clear
.\composer82.bat prod:cache
```

`php artisan serve` es suficiente para desarrollo, pero en Windows puede ser mas lento que Apache/Laragon o Docker. Para revisar tiempos basicos:

```powershell
Measure-Command { Invoke-WebRequest -Uri http://127.0.0.1:8090/login -UseBasicParsing | Out-Null }
```

## Cache De Produccion

Para produccion:

```powershell
.\php82.bat artisan config:cache
.\php82.bat artisan route:cache
.\php82.bat artisan view:cache
```

Tambien esta disponible:

```powershell
.\composer82.bat prod:cache
```

Para limpiar caches:

```powershell
.\composer82.bat prod:clear
```

## Permisos

El servidor web debe poder escribir en:

```text
storage/
bootstrap/cache/
```

En Linux/Docker:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

En Windows/Laragon normalmente basta con que el usuario actual tenga permisos de escritura sobre la carpeta del proyecto.

## SUNAT Beta

Configuracion base en `.env`:

```env
SUNAT_ENVIRONMENT=beta
SUNAT_CERTIFICATE_PATH=storage/app/private/certificado/certificado.pem
SUNAT_CERTIFICATE_PASSWORD=
```

Para producción cada empresa debe configurar su propio RUC, usuario SOL, clave SOL y certificado real. El certificado demo compartido solo se usa en Beta y nunca sustituye al certificado real de una empresa en producción.

### Primera actualización de un servidor que todavía versionaba el certificado Beta

Antes de ejecutar `git pull`, respalde el certificado fuera del repositorio. Después de actualizar, restáurelo como archivo privado:

```bash
cp storage/app/private/certificado/certificado.pem /tmp/cisma-certificado-beta.pem
git pull --ff-only origin main
mkdir -p storage/app/private/certificado
cp /tmp/cisma-certificado-beta.pem storage/app/private/certificado/certificado.pem
chmod 600 storage/app/private/certificado/certificado.pem
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

A partir de esa primera actualización, Git ignorará el certificado y los siguientes `git pull` no lo modificarán.
