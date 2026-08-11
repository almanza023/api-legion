# API Gym

Backend API para la operacion integral de un gimnasio, construida con Laravel. Centraliza autenticacion, gestion de clientes, entrenadores, membresias, rutinas, valoraciones fisicas, asistencia, publicidad, reportes y notificaciones SMS.

## Descripcion general

El proyecto expone una API REST enfocada en procesos administrativos y deportivos del gimnasio. Su alcance actual incluye:

- autenticacion de usuarios con JWT
- gestion de usuarios internos, clientes y entrenadores
- administracion de membresias y vencimientos
- registro de asistencia de clientes
- gestion de ejercicios, videos, rutinas y rutinas maestras
- valoraciones fisicas, clasificacion IMC y comparativos de avance
- activos del gimnasio: maquinas y mantenimientos
- preguntas de evaluacion y respuestas por cliente
- publicidad, empresa, reportes y configuracion SMS

La documentacion tecnica detallada de endpoints se encuentra en [MANUAL_TECNICO_ENDPOINTS.md](/Applications/MAMP/htdocs/api-gym/MANUAL_TECNICO_ENDPOINTS.md).

## Stack tecnico

- PHP 8.2+
- Laravel 11
- MySQL
- JWT con `tymon/jwt-auth`
- Generacion de PDF con `setasign/fpdf`
- Vite para assets frontend auxiliares

## Requisitos

- PHP 8.2 o superior
- Composer
- Node.js y npm
- MySQL o MariaDB
- Extensiones PHP habituales para Laravel (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`)

## Puesta en marcha

1. Clonar el repositorio.
2. Instalar dependencias PHP:

```bash
composer install
```

3. Instalar dependencias frontend:

```bash
npm install
```

4. Crear el archivo de entorno:

```bash
cp .env.example .env
```

5. Configurar la conexion a base de datos en `.env`.
6. Generar la clave de la aplicacion:

```bash
php artisan key:generate
```

7. Generar la clave JWT:

```bash
php artisan jwt:secret
```

8. Ejecutar migraciones:

```bash
php artisan migrate
```

9. Levantar el proyecto:

```bash
php artisan serve
```

Si tambien necesitas compilar assets:

```bash
npm run dev
```

## Variables de entorno importantes

Ademas de las variables base de Laravel, revisa especialmente:

```env
APP_NAME=API Gym
APP_ENV=local
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_gym
DB_USERNAME=root
DB_PASSWORD=

HABLAME_SMS_URL=https://www.hablame.co/api/sms/v5/send
HABLAME_SMS_CAMPAIGN_NAME=api-gym-campaign
HABLAME_SMS_FROM=API_GYM
```

## Autenticacion

La API usa JWT para proteger la mayor parte de los endpoints administrativos.

- Login: `POST /api/login`
- Header requerido en rutas protegidas: `Authorization: Bearer <token>`
- Middleware principal: `App\Http\Middleware\IsUserAuth`

Ejemplo de login:

```json
{
  "usuario": "12345678",
  "password": "secret"
}
```

Tambien es posible enviar `documento_identidad` en lugar de `usuario`.

## Modulos principales

Las rutas API se organizan desde [routes/api.php](/Applications/MAMP/htdocs/api-gym/routes/api.php) y sus modulos en [routes/modules](/Applications/MAMP/htdocs/api-gym/routes/modules).

Modulos destacados:

- autenticacion y gestion de usuarios
- clientes publicos y sincronizacion
- entrenadores
- roles y membresias
- clientes-membresias
- grupos musculares, ejercicios y videos
- rutinas, rutinas maestras y progreso
- valoraciones, IMC y PDF
- maquinas y mantenimientos
- publicidad y empresa
- preguntas de evaluacion y respuestas
- asistencia de clientes
- reportes
- notificaciones y configuracion SMS

## Estructura del proyecto

- [app/Http/Controllers](/Applications/MAMP/htdocs/api-gym/app/Http/Controllers): controladores HTTP por modulo
- [app/Models](/Applications/MAMP/htdocs/api-gym/app/Models): modelos Eloquent del dominio
- [routes/api.php](/Applications/MAMP/htdocs/api-gym/routes/api.php): punto de entrada de rutas API
- [routes/modules](/Applications/MAMP/htdocs/api-gym/routes/modules): division modular de endpoints
- [database/migrations](/Applications/MAMP/htdocs/api-gym/database/migrations): estructura evolutiva de base de datos
- [MANUAL_TECNICO_ENDPOINTS.md](/Applications/MAMP/htdocs/api-gym/MANUAL_TECNICO_ENDPOINTS.md): inventario funcional y tecnico de endpoints

## Comandos utiles

```bash
php artisan serve
php artisan migrate
php artisan optimize:clear
php artisan jwt:secret
npm run dev
npm run build
```

## Estado de pruebas

Actualmente el proyecto casi no tiene cobertura automatizada en [tests](/Applications/MAMP/htdocs/api-gym/tests). Si se van a introducir cambios relevantes en negocio o contratos API, conviene agregar pruebas de integracion por modulo.

## Notas de mantenimiento

- Existen endpoints publicos y privados; antes de integrar un modulo, valida si requiere JWT.
- El proyecto contiene integraciones externas para SMS, por lo que conviene probarlas con credenciales de entorno separadas.
- Para conocer contratos de entrada, salida y observaciones por endpoint, usa como referencia principal el manual tecnico.

## Licencia

Proyecto de uso interno. Ajusta esta seccion si el repositorio va a distribuirse con una licencia formal.
